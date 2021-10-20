<?php

namespace Paynow\Response\Payment;

use \Paynow\Model\Payment\Status;

class Authorize
{
    /** @var string */
    private $redirectUrl;

    /** @var string */
    private $paymentId;

    /** @var Status|string */
    private $status;

    public function __construct($redirectUrl, $paymentId, $status)
    {
        $this->redirectUrl = $redirectUrl;
        $this->paymentId = $paymentId;
        $this->status = $status;
    }

    /** @return string */
    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
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
