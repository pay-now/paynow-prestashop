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
    private const MAX_ORDER_ITEM_NAME_LENGTH = 120;
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
		$paymentMethodId = Tools::getValue('paymentMethodId');

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

		try {
			$address = new Address($this->context->cart->id_address_delivery);
			$invoiceAddress = new Address($this->context->cart->id_address_invoice);

			try {
				$state = new State($address->id_state);
			} catch (Throwable $e) {
				$state = null;
			}

			try {
				$invoiceState = new State($invoiceAddress->id_state);
			} catch (Throwable $e) {
				$invoiceState = null;
			}

			try {
				$country = Country::getIsoById($address->id_country);
			} catch (Throwable $e) {
				$country = null;
			}

			try {
				$invoiceCountry = Country::getIsoById($invoiceAddress->id_country);
			} catch (Throwable $e) {
				$invoiceCountry = null;
			}

			$request['buyer']['address'] = [
				'billing' => [
					'street' => $invoiceAddress->address1,
					'houseNumber' => $invoiceAddress->address2,
					'apartmentNumber' => '',
					'zipcode' => $invoiceAddress->postcode,
					'city' => $invoiceAddress->city,
					'county' => $invoiceState ? $invoiceState->name : '',
					'country' => $invoiceCountry ?: '',
				],
				'shipping' => [
					'street' => $address->address1,
					'houseNumber' => $address->address2,
					'apartmentNumber' => '',
					'zipcode' => $address->postcode,
					'city' => $address->city,
					'county' => $state ? $state->name : '',
					'country' => $country ?: '',
				]
			];
		} catch (Throwable $exception) {
			PaynowLogger::error('Cannot add addresses to payment data', ['msg' => $exception->getMessage()]);
		}

        if (!empty($id_customer) && $this->context->customer){
			if (method_exists($this->context->customer, 'isGuest') && !$this->context->customer->isGuest()) {
				$request['buyer']['externalId'] = PaynowKeysGenerator::generateBuyerExternalId($id_customer, $this->module);
			} elseif ($this->context->customer->is_guest === '0') {
				$request['buyer']['externalId'] = PaynowKeysGenerator::generateBuyerExternalId($id_customer, $this->module);
			}
        }

        if (! empty($paymentMethodId)) {
            $request['paymentMethodId'] = (int)$paymentMethodId;
        }

        if (Configuration::get('PAYNOW_PAYMENT_VALIDITY_TIME_ENABLED')) {
            $request['validityTime'] = Configuration::get('PAYNOW_PAYMENT_VALIDITY_TIME');
        }

        if (! empty(Tools::getValue('blikCode'))) {
            $request['authorizationCode'] = (int)preg_replace('/\s+/', '', Tools::getValue('blikCode'));
        }

        if (!empty(Tools::getValue('paymentMethodToken'))) {
            $request['paymentMethodToken'] = Tools::getValue('paymentMethodToken');
        }

		if (! empty(Tools::getValue('paymentMethodFingerprint'))) {
			$request['buyer']['deviceFingerprint'] = Tools::getValue('paymentMethodFingerprint');
		}

        if (Configuration::get('PAYNOW_SEND_ORDER_ITEMS')) {
            $products    = $this->context->cart->getProducts(true);
            $order_items = [];
            foreach ($products as $product) {
                $order_items[] = [
                    'name'     => self::truncateOrderItemName($product['name']),
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

    public static function truncateOrderItemName(string $name): string
    {
        $name = trim($name);

        if(strlen($name) <= self::MAX_ORDER_ITEM_NAME_LENGTH) {
            return $name;
        }

        return substr($name, 0, self::MAX_ORDER_ITEM_NAME_LENGTH - 3) . '...';
    }
}
