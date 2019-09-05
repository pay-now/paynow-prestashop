<?php

namespace Paynow\Tests;

use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;
use Paynow\HttpClient\HttpClient;

class TestHttpClient extends HttpClient
{
    public function mockResponse($responseFile, $httpStatus)
    {
        $content = null;
        $response = new Response($httpStatus);
        if ($responseFile != null) {
            $filePath = dirname(__FILE__) . '/resources/' . $responseFile;
            $content = file_get_contents($filePath, true);
            $response = new Response($httpStatus, ['Content-Type' => 'application/json'], Stream::factory($content));
        }

        $mock = new Mock([$response]);

        $this->client->getEmitter()->attach($mock);
    }
}