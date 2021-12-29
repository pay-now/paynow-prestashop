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

class PaynowPaymentDataBuilder
{
    private $context;

    /**
     * @var Paynow
     */
    private $module;

    /**
     * @var array
     */
    private $translations;

    public function __construct($module)
    {
        $this->context = Context::getContext();
        $this->module = $module;
        $this->translations = $this->module->getTranslationsArray();
    }

    /**
     * Returns payment request data based on cart
     *
     * @param $cart
     * @param $external_id
     *
     * @return array
     * @throws Exception
     */
    public function fromCart($cart, $external_id): array
    {
        return $this->build(
            $cart->id_currency,
            $cart->id_customer,
            $cart->getOrderTotal(),
            $cart->id,
            $this->translations['Order to cart: '] . $cart->id,
            $external_id
        );
    }

    /**
     * Returns payment request data based on order
     *
     * @param $order
     *
     * @return array
     */
    public function fromOrder($order): array
    {
        return $this->build(
            $order->id_currency,
            $order->id_customer,
            $order->total_paid,
            $order->id_cart,
            $this->translations['Order No: '] . $order->reference,
            $order->reference
        );
    }

    /**
     * Returns payments request data
     *
     * @param $id_currency
     * @param $id_customer
     * @param $total_to_paid
     * @param $id_cart
     * @param $description
     *
     * @param null $external_id
     *
     * @return array
     */
    private function build(
        $id_currency,
        $id_customer,
        $total_to_paid,
        $id_cart,
        $description,
        $external_id = null
    ): array {
        $currency = Currency::getCurrency($id_currency);
        $customer = new Customer((int)$id_customer);

        $request = [
            'amount'      => number_format($total_to_paid * 100, 0, '', ''),
            'currency'    => $currency['iso_code'],
            'externalId'  => $external_id,
            'description' => $description,
            'buyer'       => [
                'firstName' => $customer->firstname,
                'lastName'  => $customer->lastname,
                'email'     => $customer->email,
                'locale'    => $this->context->language->locale ?? $this->context->language->language_code
            ],
            'continueUrl' => PaynowLinkHelper::getContinueUrl(
                $id_cart,
                $this->module->id,
                $customer->secure_key,
                $external_id
            )
        ];

        if (! empty(Tools::getValue('paymentMethodId'))) {
            $request['paymentMethodId'] = (int)Tools::getValue('paymentMethodId');
        }

        if (Configuration::get('PAYNOW_PAYMENT_VALIDITY_TIME_ENABLED')) {
            $request['validityTime'] = Configuration::get('PAYNOW_PAYMENT_VALIDITY_TIME');
        }

        if (! empty(Tools::getValue('blikCode'))) {
            $request['authorizationCode'] = (int)preg_replace('/\s+/', '', Tools::getValue('blikCode'));
        }

        if (Configuration::get('PAYNOW_SEND_ORDER_ITEMS')) {
            $products    = $this->context->cart->getProducts(true);
            $order_items = [];
            foreach ($products as $product) {
                $order_items[] = [
                    'name'     => $product['name'],
                    'category' => $this->getCategoriesNames($product['id_category_default']),
                    'quantity' => $product['quantity'],
                    'price'    => number_format($product['price'] * 100, 0, '', '')
                ];
            }
            if (! empty($order_items)) {
                $request['orderItems'] = $order_items;
            }
        }

        return $request;
    }

    /**
     * @param $id_category_default
     *
     * @return string
     */
    private function getCategoriesNames($id_category_default): string
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
}
