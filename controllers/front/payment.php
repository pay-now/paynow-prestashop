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

class PaynowPaymentModuleFrontController extends PaynowFrontController
{
    protected $order;

    public function initContent()
    {
        $this->display_column_left = false;
        parent::initContent();

        $this->isPaymentRetry() ? $this->retryPayment() : $this->processPayment();
    }

    private function isPaymentRetry()
    {
        return Tools::getValue('id_order') !== false &&
            Tools::getValue('order_reference') !== false &&
            $this->module->canOrderPaymentBeRetried((int)Tools::getValue('id_order'));
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
        $this->sendPaymentRequest();
    }

    public function postProcess()
    {
        if (!Module::isEnabled($this->module->name)) {
            die($this->module->l('This payment method is not available.', 'payment'));
        }

        if (!$this->module->active) {
            die($this->module->l('Paynow module isn\'t active.', 'payment'));
        }

        if ($this->isPaymentRetry()) {
            $this->retryPayment();
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

        $this->sendPaymentRequest();
    }

    private function sendPaymentRequest()
    {
        try {
            $payment_client = new Paynow\Service\Payment($this->module->api_client);
            $idempotency_key = uniqid($this->order->reference . '_');
            $external_id = $this->order->reference;
            $request = $this->preparePaymentRequest($this->order, $external_id);
            $payment = $payment_client->authorize($request, $idempotency_key);
            $this->module->storePaymentState(
                $payment->getPaymentId(),
                $payment->getStatus(),
                $this->order->id,
                $this->order->id_cart,
                $this->order->reference,
                $external_id
            );
            PaynowLogger::info(
                'Payment has been successfully created {orderReference={}, paymentId={}}',
                [
                    $this->order->reference,
                    $payment->getPaymentId()
                ]
            );
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

    private function preparePaymentRequest($order, $external_id)
    {
        $currency = Currency::getCurrency($order->id_currency);
        $customer = new Customer((int)$order->id_customer);

        $request = [
            'amount' => number_format($order->total_paid * 100, 0, '', ''),
            'currency' => $currency['iso_code'],
            'externalId' => $external_id,
            'description' => $this->module->l('Order No: ', 'payment') . $order->reference,
            'buyer' => [
                'firstName' => $customer->firstname,
                'lastName' => $customer->lastname,
                'email' => $customer->email,
                'locale' => $this->context->language->locale ? $this->context->language->locale : $this->context->language->language_code
            ],
            'continueUrl' => Configuration::get('PAYNOW_USE_CLASSIC_RETURN_URL') ?
                $this->context->link->getPageLink(
                    'order-confirmation',
                    null,
                    null,
                    [
                        'id_cart' => $order->id_cart,
                        'id_module' => $this->module->id,
                        'id_order' => $external_id,
                        'key' => $customer->secure_key
                    ]
                ) : $this->context->link->getModuleLink(
                    'paynow',
                    'return',
                    [
                        'order_reference' => $order->reference,
                        'token' => Tools::encrypt($order->reference)
                    ]
                )
        ];

        if (!empty(Tools::getValue('paymentMethodId'))) {
            $request['paymentMethodId'] = (int)Tools::getValue('paymentMethodId');
        }
        if (Configuration::get('PAYNOW_SEND_ORDER_ITEMS')) {
            $products = $this->context->cart->getProducts(true);
            $order_items = [];
            foreach ($products as $product) {
                $order_items[] = [
                    'name'     => $product['name'],
                    'category' => $this->getCategoriesNames($product['id_category_default']),
                    'quantity' => $product['quantity'],
                    'price'    => $product['price']
                ];
                }
            if (!empty($order_items)) {
                $request['orderItems'] = $order_items;
            }
        }

        return $request;
    }

    private function getCategoriesNames($id_category_default)
    {
        $categoryDefault = new Category($id_category_default, $this->context->language->id);
        $categoriesNames = [$categoryDefault->name];
        foreach ($categoryDefault->getAllParents() as $category) {
            if ($category->id_parent != 0 && !$category->is_root_category) {
                array_unshift($categoriesNames, $category->name);
            }
        }
        return implode(", ", $categoriesNames); 
    }

    private function displayError()
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
            'order_reference' => $this->order->reference,
            'cta_text' => $this->callToActionText
        ]);

        $this->renderTemplate('error.tpl');
    }
}
