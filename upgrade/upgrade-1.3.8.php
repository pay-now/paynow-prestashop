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

function upgrade_module_1_3_8()
{
    try {
        $sql = "
                DELETE t1 FROM presta_paynow.ps_paynow_payments t1
                JOIN presta_paynow.ps_paynow_payments t2
                ON t2.id_payment = t1.id_payment
                AND t2.modified_at > t1.modified_at ";

        if (Db::getInstance()->execute($sql)) {
            return (int)Db::getInstance()->Insert_ID();
        }
    } catch (PrestaShopDatabaseException $exception) {
        PaynowLogger::error($exception->getMessage());
    }
    return true;
}