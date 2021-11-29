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

use Paynow\Model\Payment\Status;

class PaynowReturnModuleFrontController extends PaynowFrontController
{
    public function initContent()
    {
        $this->display_column_left = false;
        parent::initContent();

        $order_reference = Tools::getValue('order_reference');
        $id_cart = Tools::getValue('id_cart');
        $token = Tools::getValue('token');

        if ((! $order_reference || ! $id_cart) && !$this->isTokenValid()) {
            $this->redirectToOrderHistory();
        }

        if ($order_reference) {
            $this->payment = (array)PaynowPaymentData::findLastByOrderReference($order_reference);
        }

        if ($id_cart) {
            $this->payment = (array)PaynowPaymentData::findLastByCartId($id_cart);
        }

        if (!$this->payment) {
            $this->redirectToOrderHistory();
        }

        $this->order = new Order($this->payment['id_order']);

        if (!Validate::isLoadedObject($this->order)) {
            $this->redirectToOrderHistory();
        }

        if (Tools::getValue('paymentId') && Tools::getValue('paymentStatus')
            && Status::STATUS_CONFIRMED !== $this->payment['status']) {
            $payment_id = Tools::getValue('paymentId');
            $payment_status = $this->getPaymentStatus($payment_id);
            $this->updateOrderState(
                $this->payment['id_order'],
                $this->payment['id_payment'],
                $this->payment['id_cart'],
                $this->payment['order_reference'],
                $this->payment['external_id'],
                $this->payment['status'],
                $payment_status
            );
            Tools::redirectLink(LinkHelper::getContinueUrl(
                $this->order->id_cart,
                $this->module->id,
                $this->order->secure_key,
                $this->order->id,
                $this->order->reference
            ));
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
