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

    public function __construct()
    {
        $this->module = Module::getInstanceByName(Tools::getValue('module'));
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
        $order = new Order($id_order);
        if (Validate::isLoadedObject($order) &&
            $order->module == $this->module->name &&
            $this->canProcessStatusChange($old_status, $new_status) &&
            (int)$order->current_state !== (int)Configuration::get('PAYNOW_ORDER_CONFIRMED_STATE')) {
            if (!$this->isCorrectStatus($old_status, $new_status)) {
                throw new Exception(
                    'Status transition is incorrect ' . $old_status . ' - ' . $new_status
                );
            }

            switch ($new_status) {
                case Paynow\Model\Payment\Status::STATUS_NEW:
                    $this->changeState($order, (int)Configuration::get('PAYNOW_ORDER_INITIAL_STATE'));
                    break;
                case Paynow\Model\Payment\Status::STATUS_REJECTED:
                    $this->changeState($order, (int)Configuration::get('PAYNOW_ORDER_REJECTED_STATE'));
                    break;
                case Paynow\Model\Payment\Status::STATUS_CONFIRMED:
                    $order->addOrderPayment($order->total_paid, $this->module->displayName, $id_payment);
                    $this->changeState($order, (int)Configuration::get('PAYNOW_ORDER_CONFIRMED_STATE'));
                    break;
                case Paynow\Model\Payment\Status::STATUS_ERROR:
                    $this->changeState($order, (int)Configuration::get('PAYNOW_ORDER_ERROR_STATE'));
                    break;
                case Paynow\Model\Payment\Status::STATUS_ABANDONED:
                    $this->changeState($order, (int)Configuration::get('PAYNOW_ORDER_ABANDONED_STATE'));
                    break;
                case Paynow\Model\Payment\Status::STATUS_EXPIRED:
                    $this->changeState($order, (int)Configuration::get('PAYNOW_ORDER_EXPIRED_STATE'));
                    break;
            }

            try {
                if ($new_status === Paynow\Model\Payment\Status::STATUS_NEW) {
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
                PaynowLogger::error($exception->getMessage() . ' {orderReference={}}', [$order_reference]);
            }

            PaynowLogger::info(
                'Changed order status {paymentId={}, externalId={}, orderReference={}, status={}}',
                [
                    $id_payment,
                    $external_id,
                    $order_reference,
                    $new_status
                ]
            );
        }
    }

    private function canProcessStatusChange($old_status, $new_status): bool
    {
        return $old_status !== $new_status && $old_status !== Paynow\Model\Payment\Status::STATUS_CONFIRMED;
    }

    private function changeState($order, $new_order_state_id)
    {
        $history = new OrderHistory();
        $history->id_order = $order->id;
        if ($order->current_state != $new_order_state_id) {
            $history->changeIdOrderState(
                $new_order_state_id,
                $order->id
            );
            $history->addWithemail(true);
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
}
