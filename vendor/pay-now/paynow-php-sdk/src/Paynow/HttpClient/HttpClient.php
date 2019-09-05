<?php

namespace Paynow\HttpClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Paynow\Configuration;
use Paynow\Util\SignatureCalculator;

class HttpClient implements HttpClientInterface
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Configuration
     */
    protected $config;

    /**
     * HttpClient constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->client = new Client(
            [
                'base_url' => $this->config->getUrl(),
                'timeout' => 10.0,
                'defaults' => [
                    'headers' => [
                        'Api-Key' => $this->config->getApiKey(),
                        'User-Agent' => $this->getUserAgent(),
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                    ]
                ]
            ]
        );
    }

    /**
     * @return string
     */
    private function getUserAgent()
    {
        if ($this->config->getApplicationName()) {
            return $this->config->getApplicationName() . ' (' . Configuration::USER_AGENT . ')';
        }
        return Configuration::USER_AGENT;
    }

    /**
     * @param $url
     * @param array $data
     * @param null $idempotencyKey
     * @return ApiResponse
     * @throws HttpClientException
     */
    public function post($url, array $data, $idempotencyKey = null)
    {
        $options = $this->defaultOptions($data);

        if ($idempotencyKey) {
            $options['headers']['Idempotency-Key'] = $idempotencyKey;
        }

        try {
            return new ApiResponse($this->client->post($url, $options));
        } catch (RequestException $e) {
            throw new HttpClientException(
                "Error occurred during processing request",
                $e->getResponse()->getStatusCode(),
                $e->getResponse()->getBody()->getContents()
            );
        }
    }

    /**
     * @param $url
     * @param array $data
     * @return ApiResponse
     * @throws HttpClientException
     */
    public function patch($url, array $data)
    {
        try {
            return new ApiResponse($this->client->patch($url, $this->defaultOptions($data)));
        } catch (RequestException $e) {
            throw new HttpClientException(
                "Error occurred during processing request",
                $e->getResponse()->getStatusCode(),
                $e->getResponse()->getBody()->getContents()
            );
        }
    }

    /**
     * @param  $url
     * @return ApiResponse
     * @throws HttpClientException
     */
    public function get($url)
    {
        try {
            return new ApiResponse($this->client->get($url));
        } catch (RequestException $e) {
            throw new HttpClientException(
                "Error occurred during processing request",
                $e->getResponse()->getStatusCode(),
                $e->getResponse()->getBody()->getContents()
            );
        }
    }

    /**
     * @param array $data
     * @return array
     */
    private function defaultOptions(array $data)
    {
        return [
            'json' => $data,
            'headers' => [
                'Signature' => (string)new SignatureCalculator($this->config->getSignatureKey(), $data)
            ]
        ];
    }
}
