<?php

namespace Paynow\Model\Payment;

class Status
{
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_ERROR = 'ERROR';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_NEW = 'NEW';
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_WAITING_FOR_CONFIRMATION = 'WAITING_FOR_CONFIRMATION';
    public const STATUS_ABANDONED = 'ABANDONED';
}
