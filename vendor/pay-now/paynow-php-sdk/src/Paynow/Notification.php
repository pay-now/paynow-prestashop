<?php

namespace Paynow;

use InvalidArgumentException;
use Paynow\Exception\SignatureVerificationException;
use Paynow\Util\SignatureCalculator;

class Notification
{
    /**
     * @param $signatureKey
     * @param $payload
     * @param $headers
     * @throws SignatureVerificationException
     */
    public function __construct($signatureKey, $payload = null, ?array $headers = null)
    {
        if (! $payload) {
            throw new InvalidArgumentException('No payload has been provided');
        }

        if (! $headers) {
            throw new InvalidArgumentException('No headers have been provided');
        }

        $this->verify($signatureKey, $payload, $headers);
    }

    /**
     * Verify payload Signature
     *
     * @param string $signatureKey
     * @param string $data
     * @param array $headers
     * @throws SignatureVerificationException
     * @return bool
     */
    private function verify(string $signatureKey, string $data, array $headers)
    {
        $calculatedSignature = (string)new SignatureCalculator($signatureKey, $data);
        if ($calculatedSignature !== $this->getPayloadSignature($headers)) {
            throw new SignatureVerificationException('Signature mismatched for payload');
        }

        return true;
    }

    /**
     * Retrieve Signature from payload
     *
     * @param array $headers
     * @throws SignatureVerificationException
     * @return string
     */
    private function getPayloadSignature(array $headers)
    {
        if (isset($headers['Signature']) && $headers['Signature']) {
            $signature = $headers['Signature'];
        }

        if (isset($headers['signature']) && $headers['signature']) {
            $signature = $headers['signature'];
        }

        if (empty($signature)) {
            throw new SignatureVerificationException('No signature was found for payload');
        }

        return $signature;
    }
}
