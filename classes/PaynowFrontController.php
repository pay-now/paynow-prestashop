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

use Paynow\Exception\PaynowException;
use Paynow\Model\Payment\Status;

class PaynowFrontController extends ModuleFrontControllerCore
{
    protected $order;

    protected $payment;

    /** @var Paynow */
    public $module;

    public function init()
    {
        parent::init();
        PaynowHelper::$module = $this->module;
    }

    public function initContent()
    {
        $this->display_column_left = false;
        parent::initContent();
    }

    public function generateToken(): string
    {
        return Tools::encrypt($this->context->customer->secure_key);
    }

    public function isTokenValid(): bool
    {
        return $this->generateToken() === Tools::getValue('token');
    }

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
        PaynowLogger::info('Retrieving payment status {paymentId={}}', [$paymentId]);
        try {
            $status = (new Paynow\Service\Payment($this->module->getPaynowClient()))->status($paymentId)->getStatus();
            PaynowLogger::info('Retrieved payment status {paymentId={}, status={}}', [$paymentId, $status]);

            return $status;
        } catch (PaynowException $exception) {
            PaynowLogger::error($exception->getMessage() . ' {paymentId={}}', [$paymentId]);
        }

        return false;
    }

    protected function ajaxRender($value = null, $controller = null, $method = null)
    {
        header('Content-Type: application/json');
        if (version_compare(_PS_VERSION_, '1.7', 'gt')) {
            parent::ajaxRender($value, $controller, $method);
        } else {
            echo $value;
        }
    }

    protected function getOrderCurrentState($order)
    {
        if ($order) {
            $current_state      = $order->getCurrentStateFull($this->context->language->id);
            return is_array($current_state) ? $current_state['name'] : $this->getDefaultOrderStatus();
        }

        return $this->getDefaultOrderStatus();
    }

    private function getDefaultOrderStatus()
    {
        $order_state = new OrderState(Configuration::get('PAYNOW_ORDER_INITIAL_STATE'));
        return $order_state->name[$this->context->language->id];
    }
}
