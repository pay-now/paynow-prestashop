<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License (MIT)
 * that is bundled with this package in the file LICENSE.md.
 *
 * @author mElements S.A.
 * @copyright mElements S.A.
 * @license MIT License
 */

require_once(dirname(__FILE__) . '/../../classes/PaynowFrontController.php');

class PaynowChargeBlikModuleFrontController extends PaynowFrontController
{
    public function initContent()
    {
        parent::initContent();
        $this->executeBlikPayment();
    }

    private function executeBlikPayment()
    {
        $response = [
            'success'    => false,
            'payment_id' => null,
            'order_id'   => null
        ];

        $cart = new Cart(Context::getContext()->cart->id);

        if ($cart && $cart->id) {

        }

        try {

        } catch (\Paynow\Exception\PaynowException $exception) {
            PaynowLogger::error($exception->getMessage());
        }

        $this->ajaxRender(json_encode($response));
        exit;
    }
}