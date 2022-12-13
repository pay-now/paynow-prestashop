<?php

use Paynow\Exception\SignatureVerificationException;
use Paynow\Notification;

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

class PaynowNotificationsModuleFrontController extends PaynowFrontController
{
    public function process()
    {
        ob_start();
        $payload = trim(Tools::file_get_contents('php://input'));
        $notification_data = json_decode($payload, true);
        PaynowLogger::debug('Nofification: Incoming notification', $notification_data);

        try {
            new Notification(
                $this->module->getSignatureKey(),
                $payload,
                $this->getRequestHeaders()
            );
            (new PaynowOrderStateProcessor($this->module))->processNotification($notification_data);
        } catch (SignatureVerificationException | InvalidArgumentException $e) {
            $notification_data['exeption'] = $e->getMessage();
            PaynowLogger::error('Nofification: Signature verification failed', $notification_data);
            header('HTTP/1.1 400 Bad Request', true, 400);
            ob_clean();
            exit;
        } catch (PaynowNotificationStopProcessing $e) {
            $e->logContext['responseCode'] = 202;
            PaynowLogger::debug('Nofification: ' . $e->logMessage, $e->logContext);
            header('HTTP/1.1 202 OK', true, 202);
            ob_clean();
            exit;
        } catch (PaynowNotificationRetryProcessing $e) {
            $e->logContext['responseCode'] = 400;
            PaynowLogger::debug('Nofification: ' . $e->logMessage, $e->logContext);
            header('HTTP/1.1 400 Bad Request', true, 400);
            ob_clean();
            exit;
        } catch (Exception $e) {
            $notification_data['responseCode'] = 400;
            $notification_data['exeption'] = $e->getMessage();
            $notification_data['file'] = $e->getFile();
            $notification_data['line'] = $e->getLine();
            PaynowLogger::error('Nofification: unknown error', $notification_data);
            header('Content-Type: application/json');
            header('HTTP/1.1 400 Bad Request', true, 400);
            ob_clean();
            echo json_encode(
                array(
                    'message' => 'An error occurred during processing notification',
                    'reason'  => $e->getMessage(),
                )
            );
            ob_flush();
            exit;
        }

        header("HTTP/1.1 200 OK", true, 200);
        ob_clean();
        exit;
    }

    private function getRequestHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (Tools::substr($key, 0, 5) == 'HTTP_') {
                $subject = str_replace('_', ' ', Tools::strtolower(Tools::substr($key, 5)));
                $headers[str_replace(' ', '-', ucwords($subject))] = $value;
            }
        }
        return $headers;
    }

}
