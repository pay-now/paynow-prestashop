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

if (! defined('_PS_VERSION_')) {
    exit;
}

require_once(dirname(__FILE__) . '/../../classes/PaynowFrontController.php');
require_once(dirname(__FILE__) . '/../../classes/OrderStateProcessor.php');
include_once(dirname(__FILE__) . '/../../models/PaynowPaymentData.php');

use Paynow\Model\Payment\Status;

class PaynowStatusModuleFrontController extends PaynowFrontController
{
    public function initContent()
    {
        parent::initContent();
        $this->ajax = true;
    }

    public function displayAjax()
    {
        if (Tools::getValue('order_reference') && Tools::getValue('token') == Tools::encrypt(Tools::getValue('order_reference'))) {
            $payment  = PaynowPaymentData::findLastByOrderReference(Tools::getValue('order_reference'));
            $payment_status = $payment->status;
            if (Status::STATUS_CONFIRMED !== $payment->status) {
                $payment_status = $this->getPaymentStatus($payment->id_payment);
                $this->updateOrderState(
                    $payment->id_order,
                    $payment->id_payment,
                    $payment->id_cart,
                    $payment->order_reference,
                    $payment->external_id,
                    $payment->status,
                    $payment_status
                );
            }
            $this->order   = new Order($payment->id_order);
            $current_state = $this->order->getCurrentStateFull($this->context->language->id);

            $this->ajaxRender(json_encode([
                'order_status'   => $current_state['name'],
                'payment_status' => $payment_status
            ]));
            exit;
        }
    }
}