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

class PaynowLogger
{
    const DEBUG = 'debug';
    const INFO = 'info';
    const WARNING = 'warning';
    const ERROR = 'error';

    public static function log($type, $message, $context = [])
    {
        if ((int)Configuration::get('PAYNOW_DEBUG_LOGS_ENABLED')) {
            $file_name = 'paynow-' . date('Y-m-d');
            $file_path = dirname(__FILE__) . '/../log/' . $file_name . '-' . Tools::encrypt($file_name) . '.log';

            file_put_contents($file_path, self::processRecord($type, $message, $context), FILE_APPEND);
        }
    }

    private static function processRecord($type, $message, $context)
    {
        $split_message = explode('{}', $message);
        $message_part_count = sizeof($split_message);
        $result_message = '';
        for ($i = 0; $i < $message_part_count; $i++) {
            if ($i > 0 && sizeof($context) >= $i) {
                $paramValue = $context[$i - 1];
                if (!is_array($paramValue)) {
                    $result_message .= $paramValue;
                } else {
                    $result_message .= json_encode($paramValue);
                }
            }
            $messagePart = $split_message[$i];
            $result_message .= $messagePart;
        }

        return self::getTimestamp() . ' ' . Tools::strtoupper($type) . ' ' . $result_message . PHP_EOL;
    }

    public static function getTimestamp()
    {
        $now = microtime(true);
        $micro = sprintf('%06d', ($now - floor($now)) * 1000000);
        $date_time = new DateTime(date('Y-m-d H:i:s.' . $micro, $now));
        return $date_time->format('Y-m-d G:i:s.u');
    }

    public static function info($message, $context = [])
    {
        self::log(self::INFO, $message, $context);
    }

    public static function debug($message, $context = [])
    {
        self::log(self::DEBUG, $message, $context);
    }

    public static function error($message, $context = [])
    {
        self::log(self::ERROR, $message, $context);
    }

    public static function warning($message, $context = [])
    {
        self::log(self::WARNING, $message, $context);
    }
}
