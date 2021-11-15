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
            $this->payment = $this->module->getLastPaymentDataByOrderReference(Tools::getValue('order_reference'));
            $payment_id    = $this->payment['id_payment'];
            $payment_status = $this->payment['status'];
            if ($payment_status != 'CONFIRMED') {
                $payment_status = $this->getPaymentStatus($payment_id);
                $this->updateOrderState($payment_id, $payment_status);
            }
            $this->order   = new Order($this->payment['id_order']);
            $current_state = $this->order->getCurrentStateFull($this->context->language->id);

            header('Content-Type: application/json');
            echo json_encode([
                'order_status'   => $current_state['name'],
                'payment_status' => $payment_status
            ]);
        }
    }
}