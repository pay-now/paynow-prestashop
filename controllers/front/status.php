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

class PaynowStatusModuleFrontController extends PaynowFrontController
{
    public function initContent()
    {
        parent::initContent();
        $this->ajax = true;
    }

    public function displayAjax()
    {
        if (Tools::getValue('external_id') && $this->isTokenValid()) {
            $payment                = PaynowPaymentData::findLastByExternalId(Tools::getValue('external_id'));
            $payment_status_from_db = $payment->status;
            if (Status::STATUS_CONFIRMED !== $payment->status) {
                $payment_status_from_api = $this->getPaymentStatus($payment->id_payment);
                if (PaynowConfigurationHelper::CREATE_ORDER_AFTER_PAYMENT === (int)Configuration::get('PAYNOW_CREATE_ORDER_STATE') &&
                    Status::STATUS_CONFIRMED === $payment_status_from_api &&
                    (int)$payment->id_order === 0) {
                    $cart        = new Cart($payment->id_cart);
                    $this->order = (new PaynowOrderCreateProcessor())->process($cart, $payment->external_id);
                    $this->updateOrderState(
                        $this->order->id,
                        $payment->id_payment,
                        $this->order->id_cart,
                        $this->order->reference,
                        $payment->external_id,
                        $payment_status_from_db,
                        $payment_status_from_api
                    );
                    PaynowPaymentData::updateOrderIdAndOrderReferenceByPaymentId(
                        $this->order->id,
                        $this->order->reference,
                        $payment->id_payment
                    );
                    $payment_status_from_db = $payment_status_from_api;
                } else {
                    $this->updateOrderState(
                        $payment->id_order,
                        $payment->id_payment,
                        $payment->id_cart,
                        $payment->order_reference,
                        $payment->external_id,
                        $payment_status_from_db,
                        $payment_status_from_api
                    );
                    $this->order = new Order($payment->id_order);
                    $payment_status_from_db = $payment_status_from_api;
                }
            }

            $current_state = $this->order->getCurrentStateFull($this->context->language->id);

            $response = [
                'order_status'   => $current_state['name'],
                'payment_status' => $payment_status_from_db
            ];

            if (Status::STATUS_PENDING !== $payment_status_from_db) {
                $response['redirect_url'] = PaynowLinkHelper::getContinueUrl(
                    $payment->id_cart,
                    $this->module->id,
                    $this->context->customer->secure_key,
                    $payment->external_id
                );
            }

            $this->ajaxRender(json_encode($response));
            exit;
        }
    }
}
