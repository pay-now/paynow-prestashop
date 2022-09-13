<?php
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
    /** @var Module */
    public $module;

    public function __construct($module)
    {
        $this->module = $module;
    }

    public function updateState(
        $id_order,
        $id_payment,
        $id_cart,
        $order_reference,
        $external_id,
        $old_status,
        $new_status
    ) {
        PaynowLogger::info(
            'Processing order\'s state update {cartId={}, externalId={}, orderId={}, orderReference={}, paymentId={}, fromStatus={}, toStatus={}}',
            [
                $id_cart,
                $external_id,
                $id_order,
                $order_reference,
                $id_payment,
                $old_status,
                $new_status
            ]
        );
        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) {
            PaynowLogger::warning(
                'An order does not exists {cartId={}, externalId={}, orderId={}, orderReference={}, paymentId={}}',
                [
                    $id_cart,
                    $external_id,
                    $id_order,
                    $order_reference,
                    $id_payment
                ]
            );
            throw new Exception('An order does not exists');
        }

        if ($order->module !== $this->module->name) {
            throw new Exception('Another payment method is selected for order');
        }

        if ((int)$order->current_state === (int)Configuration::get('PAYNOW_ORDER_CONFIRMED_STATE')) {
            PaynowLogger::info(
                'The order has already paid status. Skipping order\'s state update {cartId={}, externalId={}, orderId={}, orderReference={}, paymentId={}}',
                [
                    $id_cart,
                    $external_id,
                    $id_order,
                    $order_reference,
                    $id_payment
                ]
            );
        } else {
            if (!$this->isCorrectStatus($old_status, $new_status)) {
                throw new Exception(
                    'Status transition is incorrect ' . $old_status . ' - ' . $new_status
                );
            }

            switch ($new_status) {
                case Paynow\Model\Payment\Status::STATUS_NEW:
                    $this->changeState($order, (int)Configuration::get('PAYNOW_ORDER_INITIAL_STATE'), $new_status, $id_payment, $external_id);
                    break;
                case Paynow\Model\Payment\Status::STATUS_REJECTED:
                    $this->changeState($order, (int)Configuration::get('PAYNOW_ORDER_REJECTED_STATE'), $new_status, $id_payment, $external_id);
                    break;
                case Paynow\Model\Payment\Status::STATUS_CONFIRMED:
                    $this->changeState($order, (int)Configuration::get('PAYNOW_ORDER_CONFIRMED_STATE'), $new_status, $id_payment, $external_id);
                    $this->addOrderPayment($order, $id_payment);
                    break;
                case Paynow\Model\Payment\Status::STATUS_ERROR:
                    $this->changeState($order, (int)Configuration::get('PAYNOW_ORDER_ERROR_STATE'), $new_status, $id_payment, $external_id);
                    break;
                case Paynow\Model\Payment\Status::STATUS_ABANDONED:
                    $this->changeState($order, (int)Configuration::get('PAYNOW_ORDER_ABANDONED_STATE'), $new_status, $id_payment, $external_id);
                    break;
                case Paynow\Model\Payment\Status::STATUS_EXPIRED:
                    $this->changeState($order, (int)Configuration::get('PAYNOW_ORDER_EXPIRED_STATE'), $new_status, $id_payment, $external_id);
                    break;
            }

            try {
                if (Paynow\Model\Payment\Status::STATUS_NEW  === $new_status &&
                    !PaynowPaymentData::findByPaymentId($id_payment)) {
                    $last_payment = PaynowPaymentData::findLastByExternalId($external_id);
                    PaynowPaymentData::create(
                        $id_payment,
                        $new_status,
                        $id_order,
                        $id_cart,
                        $order_reference,
                        $external_id,
                        $last_payment->total
                    );
                } else {
                    PaynowPaymentData::updateStatus($id_payment, $new_status);
                }
            } catch (PrestaShopDatabaseException $exception) {
                PaynowLogger::error(
                    $exception->getMessage() . ' {cartId={}, orderReference={}}',
                    [
                        $id_cart,
                        $order_reference
                    ]
                );
            }

            PaynowLogger::info(
                'Changed order\'s state {cartId={}, externalId={}, orderReference={}, paymentId={}, status={}}',
                [
                    $id_cart,
                    $external_id,
                    $order_reference,
                    $id_payment,
                    $new_status
                ]
            );
        }
    }

    private function changeState($order, $new_order_state_id, $payment_status, $id_payment, $external_id)
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
            $history->addWithemail(true);
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

    private function isCorrectStatus($previous_status, $next_status)
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
            Paynow\Model\Payment\Status::STATUS_EXPIRED => [],
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
        }
    }
}
