<?php

namespace Paynow\Response\Payment;

class Status
{
    /** @var string */
    private $paymentId;

    /** @var string */
    private $status;

    public function __construct($paymentId, $status)
    {
        $this->paymentId = $paymentId;
        $this->status = $status;
    }

    /** @return string */
    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    /** @return string */
    public function getStatus(): string
    {
        return $this->status;
    }
}
