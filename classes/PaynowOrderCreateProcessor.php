<?php

class PaynowOrderCreateProcessor
{
    /** @var Module */
    public $module;

    public function __construct($module)
    {
        $this->module = $module;
    }

    /**
     * @param $cart
     * @param $external_id
     *
     * @return Order|null
     */
    public function process($cart, $external_id): ?Order
    {
        PaynowLogger::info(
            "Creating an order from cart {externalId={}, cartId={}}",
            [
                $external_id,
                $cart->id
            ]
        );

        if ($external_id) {
            PaynowPaymentData::setOptimisticLockByExternalId($external_id);
        }

        try {
            $order_created = $this->module->validateOrder(
                (int)$cart->id,
                Configuration::get('PAYNOW_ORDER_INITIAL_STATE'),
                (float)$cart->getOrderTotal(),
                $this->module->displayName,
                null,
                [],
                (int)$cart->id_currency,
                false,
                $cart->secure_key,
                new Shop($cart->id_shop)
            );
            if ($order_created && $this->module->currentOrder) {
                $order = new Order($this->module->currentOrder);
                PaynowLogger::info(
                    "An order has been successfully created {externalId={}, orderReference={}, cartId={}, orderId={}}",
                    [
                        $external_id,
                        $order->reference,
                        $cart->id,
                        $order->id
                    ]
                );
                if ($external_id) {
                    PaynowPaymentData::unsetOptimisticLockByExternalId($external_id);
                }
                return $order;
            } else {
                PaynowLogger::error(
                    "An order has not been created {externalId={}, cartId={}}",
                    [
                        $external_id,
                        $cart->id
                    ]
                );
                return null;
            }
        } catch (Exception $exception) {
            PaynowLogger::error(
                $exception->getMessage() . ' {externalId={}, cartId={}}',
                [
                    $external_id,
                    $cart->id
                ]
            );

            return null;
        }
    }
}
