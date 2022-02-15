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
        parent::initContent();

        $order_reference = Tools::getValue('order_reference');
        $id_cart = Tools::getValue('id_cart');
        $external_id = Tools::getValue('external_id');
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

        if ($external_id) {
            $this->payment = (array)PaynowPaymentData::findLastByExternalId($external_id);
        }

        if (!$this->payment) {
            $this->redirectToOrderHistory();
        }

        $this->order = new Order($this->payment['id_order']);
        if (!Validate::isLoadedObject($this->order) && PaynowConfigurationHelper::CREATE_ORDER_BEFORE_PAYMENT === (int)Configuration::get('PAYNOW_CREATE_ORDER_STATE')) {
            $this->redirectToOrderHistory();
        }

        if (Tools::getValue('paymentId') && Tools::getValue('paymentStatus')) {
            $payment_status_from_api = $this->getPaymentStatus($this->payment['id_payment']);
            if (PaynowConfigurationHelper::CREATE_ORDER_AFTER_PAYMENT === (int)Configuration::get('PAYNOW_CREATE_ORDER_STATE') &&
                Status::STATUS_CONFIRMED === $payment_status_from_api &&
                0 === (int)$this->payment['id_order']) {
                $cart        = new Cart($this->payment['id_cart']);
                $this->order = (new PaynowOrderCreateProcessor($this->module))->process($cart, $this->payment['external_id']);
                $this->updateOrderState(
                    $this->order->id,
                    $this->payment['id_payment'],
                    $this->order->id_cart,
                    $this->order->reference,
                    $this->payment['external_id'],
                    $this->payment['status'],
                    $payment_status_from_api
                );
                PaynowPaymentData::updateOrderIdAndOrderReferenceByPaymentId(
                    $this->order->id,
                    $this->order->reference,
                    $this->payment['id_payment']
                );
            } else {
                $this->updateOrderState(
                    $this->payment['id_order'],
                    $this->payment['id_payment'],
                    $this->payment['id_cart'],
                    $this->payment['order_reference'],
                    $this->payment['external_id'],
                    $this->payment['status'],
                    $payment_status_from_api
                );
            }

            Tools::redirectLink(PaynowLinkHelper::getContinueUrl(
                $this->order->id_cart,
                $this->module->id,
                $this->order->secure_key,
                $this->payment['external_id']
            ));
        }

        $currentState = $this->order->getCurrentStateFull($this->context->language->id);
        $this->context->smarty->assign([
            'logo' => $this->module->getLogo(),
            'details_url' => PaynowLinkHelper::getOrderUrl($this->order),
            'order_status' => $currentState['name'],
            'order_reference' => $this->order->reference,
            'show_details_button' => $token == Tools::encrypt($order_reference),
            'show_retry_button' => $this->module->canOrderPaymentBeRetried($this->order),
            'HOOK_ORDER_CONFIRMATION' => $this->displayOrderConfirmation(),
            'retry_url' => PaynowLinkHelper::getPaymentUrl([
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

    private function hookParams(): array
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
