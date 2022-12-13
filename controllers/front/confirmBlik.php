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
        $payment_status_from_api = $this->getPaymentStatus($this->payment->id_payment);
        $statusToProcess = [
            'status' => $payment_status_from_api,
            'externalId' => $this->payment->external_id,
            'paymentId' => $this->payment->id_payment
        ];

        try {
            PaynowLogger::debug('confirmBlik: status processing started', $statusToProcess);
            (new PaynowOrderStateProcessor($this->module))->processNotification($statusToProcess);
            PaynowLogger::debug('confirmBlik: status processing ended', $statusToProcess);
        } catch (Exception $e) {
            $statusToProcess['exception'] = $e->getMessage();
            PaynowLogger::debug('confirmBlik: status processing failed', $statusToProcess);
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
            'order_status' => $this->getOrderCurrentState($this->order),
            'order_reference' => $this->order->reference
        ]);

        $this->renderTemplate('confirm_blik.tpl');
    }
}
