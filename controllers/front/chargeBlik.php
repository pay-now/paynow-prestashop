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

use Paynow\Exception\PaynowException;

class PaynowChargeBlikModuleFrontController extends PaynowFrontController
{
    /**
     * @var array
     */
    private $translations;

    public function initContent()
    {
        $this->translations = $this->module->getTranslationsArray();
        parent::initContent();
        $this->executePayment();
    }

    private function executePayment()
    {
        $response = [
            'success' => false
        ];

        if ($this->isTokenValid()) {
            $cart = new Cart(Context::getContext()->cart->id);
            if (empty($cart) || ! $cart->id) {
                $this->ajaxRender(json_encode($response));
                exit;
            }

            try {
                $external_id     = uniqid($this->context->cart->id . '_', false);
                $idempotency_key = substr(uniqid($this->context->cart->id . '_', true), 0, 45);
                $payment_request_data = (new PaynowPaymentDataBuilder($this->module))->fromCart($external_id);
                $payment              = (new PaynowPaymentProcessor($this->module->getPaynowClient()))
                    ->process($payment_request_data, $idempotency_key);

                if ($payment && in_array($payment->getStatus(), [
                        Paynow\Model\Payment\Status::STATUS_NEW,
                        Paynow\Model\Payment\Status::STATUS_PENDING
                    ])) {
                    $order    = new Order($this->createOrder($cart));
                    $response = array_merge($response, [
                        'success'      => true,
                        'payment_id'   => $payment->getPaymentId(),
                        'order_id'     => $order->id,
                        'redirect_url' => PaynowLinkHelper::getBlikConfirmUrl([
                            'order_reference' => $order->reference,
                            'paymentId'       => $payment->getPaymentId(),
                            'paymentStatus'   => $payment->getStatus(),
                            'token'           => Tools::encrypt($this->context->customer->secure_key)
                        ])
                    ]);

                    if ($order->id) {
                        PaynowPaymentData::create(
                            $payment->getPaymentId(),
                            Paynow\Model\Payment\Status::STATUS_NEW,
                            $order->id,
                            $order->id_cart,
                            $order->reference,
                            $payment_request_data['externalId'],
                            $order->total_paid
                        );
                    }
                    PaynowLogger::info(
                        'Payment has been successfully created {orderReference={}, paymentId={}, status={}}',
                        [
                            $order->reference,
                            $payment->getPaymentId(),
                            $payment->getStatus()
                        ]
                    );
                }
            } catch (PaynowException $exception) {
                PaynowLogger::error(
                    $exception->getMessage() . '{externalId={}}',
                    [
                        $external_id
                    ]
                );
                foreach ($exception->getErrors() as $error) {
                    PaynowLogger::error(
                        $exception->getMessage() . '{externalId={}, error={}, message={}}',
                        [
                            $external_id,
                            $error->getType(),
                            $error->getMessage()
                        ]
                    );
                }
                if ($exception->getErrors() && $exception->getErrors()[0]) {
                    PaynowLogger::error($exception->getMessage());
                    switch ($exception->getErrors()[0]->getType()) {
                        case 'AUTHORIZATION_CODE_INVALID':
                            $response['message'] = $this->translations['Wrong BLIK code'];
                            break;
                        case 'AUTHORIZATION_CODE_EXPIRED':
                            $response['message'] = $this->translations['BLIK code has expired'];
                            break;
                        case 'AUTHORIZATION_CODE_USED':
                            $response['message'] = $this->translations['BLIK code already used'];
                            break;
                        default:
                            $response['message'] = $this->translations['An error occurred during the payment process'];
                    }
                }
            }
        } else {
            $response['message'] = $this->translations['An error occurred during the payment process'];
        }

        $this->ajaxRender(json_encode($response));
        exit;
    }

    private function createOrder($cart)
    {
        $currency = $this->context->currency;
        $customer = new Customer($cart->id_customer);

        $this->module->validateOrder(
            (int)$cart->id,
            Configuration::get('PAYNOW_ORDER_INITIAL_STATE'),
            (float)$cart->getOrderTotal(),
            $this->module->displayName,
            null,
            null,
            (int)$currency->id,
            false,
            $customer->secure_key
        );

        return $this->module->currentOrder;
    }
}
