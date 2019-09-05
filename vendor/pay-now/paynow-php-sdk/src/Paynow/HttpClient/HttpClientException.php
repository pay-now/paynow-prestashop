<?php

namespace Paynow\HttpClient;

use Exception;

/**
 * Class HttpClientException
 *
 * @package Paynow\HttpClient
 */
class HttpClientException extends Exception
{
    /**
     * @var string
     */
    private $body;

    /**
     * @var array
     */
    private $errors;

    /**
     * @var int
     */
    private $status;

    /**
     * HttpClientException constructor.
     * @param $message
     * @param $status
     * @param $body
     */
    public function __construct($message, $status, $body)
    {
        parent::__construct($message);
        $this->status = $status;
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
