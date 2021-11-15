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

function upgrade_module_1_4_0()
{
        $sql = "
                DELETE t1 FROM " . _DB_PREFIX_ . "paynow_payments t1
                JOIN " . _DB_PREFIX_ . "paynow_payments t2
                ON t2.id_payment = t1.id_payment
                AND t2.modified_at > t1.modified_at ";

        return Db::getInstance()->execute($sql) &&
            Configuration::updateValue('PAYNOW_ORDER_ABANDONED_STATE', Configuration::get('PAYNOW_ORDER_INITIAL_STATE')) &&
            Configuration::updateValue('PAYNOW_ORDER_EXPIRED_STATE', Configuration::get('PAYNOW_ORDER_INITIAL_STATE')) &&
            Configuration::updateValue('PAYNOW_ORDER_REJECTED_STATE', Configuration::get('PAYNOW_ORDER_INITIAL_STATE'));
}
