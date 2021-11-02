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

    public function updateState($payment, $newStatus, $paymentId, $modifiedAt = null )
    {
        $order = new Order($payment['id_order']);
        if ($order && $order->module == $this->module->name) {
            $history = new OrderHistory();
            $history->id_order = $order->id;

            $payment_status = $payment['status'];

            if (!$this->isCorrectStatus($payment_status, $newStatus)) {
                throw new Exception(
                    'Status transition is incorrect ' . $payment_status . ' - ' . $newStatus
                );
            }

            switch ($newStatus) {
                case Paynow\Model\Payment\Status::STATUS_NEW:
                    $history->changeIdOrderState(
                        (int)Configuration::get('PAYNOW_ORDER_INITIAL_STATE'),
                        $order->id
                    );
                    $history->addWithemail(true);
                    break;
                case Paynow\Model\Payment\Status::STATUS_REJECTED:
                    $history->changeIdOrderState(
                        (int)Configuration::get('PAYNOW_ORDER_REJECTED_STATE'),
                        $order->id
                    );
                    $history->addWithemail(true);
                    break;
                case Paynow\Model\Payment\Status::STATUS_CONFIRMED:
                    $history->changeIdOrderState(
                        (int)Configuration::get('PAYNOW_ORDER_CONFIRMED_STATE'),
                        $order->id
                    );
                    $history->addWithemail(true);
                    $this->addPaymentIdToOrderPayments($order, $payment['id_payment']);
                    break;
                case Paynow\Model\Payment\Status::STATUS_ERROR:
                    $history->changeIdOrderState(
                        (int)Configuration::get('PAYNOW_ORDER_ERROR_STATE'),
                        $order->id
                    );
                    $history->addWithemail(true);
                    break;
                case Paynow\Model\Payment\Status::STATUS_ABANDONED:
                    $history->changeIdOrderState(
                        (int)Configuration::get('PAYNOW_ORDER_ABANDONED_STATE'),
                        $order->id
                    );
                    $history->addWithemail(true);
                    break;
                case Paynow\Model\Payment\Status::STATUS_EXPIRED:
                    $history->changeIdOrderState(
                        (int)Configuration::get('PAYNOW_ORDER_EXPIRED_STATE'),
                        $order->id
                    );
                    $history->addWithemail(true);
                    break;
            }
            $modifiedAt = $modifiedAt ? (new DateTime($modifiedAt))->format('Y-m-d H:i:s') : $modifiedAt;
            if($newStatus === Paynow\Model\Payment\Status::STATUS_NEW){
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