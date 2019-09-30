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

class PaynowReturnModuleFrontController extends PaynowFrontController
{
    private $order;

    public function initContent()
    {
        parent::initContent();

        $id_payment = Tools::getValue('paymentId');
        if (!$id_payment) {
            $this->redirectToOrderHistory();
        }

        $payment = $this->module->getLastPaymentStatus($id_payment);
        if (!$payment) {
            $this->redirectToOrderHistory();
        }

        $this->order = new Order($payment['id_order']);
        if (!Validate::isLoadedObject($this->order)) {
            $this->redirectToOrderHistory();
        }

        $currentState = $this->order->getCurrentStateFull($this->context->language->id);
        $this->context->smarty->assign([
            'redirect_url' => $this->module->getOrderUrl($this->order),
            'order_status' => $currentState['name'],
            'reference' => $this->order->reference
        ]);

        $this->renderTemplate('return.tpl');
    }

    private function redirectToOrderHistory()
    {
        Tools::redirect(
            'index.php?controller=history',
            __PS_BASE_URI__,
            null,
            'HTTP/1.1 301 Moved Permanently'
        );
    }
}
