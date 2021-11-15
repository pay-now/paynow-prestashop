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
require_once(dirname(__FILE__) . '/../../classes/LinkHelper.php');

class PaynowReturnModuleFrontController extends PaynowFrontController
{
    public function initContent()
    {
        $this->display_column_left = false;
        parent::initContent();

        $order_reference = Tools::getValue('order_reference');
        $token = Tools::getValue('token');

        if (!$order_reference || !$token) {
            $this->redirectToOrderHistory();
        }

        $this->payment = $this->module->getLastPaymentDataByOrderReference($order_reference);

        if (!$this->payment) {
            $this->redirectToOrderHistory();
        }

        $this->order = new Order($this->payment['id_order']);

        if (!Validate::isLoadedObject($this->order)) {
            $this->redirectToOrderHistory();
        }

        if (Tools::getValue('paymentId') && Tools::getValue('paymentStatus')) {
            $payment_id = Tools::getValue('paymentId');
            $payment_status = $this->getPaymentStatus($payment_id);
            $this->updateOrderState($payment_id, $payment_status);
            $this->redirectToReturnPageWithoutPaymentIdAndStatusQuery();
        }

        $currentState = $this->order->getCurrentStateFull($this->context->language->id);
        $this->context->smarty->assign([
            'logo' => $this->module->getLogo(),
            'details_url' => $this->module->getOrderUrl($this->order),
            'order_status' => $currentState['name'],
            'order_reference' => $this->order->reference,
            'show_details_button' => $token == Tools::encrypt($order_reference),
            'show_retry_button' => $this->module->canOrderPaymentBeRetried($this->order->id),
            'HOOK_ORDER_CONFIRMATION' => $this->displayOrderConfirmation(),
            'retry_url' => $this->context->link->getModuleLink('paynow', 'payment', [
                'id_order' => $this->order->id,
                'order_reference' => $order_reference
            ])
        ]);

        $this->renderTemplate('return.tpl');
    }

    private function redirectToReturnPageWithoutPaymentIdAndStatusQuery()
    {
        Tools::redirectLink(LinkHelper::getContinueUrl($this->order, $this->module->id, $this->order->secure_key));
    }

    private function displayOrderConfirmation()
    {
        return Hook::exec('displayOrderConfirmation', $this->hookParams());
    }

    private function hookParams()
    {
        $currency = new Currency((int)$this->order->id_currency);

        return [
            'objOrder' => $this->order,
            'order' => $this->order,
            'currencyObj' => $currency,
            'currency' => $currency->sign,
            'total_to_pay' => $this->order->getOrdersTotalPaid()
        ];
    }
}
