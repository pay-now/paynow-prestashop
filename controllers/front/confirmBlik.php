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
require_once(dirname(__FILE__) . '/../../classes/OrderStateProcessor.php');
include_once(dirname(__FILE__) . '/../../models/PaynowPaymentData.php');

class PaynowConfirmBlikModuleFrontController extends PaynowFrontController
{
    public function initContent()
    {
        parent::initContent();

        $order_reference = Tools::getValue('order_reference');
        //TODO: validate token
        $token = Tools::getValue('token');

        if (!$order_reference) {
            $this->redirectToOrderHistory();
        }

        $this->payment = PaynowPaymentData::findLastByOrderReference($order_reference);
        if (!$this->payment) {
            $this->redirectToOrderHistory();
        }

        $this->order = new Order($this->payment->id_order);

        if (!Validate::isLoadedObject($this->order)) {
            $this->redirectToOrderHistory();
        }

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

        $current_state = $this->order->getCurrentStateFull($this->context->language->id);

        if (version_compare(_PS_VERSION_, '1.7', 'gt')) {
            $this->registerJavascript(
                'paynow-confirm-blik',
                'modules/'.$this->module->name.'/views/js/confirm-blik.js',
                array(
                    'position' => 'bottom',
                    'priority' => 100
                )
            );
        } else {
            $this->addJS('modules/'.$this->module->name.'/views/js/confirm-blik.js');
        }

        $this->context->smarty->assign([
            'module_dir' => $this->module->getPathUri(),
            'order_status' => $current_state['name'],
            'order_reference' => $this->order->reference
        ]);

        $this->renderTemplate('confirm_blik.tpl');
    }
}