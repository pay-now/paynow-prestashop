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

require_once(dirname(__FILE__) . '/../../classes/PaymentStatus.php');
require_once(dirname(__FILE__) . '/../../classes/PaynowFrontController.php');

class PaynowPaymentModuleFrontController extends PaynowFrontController
{
    protected $order;

    public function initContent()
    {
        $this->display_column_left = false;

        parent::initContent();

        if ($this->isRetry()) {
            $this->retryPayment();
        } else {
            $this->processPayment();
        }
    }

    private function isRetry()
    {
        $last_payment_status = $this->paynow->getLastPaymentStatusByOrderId((int)Tools::getValue('id_order'));
        return Tools::getValue('id_order') !== false &&
            Tools::getValue('order_reference') !== false &&
            $last_payment_status['status'] !== PaymentStatus::STATUS_CONFIRMED;
    }

    private function retryPayment()
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
        $this->makePayment();
    }

    public function postProcess()
    {
        if (!Module::isEnabled($this->module->name)) {
            die($this->module->l('This payment method is not available.', 'payment'));
        }

        if (!$this->module->active) {
            die($this->module->l('Paynow module isn\'t active.', 'payment'));
        }

        if ($this->isRetry()) {
            $this->retryPayment();
        } else {
            $this->cartValidation();
        }
    }

    private function cartValidation()
    {
        if (!$this->context->cart->id) {
            PaynowLogger::log(null, null, 'Empty cart');
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

    private function processPayment()
    {
        $cart = $this->context->cart;
        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

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

        $this->makePayment();
    }

    private function makePayment()
    {
        try {
            $api_payment = $this->apiClient->createPayment($this->preparePaymentRequest($this->order));
            $this->paynow->storePaymentState(
                $api_payment->paymentId,
                $api_payment->status,
                $this->order->id,
                $this->order->id_cart,
                $this->order->reference
            );
            Tools::redirect($api_payment->redirectUrl);
        } catch (PaynowClientException $e) {
            PaynowLogger::log($e->getResponseBody(), $this->order->reference, $e->getMessage());
            $this->showError();
        }
    }

    private function preparePaymentRequest($order)
    {
        $currency = Currency::getCurrency($order->id_currency);
        $customer = new Customer((int)$order->id_customer);

        return [
            'amount' => $this->convertAmount($order->total_paid),
            'currency' => $currency['iso_code'],
            'externalId' => $order->reference,
            'description' => 'Testowa transakcja',
            'buyer' => [
                'email' => $customer->email
            ]
        ];
    }

    private function convertAmount($value)
    {
        return (int)round($value * 100);
    }

    private function showError()
    {
        $this->context->smarty->assign([
            'total_to_pay' => Tools::displayPrice($this->order->total_paid, (int)$this->order->id_currency),
            'button_action' => $this->context->link->getModuleLink(
                'paynow',
                'payment',
                [
                    'id_order' => $this->order->id,
                    'order_reference' => $this->order->reference
                ]
            ),
            'order_reference' => $this->order->reference
        ]);

        $this->renderTemplate('error.tpl');
    }
}
