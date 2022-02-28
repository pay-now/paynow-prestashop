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

    protected function updateOrderState(
        $id_order,
        $id_payment,
        $id_cart,
        $order_reference,
        $external_id,
        $old_status,
        $new_status
    ) {
        try {
            (new PaynowOrderStateProcessor($this->module))->updateState(
                $id_order,
                $id_payment,
                $id_cart,
                $order_reference,
                $external_id,
                $old_status,
                $new_status
            );
        } catch (Exception $exception) {
            PaynowLogger::error(
                'An error occurred during updating state {code={}, externalId={}, paymentId={}, status={}, message={}}',
                [
                    $exception->getCode(),
                    $external_id,
                    $id_payment,
                    $new_status,
                    $exception->getMessage()
                ]
            );
        }
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

    /**
     * @param int $id_order
     * @param string $payment_status
     * @param int $payment_data_locked
     * @param bool $orders_exists
     *
     * @return bool
     */
    protected function canProcessCreateOrder(int $id_order, string $payment_status, int $payment_data_locked, bool $orders_exists): bool
    {
        return PaynowConfigurationHelper::CREATE_ORDER_AFTER_PAYMENT === (int)Configuration::get('PAYNOW_CREATE_ORDER_STATE') &&
               Status::STATUS_CONFIRMED === $payment_status &&
               0 === $id_order &&
               0 === $payment_data_locked &&
               false === $orders_exists;
    }

    /**
     * @param $cart
     * @param $external_id
     * @param $payment_id
     *
     * @return Order|null
     */
    protected function createOrder($cart, $external_id, $payment_id): ?Order
    {
        $order = (new PaynowOrderCreateProcessor($this->module))->process($cart, $external_id, $payment_id);

        if (! $order) {
            return null;
        }

        PaynowPaymentData::updateOrderIdAndOrderReferenceByPaymentId(
            $order->id,
            $order->reference,
            $payment_id
        );

        return $order;
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
