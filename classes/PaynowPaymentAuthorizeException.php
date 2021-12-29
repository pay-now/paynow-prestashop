<?php

class PaynowPaymentAuthorizeException extends Exception
{
    /**
     * @var string
     */
    private $externalId;

    public function __construct(string $message, string $externalId, Throwable $previous = null)
    {
        $this->externalId = $externalId;
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return string
     */
    public function getExternalId(): string
    {
        return $this->externalId;
    }
}
