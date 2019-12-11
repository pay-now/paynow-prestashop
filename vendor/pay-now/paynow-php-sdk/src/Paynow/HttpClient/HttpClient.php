<?php

namespace Paynow\HttpClient;

use Http\Client\Curl\Client;
use Http\Client\Exception\RequestException;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Http\Message\StreamFactory\GuzzleStreamFactory;
use Paynow\Configuration;
use Paynow\Util\SignatureCalculator;
use Psr\Http\Message\RequestInterface;

class HttpClient implements HttpClientInterface
{
    /**
     * @var Client
     */
    protected $client;

    protected $messageFactory;

    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $options = [
            CURLOPT_CONNECTTIMEOUT => 10,
        ];
        $this->messageFactory = new GuzzleMessageFactory();
        $this->client = new Client($this->messageFactory, new GuzzleStreamFactory(), $options);
    }

    /**
     * @return string
     */
    private function getUserAgent()
    {
        if ($this->config->getApplicationName()) {
            return $this->config->getApplicationName().' ('.Configuration::USER_AGENT.')';
        }

        return Configuration::USER_AGENT;
    }

    /**
     * @param RequestInterface $request
     * @throws HttpClientException
     * @return ApiResponse
     */
    private function send(RequestInterface $request)
    {
        try {
            return new ApiResponse($this->client->sendRequest($request));
        } catch (RequestException $exception) {
            throw new HttpClientException($exception->getMessage());
        }
    }

    /**
     * @param $url
     * @param array $data
     * @param null  $idempotencyKey
     * @throws HttpClientException
     * @return ApiResponse
     */
    public function post($url, array $data, $idempotencyKey = null)
    {
        $headers = $this->prepareHeaders($data);

        if ($idempotencyKey) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        $request = $this->messageFactory->createRequest(
            'POST',
            $this->config->getUrl().$url,
            $headers,
            $this->prepareData($data)
        );

        try {
            return $this->send($request);
        } catch (RequestException $exception) {
            throw new HttpClientException($exception->getMessage());
        }
    }

    /**
     * @param $url
     * @param array $data
     * @throws HttpClientException
     * @return ApiResponse
     */
    public function patch($url, array $data)
    {
        $headers = $this->prepareHeaders($data);
        $request = $this->messageFactory->createRequest(
            'PATCH',
            $this->config->getUrl().$url,
            $headers,
            $this->prepareData($data)
        );

        return $this->send($request);
    }

    /**
     * @param  $url
     * @throws HttpClientException
     * @return ApiResponse
     */
    public function get($url)
    {
        $request = $this->messageFactory->createRequest(
            'GET',
            $this->config->getUrl().$url
        );

        return $this->send($request);
    }

    /**
     * @param array $data
     * @return string
     */
    private function prepareData(array $data)
    {
        return json_encode($data);
    }

    /**
     * @param array $data
     * @return array
     */
    private function prepareHeaders(array $data)
    {
        return [
            'Api-Key' => $this->config->getApiKey(),
            'User-Agent' => $this->getUserAgent(),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Signature' => (string) new SignatureCalculator($this->config->getSignatureKey(), $data),
        ];
    }
}
