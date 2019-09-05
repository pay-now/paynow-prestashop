<?php

namespace Paynow\Service;

use Paynow\Configuration;
use Paynow\Exception\ConfigurationException;
use Paynow\Exception\PaynowException;
use Paynow\HttpClient\ApiResponse;
use Paynow\HttpClient\HttpClientException;

/**
 * Class Payment
 *
 * @package Paynow\Service
 */
class Payment extends Service
{
    /**
     * Authorize payment
     *
     * @param array $data
     * @return mixed
     * @throws PaynowException
     * @throws ConfigurationException
     */
    public function authorize(array $data)
    {
        try {
            return $this->getClient()
                ->getHttpClient()
                ->post(
                    Configuration::API_VERSION . '/payments',
                    $data,
                    $data['externalId']
                )
                ->decode();
        } catch (HttpClientException $exception) {
            throw new PaynowException(
                $exception->getMessage(),
                $exception->getStatus(),
                $exception->getBody()
            );
        }
    }

    /**
     * @param string $paymentId
     * @return ApiResponse
     * @throws PaynowException
     */
    public function status($paymentId)
    {
        try {
            return $this->getClient()
                ->getHttpClient()
                ->get(Configuration::API_VERSION . "/payments/$paymentId/status")
                ->decode();
        } catch (HttpClientException $exception) {
            throw new PaynowException(
                $exception->getMessage(),
                $exception->getStatus(),
                $exception->getBody()
            );
        }
    }
}
