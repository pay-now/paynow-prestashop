<?php

namespace Paynow\HttpClient;

use Psr\Http\Message\ResponseInterface;

class ApiResponse
{
    /**
     * Body content
     *
     * @var string
     */
    public $body;

    /**
     * Http status
     *
     * @var int
     */
    public $status;

    /**
     * Headers list
     *
     * @var array
     */
    protected $headers;

    /**
     * @param ResponseInterface $response
     * @throws HttpClientException
     */
    public function __construct(ResponseInterface $response)
    {
        $this->body = $response->getBody();
        $this->status = $response->getStatusCode();
        $this->headers = $response->getHeaders();

        if ($response->getStatusCode() >= 400) {
            throw new HttpClientException(
                'Error occurred during processing request',
                $response->getStatusCode(),
                $response->getBody()->getContents()
            );
        }
    }

    /**
     * Parse JSON
     *
     * @return mixed
     */
    public function decode()
    {
        return json_decode($this->body);
    }

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
