<?php

namespace Paynow;

use Paynow\HttpClient\HttpClientInterface;

class Client
{
    private $configuration;

    private $httpClient;

    /**
     * @param $apiKey
     * @param $apiSignatureKey
     * @param $environment
     * @param $applicationName
     */
    public function __construct($apiKey, $apiSignatureKey, $environment, $applicationName = null)
    {
        $this->configuration = new Configuration();
        $this->configuration->setApiKey($apiKey);
        $this->configuration->setSignatureKey($apiSignatureKey);
        $this->configuration->setEnvironment($environment);
        $this->configuration->setApplicationName($applicationName);
        $this->httpClient = new HttpClient\HttpClient($this->configuration);
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param HttpClientInterface $httpClient
     */
    public function setHttpClient(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @return HttpClient\HttpClient
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }
}
