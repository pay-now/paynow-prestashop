<?php

use Paynow\Exception\PaynowException;
use Paynow\Model\Payment\Status;

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

class PaynowCompatibilityHelper
{
    public static function encrypt($to_encrypt)
    {
		if (method_exists('Tools', 'hash')) {
			return Tools::hash($to_encrypt);
		} else {
			return Tools::encrypt($to_encrypt);
		}
    }
}
