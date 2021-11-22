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

if (!defined('_PS_VERSION_')) {
    exit;
}

class LinkHelper
{
    public static function getContinueUrl($id_cart, $id_order, $id_module, $secure_key, $order_reference = null)
    {
        if (Configuration::get('PAYNOW_USE_CLASSIC_RETURN_URL')) {
            return Context::getContext()->link->getPageLink(
                'order-confirmation',
                null,
                null,
                [
                    'id_cart'   => $id_cart,
                    'id_module' => $id_module,
                    'id_order'  => $id_order,
                    'key'       => $secure_key
                ]
            );
        }

        return LinkHelper::getReturnUrl($order_reference, Tools::encrypt($order_reference));
    }

    public static function getPaymentUrl($url_params = null)
    {
        return Context::getContext()->link->getModuleLink(
            'paynow',
            'payment',
            ! empty($url_params) ? $url_params : []
        );
    }

    public static function getNotificationUrl()
    {
        return Context::getContext()->link->getModuleLink('paynow', 'notifications');
    }

    public static function getReturnUrl($order_reference, $token)
    {
        return Context::getContext()->link->getModuleLink(
            'paynow',
            'return',
            [
                'order_reference' => $order_reference,
                'token'           => $token
            ]
        );
    }

    public static function getOrderUrl($order)
    {
        if (Cart::isGuestCartByCartId($order->id_cart)) {
            $customer = new Customer((int)$order->id_customer);
            return Context::getContext()->link->getPageLink(
                'guest-tracking',
                null,
                Context::getContext()->language->id,
                [
                    'order_reference' => $order->reference,
                    'email' => $customer->email
                ]
            );
        }

        return Context::getContext()->link->getPageLink(
            'order-detail',
            null,
            Context::getContext()->language->id,
            [
                'id_order' => $order->id
            ]
        );
    }
}