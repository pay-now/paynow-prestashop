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

function upgrade_module_1_5_0()
{
        $sql = "ALTER TABLE " . _DB_PREFIX_ . "paynow_payments CHANGE `order_reference` `order_reference` VARCHAR(9)";

        return Db::getInstance()->execute($sql);
}
