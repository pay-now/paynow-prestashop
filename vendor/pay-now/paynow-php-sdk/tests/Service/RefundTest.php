<?php

namespace Paynow\Tests\Service;

use Paynow\Exception\PaynowException;
use Paynow\Service\Refund;
use Paynow\Tests\TestCase;

class RefundTest extends TestCase
{
    public function testShouldRefundPaymentSuccessfully()
    {
        // given
        $this->testHttpClient->mockResponse('refund_success.json', 200);
        $this->client->setHttpClient($this->testHttpClient);
        $refundService = new Refund($this->client);

        // when
        $response = $refundService->create('NOR3-FUN-D4U-LOL', 'idempotencyKey123', 100, null);

        // then
        $this->assertNotEmpty($response->getRefundId());
        $this->assertNotEmpty($response->getStatus());
    }

    public function testShouldNotAuthorizePaymentSuccessfully()
    {
        // given
        $this->testHttpClient->mockResponse('refund_failed.json', 400);
        $this->client->setHttpClient($this->testHttpClient);
        $refundService = new Refund($this->client);

        // when
        try {
            $response = $refundService->create('NOR3-FUN-D4U-LOL', 'idempotencyKey123', 100, null);
        } catch (PaynowException $exception) {
            // then
            $this->assertEquals(400, $exception->getCode());
            $this->assertEquals('INSUFFICIENT_BALANCE_FUNDS', $exception->getErrors()[0]->getType());
            $this->assertEquals(
                'Insufficient funds on balance',
                $exception->getErrors()[0]->getMessage()
            );
        }
    }

    public function testShouldRetrieveRefundStatusSuccessfully()
    {
        // given
        $this->testHttpClient->mockResponse('refund_status_success.json', 200);
        $this->client->setHttpClient($this->testHttpClient);
        $refundService = new Refund($this->client);
        $refundId = 'R3FU-UND-D8K-WZD';

        // when
        $response = $refundService->status($refundId);

        // then
        $this->assertEquals($refundId, $response->getRefundId());
        $this->assertEquals('NEW', $response->getStatus());
    }

    public function testShouldNotRetrievePaymentStatusSuccesfully()
    {
        // given
        $this->testHttpClient->mockResponse('refund_status_not_found.json', 404);
        $this->client->setHttpClient($this->testHttpClient);
        $refundService = new Refund($this->client);
        $refundId = 'R3FU-UND-D8K-WZD';

        // when
        try {
            $response = $refundService->status($refundId);
        } catch (PaynowException $exception) {
            // then
            $this->assertEquals(404, $exception->getCode());
            $this->assertEquals('NOT_FOUND', $exception->getErrors()[0]->getType());
            $this->assertEquals(
                'Could not find status for refund {refundId=R3FU-UND-D8K-WZD}',
                $exception->getErrors()[0]->getMessage()
            );
        }
    }
}
