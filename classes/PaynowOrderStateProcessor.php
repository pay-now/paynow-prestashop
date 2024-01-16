<?php

use Paynow\Model\Payment\Status;

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License (MIT)
 * that is bundled with this package in the file LICENSE.md.
 *
 * @author mElements S.A.
 * @copyright mElements S.A.
 * @license   MIT License
 */

class PaynowOrderStateProcessor
{
    /** @var Paynow */
    public $module;

    /** @var \PaynowLockingHelper */
    private $lockingHelper;

    public function __construct($module)
    {
        $this->module = $module;
        $this->lockingHelper = new PaynowLockingHelper();
    }

    /**
     * @throws \PrestaShopException
     * @throws \PaynowNotificationRetryProcessing
     * @throws \PaynowNotificationStopProcessing
     */
    public function processNotification($data)
    {
        PaynowLogger::info('Lock checking...', $data);

        $externalIdForLockingSystem = $data['externalId'] ?? $data['paymentId'] ?? 'unknown';
        if ($this->lockingHelper->checkAndCreate($externalIdForLockingSystem)) {
            for ($i = 1; $i<=3; $i++) {
                sleep(1);
                $isNotificationLocked = $this->lockingHelper->checkAndCreate($externalIdForLockingSystem);
                if (!$isNotificationLocked) {
                    break;
                } elseif ($i == 3) {
                    throw new PaynowNotificationRetryProcessing(
                        'Skipped processing. Previous notification is still processing.',
                        $data
                    );
                }
            }
        }

        PaynowLogger::info('Lock passed successfully, notification validation starting.', $data);

        if (empty($data['modifiedAt'])) {
            $data['modifiedAt'] = (new DateTime('now', new DateTimeZone(Configuration::get('PS_TIMEZONE'))))->format('Y-m-d H:i:s');
        } else {
            $data['modifiedAt'] = str_replace('T', ' ', $data['modifiedAt']);
        }

        $isNew = $data['status'] == Status::STATUS_NEW;
        $isConfirmed = $data['status'] == Status::STATUS_CONFIRMED;

        // Delay NEW status, in case when API sends notifications in bundle,
        // status NEW should finish processing at the very end
        if ($isNew) {
            sleep(1);
        }

        /** @var \PaynowPaymentData $payment */
        $payment = PaynowPaymentData::getActiveByExternalId($data['externalId'], true, $data['paymentId'] ?? 'unknown');

        if (empty($payment)) {
            $this->lockingHelper->delete($externalIdForLockingSystem);
            throw new PaynowNotificationStopProcessing(
                'Skipped processing. Payment, Order or Cart not found.',
                $data
            );
        }

        $data['activePaymentId'] = $payment->id_payment;
        $data['activePaymentStatus']    = $payment->status;
        $data['activePaymentDate']      = $payment->sent_at;

        if ($payment->status === Status::STATUS_CONFIRMED) {
            $this->lockingHelper->delete($externalIdForLockingSystem);
            throw new PaynowNotificationStopProcessing(
                'Skipped processing. An order already has a paid status.',
                $data
            );
        }

        if ($data['status'] == $payment->status
            && $data['paymentId'] == $payment->id_payment) {
            $this->lockingHelper->delete($externalIdForLockingSystem);
            throw new PaynowNotificationStopProcessing(
                sprintf(
                    'Skipped processing. Transition status (%s) already consumed.',
                    $payment->status
                ),
                $data
            );
        }

        // proceed with creating order strategy
        if ($payment->id_order == '0') {
            $cart = new Cart((int)$payment->id_cart);
            $canProcessCreateOrder = PaynowHelper::canProcessCreateOrder(
                (int)$payment->id_order,
                $data['status'],
                (int)$payment->locked,
                $cart->orderExists()
            );

            if ($canProcessCreateOrder) {
                if ($payment->id_payment != $data['paymentId']) {
                    PaynowPaymentData::updatePaymentIdByExternalId($data['externalId'], $data['paymentId']);
                }

                $data['cartId']        = $payment->id_cart;
                $data['paymentLocked'] = $payment->locked;
                PaynowLogger::info(
                    'Processing notification to create new order from cart',
                    $data
                );
                if (method_exists($cart, 'getCartTotalPrice')) {
                    $cartTotalPrice = $cart->getCartTotalPrice();
                } else {
                    $cartTotalPrice = $this->getCartTotalPrice($cart);
                }
                if ((float)$payment->total !== $cartTotalPrice) {
                    $data['cartTotalPrice'] = $cartTotalPrice;
                    $data['paymentTotal'] = (float)$payment->total;
                    $this->lockingHelper->delete($externalIdForLockingSystem);
                    throw new PaynowNotificationStopProcessing(
                        'Inconsistent payment and cart amount.',
                        $data
                    );
                }
                PaynowHelper::createOrder($cart, $data['externalId'], $data['paymentId']);
                $payment = PaynowPaymentData::getActiveByExternalId($data['externalId']);
            }
        }

        if ($data['paymentId'] != $payment->id_payment && !$isNew && !$isConfirmed) {
            $this->retryProcessingNTimes(
                $payment,
                'Skipped processing. Order has another active payment.',
                $data
            );
        }

        if (!empty($payment->sent_at) && $payment->sent_at > $data['modifiedAt'] && !$isConfirmed) {
            $this->lockingHelper->delete($externalIdForLockingSystem);
            throw new PaynowNotificationStopProcessing(
                'Skipped processing. Order has newer status. Time travels are prohibited.',
                $data
            );
        }

        $order = new Order($payment->id_order);
        if (!Validate::isLoadedObject($order)) {
            $data['payment->id_order'] = var_export($payment->id_order, true);
            $this->retryProcessingNTimes(
                $payment,
                'Skipped processing. Order not found.',
                $data
            );
        }

        if ($order->module !== $this->module->name) {
            $data['orderModule'] = $order->module;
            $this->lockingHelper->delete($externalIdForLockingSystem);
            throw new PaynowNotificationStopProcessing(
                'Skipped processing. Order has other payment (other payment driver).',
                $data
            );
        }

        if ((int)$order->current_state === (int)Configuration::get('PAYNOW_ORDER_CONFIRMED_STATE')) {
            $this->lockingHelper->delete($externalIdForLockingSystem);
            throw new PaynowNotificationStopProcessing(
                'Skipped processing. The order has already paid status.',
                $data
            );
        }

        if (!$this->isCorrectStatus($payment->status, $data['status']) && !$isConfirmed  && !$isNew) {
            $this->retryProcessingNTimes(
                $payment,
                sprintf(
                    'Skipped processing. Status transition is incorrect (%s => %s).',
                    $payment->status,
                    $data['status']
                ),
                $data
            );
        }

        switch ($data['status']) {
            case Paynow\Model\Payment\Status::STATUS_NEW:
                $this->changeState(
                    $order,
                    (int)Configuration::get('PAYNOW_ORDER_INITIAL_STATE'),
                    $data['status'],
                    $data['paymentId'],
                    $data['externalId']
                );
                break;
            case Paynow\Model\Payment\Status::STATUS_REJECTED:
                $this->changeState(
                    $order,
                    (int)Configuration::get('PAYNOW_ORDER_REJECTED_STATE'),
                    $data['status'],
                    $data['paymentId'],
                    $data['externalId']
                );
                break;
            case Paynow\Model\Payment\Status::STATUS_CONFIRMED:
                $this->changeState(
                    $order,
                    (int)Configuration::get('PAYNOW_ORDER_CONFIRMED_STATE'),
                    $data['status'],
                    $data['paymentId'],
                    $data['externalId']
                );
                $this->addOrderPayment($order, $data['paymentId']);
                break;
            case Paynow\Model\Payment\Status::STATUS_ERROR:
                $this->changeState(
                    $order,
                    (int)Configuration::get('PAYNOW_ORDER_ERROR_STATE'),
                    $data['status'],
                    $data['paymentId'],
                    $data['externalId']
                );
                break;
            case Paynow\Model\Payment\Status::STATUS_ABANDONED:
                $this->changeState(
                    $order,
                    (int)Configuration::get('PAYNOW_ORDER_ABANDONED_STATE'),
                    $data['status'],
                    $data['paymentId'],
                    $data['externalId']
                );
                break;
            case Paynow\Model\Payment\Status::STATUS_EXPIRED:
                $this->changeState(
                    $order,
                    (int)Configuration::get('PAYNOW_ORDER_EXPIRED_STATE'),
                    $data['status'],
                    $data['paymentId'],
                    $data['externalId']
                );
                break;
        }


        if ($isNew) {
            PaynowPaymentData::create(
                $data['paymentId'],
                $data['status'],
                $order->id,
                $payment->id_cart,
                $payment->order_reference,
                $data['externalId'],
                $payment->total,
                $data['modifiedAt']
            );
        } else {
            $payment->status = $data['status'];
            $payment->sent_at = $data['modifiedAt'];
            $payment->save();
        }

        $this->lockingHelper->delete($externalIdForLockingSystem);

    }

