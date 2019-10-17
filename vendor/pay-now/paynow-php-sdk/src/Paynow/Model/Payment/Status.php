<?php

namespace Paynow\Model\Payment;

class Status
{
    const STATUS_NEW = 'NEW';
    const STATUS_PENDING = 'PENDING';
    const STATUS_REJECTED = 'REJECTED';
    const STATUS_CONFIRMED = 'CONFIRMED';
    const STATUS_ERROR = 'ERROR';
}
