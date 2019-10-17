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
        $this->assertNotEmpty($response->redirectUrl);
        $this->assertNotEmpty($response->paymentId);
        $this->assertNotEmpty($response->status);
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
            $this->assertEquals('VALIDATION_ERROR', $exception->getErrors()[0]->errorType);
            $this->assertEquals(
                'currency: invalid field value (EUR)',
                $exception->getErrors()[0]->message
            );
        }
    }

    public function testShouldRetrievePaymentStatusSuccessfully()
    {
        // given
        $this->testHttpClient->mockResponse('payment_status.json', 200);
        $this->client->setHttpClient($this->testHttpClient);
        $paymentService = new Payment($this->client);
        $paymentId = 'PBYV-3AZ-UPW-DPC';

        // when
        $response = $paymentService->status($paymentId);

        // then
        $this->assertEquals($response->paymentId, $paymentId);
        $this->assertEquals($response->status, 'NEW');
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
            $this->assertEquals('NOT_FOUND', $exception->getErrors()[0]->errorType);
            $this->assertEquals(
                'Could not find status for payment {paymentId=PBYV-3AZ-UPW-DPCf}',
                $exception->getErrors()[0]->message
            );
        }
    }
}
