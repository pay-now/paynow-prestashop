<?php

namespace Paynow\Tests\Util;

use Paynow\Tests\TestCase;
use Paynow\Util\SignatureCalculator;

class SignatureCalculatorTest extends TestCase
{
    public function testNotValidSuccessfully()
    {
        $signatureCalculator = new SignatureCalculator('InvalidSecretKey', ['key' => 'value']);
        $this->assertNotEquals('hash', $signatureCalculator->getHash());
    }

    public function testShouldValidSuccessfully()
    {
        $signatureCalculator = new SignatureCalculator(
            'a621a1fb-b4d8-48ba-a6a3-2a28ed61f605',
            [
                'key1' => 'value1',
                'key2' => 'value2',
            ]
        );
        $this->assertEquals('rFAkhfbUFRn4bTR82qb742Mwy34g/CSi8frEHciZhCU=', $signatureCalculator->getHash());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionForEmptyData()
    {
        $signatureCalculator = new SignatureCalculator('a621a1fb-b4d8-48ba-a6a3-2a28ed61f605', []);

        return $signatureCalculator->getHash();
    }
}
