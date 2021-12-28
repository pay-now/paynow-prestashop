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

class PaynowNotificationsModuleFrontController extends PaynowFrontController
{
    public function process()
    {
        $payload = trim(Tools::file_get_contents('php://input'));
        $headers = $this->getRequestHeaders();
        $notification_data = json_decode($payload, true);
        PaynowLogger::info(
            'Incoming notification {paymentId={}, externalId={}, status={}}',
            [
                $notification_data['paymentId'],
                $notification_data['externalId'],
                $notification_data['status']
            ]
        );

        try {
            new Notification($this->module->getSignatureKey(), $payload, $headers);
            $filteredPayments = $this->getFilteredPayments(
                $notification_data['externalId'],
                $notification_data['paymentId'],
                $notification_data['status']
            );

            if (Paynow\Model\Payment\Status::STATUS_CONFIRMED === $filteredPayments[0]->status) {
                PaynowLogger::info(
                    'Order already has a paid status. Skipped notification processing {paymentId={}, externalId={}}',
                    [
                        $notification_data['paymentId'],
                        $notification_data['externalId'],
                    ]
                );
                header("HTTP/1.1 202 Accepted");
                exit;
            }

            if (empty($filteredPayments)) {
                PaynowLogger::warning(
                    'Payment for order or cart not exists {paymentId={}, status={}, externalId={}}',
                    [
                        $notification_data['paymentId'],
                        $notification_data['status'],
                        $notification_data['externalId']
                    ]
                );
                header('HTTP/1.1 400 Bad Request', true, 400);
                exit;
            }

            if ($this->canProcessCreateOrder($filteredPayments, $notification_data['status'])) {
                $cart = new Cart((int)$notification_data['externalId']);

                if ((float)$filteredPayments[0]->total === $cart->getCartTotalPrice()) {
                    $order = $this->createOrderFromCart($cart);
                    PaynowLogger::info(
                        'An order has been created {paymentId={}, externalId={}, orderId={}}',
                        [
                            $notification_data['paymentId'],
                            $notification_data['externalId'],
                            $order->id,
                        ]
                    );
                    PaynowPaymentData::updateOrderIdAndOrderReferenceByPaymentId(
                        $order->id,
                        $order->reference,
                        $notification_data['paymentId']
                    );
                    $filteredPayments = $this->getFilteredPayments(
                        $notification_data['externalId'],
                        $notification_data['paymentId'],
                        $notification_data['status']
                    );
                } else {
                    PaynowLogger::warning(
                        'Inconsistent payment and cart amount {paymentId={}, externalId={}, paymentAmount={}, cartAmount={}}',
                        [
                            $notification_data['paymentId'],
                            $notification_data['externalId'],
                            $filteredPayments[0]->total,
                            $cart->getCartTotalPrice()
                        ]
                    );
                }
            }

            (new PaynowOrderStateProcessor())->updateState(
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

    private function getRequestHeaders(): array
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

    private function canProcessCreateOrder($filteredPayments, $payment_notification_status): bool
    {
        return 1 <= count($filteredPayments) &&
        PaynowConfigurationHelper::CREATE_ORDER_AFTER_PAYMENT === (int)Configuration::get('PAYNOW_CREATE_ORDER_STATE') &&
        Paynow\Model\Payment\Status::STATUS_CONFIRMED === $payment_notification_status;
    }

    private function createOrderFromCart($cart): Order
    {
        $this->module->validateOrder(
            (int)$cart->id,
            Configuration::get('PAYNOW_ORDER_INITIAL_STATE'),
            $cart->getCartTotalPrice(),
            $this->module->displayName,
            null,
            null,
            (int)$cart->id_currency,
            false,
            $cart->secure_key
        );

        return new Order($this->module->currentOrder);
    }

    private function getFilteredPayments($external_id, $payment_id, $payment_status): array
    {
        $payments = PaynowPaymentData::findAllByExternalId($external_id)->getResults();

        return array_filter($payments, function ($payment) use ($payment_id, $payment_status) {
            return $payment->id_payment === $payment_id ||
                   $payment_status === Paynow\Model\Payment\Status::STATUS_NEW;
        });
    }
}
