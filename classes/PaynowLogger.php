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

class PaynowLogger
{
    public static function log($message, $id_payment = '', $comment = '')
    {
        if ((int)Configuration::get('PAYNOW_SANDBOX_ENABLED')) {
            $file = dirname(__FILE__) . '/../log/paynow.log';
            self::writeToLog($message, $id_payment, $file, $comment);
        }
    }

    public static function formatMessage($message, $id_payment, $comment)
    {
        return '[' . self::getTimestamp() . '][' . $id_payment . ']' . (($comment == '') ? '' : ($comment . PHP_EOL)) . $message . PHP_EOL;
    }

    public static function getTimestamp()
    {
        $originalTime = microtime(true);
        $micro = sprintf('%06d', ($originalTime - floor($originalTime)) * 1000000);
        $date = new DateTime(date('Y-m-d H:i:s.' . $micro, $originalTime));
        return $date->format('Y-m-d G:i:s.u');
    }

    private static function writeToLog($message, $id_payment, $logFile, $comment)
    {
        if (!file_exists($logFile)) {
            fopen($logFile, 'a');
        }

        file_put_contents($logFile, self::formatMessage($message, $id_payment, $comment), FILE_APPEND);
    }
}
