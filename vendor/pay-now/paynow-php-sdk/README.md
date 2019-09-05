# Paynow PHP SDK

Paynow PHP Library provides access to Paynow API from Applications written in PHP language. 

## Installation

### Composer
Install the library using Composer
```bash
$ composer require pay-now/paynow-php-sdk
```
and include composer autoloader
```php
require_once('vendor/autoload.php');
```

### Manual installation
You can download the [latest release](https://github.com/pay-now/paynow-php-sdk/releases)

## Usage
Make a payment on Sandbox environment
```php
$client = new \Paynow\Client('TestApiKey', 'TestSignatureKey', Environment::SANDBOX);

$data = [
    "amount": "100",
    "currency": "PLN",
    "externalId": "success_1234567",
    "description": "Test payment",
    "buyer" => [
        "email": "customer@domain.com"
    ]
];
$payment = new \Paynow\Payment($client);
$result = $payment->authorize($data);
```

## Documentation
See the Paynow API [documentation](https://docs.paynow.pl)

## Support
If you have any problems, questions or suggestions contact with us on [Slack](https://pay-now.slack.com)

## License
MIT license. For more information open the [LICENSE](LICENSE) file