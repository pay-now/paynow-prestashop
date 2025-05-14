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

/**
 * @throws PrestaShopDatabaseException
 */
function upgrade_module_1_7_11($module)
{
    try {

		if (Db::getInstance()->executeS('SHOW COLUMNS FROM ' . _DB_PREFIX_ . 'paynow_payments LIKE "external_id"') == false) {
			Db::getInstance()->execute('ALTER TABLE ' . _DB_PREFIX_ . 'paynow_payments ADD external_id VARCHAR(50) NOT NULL AFTER `order_reference`');
		}

		if (Db::getInstance()->ExecuteS('SHOW COLUMNS FROM ' . _DB_PREFIX_ . 'paynow_payments LIKE "total"') == false) {
			Db::getInstance()->Execute("ALTER TABLE `" . _DB_PREFIX_ . "paynow_payments` ADD `total` DECIMAL(20,6) NOT NULL DEFAULT '0.000000' AFTER `status`;");
		}

		if (Db::getInstance()->ExecuteS('SHOW COLUMNS FROM ' . _DB_PREFIX_ . 'paynow_payments LIKE "locked"') == false) {
			Db::getInstance()->Execute("ALTER TABLE `" . _DB_PREFIX_ . "paynow_payments` ADD `locked` TINYINT(1) NOT NULL DEFAULT 0 AFTER `total`;");
		}

		if (Db::getInstance()->ExecuteS('SHOW COLUMNS FROM ' . _DB_PREFIX_ . 'paynow_payments LIKE "counter"') == false) {
			Db::getInstance()->Execute("ALTER TABLE `" . _DB_PREFIX_ . "paynow_payments` ADD `counter` TINYINT(1) NOT NULL DEFAULT 0 AFTER `locked`;");
		}

		if (Db::getInstance()->ExecuteS('SHOW COLUMNS FROM ' . _DB_PREFIX_ . 'paynow_payments LIKE "active"') == false) {
			Db::getInstance()->Execute("ALTER TABLE `" . _DB_PREFIX_ . "paynow_payments` ADD `active` TINYINT(1) NOT NULL DEFAULT 0 AFTER `counter`;");
		}

		if (Db::getInstance()->ExecuteS('SHOW COLUMNS FROM ' . _DB_PREFIX_ . 'paynow_payments LIKE "sent_at"') == false) {
			Db::getInstance()->Execute("ALTER TABLE `" . _DB_PREFIX_ . "paynow_payments` ADD `sent_at` datetime NULL AFTER `active`;");
		}

	} catch (PrestaShopDatabaseException $exception) {
        PaynowLogger::error('Fatal error on upgrade: ' . $exception->getMessage() . ' ' . $exception->getTraceAsString());
    }

    return true;
}
