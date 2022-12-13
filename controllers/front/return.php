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

/**
 * @property PaynowPaymentData $payment
 */
class PaynowReturnModuleFrontController extends PaynowFrontController
{
    public function initContent()
    {
        parent::initContent();

        $order_reference = Tools::getValue('order_reference');
        $id_cart = Tools::getValue('id_cart');
        $external_id = Tools::getValue('external_id');

        if ((! $order_reference || ! $id_cart) && !$this->isTokenValid()) {
            $this->redirectToOrderHistory();
        }

        if ($order_reference) {
            $this->payment = PaynowPaymentData::findLastByOrderReference($order_reference);
        }

        if ($id_cart) {
            $this->payment = PaynowPaymentData::findLastByCartId($id_cart);
        }

        if ($external_id) {
            $this->payment = PaynowPaymentData::findLastByExternalId($external_id);
        }

        if (!$this->payment) {
            $this->redirectToOrderHistory();
        }


        $this->order = new Order($this->payment->id_order);
        if (!Validate::isLoadedObject($this->order) && PaynowConfigurationHelper::CREATE_ORDER_BEFORE_PAYMENT === (int)Configuration::get('PAYNOW_CREATE_ORDER_STATE')) {
            $this->redirectToOrderHistory();
        }

        if (Tools::getValue('paymentId')) {
            $payment_status_from_api = $this->getPaymentStatus($this->payment->id_payment);
            $statusToProcess = [
                'status' => $payment_status_from_api,
                'externalId' => $this->payment->external_id,
                'paymentId' => $this->payment->id_payment
            ];
            try {
                PaynowLogger::debug('Return: status processing started', $statusToProcess);
                (new PaynowOrderStateProcessor($this->module))->processNotification($statusToProcess);
                PaynowLogger::debug('Return: status processing ended', $statusToProcess);
                $this->payment = PaynowPaymentData::findByPaymentId($this->payment->id_payment);
            } catch (Exception $e) {
                $statusToProcess['exception'] = $e->getMessage();
                PaynowLogger::debug('Return: status processing failed', $statusToProcess);
            }

            Tools::redirectLink(PaynowLinkHelper::getContinueUrl(
                $this->order->id_cart,
                $this->module->id,
                $this->order->secure_key,
                $this->payment->external_id
            ));
        }

        $currentState = $this->order->getCurrentStateFull($this->context->language->id);
        $this->context->smarty->assign([
            'logo' => $this->module->getLogo(),
            'details_url' => PaynowLinkHelper::getOrderUrl($this->order),
            'payment_status' => $this->payment->status,
            'order_status' => $currentState['name'],
            'order_reference' => $this->order->reference,
            'show_details_button' => $this->isTokenValid(),
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
