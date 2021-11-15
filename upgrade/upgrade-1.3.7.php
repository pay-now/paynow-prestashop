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

function upgrade_module_1_3_7()
{
    try {
        $sql = "
                DELETE t1, t2 FROM `" . _DB_PREFIX_ . "order_state` t1 INNER JOIN `" . _DB_PREFIX_ . "order_state_lang` t2
                WHERE t1.id_order_state <> (SELECT * FROM(SELECT max(id_order_state) FROM `" . _DB_PREFIX_ . "order_state` WHERE module_name = 'paynow')tblTmp)
                AND t1.module_name = 'paynow'
                AND t1.id_order_state = t2.id_order_state;";

        if (Db::getInstance()->execute($sql)) {
            return (int)Db::getInstance()->Insert_ID();
        }
    } catch (PrestaShopDatabaseException $exception) {
        PaynowLogger::error($exception->getMessage());
    }
    return true;
}
