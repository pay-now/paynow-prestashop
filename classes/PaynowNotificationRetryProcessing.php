<?php

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License (MIT)
 * that is bundled with this package in the file LICENSE.md.
 *
 * @author mElements S.A.
 * @copyright mElements S.A.
 * @license   MIT License
 */

class PaynowNotificationRetryProcessing extends Exception
{

    public $logMessage;
    public $logContext;

    /**
     * @param string $message
     * @param array  $context
     */
    public function __construct($message, $context)
    {
        $this->logMessage = $message;
        $this->logContext = $context;

        parent::__construct($message);
    }

}
