<?php

namespace Paynow\Tests\Service;

use Paynow\Exception\PaynowException;
use Paynow\Service\Payment;
use Paynow\Tests\TestCase;

class PaymentTest extends TestCase
{
    public function testShouldAuthorizePaymentSuccessfully()
    {
        // given
        $this->testHttpClient->mockResponse('payment_success.json', 200);
        $this->client->setHttpClient($this->testHttpClient);
        $paymentService = new Payment($this->client);
        $paymentData = $this->loadData('payment_request.json');

        // when
        $response = $paymentService->authorize($paymentData, 'idempotencyKey123');

        // then
        $this->assertNotEmpty($response->getRedirectUrl());
        $this->assertNotEmpty($response->getPaymentId());
        $this->assertNotEmpty($response->getStatus());
    }

    public function testShouldNotAuthorizePaymentSuccessfully()
    {
        // given
        $this->testHttpClient->mockResponse('payment_failed.json', 400);
        $this->client->setHttpClient($this->testHttpClient);

        $paymentData = $this->loadData('payment_request.json');
        $paymentService = new Payment($this->client);

        // when
        try {
            $response = $paymentService->authorize($paymentData, 'idempotencyKey123');
        } catch (PaynowException $exception) {
            // then
            $this->assertEquals(400, $exception->getCode());
            $this->assertEquals('VALIDATION_ERROR', $exception->getErrors()[0]->getType());
            $this->assertEquals(
                'currency: invalid field value (EUR)',
                $exception->getErrors()[0]->getMessage()
            );
        }
    }

    public function testShouldRetrievePaymentStatusSuccessfully()
    {
        // given
        $this->testHttpClient->mockResponse('payment_status_success.json', 200);
        $this->client->setHttpClient($this->testHttpClient);
        $paymentService = new Payment($this->client);
        $paymentId = 'PBYV-3AZ-UPW-DPC';

        // when
        $response = $paymentService->status($paymentId);

        // then
        $this->assertEquals($paymentId, $response->getPaymentId());
        $this->assertEquals('NEW', $response->getStatus());
    }

    public function testShouldNotRetrievePaymentStatusSuccesfully()
    {
        // given
        $this->testHttpClient->mockResponse('payment_status_not_found.json', 404);
        $this->client->setHttpClient($this->testHttpClient);
        $paymentService = new Payment($this->client);
        $paymentId = 'PBYV-3AZ-UPW-DPC';

        // when
        try {
            $response = $paymentService->status($paymentId);
        } catch (PaynowException $exception) {
            // then
            $this->assertEquals(404, $exception->getCode());
            $this->assertEquals('NOT_FOUND', $exception->getErrors()[0]->getType());
            $this->assertEquals(
                'Could not find status for payment {paymentId=PBYV-3AZ-UPW-DPC}',
                $exception->getErrors()[0]->getMessage()
            );
        }
    }
}
