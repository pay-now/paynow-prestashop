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

    private $payment;

    public function initContent()
    {
        $this->display_column_left = false;
        parent::initContent();

        $order_reference = Tools::getValue('order_reference');
        $token = Tools::getValue('token');

        if (!$order_reference) {
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
            $this->getPaymentStatus();
        }

        $currentState = $this->order->getCurrentStateFull($this->context->language->id);
        $this->context->smarty->assign([
            'logo' => $this->module->getLogo(),
            'details_url' => $this->module->getOrderUrl($this->order),
            'order_status' => $currentState['name'],
            'order_reference' => $this->order->reference,
            'show_details_button' => $token == Tools::encrypt($order_reference),
            'HOOK_ORDER_CONFIRMATION' => $this->displayOrderConfirmation()
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

    private function redirectToReturnPageWithoutPaymentIdAndStatusQuery()
    {
        $url = parse_url($_SERVER['REQUEST_URI']);
        $query = isset($url['query']) ? $url['query'] : '';
        parse_str($query, $queryArray);
        $queryArray['fc'] = 'module';
        $queryArray['module'] = $this->module->name;
        $queryArray['controller'] = 'return';
        unset($queryArray['paymentId']);
        unset($queryArray['paymentStatus']);
        $newUrl =  '?' . http_build_query($queryArray);

        Tools::redirect($newUrl, __PS_BASE_URI__);
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

    private function getPaymentStatus()
    {
        try {
            $payment_client = new Paynow\Service\Payment($this->module->api_client);
            $paymentId = Tools::getValue('paymentId');
            $status = $payment_client->status($paymentId)->getStatus();
            $this->module->updateOrderState($this->payment, $status, $paymentId);
        } catch (Exception $exception) {
            PaynowLogger::error($exception->getMessage());
        }
        $this->redirectToReturnPageWithoutPaymentIdAndStatusQuery();
    }
}
