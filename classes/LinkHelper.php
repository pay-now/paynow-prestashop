<?php

class LinkHelper
{
    public static function getContinueUrl($order, $id_module, $secure_key, $url_params = null)
    {
        if (Configuration::get('PAYNOW_USE_CLASSIC_RETURN_URL')) {
            return ContextCore::getContext()->link->getPageLink(
                'order-confirmation',
                null,
                null,
                [
                    'id_cart'   => $order->id_cart,
                    'id_module' => $id_module,
                    'id_order'  => $order->reference,
                    'key'       => $secure_key
                ]
            );
        }

        return LinkHelper::getReturnUrl($order);
    }

    public static function getPaymentUrl($url_params = null)
    {
        return ContextCore::getContext()->link->getModuleLink(
            'paynow',
            'payment',
            ! empty($url_params) ? $url_params : []
        );
    }

    public static function getNotificationUrl()
    {
        return ContextCore::getContext()->link->getModuleLink('paynow', 'notifications');
    }

    public static function getReturnUrl($order = null)
    {
        return ContextCore::getContext()->link->getModuleLink(
            'paynow',
            'return',
            ! empty($order) ? [
                'order_reference' => $order->reference,
                'token'           => Tools::encrypt($order->reference)
            ] : []
        );
    }

    public static function getOrderUrl($order)
    {
        if (Cart::isGuestCartByCartId($order->id_cart)) {
            $customer = new Customer((int)$order->id_customer);
            return ContextCore::getContext()->link->getPageLink(
                'guest-tracking',
                null,
                ContextCore::getContext()->language->id,
                [
                    'order_reference' => $order->reference,
                    'email' => $customer->email
                ]
            );
        }

        return ContextCore::getContext()->link->getPageLink(
            'order-detail',
            null,
            ContextCore::getContext()->language->id,
            [
                'id_order' => $order->id
            ]
        );
    }
}