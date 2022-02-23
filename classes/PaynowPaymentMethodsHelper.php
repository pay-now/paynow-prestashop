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
     *
     * @return PaymentMethods|null
     */
    public function getAvailable($currency_iso_code, $total): ?PaymentMethods
    {
        try {
            return $this->payment_client->getPaymentMethods($currency_iso_code, $total);
        } catch (PaynowException $exception) {
            PaynowLogger::error(
                'An error occurred during payment methods retrieve {code={}, message={}}',
                [
                    $exception->getCode(),
                    $exception->getPrevious()->getMessage()
                ]
            );
        }

        return null;
    }
}
