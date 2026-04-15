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

class PaynowCompatibilityHelper
{
	public static function encrypt($to_encrypt)
	{
		return md5(_COOKIE_KEY_.$to_encrypt);
	}
	
	public static function redirect($url)
	{
		if (method_exists('Tools', 'redirect')) {
			return Tools::redirect($url);
		} else {
			return Tools::redirectLink($url);
		}
	}
}
