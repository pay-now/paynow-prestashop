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

class PaynowCustomerTokenModuleFrontController extends PaynowFrontController
{
    public function initContent()
    {
        parent::initContent();

        $this->ajaxRender(json_encode([
            'token' => $this->generateToken(),
        ]));
        exit;
    }
}
