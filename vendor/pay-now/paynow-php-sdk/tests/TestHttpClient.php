<?php

namespace Paynow\Tests;

use GuzzleHttp\Psr7\Response;
use Http\Mock\Client;
use Paynow\HttpClient\HttpClient;

class TestHttpClient extends HttpClient
{
    public function mockResponse($responseFile, $httpStatus)
    {
        $this->client = new Client();
        $content = null;
        if (null != $responseFile) {
            $filePath = dirname(__FILE__).'/resources/'.$responseFile;
            $content = file_get_contents($filePath, true);
        }
        $response = new Response($httpStatus, ['Content-Type' => 'application/json'], $content);
        $this->client->addResponse($response);
    }
}
