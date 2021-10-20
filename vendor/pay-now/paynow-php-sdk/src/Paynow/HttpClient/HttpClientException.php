<?php

namespace Paynow\HttpClient;

use Exception;

class HttpClientException extends Exception
{
    /** @var string|null */
    private $body;

    /** @var array */
    private $errors;

    /** @var int|null */
    private $status;

    /**
     * @param string $message
     * @param int|null $status
     * @param string|null $body
     */
    public function __construct(string $message, ?int $status = null, ?string $body = null)
    {
        parent::__construct($message);
        $this->status = $status;
        $this->body = $body;
    }

    /**
     * @return string|null
     */
    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * @return int|null
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
