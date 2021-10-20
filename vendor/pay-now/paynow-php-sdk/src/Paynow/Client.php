<?php

namespace Paynow;

use Paynow\HttpClient\HttpClient;
use Paynow\HttpClient\HttpClientInterface;

class Client
{
    private $configuration;

    private $httpClient;

    /**
     * @param string $apiKey
     * @param string $apiSignatureKey
     * @param string $environment
     * @param string|null $applicationName
     */
    public function __construct(
        string $apiKey,
        string $apiSignatureKey,
        string $environment,
        ?string $applicationName = null
    ) {
        $this->configuration = new Configuration();
        $this->configuration->setApiKey($apiKey);
        $this->configuration->setSignatureKey($apiSignatureKey);
        $this->configuration->setEnvironment($environment);
        $this->configuration->setApplicationName($applicationName);
        $this->httpClient = new HttpClient($this->configuration);
    }

    /**
     * @return Configuration
     */
    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    /**
     * @param HttpClientInterface $httpClient
     */
    public function setHttpClient(HttpClientInterface $httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @return HttpClientInterface
     */
    public function getHttpClient(): HttpClientInterface
    {
        return $this->httpClient;
    }
}
