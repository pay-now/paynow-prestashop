<?php

use Http\Discovery\Exception\NotFoundException;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class PaynowGithubClient
{
    /** @var ClientInterface */
    private $client;

    /** @var RequestFactoryInterface */
    protected $messageFactory;

    /** @var \Psr\Http\Message\UriInterface */
    private $url;

    public function __construct()
    {
        try {
            $this->client = Psr18ClientDiscovery::find();
        } catch (NotFoundException $exception) {
            $this->client = HttpClientDiscovery::find();
        }
        $this->messageFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->url = Psr17FactoryDiscovery::findUrlFactory()->createUri('https://api.github.com');
    }

    public function latest($username, $repository)
    {
        $request = $this->messageFactory->createRequest(
            'GET',
            $this->url->withPath('/repos/'.rawurlencode($username).'/'.rawurlencode($repository).'/releases/latest')
        );

        try {
            return json_decode($this->client->sendRequest($request)->getBody()->getContents());
        } catch (ClientExceptionInterface $exception) {
            PaynowLogger::error("Error occurred during retrieving github latest release information {message={}}", $exception->getMessage());
        }

        return null;
    }
}
