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
            $payment = PaynowPaymentData::findLastByExternalId($external_id);
            $payment_status = $payment->status;
            if (Status::STATUS_CONFIRMED !== $payment->status) {
                $payment_status_from_api = $this->getPaymentStatus($payment->id_payment);
                $statusToProcess = [
                    'status' => $payment_status_from_api,
                    'externalId' => $this->payment->external_id,
                    'paymentId' => $this->payment->id_payment
                ];
                try {
                    PaynowLogger::debug('Status: status processing started', $statusToProcess);
                    (new PaynowOrderStateProcessor($this->module))
                        ->processNotification($statusToProcess);
                    PaynowLogger::debug('Status: status processing ended', $statusToProcess);
                    $payment_status = $payment_status_from_api;
                } catch (Exception $e) {
                    $statusToProcess['exception'] = $e->getMessage();
                    PaynowLogger::debug('Status: status processing failed', $statusToProcess);
                }

                $this->order = new Order($payment->id_order);
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
