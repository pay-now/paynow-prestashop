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

class PaynowConfirmBlikModuleFrontController extends PaynowFrontController
{
    public function initContent()
    {
        parent::initContent();

        $external_id = Tools::getValue('external_id');

        if (!$external_id || !$this->isTokenValid()) {
            $this->redirectToOrderHistory();
        }

        $this->payment = PaynowPaymentData::findLastByExternalId($external_id);
        if (!$this->payment) {
            $this->redirectToOrderHistory();
        }

        $this->order = new Order($this->payment->id_order);

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

        if ($this->order) {
            $current_state = $this->order->getCurrentStateFull($this->context->language->id);
            $current_state_name = $current_state['name'];
        } else {
            $order_state = new OrderState(Configuration::get('PAYNOW_ORDER_INITIAL_STATE'));
            $current_state_name = $order_state->name[$this->context->language->id];
        }

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
            'order_status' => $current_state_name,
            'order_reference' => $this->order->reference
        ]);

        $this->renderTemplate('confirm_blik.tpl');
    }
}
