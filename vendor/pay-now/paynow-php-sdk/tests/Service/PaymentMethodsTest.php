<?php

namespace Paynow\Tests\Service;

use Paynow\Model\PaymentMethods\Status;
use Paynow\Model\PaymentMethods\Type;
use Paynow\Service\Payment;
use Paynow\Tests\TestCase;

class PaymentMethodsTest extends TestCase
{
    public function testShouldRetrieveAllPaymentMethodsListSuccessfully()
    {
        // given
        $this->testHttpClient->mockResponse('payment_methods_success.json', 200);
        $this->client->setHttpClient($this->testHttpClient);
        $paymentService = new Payment($this->client);

        // when
        $paymentMethods = $paymentService->getPaymentMethods('PLN', 1000)->getAll();

        // then
        $this->assertNotEmpty($paymentMethods);
        $this->assertEquals('2007', $paymentMethods[0]->getId());
        $this->assertEquals('BLIK', $paymentMethods[0]->getName());
        $this->assertEquals('https://static.sandbox.paynow.pl/payment-method-icons/2007.png', $paymentMethods[0]->getImage());
        $this->assertEquals('Płacę z Blikiem', $paymentMethods[0]->getDescription());
        $this->assertEquals(Type::BLIK, $paymentMethods[0]->getType());
        $this->assertEquals(Status::ENABLED, $paymentMethods[0]->getStatus());
        $this->assertTrue($paymentMethods[0]->isEnabled());
    }

    public function testShouldRetrieveOnlyBlikPaymentMethodsListSuccessfully()
    {
        // given
        $this->testHttpClient->mockResponse('payment_methods_success.json', 200);
        $this->client->setHttpClient($this->testHttpClient);
        $paymentService = new Payment($this->client);

        // when
        $blikPaymentMethods = $paymentService->getPaymentMethods("PLN")->getOnlyBlik();

        // then
        $this->assertNotEmpty($blikPaymentMethods);
        $this->assertEquals(1, sizeof($blikPaymentMethods));
        $this->assertEquals('BLIK', $blikPaymentMethods[0]->getName());
        $this->assertEquals(Type::BLIK, $blikPaymentMethods[0]->getType());
        $this->assertTrue($blikPaymentMethods[0]->isEnabled());
    }

    public function testShouldRetrieveOnlyCardPaymentMethodsListSuccessfully()
    {
        // given
        $this->testHttpClient->mockResponse('payment_methods_success.json', 200);
        $this->client->setHttpClient($this->testHttpClient);
        $paymentService = new Payment($this->client);

        // when
        $cardPaymentMethods = $paymentService->getPaymentMethods()->getOnlyCards();

        // then
        $this->assertNotEmpty($cardPaymentMethods);
        $this->assertEquals(1, sizeof($cardPaymentMethods));
        $this->assertEquals('Karta płatnicza', $cardPaymentMethods[0]->getName());
        $this->assertEquals(Type::CARD, $cardPaymentMethods[0]->getType());
        $this->assertTrue($cardPaymentMethods[0]->isEnabled());
    }

    public function testShouldRetrieveOnlyGooglePayPaymentMethodsListSuccessfully()
    {
        // given
        $this->testHttpClient->mockResponse('payment_methods_success.json', 200);
        $this->client->setHttpClient($this->testHttpClient);
        $paymentService = new Payment($this->client);

        // when
        $cardPaymentMethods = $paymentService->getPaymentMethods()->getOnlyGooglePay();

        // then
        $this->assertNotEmpty($cardPaymentMethods);
        $this->assertEquals(1, sizeof($cardPaymentMethods));
        $this->assertEquals('Google Pay', $cardPaymentMethods[0]->getName());
        $this->assertEquals(Type::GOOGLE_PAY, $cardPaymentMethods[0]->getType());
        $this->assertTrue($cardPaymentMethods[0]->isEnabled());
    }

    public function testShouldRetrieveOnlyPblPaymentMethodsListSuccessfully()
    {
        // given
        $this->testHttpClient->mockResponse('payment_methods_success.json', 200);
        $this->client->setHttpClient($this->testHttpClient);
        $paymentService = new Payment($this->client);

        // when
        $pblPaymentMethods = $paymentService->getPaymentMethods()->getOnlyPbls();

        // then
        $this->assertNotEmpty($pblPaymentMethods);
        $this->assertEquals(3, sizeof($pblPaymentMethods));
        $this->assertEquals('mTransfer', $pblPaymentMethods[0]->getName());
        $this->assertEquals(Type::PBL, $pblPaymentMethods[0]->getType());
        $this->assertTrue($pblPaymentMethods[0]->isEnabled());
    }
}
