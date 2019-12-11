<?php

namespace Paynow\Tests;

use Paynow\Client;
use Paynow\Environment;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected $testHttpClient;

    protected $client;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        $this->client = new Client(
            'TestApiKey',
            'TestSignatureKey',
            Environment::SANDBOX,
            'PHPUnitTests'
        );
        $this->testHttpClient = new TestHttpClient($this->client->getConfiguration());
        parent::__construct($name, $data, $dataName);
    }

    public function loadData($fileName, $asString = false)
    {
        $filePath = dirname(__FILE__).'/resources/'.$fileName;
        if (! $asString) {
            return json_decode(file_get_contents($filePath), true);
        } else {
            return file_get_contents($filePath);
        }
    }
}
