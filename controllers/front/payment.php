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
        $this->display_column_left = false;
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

    private function processRetryPayment()
    {
        $id_order = (int)Tools::getValue('id_order');
        $order_reference = Tools::getValue('order_reference');

        if (!$id_order || !Validate::isUnsignedId($id_order)) {
            Tools::redirect('index.php?controller=history');
        }

        $this->order = new Order($id_order);

        if (! Validate::isLoadedObject($this->order) || $this->order->reference !== $order_reference) {
            Tools::redirect('index.php?controller=history');
        }

        $this->customerValidation($this->order->id_customer);
        $this->sendPaymentRequest();
    }

    private function cartValidation()
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

        $this->customerValidation($this->context->cart->id_customer);
    }

    private function customerValidation($id_customer)
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
     * @throws ConfigurationException
     * @throws Exception
     */
    private function processNewPayment()
    {
        $this->cartValidation();

        $total = (float)$this->context->cart->getOrderTotal();
        if ($this->canCreateOrderBeforePayment()) {
            $this->module->validateOrder(
                (int)$this->context->cart->id,
                Configuration::get('PAYNOW_ORDER_INITIAL_STATE'),
                $total,
                $this->module->displayName,
                null,
                null,
                (int)$this->context->currency->id,
                false,
                $this->context->cart->secure_key
            );
        }

        $this->sendPaymentRequest();
    }

    /**
     * @throws ConfigurationException|Exception
     */
    private function sendPaymentRequest()
    {
        try {
            if (PaynowConfigurationHelper::CREATE_ORDER_BEFORE_PAYMENT === (int)Configuration::get('PAYNOW_CREATE_ORDER_STATE')) {
                $this->order = new Order($this->module->currentOrder);
                $external_id = $this->order->reference;
                $idempotency_key = substr(uniqid($this->order->reference . '_', true), 0, 45);
                $payment_request_data = (new PaynowPaymentDataBuilder($this->module))->fromOrder($this->order);
            } else {
                $external_id     = uniqid($this->context->cart->id . '_', false);
                $idempotency_key = substr(uniqid($this->context->cart->id . '_', true), 0, 45);
                $payment_request_data = (new PaynowPaymentDataBuilder($this->module))->fromCart($external_id);
            }

            $payment              = (new PaynowPaymentProcessor($this->module->getPaynowClient()))
                ->process($payment_request_data, $idempotency_key);


            if ($this->canCreateOrderBeforePayment()) {
                PaynowPaymentData::create(
                    $payment->getPaymentId(),
                    Paynow\Model\Payment\Status::STATUS_NEW,
                    $this->order->id,
                    $this->order->id_cart,
                    $this->order->reference,
                    $this->order->reference,
                    $this->order->total_paid
                );
            } else {
                PaynowPaymentData::create(
                    $payment->getPaymentId(),
                    Paynow\Model\Payment\Status::STATUS_NEW,
                    null,
                    $external_id,
                    null,
                    $external_id,
                    $this->context->cart->getOrderTotal()
                );
            }

            PaynowLogger::info(
                'Payment has been successfully created {externalId={}, paymentId={}, status={}}',
                [
                    $external_id,
                    $payment->getPaymentId(),
                    $payment->getStatus()
                ]
            );

            if (! in_array($payment->getStatus(), [
                Paynow\Model\Payment\Status::STATUS_NEW,
                Paynow\Model\Payment\Status::STATUS_PENDING
            ])) {
                Tools::redirect(PaynowLinkHelper::getReturnUrl(
                    $this->order->id_cart,
                    Tools::encrypt($this->order->reference),
                    $this->order->reference
                ));
            }

            if (! $payment->getRedirectUrl()) {
                Tools::redirect(PaynowLinkHelper::getContinueUrl(
                    $this->order->id_cart,
                    $this->module->id,
                    $this->order->secure_key,
                    $this->order->id,
                    $this->order->reference
                ));
            }
            Tools::redirect($payment->getRedirectUrl());
        } catch (Paynow\Exception\PaynowException $exception) {
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
            $this->displayError();
        }
    }

    private function canCreateOrderBeforePayment(): bool
    {
        return PaynowConfigurationHelper::CREATE_ORDER_BEFORE_PAYMENT === (int)Configuration::get('PAYNOW_CREATE_ORDER_STATE');
    }

    private function displayError()
    {
        $currency = new Currency($this->context->cart->id_currency);
        $this->context->smarty->assign([
            'total_to_pay' => Tools::displayPrice($this->context->cart->getCartTotalPrice(), $currency),
            'button_action' => PaynowLinkHelper::getPaymentUrl(
                !empty($this->order) ??
                [
                    'id_order' => $this->order->id,
                    'order_reference' => $this->order->reference
                ]
            ),
            'cta_text' => $this->module->getCallToActionText()
        ]);

        $this->renderTemplate('error.tpl');
    }
}
