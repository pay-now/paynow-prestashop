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

use Paynow\Exception\ConfigurationException;
use Paynow\Exception\PaynowException;
use Paynow\Response\Payment\Authorize;
use Paynow\Service\Payment;

class PaynowPaymentProcessor
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var Paynow
     */
    private $module;

    /**
     * @var Payment
     */
    private $paymentClient;

    private $paymentDataBuilder;

    private $externalId = null;

    /**
     * @param Context $context
     * @param $module
     *
     * @throws ConfigurationException
     */
    public function __construct(Context $context, $module)
    {
        $this->context            = $context;
        $this->module             = $module;
        $this->paymentClient      = new Payment($module->getPaynowClient());
        $this->paymentDataBuilder = new PaynowPaymentDataBuilder($this->module);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function process(): array
    {
        if (PaynowConfigurationHelper::CREATE_ORDER_BEFORE_PAYMENT === (int)Configuration::get('PAYNOW_CREATE_ORDER_STATE') && ! empty($this->module->currentOrder)) {
            $order   = new Order($this->module->currentOrder);
            $payment = $this->processFromOrder($order, $this->getExternalId());

            PaynowPaymentData::create(
                $payment->getPaymentId(),
                Paynow\Model\Payment\Status::STATUS_NEW,
                $order->id,
                $order->id_cart,
                $order->reference,
                $order->reference,
                $order->total_paid
            );
        } else {
            $cart    = $this->context->cart;
            $payment = $this->processFromCart($cart, $this->getExternalId());

            PaynowPaymentData::create(
                $payment->getPaymentId(),
                Paynow\Model\Payment\Status::STATUS_NEW,
                null,
                $cart->id,
                null,
                $this->getExternalId(),
                $cart->getOrderTotal()
            );
        }

        PaynowLogger::info(
            'Payment has been successfully created {cartId={}, externalId={}, paymentId={}, status={}}',
            [
                $this->context->cart->id,
                $this->getExternalId(),
                $payment->getPaymentId(),
                $payment->getStatus()
            ]
        );

        return [
            'payment_id'   => $payment->getPaymentId(),
            'status'       => $payment->getStatus(),
            'redirect_url' => $payment->getRedirectUrl() ?? null,
            'external_id'  => $this->getExternalId()
        ];
    }

    /**
     * @return string
     */
    public function getExternalId(): string
    {
        if (empty($this->externalId)) {
            $this->generateExternalId();
        }

        return $this->externalId;
    }

    /**
     * @throws PaynowPaymentAuthorizeException
     */
    private function processFromOrder($order, $external_id): ?Authorize
    {
        PaynowLogger::info(
            'Processing payment for order {cartId={}, externalId={}, orderId={}, orderReference={}}',
            [
                $order->id_cart,
                $external_id,
                $order->id,
                $order->reference
            ]
        );
        $idempotency_key      = PaynowKeysGenerator::generateIdempotencyKey($external_id);
        $payment_request_data = $this->paymentDataBuilder->fromOrder($order);

		if ( !PaynowPaymentLockData::checkIsCartLocked($order->id_cart, $order->id) ) {
			throw new PaynowPaymentAuthorizeException(
				'Cannot create another payment transaction',
				$external_id
			);
		}

        return $this->sendPaymentRequest($payment_request_data, $idempotency_key);
    }

    /**
     * @throws PaynowPaymentAuthorizeException
     */
    private function processFromCart($cart, $external_id): ?Authorize
    {
        PaynowLogger::info(
            'Processing payment for cart {cartId={}, externalId={}}',
            [
                $cart->id,
                $external_id
            ]
        );
        $idempotency_key      = PaynowKeysGenerator::generateIdempotencyKey($external_id);
        $payment_request_data = $this->paymentDataBuilder->fromCart($cart, $external_id);

		if ( !PaynowPaymentLockData::checkIsCartLocked($cart->id) ) {
			throw new PaynowPaymentAuthorizeException(
				'Cannot create another payment transaction',
				$external_id
			);
		}

        return $this->sendPaymentRequest($payment_request_data, $idempotency_key);
    }

    /**
     * @return void
     */
    private function generateExternalId(): void
    {
        if (PaynowConfigurationHelper::CREATE_ORDER_BEFORE_PAYMENT === (int)Configuration::get('PAYNOW_CREATE_ORDER_STATE') && ! empty($this->module->currentOrder)) {
            $order = new Order($this->module->currentOrder);

            $this->externalId = PaynowKeysGenerator::generateExternalIdByOrder($order);
        } else {
            $cart = $this->context->cart;

            $this->externalId = PaynowKeysGenerator::generateExternalIdByCart($cart);
        }
    }

    /**
     * @param $payment_request_data
     * @param $idempotency_key
     *
     * @return Authorize|null
     * @throws PaynowPaymentAuthorizeException
     */
    private function sendPaymentRequest($payment_request_data, $idempotency_key): ?Authorize
    {
        try {
            return $this->paymentClient->authorize($payment_request_data, $idempotency_key);
        } catch (PaynowException $exception) {
            throw new PaynowPaymentAuthorizeException(
                $exception->getMessage(),
                $payment_request_data['externalId'],
                $exception
            );
        }
    }
}
