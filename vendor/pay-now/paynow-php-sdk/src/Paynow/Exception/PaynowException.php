<?php

namespace Paynow\Exception;

use Exception;
use Throwable;

class PaynowException extends Exception
{
    /** @var Error[] */
    private $errors = [];

    /**
     * PaynowException constructor.
     * @param string $message
     * @param int $code
     * @param string|null $body
     * @param Throwable|null $previous
     */
    public function __construct(string $message, int $code = 0, ?string $body = null, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        if ($body) {
            $json = json_decode($body);
            if ($json->errors) {
                foreach ($json->errors as $error) {
                    $this->errors[] = new Error($error->errorType, $error->message);
                }
            }
        }
    }

    /**
     * @return Error[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
