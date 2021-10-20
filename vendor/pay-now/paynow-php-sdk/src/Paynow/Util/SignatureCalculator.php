<?php

namespace Paynow\Util;

use InvalidArgumentException;

class SignatureCalculator
{
    /** @var string */
    protected $hash;

    /**
     * @param string $signatureKey
     * @param string $data
     * @throws InvalidArgumentException
     */
    public function __construct(string $signatureKey, string $data)
    {
        if (empty($signatureKey)) {
            throw new InvalidArgumentException('You did not provide a Signature key');
        }

        if (empty($data)) {
            throw new InvalidArgumentException('You did not provide any data');
        }
        $this->hash = base64_encode(hash_hmac('sha256', $data, $signatureKey, true));
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->getHash();
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }
}
