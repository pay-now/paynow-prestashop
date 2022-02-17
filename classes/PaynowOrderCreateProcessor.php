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
        if ( ! $this->canProcess($cart->id, $external_id)) {
            PaynowLogger::warning(
                'Can\'t create and order due optimistic lock on paynow\'s payment data {cartId={}, externalId={}}',
                [
                    $cart->id,
                    $external_id
                ]
            );

            return null;
        }

        $this->setOptimisticLock($cart->id, $external_id);
        PaynowLogger::info(
            'Creating an order from cart {externalId={}, cartId={}}',
            [
                $external_id,
                $cart->id
            ]
        );

        try {
            return $this->createOrder($cart, $external_id);
        } catch (Exception $exception) {
            PaynowLogger::error(
                'An order has not been created {externalId={}, cartId={}, message={}}',
                [
                    $external_id,
                    $cart->id,
                    $exception->getMessage()
                ]
            );

            return null;
        }
    }

    /**
     * @param Cart $cart
     * @param $external_id
     *
     * @return Order|null
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function createOrder(Cart $cart, $external_id): ?Order
    {
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

        if ( ! $order_created && ! $this->module->currentOrder) {
            PaynowLogger::error(
                'An order has not been created {externalId={}, cartId={}}',
                [
                    $external_id,
                    $cart->id
                ]
            );

            return null;
        }

        $order = new Order($this->module->currentOrder);
        PaynowLogger::info(
            'An order has been successfully created {externalId={}, orderReference={}, cartId={}, orderId={}}',
            [
                $external_id,
                $order->reference,
                $cart->id,
                $order->id
            ]
        );
        $this->unsetOptimisticLock($cart->id, $external_id);

        return $order;
    }

    /**
     * @param $cart_id
     * @param null $external_id
     *
     * @return bool
     */
    private function canProcess($cart_id, $external_id = null): bool
    {
        try {
            if ($external_id) {
                $payment_data = PaynowPaymentData::findLastByExternalId($external_id);
            } else {
                $payment_data = PaynowPaymentData::findLastByCartId($cart_id);
            }

            return 0 === (int)$payment_data->locked;
        } catch (PrestaShopException $exception) {
            PaynowLogger::error(
                'An error occurred during check can process create order {cartId={}, externalId={}}',
                [
                    $cart_id,
                    $external_id
                ]
            );

            return false;
        }
    }

    private function setOptimisticLock($cart_id, $external_id = null)
    {
        if ($external_id) {
            PaynowPaymentData::setOptimisticLockByExternalId($external_id);
        } else {
            PaynowPaymentData::setOptimisticLockByCartId($cart_id);
        }
    }

    private function unsetOptimisticLock($cart_id, $external_id = null)
    {
        if ($external_id) {
            PaynowPaymentData::unsetOptimisticLockByExternalId($external_id);
        } else {
            PaynowPaymentData::unsetOptimisticLockByCartId($cart_id);
        }
    }
}
