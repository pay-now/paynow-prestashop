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

class LinkHelper
{
    public static function getContinueUrl($id_cart, $id_module, $secure_key, $id_order = null, $order_reference = null)
    {
        if (Configuration::get('PAYNOW_USE_CLASSIC_RETURN_URL')) {
            $params =                 [
                'id_cart'   => $id_cart,
                'id_module' => $id_module,
                'key'       => $secure_key
            ];

            if ($id_order) {
                $params['id_order'] = $id_order;
            }

            if ($order_reference) {
                $params['order_reference'] = $id_order;
            }

            return Context::getContext()->link->getPageLink(
                'order-confirmation'
            );
        }

        return LinkHelper::getReturnUrl($id_cart, Tools::encrypt($order_reference), $order_reference);
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

    public static function getReturnUrl($id_cart, $token, $order_reference = null)
    {
        $params = [
            'token' => $token
        ];

        if ($order_reference) {
            $params['order_reference'] = $order_reference;
        }

        if ($id_cart) {
            $params['id_cart'] = $id_cart;
        }

        return Context::getContext()->link->getModuleLink(
            'paynow',
            'return',
            $params
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

    public static function getBlikConfirmUrl($url_params): string
    {
        return Context::getContext()->link->getModuleLink('paynow', 'confirmBlik', $url_params);
    }
}
