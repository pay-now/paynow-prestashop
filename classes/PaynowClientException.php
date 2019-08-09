<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License (MIT)
 * that is bundled with this package in the file LICENSE.md.
 *
 * @copyright mBank S.A.
 * @license   MIT License
 */

class PaynowClientException extends Exception
{
    private $response_body;

    public function __construct($message, $body)
    {
        parent::__construct($message);
        $this->response_body = $body;

    }

    public function getResponseBody()
    {
        return $this->response_body;
    }
}
