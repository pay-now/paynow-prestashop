<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License (MIT)
 * that is bundled with this package in the file LICENSE.md.
 *
 * @copyright mBank S.A.
 * @license   MIT License
 */

require_once(dirname(__FILE__) . '/../../classes/PaynowFrontController.php');
require_once(dirname(__FILE__) . '/../../classes/PaynowClientException.php');
require_once(dirname(__FILE__) . '/../../classes/PaymentStatus.php');

class PaynowNotificationsModuleFrontController extends PaynowFrontController
{
    public function process()
    {
        $request = $this->getNotificationBody();
        PaynowLogger::log(print_r($request, true), $request['paymentId'], 'Incoming notification: ');

        $payment = $this->module->getLastPaymentStatus($request['paymentId']);

        if (!$payment) {
            PaynowLogger::log(print_r($request, true), $request['paymentId'], 'Order for payment not exists: ');
            header('HTTP/1.1 400 Bad Request', true, 400);
            exit;
        }

        $headers = $this->getRequestHeaders();
        $incomingSignature = isset($headers['Signature']) ? $headers['Signature'] : $headers['signature'];
        PaynowLogger::log(print_r($headers, true), $request['paymentId'], 'Notification headers: ');
        if (!$this->isNotificationVerified($request, $incomingSignature)) {
            PaynowLogger::log(print_r($request, true), $request['paymentId'], 'Signature is invalid: ');
            header('HTTP/1.1 400 Bad Request', true, 400);
            exit;
        }

        $this->updateOrderState($payment, $request);

        header("HTTP/1.1 202 OK");
        exit;
    }

    private function isNotificationVerified(array $request, $signature)
    {
        $calculated = $this->apiClient->calculateSignature($request);
        return $calculated == $signature;
    }

    private function getNotificationBody()
    {
        return json_decode(trim(Tools::file_get_contents('php://input')), true);
    }

    private function getRequestHeaders()
    {
        if (!function_exists('apache_request_headers')) {
            $headers = [];
            foreach ($_SERVER as $key => $value) {
                if (Tools::substr($key, 0, 5) == 'HTTP_') {
                    $subject = ucwords(str_replace('_', ' ', Tools::strtolower(Tools::substr($key, 5))));
                    $headers[str_replace(' ', '-', $subject)] = $value;
                }
            }
            return $headers;
        }
        return apache_request_headers();
    }

    private function updateOrderState($payment, $request)
    {
        $order = new Order($payment['id_order']);
        if ($order) {
            $history = new OrderHistory();
            $history->id_order = $order->id;

            if ($this->isCorrectStatus($payment['status'], $request['status'])) {
                switch ($request['status']) {
                    case PaymentStatus::STATUS_PENDING:
                        break;
                    case PaymentStatus::STATUS_REJECTED:
                        $history->changeIdOrderState(6, $order->id);
                        $history->addWithemail(true);
                        break;
                    case PaymentStatus::STATUS_CONFIRMED:
                        $history->changeIdOrderState(2, $order->id);
                        $history->addWithemail(true);
                        $this->addPaymentIdToOrderPayments($order, $payment['id_payment']);
                        break;
                    case PaymentStatus::STATUS_ERROR:
                        $history->changeIdOrderState(8, $order->id);
                        $history->addWithemail(true);
                        break;
                }

                $this->module->storePaymentState($request['paymentId'], $request['status'], $payment['id_order'], $payment['id_cart'], $payment['order_reference'], (new DateTime($request['modifiedAt']))->format('Y-m-d H:i:s'));
            }
        }
    }

    private function isCorrectStatus($previous_status, $next_status)
    {
        $payment_status_flow = [
            PaymentStatus::STATUS_NEW => [PaymentStatus::STATUS_PENDING, PaymentStatus::STATUS_ERROR],
            PaymentStatus::STATUS_PENDING => [PaymentStatus::STATUS_CONFIRMED, PaymentStatus::STATUS_REJECTED],
            PaymentStatus::STATUS_REJECTED => [PaymentStatus::STATUS_CONFIRMED],
            PaymentStatus::STATUS_CONFIRMED => [],
            PaymentStatus::STATUS_ERROR => []
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
