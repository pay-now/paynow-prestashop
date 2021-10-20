<?php

namespace Paynow\Response\Refund;

class Status
{
    /** @var string */
    private $refundId;

    /** @var string|Status */
    private $status;

    public function __construct($refundId, $status)
    {
        $this->refundId = $refundId;
        $this->status = $status;
    }

    /** @return string */
    public function getRefundId(): string
    {
        return $this->refundId;
    }

    /** @return string */
    public function getStatus(): string
    {
        return $this->status;
    }
}