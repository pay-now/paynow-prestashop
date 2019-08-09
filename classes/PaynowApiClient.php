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

require_once(dirname(__FILE__) . '/../vendor/autoload.php');

class PaynowApiClient
{
    private $client;
    private $apiKey;
    private $signatureApiKey;

    public function __construct($apiUrl, $apiKey, $signatureApiKey, $userAgent)
    {
        $this->signatureApiKey = $signatureApiKey;
        $this->apiKey = $apiKey;

        $this->client = new GuzzleHttp\Client([
            'base_url' => $apiUrl.'/v1/',
            'timeout' => 30.0,
            'defaults' => [
                'headers' => [
                    'Api-Key' => $this->apiKey,
                    'User-Agent' => $userAgent,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ]
            ]
        ]);
    }

    public function createPayment($request)
    {
        $signature = $this->calculateSignature($request);
        try {
            $response = $this->client->post(
                'payments',
                [
                    'json' => $request,
                    'headers' => [
                        'Signature' => $signature,
                        'Idempotency-Key' => $request['externalId']
                    ]
                ]
            );
            return json_decode($response->getBody());
        } catch (GuzzleHttp\Exception\RequestException $e) {
            throw new PaynowClientException(
                "Error occurred during payment processing",
                $e->getResponse()->getStatusCode() . " - " . $e->getResponse()->getBody()->getContents()
            );
        }
    }

    public function calculateSignature(array $request)
    {
        return base64_encode(hash_hmac('sha256', json_encode($request), $this->signatureApiKey, true));
    }
}
