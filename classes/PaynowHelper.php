<?php

use Paynow\Exception\PaynowException;
use Paynow\Model\Payment\Status;

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

class PaynowHelper
{
    /** @var Paynow */
    public static $module;

    /**
     * @param int $id_order
     * @param string $payment_status
     * @param int $payment_data_locked
     * @param bool $orders_exists
     *
     * @return bool
     */
    public static function canProcessCreateOrder(int $id_order, string $payment_status, int $payment_data_locked, bool $orders_exists): bool
    {
        return PaynowConfigurationHelper::CREATE_ORDER_AFTER_PAYMENT === (int)Configuration::get('PAYNOW_CREATE_ORDER_STATE') &&
            Status::STATUS_CONFIRMED === $payment_status &&
            0 === $id_order &&
            0 === $payment_data_locked &&
            false === $orders_exists;
    }

    /**
     * @param $cart
     * @param $external_id
     * @param $payment_id
     *
     * @return Order|null
     */
    public static function createOrder($cart, $external_id, $payment_id): ?Order
    {
        $order = (new PaynowOrderCreateProcessor(self::$module))->process($cart, $external_id, $payment_id);

        if (! $order) {
            return null;
        }

        PaynowPaymentData::updateOrderIdAndOrderReferenceByPaymentId(
            $order->id,
            $order->reference,
            $payment_id
        );

        return $order;
    }
}
