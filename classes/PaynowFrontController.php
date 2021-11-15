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

if (!defined('_PS_VERSION_')) {
    exit;
}

use Paynow\Exception\PaynowException;

class PaynowFrontController extends ModuleFrontController
{
    protected $order;

    protected $payment;

    protected function renderTemplate($template_name)
    {
        if (version_compare(_PS_VERSION_, '1.7', 'gt')) {
            $template_name = 'module:paynow/views/templates/front/1.7/' . $template_name;
        }

        $this->setTemplate($template_name);
    }

    protected function redirectToOrderHistory()
    {
        Tools::redirect(
            'index.php?controller=history',
            __PS_BASE_URI__,
            null,
            'HTTP/1.1 301 Moved Permanently'
        );
    }

    protected function getPaymentStatus($paymentId)
    {
        try {
            $payment_client = new Paynow\Service\Payment($this->module->getPaynowClient());
            return $payment_client->status($paymentId)->getStatus();
        } catch (PaynowException $exception) {
            PaynowLogger::error($exception->getMessage());
        }

        return false;
    }

    protected function updateOrderState($paymentId, $payment_status)
    {
        try {
            $orderStateProcessor = new OrderStateProcessor();
            $orderStateProcessor->updateState($this->payment, $payment_status, $paymentId);
        } catch (Exception $e) {
            PaynowLogger::error($e->getMessage());
        }
    }
}