    private function changeState($order, $new_order_state_id, $payment_status, $id_payment, $external_id, $withEmail = true)
    {
        $history = new OrderHistory();
        $history->id_order = $order->id;
        if ($order->current_state != $new_order_state_id) {
            PaynowLogger::info(
                'Adding new state to order\'s history {externalId={}, orderId={}, orderReference={}, paymentId={}, paymentStatus={}, state={}}',
                [
                    $external_id,
                    $order->id,
                    $order->reference,
                    $id_payment,
                    $payment_status,
                    $new_order_state_id
                ]
            );
            $history->changeIdOrderState(
                $new_order_state_id,
                $order->id
            );
            $history->addWithemail($withEmail);
            PaynowLogger::info(
                'Added new state to order\'s history {externalId={}, orderId={}, orderReference={},  paymentId={}, paymentStatus={}, state={}, historyId={}}',
                [
                    $external_id,
                    $order->id,
                    $order->reference,
                    $id_payment,
                    $payment_status,
                    $new_order_state_id,
                    $history->id
                ]
            );
        }
    }

    private function isCorrectStatus($previous_status, $next_status): bool
    {
        $payment_status_flow = [
            Paynow\Model\Payment\Status::STATUS_NEW => [
                Paynow\Model\Payment\Status::STATUS_NEW,
                Paynow\Model\Payment\Status::STATUS_PENDING,
                Paynow\Model\Payment\Status::STATUS_ERROR,
                Paynow\Model\Payment\Status::STATUS_CONFIRMED,
                Paynow\Model\Payment\Status::STATUS_REJECTED,
                Paynow\Model\Payment\Status::STATUS_EXPIRED

            ],
            Paynow\Model\Payment\Status::STATUS_PENDING => [
                Paynow\Model\Payment\Status::STATUS_NEW,
                Paynow\Model\Payment\Status::STATUS_CONFIRMED,
                Paynow\Model\Payment\Status::STATUS_REJECTED,
                Paynow\Model\Payment\Status::STATUS_EXPIRED,
                Paynow\Model\Payment\Status::STATUS_ABANDONED
            ],
            Paynow\Model\Payment\Status::STATUS_REJECTED => [
                Paynow\Model\Payment\Status::STATUS_CONFIRMED,
                Paynow\Model\Payment\Status::STATUS_ABANDONED,
                Paynow\Model\Payment\Status::STATUS_NEW
            ],
            Paynow\Model\Payment\Status::STATUS_CONFIRMED => [],
            Paynow\Model\Payment\Status::STATUS_ERROR => [
                Paynow\Model\Payment\Status::STATUS_CONFIRMED,
                Paynow\Model\Payment\Status::STATUS_REJECTED,
                Paynow\Model\Payment\Status::STATUS_ABANDONED,
                Paynow\Model\Payment\Status::STATUS_NEW
            ],
            Paynow\Model\Payment\Status::STATUS_EXPIRED => [
                Paynow\Model\Payment\Status::STATUS_NEW
            ],
            Paynow\Model\Payment\Status::STATUS_ABANDONED => [
                Paynow\Model\Payment\Status::STATUS_NEW
            ]
        ];
        $previous_status_exists = isset($payment_status_flow[$previous_status]);
        $is_change_possible = in_array($next_status, $payment_status_flow[$previous_status]);
        return $previous_status_exists && $is_change_possible;
    }

