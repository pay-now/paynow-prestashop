<?php

namespace Paynow\Service;

use Paynow\Client;
use Paynow\Environment;
use Paynow\Exception\ConfigurationException;

class Service
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @param Client $client
     * @throws ConfigurationException
     */
    public function __construct(Client $client)
    {
        if (! $client->getConfiguration()->getEnvironment()) {
            $message = 'Provide correct environment, use '.Environment::PRODUCTION.' or '.Environment::SANDBOX;
            throw new ConfigurationException($message);
        }

        if (! $client->getConfiguration()->getApiKey()) {
            throw new ConfigurationException('You did not provide Api Key');
        }

        if (! $client->getConfiguration()->getSignatureKey()) {
            throw new ConfigurationException('You did not provide Signature Key');
        }

        $this->client = $client;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }
}
