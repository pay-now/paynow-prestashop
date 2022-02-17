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
            $external_id = Tools::getValue('external_id');
            PaynowLogger::info(
                'Checking order\'s payment status {externalId={}}',
                [
                    $external_id
                ]
            );
            $payment                = PaynowPaymentData::findLastByExternalId($external_id);
            $payment_status = $payment->status;
            if (Status::STATUS_CONFIRMED !== $payment->status) {
                $payment_status_from_api = $this->getPaymentStatus($payment->id_payment);
                $cart                    = new Cart($payment->id_cart);
                if ($this->canProcessCreateOrder((int)$payment->id_order, $payment_status_from_api,
                    (int)$payment->locked, $cart->orderExists())) {
                    $this->order = $this->createOrder($cart, $external_id, $payment->id_payment);
                    if ($this->order) {
                        $this->updateOrderState(
                            $this->order->id,
                            $payment->id_payment,
                            $this->order->id_cart,
                            $this->order->reference,
                            $payment->external_id,
                            $payment_status,
                            $payment_status_from_api
                        );
                    }
                } else {
                    $this->updateOrderState(
                        $payment->id_order,
                        $payment->id_payment,
                        $payment->id_cart,
                        $payment->order_reference,
                        $payment->external_id,
                        $payment_status,
                        $payment_status_from_api
                    );
                    $this->order = new Order($payment->id_order);
                }
                $payment_status = $payment_status_from_api;
            }

            $response = [
                'order_status'   => $this->getOrderCurrentState($this->order),
                'payment_status' => $payment_status
            ];

            if (Status::STATUS_PENDING !== $payment_status) {
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
