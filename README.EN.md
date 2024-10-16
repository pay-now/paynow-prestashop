[**Wersja polska**][ext0]

# PrestaShop Plugin for paynow gateway

The `paynow` plugin adds quick bank transfers, BLIK and cards payments to PrestaShop.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [FAQ](#FAQ)
- [Sandbox](#sandbox)
- [Support](#support)
- [License](#license)

## Prerequisites

- PHP since 7.2
- PrestaShop 1.6.0 and higher

## Installation

See also [the instructional video][ext8].

1. Download the paynow.zip file from [Github repository][ext1] and save to your computer
2. Go to the PrestaShop administration page
3. Go to `Modules > Module Manager`

![Installation step 6][ext3]

4. Use the `Upload a module` option in the top right corner and point to the archive containing the plugin (downloaded in step 1)

![Installation step 7][ext4]

5. Upload the plugin

## Configuration

1. Go to the PrestaShop administration page
2. Go to `Modules > Module Manager`
3. Search and select `paynow` and click `Configure`

![Configuration step 3][ext5]

4. Production credential keys can be found in the tab `My business > Paynow > Settings > Shops and poses > Authentication data` in the mBank online banking.

   Sandbox credential keys can be found in `Settings > Shops and poses > Authentication data` in the [sandbox panel][ext10].

![Configuration step 4a][ext6]
![Configuration step 4b][ext11]

5. Depending on the environment you want to connect to, go to the `Production configuration` or the `Sandbox configuration` section and type `Api Key` and `API Signature Key` in the proper fields

![Configuration step 5][ext7]

## FAQ

**How to configure the return address?**

The return address will be set automatically for each order. There is no need to manually configure this address.

**How to configure the notification address?**

In the paynow merchant panel go to the tab `Settings > Shops and poses`, in the field `Notification address` set the address: `https://twoja-domena.pl/module/paynow/notifications`.

![Configuration of the notifiction address][ext9]

## Sandbox

To be able to test our paynow Sandbox environment, register [here][ext2].

## Support

If you have any questions or issues, please contact our support at support@paynow.pl.

If you wish to learn more about paynow, visit our website: https://www.paynow.pl/.

## License

MIT license. For more information, see the LICENSE file.

[ext0]: README.md
[ext1]: https://github.com/pay-now/paynow-prestashop/releases/latest
[ext2]: https://panel.sandbox.paynow.pl/auth/register
[ext3]: instruction/step1_EN.png
[ext4]: instruction/step2_EN.png
[ext5]: instruction/step3_EN.png
[ext6]: instruction/step4a.png
[ext7]: instruction/step5_EN.png
[ext8]: https://paynow.wistia.com/medias/nym9wdwdwl
[ext9]: instruction/step6.png
[ext10]: https://panel.sandbox.paynow.pl/merchant/payments
[ext11]: instruction/step4b.png
