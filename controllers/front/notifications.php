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
require_once(dirname(__FILE__) . '/../../classes/OrderStateProcessor.php');

class PaynowNotificationsModuleFrontController extends PaynowFrontController
{
    public function process()
    {
        $payload = trim(Tools::file_get_contents('php://input'));
        $headers = $this->getRequestHeaders();
        $notification_data = json_decode($payload, true);
        PaynowLogger::info(
            'Incoming notification {paymentId={}, status={}}',
            [
                $notification_data['paymentId'],
                $notification_data['status']
            ]
        );

        try {
            new Notification($this->module->getSignatureKey(), $payload, $headers);
            $payments = PaynowPaymentData::findAllByExternalId($notification_data['externalId'])->getResults();

            $filteredPayments = array_filter($payments, function ($payment) use ($notification_data, $payments) {
                return $payment->id_payment === $notification_data['paymentId'] ||
                     $notification_data['status'] === Paynow\Model\Payment\Status::STATUS_NEW;
            });

            if (empty($filteredPayments)) {
                PaynowLogger::warning(
                    'Payment for order not exists {paymentId={}, status={}, externalId={}}',
                    [
                        $notification_data['paymentId'],
                        $notification_data['status'],
                        $notification_data['externalId']
                    ]
                );
                header('HTTP/1.1 400 Bad Request', true, 400);
                exit;
            }
            (new OrderStateProcessor())->updateState(
                $filteredPayments[0]->id_order,
                $notification_data['paymentId'],
                $filteredPayments[0]->id_cart,
                $filteredPayments[0]->order_reference,
                $filteredPayments[0]->external_id,
                $filteredPayments[0]->status,
                $notification_data['status']
            );
        } catch (Exception $exception) {
            PaynowLogger::error(
                'An error occurred during processing notification {paymentId={}, status={}, message={}}',
                [
                    $notification_data['paymentId'],
                    $notification_data['status'],
                    $exception->getMessage()
                ]
            );
            header('HTTP/1.1 400 Bad Request', true, 400);
            exit;
        }

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

    private function getAllPaymentsDataByOrderReference($order_reference)
    {
        return Db::getInstance()->executeS('
            SELECT id_order, id_cart, order_reference, status, id_payment, external_id 
            FROM  ' . _DB_PREFIX_ . 'paynow_payments 
            WHERE order_reference="' . pSQL($order_reference) . '" ORDER BY created_at DESC');
    }
}
