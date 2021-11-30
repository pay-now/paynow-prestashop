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

function upgrade_module_1_5_0($module)
{
    return $module->registerHook('displayAdminOrder') &&
           Db::getInstance()->execute(
               "ALTER TABLE `" . _DB_PREFIX_ . "paynow_payments` 
               ADD INDEX `index_order_cart_payment_reference` (`id_order`, `id_cart`, `id_payment`, `order_reference`)");
}
