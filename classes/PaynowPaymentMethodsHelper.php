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

use Paynow\Client;
use Paynow\Exception\ConfigurationException;
use Paynow\Exception\PaynowException;
use Paynow\Response\PaymentMethods\PaymentMethods;
use Paynow\Service\Payment;

/**
 * Class PaymentMethodsHelper
 */
class PaynowPaymentMethodsHelper
{
    /**
     * @var Payment
     */
    private $payment_client;

    /**
     * @param Client $client
     *
     * @throws ConfigurationException
     */
    public function __construct(Client $client)
    {
        $this->payment_client = new Paynow\Service\Payment($client);
    }

    /**
     * @param $currency_iso_code
     * @param $total
     * @param $context
     * @param $module
     *
     * @return PaymentMethods|null
     */
    public function getAvailable($currency_iso_code, $total, $context, $module): ?PaymentMethods
    {
        try {
            $applePayEnabled = htmlspecialchars($_COOKIE['applePayEnabled'] ?? '0') === '1';
            $idempotencyKey = PaynowKeysGenerator::generateIdempotencyKey(PaynowKeysGenerator::generateExternalIdByCart($context->cart));
            $buyerExternalId = null;
            if ($context->customer && $context->customer->isLogged()) {
                $buyerExternalId = PaynowKeysGenerator::generateBuyerExternalId($context->cart->id_customer, $module);
            }

            return $this->payment_client->getPaymentMethods($currency_iso_code, $total, $applePayEnabled, $idempotencyKey, $buyerExternalId);
        } catch (PaynowException $exception) {
            PaynowLogger::error(
                'An error occurred during payment methods retrieve {currency={}, total={}, code={}, message={}, errors={}, m={}}',
                [
					$currency_iso_code,
					$total,
                    $exception->getCode(),
                    $exception->getPrevious()->getMessage(),
                    $exception->getErrors(),
                    $exception->getMessage()
                ]
            );
        }

        return null;
    }
}
