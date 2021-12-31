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

function upgrade_module_1_6_2($module)
{
    try {
        Db::getInstance()->execute(
            "ALTER TABLE `" . _DB_PREFIX_ . "paynow_payments` DROP INDEX index_order_cart_payment_reference;"
        );
        Db::getInstance()-> execute(
            "ALTER TABLE `" . _DB_PREFIX_ . "paynow_payments` DROP INDEX index_order_cart_payment_reference_external_id;"
        );
        Db::getInstance()-> execute(
            "ALTER TABLE `" . _DB_PREFIX_ . "paynow_payments` 
               ADD INDEX `index_order_cart_payment_reference_external_id` (`id_order`, `id_cart`, `id_payment`, `order_reference`, `external_id`)"
        );
    } catch (PrestaShopDatabaseException $exception) {
        PaynowLogger::error($exception->getMessage() . ' ' . $exception->getTraceAsString());
    }

    return Configuration::updateValue('PAYNOW_CREATE_ORDER_STATE', 1) &&
           Configuration::updateValue('PAYNOW_ORDER_ABANDONED_STATE', Configuration::get('PAYNOW_ORDER_INITIAL_STATE'));
}
