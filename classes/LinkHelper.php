<?php

class LinkHelper
{
    public static function getContinueUrl($order, $id_module, $secure_key, $url_params = null)
    {
        $context = ContextCore::getContext();
        if (Configuration::get('PAYNOW_USE_CLASSIC_RETURN_URL')) {
            return $context->link->getPageLink(
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

        return $context->link->getModuleLink(
            'paynow',
            'return',
            !empty($url_params) ? $url_params : [
                'order_reference' => $order->reference,
                'token'           => Tools::encrypt($order->reference)
            ]
        );
    }
}