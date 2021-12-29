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
                $payment_data = (new PaynowPaymentProcessor($this->context, $this->module))->process();

                if ($payment_data['status'] && in_array($payment_data['status'], [
                        Paynow\Model\Payment\Status::STATUS_NEW,
                        Paynow\Model\Payment\Status::STATUS_PENDING
                    ])) {
                    if (PaynowConfigurationHelper::CREATE_ORDER_BEFORE_PAYMENT === (int)Configuration::get('PAYNOW_CREATE_ORDER_STATE')) {
                        $order = $this->createOrder($cart);
                    }
                    $response = array_merge($response, [
                        'success'      => true,
                        'payment_id'   => $payment_data['payment_id'],
                        'order_id'     => $order->id ?? null,
                        'redirect_url' => PaynowLinkHelper::getBlikConfirmUrl([
                            'external_id'   => $payment_data['external_id'],
                            'paymentId'     => $payment_data['payment_id'],
                            'paymentStatus' => $payment_data['status'],
                            'token'         => Tools::encrypt($this->context->customer->secure_key)
                        ])
                    ]);

                    if (!empty($order) && $order->id) {
                        PaynowPaymentData::updateOrderIdAndOrderReferenceByPaymentId(
                            $order->id,
                            $order->reference,
                            $payment_data['payment_id']
                        );
                    }
                } else {
                    $response['message'] = $this->translations['An error occurred during the payment process'];
                }
            } catch (PaynowPaymentAuthorizeException $exception) {
                PaynowLogger::error(
                    $exception->getMessage() . '{externalId={}}',
                    [
                        $exception->getExternalId()
                    ]
                );

                foreach ($exception->getPrevious()->getErrors() as $error) {
                    PaynowLogger::error(
                        $exception->getMessage() . '{externalId={}, error={}, message={}}',
                        [
                            $exception->getExternalId(),
                            $error->getType(),
                            $error->getMessage()
                        ]
                    );
                }
                if ($exception->getPrevious()->getErrors() && $exception->getPrevious()->getErrors()[0]) {
                    PaynowLogger::error($exception->getMessage());
                    switch ($exception->getPrevious()->getErrors()[0]->getType()) {
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

    private function createOrder($cart): Order
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

        return new Order($this->module->currentOrder);
    }
}
