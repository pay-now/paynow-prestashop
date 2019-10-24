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

require_once(dirname(__FILE__) . '/../../classes/PaynowFrontController.php');

class PaynowNotificationsModuleFrontController extends PaynowFrontController
{
    public function process()
    {
        $payload = trim(Tools::file_get_contents('php://input'));
        $headers = $this->getRequestHeaders();
        $notification_data = json_decode($payload, true);
        PaynowLogger::log('Incoming notification', $payload, $notification_data['paymentId']);

        try {
            new \Paynow\Notification($this->module->getSignatureKey(), $payload, $headers);
            $payment = $this->module->getLastPaymentStatus($notification_data['paymentId']);

            if (!$payment) {
                PaynowLogger::log('Order for payment not exists', $payload, $notification_data['paymentId']);
                header('HTTP/1.1 400 Bad Request', true, 400);
                exit;
            }
        } catch (\Exception $exception) {
            PaynowLogger::log($exception->getMessage(), $payload, $notification_data['paymentId']);
            header('HTTP/1.1 400 Bad Request', true, 400);
            exit;
        }

        $this->updateOrderState($payment, $notification_data);
        header("HTTP/1.1 202 Accepted");
        exit;
    }

    private function getRequestHeaders()
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (Tools::substr($key, 0, 5) == 'HTTP_') {
                $subject = str_replace('_', ' ', Tools::strtolower(Tools::substr($key, 5)));
                $headers[str_replace(' ', '-', ucwords($subject))] = $value;
            }
        }
        return $headers;
    }

    private function updateOrderState($payment, $notification_data)
    {
        $order = new Order($payment['id_order']);
        if ($order) {
            $history = new OrderHistory();
            $history->id_order = $order->id;

            if ($this->isCorrectStatus($payment['status'], $notification_data['status'])) {
                switch ($notification_data['status']) {
                    case \Paynow\Model\Payment\Status::STATUS_PENDING:
                        break;
                    case \Paynow\Model\Payment\Status::STATUS_REJECTED:
                        $history->changeIdOrderState(
                            (int)Configuration::get('PAYNOW_ORDER_REJECTED_STATE'),
                            $order->id
                        );
                        $history->addWithemail(true);
                        break;
                    case \Paynow\Model\Payment\Status::STATUS_CONFIRMED:
                        $history->changeIdOrderState(
                            (int)Configuration::get('PAYNOW_ORDER_CONFIRMED_STATE'),
                            $order->id
                        );
                        $history->addWithemail(true);
                        $this->addPaymentIdToOrderPayments($order, $payment['id_payment']);
                        break;
                    case \Paynow\Model\Payment\Status::STATUS_ERROR:
                        $history->changeIdOrderState(
                            (int)Configuration::get('PAYNOW_ORDER_ERROR_STATE'),
                            $order->id
                        );
                        $history->addWithemail(true);
                        break;
                }

                $this->module->storePaymentState(
                    $notification_data['paymentId'],
                    $notification_data['status'],
                    $payment['id_order'],
                    $payment['id_cart'],
                    $payment['order_reference'],
                    $payment['external_id'],
                    (new DateTime($notification_data['modifiedAt']))->format('Y-m-d H:i:s')
                );
            }
        }
    }

    private function isCorrectStatus($previous_status, $next_status)
    {
        $payment_status_flow = [
            \Paynow\Model\Payment\Status::STATUS_NEW => [
                \Paynow\Model\Payment\Status::STATUS_PENDING,
                \Paynow\Model\Payment\Status::STATUS_ERROR
            ],
            \Paynow\Model\Payment\Status::STATUS_PENDING => [
                \Paynow\Model\Payment\Status::STATUS_CONFIRMED,
                \Paynow\Model\Payment\Status::STATUS_REJECTED
            ],
            \Paynow\Model\Payment\Status::STATUS_REJECTED => [\Paynow\Model\Payment\Status::STATUS_CONFIRMED],
            \Paynow\Model\Payment\Status::STATUS_CONFIRMED => [],
            \Paynow\Model\Payment\Status::STATUS_ERROR => []
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
