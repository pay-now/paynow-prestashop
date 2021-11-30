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
use Paynow\Response\Payment\Authorize;
use Paynow\Service\Payment;

class PaymentProcessor
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
        $this->payment_client = new Payment($client);
    }

    /**
     * @param $payment_request_data
     * @param $idempotency_key
     *
     * @return Authorize|null
     */
    public function process($payment_request_data, $idempotency_key): ?Authorize
    {
        return $this->payment_client->authorize($payment_request_data, $idempotency_key);
    }
}
