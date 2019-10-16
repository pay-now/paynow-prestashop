<?php

namespace Paynow\Util;

use InvalidArgumentException;

class SignatureCalculator
{
    /**
     * @var string
     */
    protected $hash;

    /**
     * @param string $signatureKey
     * @param array  $data
     * @throws InvalidArgumentException
     */
    public function __construct($signatureKey, array $data)
    {
        if (empty($signatureKey)) {
            throw new InvalidArgumentException('You did not provide a Signature key');
        }

        if (empty($data)) {
            throw new InvalidArgumentException('You did not provide any data');
        }
        $this->hash = base64_encode(hash_hmac('sha256', json_encode($data), $signatureKey, true));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getHash();
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }
}
