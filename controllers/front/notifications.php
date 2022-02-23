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
            'Incoming notification {externalId={}, paymentId={}, status={}}',
            [
                $notification_data['externalId'],
                $notification_data['paymentId'],
                $notification_data['status']
            ]
        );

        try {
            new Notification($this->module->getSignatureKey(), $payload, $headers);
            $filtered_payments = $this->getFilteredPayments(
                $notification_data['externalId'],
                $notification_data['paymentId'],
                $notification_data['status']
            );

            $filtered_payment = reset($filtered_payments);
            if ($filtered_payment && Paynow\Model\Payment\Status::STATUS_CONFIRMED === $filtered_payment->status) {
                PaynowLogger::info(
                    'An order already has a paid status. Skipped notification processing {externalId={}, paymentId={}, status={}}',
                    [
                        $notification_data['externalId'],
                        $notification_data['paymentId'],
                        $notification_data['status']
                    ]
                );
                header("HTTP/1.1 202 Accepted");
                exit;
            }

            if (empty($filtered_payments)) {
                PaynowLogger::warning(
                    'Payment for order or cart not exists {externalId={}, paymentId={}, status={}}',
                    [
                        $notification_data['externalId'],
                        $notification_data['paymentId'],
                        $notification_data['status']
                    ]
                );
                header('HTTP/1.1 400 Bad Request', true, 400);
                exit;
            }

            $cart = new Cart((int)$filtered_payment->id_cart);
            if (1 <= count($filtered_payments) && $this->canProcessCreateOrder(
                (int)$filtered_payment->id_order,
                $notification_data['status'],
                (int)$filtered_payment->locked,
                $cart->orderExists()
            )) {
                PaynowLogger::info(
                    'Processing notification to create new order from cart {cartId={}, externalId={},, paymentId={}, locked={}}',
                    [
                        $filtered_payment->id_cart,
                        $notification_data['externalId'],
                        $notification_data['paymentId'],
                        $filtered_payment->locked
                    ]
                );

                if ((float)$filtered_payment->total === $cart->getCartTotalPrice()) {
                    $this->order = $this->createOrder($cart, $notification_data['externalId'], $notification_data['paymentId']);
                    $filtered_payments = $this->getFilteredPayments(
                        $notification_data['externalId'],
                        $notification_data['paymentId'],
                        $notification_data['status']
                    );
                    $filtered_payment = reset($filtered_payments);
                } else {
                    PaynowLogger::warning(
                        'Inconsistent payment and cart amount {cartAmount={}, cartId={}, externalId={}, paymentId={}, paymentAmount={}}',
                        [
                            $cart->getCartTotalPrice(),
                            $cart->id,
                            $notification_data['externalId'],
                            $notification_data['paymentId'],
                            $filtered_payment->total
                        ]
                    );
                }
            } else {
                if (PaynowPaymentData::findByPaymentId($notification_data['paymentId'])) {
                    PaynowPaymentData::updateStatus($notification_data['paymentId'], $notification_data['status']);
                } else {
                    $previous_payment_data = PaynowPaymentData::findLastByExternalId($notification_data['externalId']);
                    if ($previous_payment_data) {
                        PaynowPaymentData::create(
                            $notification_data['paymentId'],
                            $notification_data['status'],
                            $previous_payment_data->id_order,
                            $previous_payment_data->id_cart,
                            $previous_payment_data->order_reference,
                            $previous_payment_data->external_id,
                            $previous_payment_data->total
                        );
                    }
                }
            }

            (new PaynowOrderStateProcessor($this->module))->updateState(
                (int)$filtered_payment->id_order,
                $notification_data['paymentId'],
                (int)$filtered_payment->id_cart,
                $filtered_payment->order_reference,
                $filtered_payment->external_id,
                $filtered_payment->status,
                $notification_data['status']
            );
        } catch (Exception $exception) {
            PaynowLogger::error(
                'An error occurred during processing notification {externalId={}, paymentId={}, status={}, message={}}',
                [
                    $notification_data['externalId'],
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

    private function getFilteredPayments($external_id, $payment_id, $payment_status): array
    {
        $payments = PaynowPaymentData::findAllByExternalId($external_id)->getResults();

        return array_filter($payments, function ($payment) use ($payment_id, $payment_status) {
            return $payment->id_payment === $payment_id ||
                   $payment_status === Paynow\Model\Payment\Status::STATUS_NEW;
        });
    }
}
