<?php

use Paynow\Client;
use Paynow\Response\PaymentMethods\PaymentMethods;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class PaymentMethodsHelper
 */
class PaymentMethodsHelper
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param $currency_iso_code
     * @param $total
     *
     * @return PaymentMethods|null
     */
    public function getAvailable($currency_iso_code, $total)
    {
        try {
            $payment_methods_client = new Paynow\Service\Payment($this->client);
            return $payment_methods_client->getPaymentMethods($currency_iso_code, $total);
        } catch (\Paynow\Exception\PaynowException $exception) {
            PaynowLogger::error($exception->getMessage());
        }

        return null;
    }
}