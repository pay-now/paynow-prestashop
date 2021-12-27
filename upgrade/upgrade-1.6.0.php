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

function upgrade_module_1_6_0($module)
{
    return Configuration::updateValue('PAYNOW_ORDER_ABANDONED_STATE', 1) &&
           Db::getInstance()->execute(
               "ALTER TABLE `" . _DB_PREFIX_ . "paynow_payments` 
               ADD `total` DECIMAL(20,6) NOT NULL DEFAULT '0.000000' 
               AFTER `status`"
           );
}
