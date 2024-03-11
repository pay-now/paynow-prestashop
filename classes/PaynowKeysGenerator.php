<?php

use Paynow\Util\ClientExternalIdCalculator;

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

class PaynowKeysGenerator
{
    /**
     * @param string $externalId
     * @return false|string
     */
    public static function generateIdempotencyKey(string $externalId)
    {
        return substr(uniqid($externalId . '_', true), 0, 45);
    }

    /**
     * @param $order
     * @return mixed
     */
    public static function generateExternalIdByOrder($order)
    {
        return $order->reference;
    }

    /**
     * @param $cart
     * @return string
     */
    public static function generateExternalIdByCart($cart): string
    {
        return uniqid($cart->id . '_', false);
    }

    /**
     * @param $customerId
     * @param $module
     * @return string
     */
    public static function generateBuyerExternalId($customerId, $module): string
    {
        return ClientExternalIdCalculator::calculate("$customerId", $module->getSignatureKey());
    }
}
