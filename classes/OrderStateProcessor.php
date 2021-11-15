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

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(dirname(__FILE__) . '/PaynowLogger.php');

class OrderStateProcessor
{
    /** @var Module */
    public $module;

    public function __construct()
    {
        $this->module = Module::getInstanceByName(Tools::getValue('module'));
    }

    public function updateState($payment, $newStatus, $paymentId, $modifiedAt = null)
    {
        $order = new Order($payment['id_order']);
        if ($order && $order->module == $this->module->name) {
            $payment_status = $payment['status'];

            if (!$this->isCorrectStatus($payment_status, $newStatus)) {
                throw new Exception(
                    'Status transition is incorrect ' . $payment_status . ' - ' . $newStatus
                );
            }

            switch ($newStatus) {
                case Paynow\Model\Payment\Status::STATUS_NEW:
                    $this->changeState($order, (int)Configuration::get('PAYNOW_ORDER_INITIAL_STATE'));
                    break;
                case Paynow\Model\Payment\Status::STATUS_REJECTED:
                    $this->changeState($order, (int)Configuration::get('PAYNOW_ORDER_REJECTED_STATE'));
                    break;
                case Paynow\Model\Payment\Status::STATUS_CONFIRMED:
                    $this->changeState($order, (int)Configuration::get('PAYNOW_ORDER_CONFIRMED_STATE'));
                    $this->addPaymentIdToOrderPayments($order, $payment['id_payment']);
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
            $modifiedAt = $modifiedAt ? (new DateTime($modifiedAt))->format('Y-m-d H:i:s') : $modifiedAt;
            if ($newStatus === Paynow\Model\Payment\Status::STATUS_NEW) {
                $this->module->storePaymentState(
                    $paymentId,
                    $newStatus,
                    $payment['id_order'],
                    $payment['id_cart'],
                    $payment['order_reference'],
                    $payment['external_id'],
                    $modifiedAt
                );
            } else {
                $this->module->updatePaymentState(
                    $paymentId,
                    $newStatus,
                    $modifiedAt
                );
            }

            PaynowLogger::info(
                'Changed order status {orderReference={}, paymentId={}, status={}}',
                [
                    $payment['order_reference'],
                    $paymentId,
                    $newStatus
                ]
            );
        }
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

    private function addPaymentIdToOrderPayments($order, $id_payment)
    {
        if ($id_payment === null) {
            return;
        }

        $payments = $order->getOrderPaymentCollection()->getResults();
        if (count($payments) > 0) {
            $payments[0]->transaction_id = $id_payment;
            $payments[0]->update();
        }
    }
}
