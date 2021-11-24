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

require_once(dirname(__FILE__) . '/../../classes/PaynowFrontController.php');
require_once(dirname(__FILE__) . '/../../classes/OrderStateProcessor.php');
include_once(dirname(__FILE__) . '/../../models/PaynowPaymentData.php');

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
            $this->payment  = PaynowPaymentData::findLastByOrderReference(Tools::getValue('order_reference'));
            $payment_status = $this->payment->status;
            if ($this->payment->status != 'CONFIRMED') {
                $payment_status = $this->getPaymentStatus($this->payment->id_payment);
                $this->updateOrderState(
                    $this->payment->id_order,
                    $this->payment->id_payment,
                    $this->payment->id_cart,
                    $this->payment->order_reference,
                    $this->payment->external_id,
                    $this->payment->status,
                    $payment_status
                );
            }
            $this->order   = new Order($this->payment->id_order);
            $current_state = $this->order->getCurrentStateFull($this->context->language->id);

            header('Content-Type: application/json');
            echo json_encode([
                'order_status'   => $current_state['name'],
                'payment_status' => $payment_status
            ]);
        }
    }
}