    /**
     * @param $order
     * @param $id_payment
     */
    private function addOrderPayment($order, $id_payment)
    {
        $payments = $order->getOrderPaymentCollection()->getResults();
        if (count($payments) > 0) {
            $payments[0]->transaction_id = $id_payment;
            $payments[0]->update();
        } else {
			// in case when order payment was not created
			$result = $order->addOrderPayment(
				$order->getTotalPaid(),
				$this->module->displayName,
				$id_payment
			);

			if (!$result) {
				PaynowLogger::error(
					'Cannot create order payment entry',
					[
						$order->id,
						$order->reference,
						$id_payment
					]
				);
			}
		}
    }

    /**
     * @param PaynowPaymentData $payment
     * @param                   $message
     * @param                   $data
     * @param int               $counter
     * @throws \PaynowNotificationStopProcessing
     * @throws \PaynowNotificationRetryProcessing
     * @throws \PrestaShopException
     */
    private function retryProcessingNTimes(PaynowPaymentData $payment, $message, $data, $counter = 5)
    {
        $payment->counter = (int)$payment->counter + 1;
        $payment->save();

        $data['counter'] = $payment->counter;
        $this->lockingHelper->delete($data['externalId'] ?? $data['paymentId'] ?? 'unknown');
        if ($payment->counter >= $counter) {
            throw new PaynowNotificationStopProcessing($message, $data);
        } else {
            throw new PaynowNotificationRetryProcessing($message, $data);
        }
    }

    private function getCartTotalPrice($cart): float
    {
        $summary = $cart->getSummaryDetails();

        if (version_compare(_PS_VERSION_, '1.7.1.0', 'ge')) {
            $id_order = (int)Order::getIdByCartId($cart->id);
        } else {
            $id_order = (int)Order::getOrderByCartId($cart->id);
        }

        $order = new Order($id_order);

        if (Validate::isLoadedObject($order)) {
            $taxCalculationMethod = $order->getTaxCalculationMethod();
        } else {
            $taxCalculationMethod = Group::getPriceDisplayMethod(Group::getCurrent()->id);
        }

        return $taxCalculationMethod == PS_TAX_EXC ?
            (float)$summary['total_price_without_tax'] :
            (float)$summary['total_price'];
    }

}
