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

use Paynow\Exception\ConfigurationException;

class PaynowPaymentModuleFrontController extends PaynowFrontController
{
    protected $order;

    public function initContent()
    {
        parent::initContent();

        if ($this->isRetryPayment()) {
            $this->processRetryPayment();
        } else {
            $this->processNewPayment();
        }
    }

    private function isRetryPayment(): bool
    {
        return Tools::getValue('id_order') !== false &&
            Tools::getValue('order_reference') !== false &&
            $this->module->canOrderPaymentBeRetried(new Order((int)Tools::getValue('id_order')));
    }

    /**
     * @throws ConfigurationException
     */
    private function processRetryPayment()
    {
        $id_order = (int)Tools::getValue('id_order');
        $order_reference = Tools::getValue('order_reference');

        if (!$id_order || !Validate::isUnsignedId($id_order)) {
            Tools::redirect('index.php?controller=history');
        }

        $this->order = new Order($id_order);
        $this->module->currentOrder = $this->order->id;

        if (! Validate::isLoadedObject($this->order) || $this->order->reference !== $order_reference) {
            Tools::redirect('index.php?controller=history');
        }

        $this->validateCustomer($this->order->id_customer);
        $this->sendPaymentRequest();
    }

    /**
     * @throws ConfigurationException
     * @throws Exception
     */
    private function processNewPayment()
    {
        $this->validateCart();

        if (PaynowConfigurationHelper::CREATE_ORDER_BEFORE_PAYMENT === (int)Configuration::get('PAYNOW_CREATE_ORDER_STATE') &&
            false === $this->context->cart->orderExists()) {
            $this->order = (new PaynowOrderCreateProcessor($this->module))->process($this->context->cart, null);
        }

        $this->sendPaymentRequest();
    }

    /**
     * @throws ConfigurationException|Exception
     */
    private function sendPaymentRequest()
    {
        try {
            $payment_data = (new PaynowPaymentProcessor($this->context, $this->module))->process();
            Tools::redirect($this->getRedirectUrl($payment_data));
        } catch (PaynowPaymentAuthorizeException $exception) {
            $errors = $exception->getPrevious()->getErrors();
            if (! empty($errors)) {
                foreach ($errors as $error) {
                    PaynowLogger::error(
                        'An error occurred during sending payment request {errorType={}, externalId={}, message={}}',
                        [
                            $error->getType(),
                            $exception->getExternalId(),
                            $error->getMessage()
                        ]
                    );
                }
            } else {
                PaynowLogger::error(
                    $exception->getMessage() . ' {code={}, externalId={}, message={}}',
                    [
                        $exception->getCode(),
                        $exception->getExternalId(),
                        $exception->getMessage(),
                    ]
                );
            }
            $this->displayError();
        }
    }

    /**
     * Validate cart
     */
    private function validateCart()
    {
        if (! $this->context->cart->id) {
            PaynowLogger::warning('Empty cart');
            Tools::redirect('index.php?controller=cart');
        }

        if ($this->context->cart->id_customer == 0 ||
            $this->context->cart->id_address_delivery == 0 ||
            $this->context->cart->id_address_invoice == 0) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $this->validateCustomer($this->context->cart->id_customer);
    }

    /**
     * Validate customer
     */
    private function validateCustomer($id_customer)
    {
        if ($id_customer != $this->context->customer->id) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $customer = new Customer($id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }
    }

    /**
     * @param array $payment_data
     *
     * @return string
     */
    private function getRedirectUrl(array $payment_data): string
    {
        if (! in_array($payment_data['status'], [
            Paynow\Model\Payment\Status::STATUS_NEW,
            Paynow\Model\Payment\Status::STATUS_PENDING
        ])) {
            return PaynowLinkHelper::getReturnUrl(
                $payment_data['external_id'],
                Tools::encrypt($payment_data['external_id'])
            );
        }

        if (! $payment_data['redirect_url']) {
            return PaynowLinkHelper::getContinueUrl(
                $this->context->cart->id_cart,
                $this->module->id,
                $this->context->cart->secure_key,
                $payment_data['external_id']
            );
        }

        return $payment_data['redirect_url'];
    }

    private function displayError()
    {
        $currency = new Currency($this->context->cart->id_currency);
        $this->context->smarty->assign([
            'total_to_pay' => Tools::displayPrice($this->order->total_paid, $currency),
            'button_action' => PaynowLinkHelper::getPaymentUrl(
                !empty($this->order) ?
                [
                    'id_order' => $this->order->id,
                    'order_reference' => $this->order->reference
                ]: null
            ),
            'cta_text' => $this->module->getCallToActionText()
        ]);

        $this->renderTemplate('error.tpl');
    }
}
