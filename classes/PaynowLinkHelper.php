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

class PaynowLinkHelper
{
    public static function getContinueUrl($id_cart, $id_module, $secure_key, $external_id = null): string
    {
        if (Configuration::get('PAYNOW_USE_CLASSIC_RETURN_URL')) {
            $params =                 [
                'id_cart'   => $id_cart,
                'id_module' => $id_module,
                'key'       => $secure_key
            ];

            return Context::getContext()->link->getPageLink(
                'order-confirmation',
                null,
                Context::getContext()->language->id,
                $params
            );
        }

        return PaynowLinkHelper::getReturnUrl($external_id, Tools::encrypt($secure_key));
    }

    public static function getPaymentUrl($url_params = null): string
    {
        return Context::getContext()->link->getModuleLink(
            'paynow',
            'payment',
            ! empty($url_params) ? $url_params : []
        );
    }

    public static function getNotificationUrl(): string
    {
        return Context::getContext()->link->getModuleLink('paynow', 'notifications');
    }

    public static function getReturnUrl($external_id, $token = null): string
    {
        $params = [
            'token' => $token
        ];

        if ($external_id) {
            $params['external_id'] = $external_id;
        }

        return Context::getContext()->link->getModuleLink(
            'paynow',
            'return',
            $params
        );
    }

    public static function getOrderUrl($order): string
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
