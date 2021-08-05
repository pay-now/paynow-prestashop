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

function upgrade_module_1_3_6()
{
    Configuration::updateValue('PAYNOW_PAYMENT_VALIDITY_TIME_ENABLED', 0);
    Configuration::updateValue('PAYNOW_PAYMENT_VALIDITY_TIME', 86400);
    return true;
}
