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

class PaynowConfigurationHelper
{
    public const CREATE_ORDER_BEFORE_PAYMENT = 1;
    public const CREATE_ORDER_AFTER_PAYMENT = 2;

    public static function update()
    {
        Configuration::updateValue(
            'PAYNOW_DEBUG_LOGS_ENABLED',
            Tools::getValue('PAYNOW_DEBUG_LOGS_ENABLED')
        );
        Configuration::updateValue(
            'PAYNOW_USE_CLASSIC_RETURN_URL',
            Tools::getValue('PAYNOW_USE_CLASSIC_RETURN_URL')
        );
        Configuration::updateValue(
            'PAYNOW_REFUNDS_ENABLED',
            Tools::getValue('PAYNOW_REFUNDS_ENABLED')
        );
        Configuration::updateValue(
            'PAYNOW_REFUNDS_AFTER_STATUS_CHANGE_ENABLED',
            Tools::getValue('PAYNOW_REFUNDS_AFTER_STATUS_CHANGE_ENABLED')
        );
        Configuration::updateValue(
            'PAYNOW_REFUNDS_ON_STATUS',
            Tools::getValue('PAYNOW_REFUNDS_ON_STATUS')
        );
        Configuration::updateValue(
            'PAYNOW_REFUNDS_WITH_SHIPPING_COSTS',
            Tools::getValue('PAYNOW_REFUNDS_WITH_SHIPPING_COSTS')
        );
        Configuration::updateValue(
            'PAYNOW_SEPARATE_PAYMENT_METHODS',
            Tools::getValue('PAYNOW_SEPARATE_PAYMENT_METHODS')
        );
        Configuration::updateValue(
            'PAYNOW_PROD_API_KEY',
            Tools::getValue('PAYNOW_PROD_API_KEY')
        );
        Configuration::updateValue(
            'PAYNOW_PROD_API_SIGNATURE_KEY',
            Tools::getValue('PAYNOW_PROD_API_SIGNATURE_KEY')
        );
        Configuration::updateValue(
            'PAYNOW_SANDBOX_ENABLED',
            Tools::getValue('PAYNOW_SANDBOX_ENABLED')
        );
        Configuration::updateValue(
            'PAYNOW_SANDBOX_API_KEY',
            Tools::getValue('PAYNOW_SANDBOX_API_KEY')
        );
        Configuration::updateValue(
            'PAYNOW_SANDBOX_API_SIGNATURE_KEY',
            Tools::getValue('PAYNOW_SANDBOX_API_SIGNATURE_KEY')
        );
        Configuration::updateValue(
            'PAYNOW_ORDER_INITIAL_STATE',
            Tools::getValue('PAYNOW_ORDER_INITIAL_STATE')
        );
        Configuration::updateValue(
            'PAYNOW_ORDER_CONFIRMED_STATE',
            Tools::getValue('PAYNOW_ORDER_CONFIRMED_STATE')
        );
        Configuration::updateValue(
            'PAYNOW_ORDER_REJECTED_STATE',
            Tools::getValue('PAYNOW_ORDER_REJECTED_STATE')
        );
        Configuration::updateValue(
            'PAYNOW_ORDER_ERROR_STATE',
            Tools::getValue('PAYNOW_ORDER_ERROR_STATE')
        );
        Configuration::updateValue(
            'PAYNOW_SEND_ORDER_ITEMS',
            Tools::getValue('PAYNOW_SEND_ORDER_ITEMS')
        );
        Configuration::updateValue(
            'PAYNOW_PAYMENT_VALIDITY_TIME_ENABLED',
            Tools::getValue('PAYNOW_PAYMENT_VALIDITY_TIME_ENABLED')
        );
        Configuration::updateValue(
            'PAYNOW_PAYMENT_VALIDITY_TIME',
            Tools::getValue('PAYNOW_PAYMENT_VALIDITY_TIME')
        );
        Configuration::updateValue(
            'PAYNOW_ORDER_ABANDONED_STATE',
            Tools::getValue('PAYNOW_ORDER_ABANDONED_STATE')
        );
        Configuration::updateValue(
            'PAYNOW_ORDER_EXPIRED_STATE',
            Tools::getValue('PAYNOW_ORDER_EXPIRED_STATE')
        );
        Configuration::updateValue(
            'PAYNOW_CREATE_ORDER_STATE',
            Tools::getValue('PAYNOW_CREATE_ORDER_STATE')
        );
        Configuration::updateValue(
            'PAYNOW_RETRY_PAYMENT_BUTTON_ENABLED',
            Tools::getValue('PAYNOW_RETRY_PAYMENT_BUTTON_ENABLED')
        );
        Configuration::updateValue(
            'PAYNOW_RETRY_BUTTON_ORDER_STATE',
            join(',', Tools::getValue('PAYNOW_RETRY_BUTTON_ORDER_STATE'))
        );
        Configuration::updateValue(
            'PAYNOW_BLIK_AUTOFOCUS_ENABLED',
            Tools::getValue('PAYNOW_BLIK_AUTOFOCUS_ENABLED')
        );
    }
}
