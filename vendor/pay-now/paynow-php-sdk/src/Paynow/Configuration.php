<?php

namespace Paynow;

class Configuration implements ConfigurationInterface
{
    const API_VERSION = 'v1';
    const API_PRODUCTION_URL = 'https://api.paynow.pl/';
    const API_SANDBOX_URL = 'https://api.sandbox.paynow.pl/';
    const USER_AGENT = 'paynow-php-sdk';

    /**
     * @var array
     */
    protected $data = [];

    /**
     * Set a key value pair
     *
     * @param string $key   Key to set
     * @param mixed  $value Value to set
     */
    private function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Get a specific key value
     *
     * @param string $key key to retrieve
     * @return mixed|null Value of the key or NULL
     */
    private function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * Get an API key
     *
     * @return mixed|null
     */
    public function getApiKey()
    {
        return $this->get('api_key');
    }

    /**
     * Get Signature key
     *
     * @return mixed|null
     */
    public function getSignatureKey()
    {
        return $this->get('signature_key');
    }

    /**
     * Get environment name
     *
     * @return mixed|null
     */
    public function getEnvironment()
    {
        return $this->get('environment');
    }

    /**
     * Get API url
     *
     * @return mixed|null
     */
    public function getUrl()
    {
        return $this->get('url');
    }

    /**
     * Set an API Key
     *
     * @param $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->set('api_key', $apiKey);
    }

    /**
     * Set Signature Key
     *
     * @param $signatureKey
     */
    public function setSignatureKey($signatureKey)
    {
        $this->set('signature_key', $signatureKey);
    }

    /**
     * Set environment
     *
     * @param $environment
     */
    public function setEnvironment($environment)
    {
        if (Environment::PRODUCTION === $environment) {
            $this->set('environment', Environment::PRODUCTION);
            $this->set('url', self::API_PRODUCTION_URL);
        } else {
            $this->set('environment', Environment::SANDBOX);
            $this->set('url', self::API_SANDBOX_URL);
        }
    }

    /**
     * Set an application name
     *
     * @param $applicationName
     */
    public function setApplicationName($applicationName)
    {
        $this->set('application_name', $applicationName);
    }

    /**
     * @return mixed|null
     */
    public function getApplicationName()
    {
        return $this->get('application_name');
    }
}
