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

function upgrade_module_1_3_0($module)
{
    if (!$module->registerHook('actionOrderStatusPostUpdate') ||
        !$module->registerHook('actionOrderSlipAdd') ||
        !$module->registerHook('displayAdminOrderTop') ||
        !Configuration::updateValue('PAYNOW_REFUNDS_ENABLED', 1) ||
        !Configuration::updateValue('PAYNOW_REFUNDS_AFTER_STATUS_CHANGE_ENABLED', 0)) {
        return false;
    }

    return true;
}
