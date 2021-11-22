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
require_once(dirname(__FILE__) . '/../../classes/OrderStateProcessor.php');
require_once(dirname(__FILE__) . '/../../models/PaynowPaymentData.php');

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

        $this->payment = (array)PaynowPaymentData::findLastByOrderReference($order_reference);

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
            'details_url' => LinkHelper::getOrderUrl($this->order),
            'order_status' => $currentState['name'],
            'order_reference' => $this->order->reference,
            'show_details_button' => $token == Tools::encrypt($order_reference),
            'show_retry_button' => $this->module->canOrderPaymentBeRetried($this->order),
            'HOOK_ORDER_CONFIRMATION' => $this->displayOrderConfirmation(),
            'retry_url' => LinkHelper::getPaymentUrl([
                'id_order'        => $this->order->id,
                'order_reference' => $this->order->reference
            ])
        ]);

        $this->renderTemplate('return.tpl');
    }

    private function redirectToReturnPageWithoutPaymentIdAndStatusQuery()
    {
        Tools::redirectLink(LinkHelper::getContinueUrl($this->order->id_cart, $this->order->id, $this->module->id, $this->order->secure_key, $this->order->reference));
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
