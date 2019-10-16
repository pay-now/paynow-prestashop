<?php

namespace Paynow\Exception;

class Error
{
    /**
     * @var string
     */
    private $errorType;

    /**
     * @var string
     */
    private $message;

    public function __construct($errorType, $message)
    {
        $this->errorType = $errorType;
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getErrorType()
    {
        return $this->errorType;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}
