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

        $this->isPaymentRetry() ? $this->processRetryPayment() : $this->processNewPayment();
    }

    private function isPaymentRetry()
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

        if (!Validate::isLoadedObject($this->order) || $this->order->reference !== $order_reference) {
            Tools::redirect('index.php?controller=history');
        }

        $this->customerValidation($this->order->id_customer);
        $this->sendPaymentRequest();
    }

    public function postProcess()
    {
        if (!Module::isEnabled($this->module->name)) {
            die($this->module->l('This payment method is not available.'));
        }

        if (!$this->module->active) {
            die($this->module->l('Paynow module isn\'t active.'));
        }

        if ($this->isPaymentRetry()) {
            $this->processRetryPayment();
        } else {
            $this->cartValidation();
        }
    }

    private function cartValidation()
    {
        if (!$this->context->cart->id) {
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

        // Check if customer exists
        $customer = new Customer($id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }
    }

    private function processNewPayment()
    {
        $cart = $this->context->cart;
        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal();

        $this->cartValidation();
        $customer = new Customer($cart->id_customer);
        $this->module->validateOrder(
            (int)$cart->id,
            Configuration::get('PAYNOW_ORDER_INITIAL_STATE'),
            $total,
            $this->module->displayName,
            null,
            null,
            (int)$currency->id,
            false,
            $customer->secure_key
        );
        $this->order = new Order($this->module->currentOrder);

        $this->sendPaymentRequest();
    }

    /**
     * @throws ConfigurationException
     */
    private function sendPaymentRequest()
    {
        try {
            $idempotency_key      = uniqid($this->order->reference . '_');
            $payment_request_data = (new PaynowPaymentDataBuilder($this->module))->fromOrder($this->order);
            $payment              = (new PaynowPaymentProcessor($this->module->getPaynowClient()))
                ->process($payment_request_data, $idempotency_key);

            PaynowPaymentData::create(
                $payment->getPaymentId(),
                Paynow\Model\Payment\Status::STATUS_NEW,
                $this->order->id,
                $this->order->id_cart,
                $this->order->reference,
                $this->order->reference
            );
            PaynowLogger::info(
                'Payment has been successfully created {orderReference={}, paymentId={}, status={}}',
                [
                    $this->order->reference,
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
                $exception->getMessage() . '{orderReference={}}',
                [
                    $this->order->reference
                ]
            );
            foreach ($exception->getErrors() as $error) {
                PaynowLogger::error(
                    $exception->getMessage() . '{orderReference={}, error={}, message={}}',
                    [
                        $this->order->reference,
                        $error->getType(),
                        $error->getMessage()
                    ]
                );
            }
            $this->displayError();
        }
    }

    private function displayError()
    {
        $this->context->smarty->assign([
            'total_to_pay' => Tools::displayPrice($this->order->total_paid, (int)$this->order->id_currency),
            'button_action' => PaynowLinkHelper::getPaymentUrl(
                [
                    'id_order' => $this->order->id,
                    'order_reference' => $this->order->reference
                ]
            ),
            'order_reference' => $this->order->reference,
            'cta_text' => $this->module->getCallToActionText()
        ]);

        $this->renderTemplate('error.tpl');
    }
}
