<?php

namespace Paynow\HttpClient;

use Http\Discovery\Exception\NotFoundException;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Paynow\Configuration;
use Paynow\Util\SignatureCalculator;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriInterface;

class HttpClient implements HttpClientInterface
{
    /** @var ClientInterface */
    protected $client;

    /** @var RequestFactoryInterface */
    protected $messageFactory;

    /** @var StreamFactoryInterface */
    protected $streamFactory;

    /** @var Configuration */
    protected $config;

    /** @var UriInterface */
    private $url;

    /** @param Configuration $config */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->messageFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        try {
            $this->client = Psr18ClientDiscovery::find();
        } catch (NotFoundException $exception) {
            $this->client = HttpClientDiscovery::find();
        }
        $this->url = Psr17FactoryDiscovery::findUrlFactory()->createUri((string)$config->getUrl());
    }

    /**
     * @return string
     */
    private function getUserAgent(): string
    {
        if ($this->config->getApplicationName()) {
            return $this->config->getApplicationName() . ' (' . Configuration::USER_AGENT . ')';
        }

        return Configuration::USER_AGENT;
    }

    /**
     * @param RequestInterface $request
     * @throws HttpClientException
     * @return ApiResponse
     */
    private function send(RequestInterface $request): ApiResponse
    {
        try {
            return new ApiResponse($this->client->sendRequest($request));
        } catch (ClientExceptionInterface $exception) {
            throw new HttpClientException($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @param string $url
     * @param array $data
     * @param string|null $idempotencyKey
     * @throws HttpClientException
     * @return ApiResponse
     */
    public function post(string $url, array $data, ?string $idempotencyKey = null): ApiResponse
    {
        $headers = $this->prepareHeaders($data);

        if ($idempotencyKey) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        $request = $this->messageFactory->createRequest(
            'POST',
            $this->url->withPath($url)
        );

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $request = $request->withBody($this->streamFactory->createStream($this->arrayAsJson($data)));

        return $this->send($request);
    }

    /**
     * @param string $url
     * @param array $data
     * @throws HttpClientException
     * @return ApiResponse
     */
    public function patch(string $url, array $data): ApiResponse
    {
        $headers = $this->prepareHeaders($data);
        $request = $this->messageFactory->createRequest(
            'PATCH',
            $this->url->withPath($url)
        );

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $request = $request->withBody($this->streamFactory->createStream($this->arrayAsJson($data)));

        return $this->send($request);
    }

    /**
     * @param string $url
     * @param string|null $query
     * @return ApiResponse
     * @throws HttpClientException
     */
    public function get(string $url, string $query = null): ApiResponse
    {
        $request = $this->messageFactory->createRequest(
            'GET',
            $query ? $this->url->withPath($url)->withQuery($query) : $this->url->withPath($url)
        );

        foreach ($this->prepareHeaders() as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $this->send($request);
    }

    /**
     * @param array $data
     * @return string
     */
    private function arrayAsJson(array $data): string
    {
        return json_encode($data);
    }

    /**
     * @param null|array $data
     * @return array
     */
    private function prepareHeaders(?array $data = null)
    {
        $headers = [
            'Api-Key' => $this->config->getApiKey(),
            'User-Agent' => $this->getUserAgent(),
            'Accept' => 'application/json'
        ];

        if ($data) {
            $headers['Content-Type'] = 'application/json';
            $headers['Signature'] = (string)new SignatureCalculator($this->config->getSignatureKey(), json_encode($data));
        }

        return $headers;
    }
}
