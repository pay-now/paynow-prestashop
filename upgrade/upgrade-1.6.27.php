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

/**
 * @throws PrestaShopDatabaseException
 */
function upgrade_module_1_6_27($module)
{
    try {

        Db::getInstance()->execute(
            "ALTER TABLE `" . _DB_PREFIX_ . "paynow_payments` 
            CHANGE `order_reference` `order_reference` varchar(50) NOT NULL AFTER `id_payment`"
        );

        if (!Configuration::get('PAYNOW_HIDE_PAYMENT_TYPES')) {
            Configuration::updateValue('PAYNOW_HIDE_PAYMENT_TYPES', 'none');
        }

    } catch (PrestaShopDatabaseException $exception) {
        PaynowLogger::error('Fatal error on upgrade: ' . $exception->getMessage() . ' ' . $exception->getTraceAsString());
    }

    return true;
}
