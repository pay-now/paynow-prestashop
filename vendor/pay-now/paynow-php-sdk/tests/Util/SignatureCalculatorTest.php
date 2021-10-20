<?php

namespace Paynow\Tests\Util;

use InvalidArgumentException;
use Paynow\Tests\TestCase;
use Paynow\Util\SignatureCalculator;

class SignatureCalculatorTest extends TestCase
{
    public function testNotValidSuccessfully()
    {
        // given + when
        $signatureCalculator = new SignatureCalculator('InvalidSecretKey', json_encode(['key' => 'value']));

        // then
        $this->assertNotEquals('hash', $signatureCalculator->getHash());
    }

    public function testShouldValidSuccessfully()
    {
        // given + when
        $signatureCalculator = new SignatureCalculator(
            'a621a1fb-b4d8-48ba-a6a3-2a28ed61f605',
            json_encode([
                'key1' => 'value1',
                'key2' => 'value2',
            ])
        );

        // then
        $this->assertEquals('rFAkhfbUFRn4bTR82qb742Mwy34g/CSi8frEHciZhCU=', $signatureCalculator->getHash());
    }

    public function testExceptionForEmptyData()
    {
        // given
        $this->expectException(InvalidArgumentException::class);

        // when
        $signatureCalculator = new SignatureCalculator('a621a1fb-b4d8-48ba-a6a3-2a28ed61f605', "");

        // then
    }
}
