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

function upgrade_module_1_3_2($module)
{
    if (!$module->registerHook('displayAdminAfterHeader') ||
        !Configuration::updateValue('PAYNOW_SEPARATE_PAYMENT_METHODS', 0)) {
        return false;
    }

    return true;
}
