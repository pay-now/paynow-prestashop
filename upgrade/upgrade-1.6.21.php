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
function upgrade_module_1_6_21($module)
{
    try {

        $db = Db::getInstance();

        // extra check if previous migrations ware executed correctly
        // => 1.6.3
        if (!$db->executeS('SHOW COLUMNS FROM ' . _DB_PREFIX_ . 'paynow_payments LIKE "total"')) {
            $db->execute(
                "ALTER TABLE `" . _DB_PREFIX_ . "paynow_payments` 
               ADD `total` DECIMAL(20,6) NOT NULL DEFAULT '0.000000' 
               AFTER `status`"
            );
        }

        // => 1.6.11 / 1.6.12
        if (!$db->executeS('SHOW COLUMNS FROM ' . _DB_PREFIX_ . 'paynow_payments LIKE "locked"')) {
            $db->execute(
                "ALTER TABLE `" . _DB_PREFIX_ . "paynow_payments` 
                ADD `locked` TINYINT(1) NOT NULL DEFAULT 0
                AFTER `total`"
            );
        }

        // => 1.6.19
        if (Configuration::get('PAYNOW_RETRY_BUTTON_ORDER_STATE') === false) {
            Configuration::updateValue('PAYNOW_RETRY_BUTTON_ORDER_STATE', join(',', [8,20,12]));
        }


        // 1.6.21
        Db::getInstance()->execute(
            "ALTER TABLE `" . _DB_PREFIX_ . "paynow_payments` 
           ADD `counter` tinyint(1) NOT NULL DEFAULT '0' AFTER `locked`;"
        );

        Db::getInstance()->execute(
            "ALTER TABLE `" . _DB_PREFIX_ . "paynow_payments` 
           ADD `active` tinyint(1) NOT NULL DEFAULT '0' AFTER `counter`;"
        );

        Db::getInstance()->execute(
            "ALTER TABLE `" . _DB_PREFIX_ . "paynow_payments` 
           ADD `sent_at` datetime NULL AFTER `active`;"
        );

        Configuration::updateValue('PAYNOW_BLIK_AUTOFOCUS_ENABLED', 1);

    } catch (PrestaShopDatabaseException $exception) {
        PaynowLogger::error('Fatal error on upgrade: ' . $exception->getMessage() . ' ' . $exception->getTraceAsString());
    }

    return true;
}
