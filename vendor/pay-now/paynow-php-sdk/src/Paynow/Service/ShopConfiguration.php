<?php

namespace Paynow\Service;

use Paynow\Configuration;
use Paynow\Exception\PaynowException;
use Paynow\HttpClient\ApiResponse;
use Paynow\HttpClient\HttpClientException;

class ShopConfiguration extends Service
{
    /**
     * @param string $continueUrl
     * @param string $notificationUrl
     * @throws PaynowException
     * @return ApiResponse
     */
    public function changeUrls($continueUrl, $notificationUrl)
    {
        $data = [
            'continueUrl' => $continueUrl,
            'notificationUrl' => $notificationUrl,
        ];
        try {
            return $this->getClient()
                ->getHttpClient()
                ->patch(Configuration::API_VERSION.'/configuration/shop/urls', $data);
        } catch (HttpClientException $exception) {
            throw new PaynowException(
                $exception->getMessage(),
                $exception->getStatus(),
                $exception->getBody()
            );
        }
    }
}
