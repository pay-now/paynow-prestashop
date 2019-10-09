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

function upgrade_module_1_1_2($module)
{
    $query = 'SHOW COLUMNS FROM ' . _DB_PREFIX_ . 'paynow_payments LIKE "external_id"';
    if (Db::getInstance()->executeS($query) == false) {
        Db::getInstance()->execute('
            ALTER TABLE ' . _DB_PREFIX_ . 'paynow_payments 
            ADD external_id VARCHAR(50) NOT NULL AFTER order_reference');
    }

    return Configuration::updateValue('PAYNOW_ORDER_CONFIRMED_STATE', 2) &&
        Configuration::updateValue('PAYNOW_ORDER_REJECTED_STATE', 6) &&
        Configuration::updateValue('PAYNOW_ORDER_ERROR_STATE', 8) &&
        $module->registerHook('displayOrderDetail');
}
