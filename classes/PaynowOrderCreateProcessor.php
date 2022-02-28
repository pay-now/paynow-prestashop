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
     * @param $payment_id
     *
     * @return Order|null
     */
    public function process($cart, $external_id, $payment_id = null): ?Order
    {
        if (! $this->canProcess($cart->id, $external_id)) {
            PaynowLogger::warning(
                'Can\'t create an order due optimistic lock on paynow\'s payment data {cartId={}, externalId={}}',
                [
                    $cart->id,
                    $external_id
                ]
            );

            return null;
        }

        $this->setOptimisticLock($cart->id, $external_id);
        PaynowLogger::info(
            'Creating an order from cart {cartId={}, externalId={}}',
            [
                $cart->id,
                $external_id,
            ]
        );

        try {
            return $this->createOrder($cart, $external_id, $payment_id);
        } catch (Exception $exception) {
            PaynowLogger::error(
                'An order has not been created {cartId={}, externalId={}, message={}}',
                [
                    $cart->id,
                    $external_id,
                    $exception->getMessage()
                ]
            );

            return null;
        }
    }

    /**
     * @param Cart $cart
     * @param $external_id
     * @param $payment_id
     *
     * @return Order|null
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function createOrder(Cart $cart, $external_id, $payment_id = null): ?Order
    {
        $order_created = $this->module->validateOrder(
            (int)$cart->id,
            Configuration::get('PAYNOW_ORDER_INITIAL_STATE'),
            (float)$cart->getOrderTotal(),
            $this->module->displayName,
            null,
            $payment_id ? ['transaction_id' => $payment_id] : [],
            (int)$cart->id_currency,
            false,
            $cart->secure_key,
            new Shop($cart->id_shop)
        );

        if (! $order_created && ! $this->module->currentOrder) {
            PaynowLogger::error(
                'An order has not been created {cartId={}, externalId={}, paymentId={}}',
                [
                    $cart->id,
                    $external_id,
                    $payment_id
                ]
            );

            return null;
        }

        $order = new Order($this->module->currentOrder);
        PaynowLogger::info(
            'An order has been successfully created {cartId={}, externalId={}, orderId={}, orderReference={}, paymentId={}}',
            [
                $cart->id,
                $external_id,
                $order->id,
                $order->reference,
                $payment_id
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
            return $payment_data === false || ($payment_data && 0 === (int)$payment_data->locked);
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
        PaynowLogger::debug(
            'Setting optimistic lock on paynow data {cartId={}, externalId={}}',
            [
                $cart_id,
                $external_id
            ]
        );
        if ($external_id) {
            PaynowPaymentData::setOptimisticLockByExternalId($external_id);
        } else {
            PaynowPaymentData::setOptimisticLockByCartId($cart_id);
        }
    }

    private function unsetOptimisticLock($cart_id, $external_id = null)
    {
        PaynowLogger::debug(
            'Unsetting optimistic lock on paynow data {cartId={}, externalId={}}',
            [
                $cart_id,
                $external_id
            ]
        );
        if ($external_id) {
            PaynowPaymentData::unsetOptimisticLockByExternalId($external_id);
        } else {
            PaynowPaymentData::unsetOptimisticLockByCartId($cart_id);
        }
    }
}
