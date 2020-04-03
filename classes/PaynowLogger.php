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
    public static function log($message, $description = null, $id_payment = null)
    {
        if ((int)Configuration::get('PAYNOW_DEBUG_LOGS_ENABLED')) {
            $file_name = 'paynow-' . date('Y-m-d');
            $file_path = dirname(__FILE__) . '/../log/' . $file_name . '-' . Tools::encrypt($file_name) . '.log';
            self::writeToLog($file_path, $message, $description, $id_payment);
        }
    }

    public static function formatMessage($message, $description = null, $id_payment = null)
    {
        $log_message = '[' . self::getTimestamp() . ']';

        if ($id_payment) {
            $log_message .= '[' . $id_payment . ']';
        }

        if ($message) {
            $log_message .= ' ' . $message;
        }

        if ($description) {
            $log_message .= ' ' . $description;
        }

        return $log_message . PHP_EOL;
    }

    public static function getTimestamp()
    {
        $now = microtime(true);
        $micro = sprintf('%06d', ($now - floor($now)) * 1000000);
        $date_time = new DateTime(date('Y-m-d H:i:s.' . $micro, $now));
        return $date_time->format('Y-m-d G:i:s.u');
    }

    private static function writeToLog($log_file, $message, $description = null, $id_payment = null)
    {
        file_put_contents($log_file, self::formatMessage($message, $description, $id_payment), FILE_APPEND);
    }
}
