# Paynow PHP SDK

[![Latest Version](https://img.shields.io/github/release/pay-now/paynow-php-sdk.svg?style=flat-square)](https://github.com/pay-now/paynow-php-sdk/releases)
[![Build Status](https://travis-ci.org/pay-now/paynow-php-sdk.svg?branch=master)](https://travis-ci.org/pay-now/paynow-php-sdk)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
<!--[![Total Downloads](https://img.shields.io/packagist/dt/pay-now/paynow-php-sdk.svg?style=flat-square)](https://packagist.org/packages/pay-now/paynow-php-sdk)-->

Paynow PHP Library provides access to Paynow API from Applications written in PHP language. 

## Installation

### Composer
Install the library using [Composer](https://getcomposer.org)
```bash
$ composer require pay-now/paynow-php-sdk
```
and include composer autoloader
```php
require_once('vendor/autoload.php');
```

## Usage
Making a payment
```php
$client = new \Paynow\Client('TestApiKey', 'TestSignatureKey', Environment::SANDBOX);
$orderReference = "success_1234567"
$idempotencyKey = uniqid($orderReference . '_');

$paymentData = [
    "amount" => "100",
    "currency" => "PLN",
    "externalId" => $orderReference,
    "description" => "Payment description",
    "buyer" => [
        "email" => "customer@domain.com"
    ]
];

$payment = new \Paynow\Service\Payment($client);
$result = $payment->authorize($paymentData, $idempotencyKey);
```

Handling notification with current payment status
```php
$payload = trim(Tools::file_get_contents('php://input'));
$headers = getallheaders();
$notificationData = json_decode($payload, true);

try {
    new \Paynow\Notification('TestSignatureKey', $payload, $headers);
    // process notification with $notificationData
} catch (\Exception $exception) {
    header('HTTP/1.1 400 Bad Request', true, 400);
}

header('HTTP/1.1 202 Accepted', true, 202);
```

## Documentation
See the [Paynow API documentation](https://docs.paynow.pl)

## Support
If you have any problems, questions or suggestions contact with us on [Slack](https://pay-now.slack.com)

## License
MIT license. For more information see the [LICENSE file](LICENSE)