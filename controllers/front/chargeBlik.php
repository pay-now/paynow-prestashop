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

if (! defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__) . '/../../classes/PaynowFrontController.php');
require_once(dirname(__FILE__) . '/../../classes/PaymentProcessor.php');
require_once(dirname(__FILE__) . '/../../classes/PaymentDataBuilder.php');
include_once(dirname(__FILE__) . '/../../models/PaynowPaymentData.php');

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

        if ($this->context->customer->secure_key === Tools::getValue('token')) {
            $cart = new Cart(Context::getContext()->cart->id);
            if (empty($cart) || ! $cart->id) {
                $this->ajaxRender(json_encode($response));
                exit;
            }

            try {
                $external_id          = $this->context->cart->id;
                $idempotency_key      = uniqid($external_id . '_');
                $payment_request_data = (new PaymentDataBuilder($this->module))->fromCart();
                $payment              = (new PaymentProcessor($this->module->getPaynowClient()))
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
                        'redirect_url' => LinkHelper::getBlikConfirmUrl([
                            'order_reference' => $order->reference,
                            'paymentId'       => $payment->getPaymentId(),
                            'paymentStatus'   => $payment->getStatus(),
                            'token'           => Tools::encrypt($order->reference)
                        ])
                    ]);

                    if ($order->id) {
                        PaynowPaymentData::create(
                            $payment->getPaymentId(),
                            Paynow\Model\Payment\Status::STATUS_NEW,
                            $order->id,
                            $order->id_cart,
                            $order->reference,
                            $payment_request_data['externalId']
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
                if ($exception->getErrors() && $exception->getErrors()[0]) {
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
                            $response['message'] = $this->translations['An error occurred during the payment process and the payment could not be completed'];
                    }
                }
            }
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
