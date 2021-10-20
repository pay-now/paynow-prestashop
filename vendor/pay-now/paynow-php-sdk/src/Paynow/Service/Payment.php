<?php

namespace Paynow\Service;

use Paynow\Configuration;
use Paynow\Exception\PaynowException;
use Paynow\HttpClient\HttpClientException;
use Paynow\Response\Payment\Authorize;
use Paynow\Response\Payment\Status;
use Paynow\Response\PaymentMethods\PaymentMethods;

class Payment extends Service
{
    /**
     * Authorize payment
     *
     * @param array $data
     * @param string|null $idempotencyKey
     * @throws PaynowException
     * @return Authorize
     */
    public function authorize(array $data, ?string $idempotencyKey = null): Authorize
    {
        try {
            $decodedApiResponse = $this->getClient()
                ->getHttpClient()
                ->post(
                    Configuration::API_VERSION . '/payments',
                    $data,
                    $idempotencyKey ?? $data['externalId']
                )
                ->decode();
            return new Authorize($decodedApiResponse->redirectUrl, $decodedApiResponse->paymentId, $decodedApiResponse->status);
        } catch (HttpClientException $exception) {
            throw new PaynowException(
                $exception->getMessage(),
                $exception->getStatus(),
                $exception->getBody(),
                $exception
            );
        }
    }

    /**
     * Retrieve available payment methods
     *
     * @param string|null $currency
     * @param int|null $amount
     * @throws PaynowException
     * @return PaymentMethods
     */
    public function getPaymentMethods(?string $currency = null, ?int $amount = 0)
    {
        $parameters = [];
        if (! empty($currency)) {
            $parameters['currency'] = $currency;
        }

        if ($amount > 0) {
            $parameters['amount'] = $amount;
        }

        try {
            $decodedApiResponse = $this->getClient()
                ->getHttpClient()
                ->get(Configuration::API_VERSION . '/payments/paymentmethods', http_build_query($parameters, '', '&'))
                ->decode();
            return new PaymentMethods($decodedApiResponse);
        } catch (HttpClientException $exception) {
            throw new PaynowException(
                $exception->getMessage(),
                $exception->getStatus(),
                $exception->getBody(),
                $exception
            );
        }
    }

    /**
     * Retrieve payment status
     *
     * @param string $paymentId
     * @throws PaynowException
     * @return Status
     */
    public function status(string $paymentId): Status
    {
        try {
            $decodedApiResponse = $this->getClient()
                ->getHttpClient()
                ->get(Configuration::API_VERSION . "/payments/$paymentId/status")
                ->decode();

            return new Status($decodedApiResponse->paymentId, $decodedApiResponse->status);
        } catch (HttpClientException $exception) {
            throw new PaynowException(
                $exception->getMessage(),
                $exception->getStatus(),
                $exception->getBody(),
                $exception
            );
        }
    }
}
