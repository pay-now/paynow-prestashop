[**Wersja polska**][ext0]
# Paynow PrestaShop Plugin

Paynow plugin adds quick bank transfers and BLIK payment to PrestaShop.

This plugin supports PrestaShop 1.6.0 and higher.

## Table of Contents

* [Installation](#installation)
* [Configuration](#configuration)
* [FAQ](#FAQ)
* [Sandbox](#sandbox)
* [Support](#support)
* [License](#license)

## Installation
1. Download the plugin from [Github repository][ext1] to the local directory as zip file
2. Unzip the locally downloaded file
3. Rename the unzipped folder to `paynow`
4. Create a zip archive of that folder named paynow.zip
5. Go to the PrestaShop administration page
6. Go to `Modules > Module Manager`

![Installation step 6][ext3]

7. Use the `Upload a module` option in the top right corner and point to the archive containing the plugin (created in step 3)

![Installation step 7][ext4]

8. Upload the plugin

## Configuration
1. Go to the PrestaShop administration page
2. Go to `Modules > Module Manager`
3. Search and select `Paynow` and click `Configure`

![Configuration step 3][ext5]

4. Credential Keys can be found in `Settings > Shops and poses > Authentication data` in Paynow merchant panel

![Configuration step 4][ext6]

5. Depending on the environment you want to connect with go to section `Production configuration` or `Sandbox configuration` and type `Api-Key` and `Signature-Key` in proper fields

![Configuration step 5][ext7]

## Sandbox
To be able to test our Paynow Sandbox environment register [here][ext2]

## Support
If you have any questions or issues, please contact our support at support@paynow.pl.

If you wish to learn more about Paynow visit our website: https://www.paynow.pl/

## License
MIT license. For more information, see the LICENSE file.

[ext0]: README.md
[ext1]: https://github.com/pay-now/paynow-prestashop/releases/latest
[ext2]: https://panel.sandbox.paynow.pl/auth/register
[ext3]: instruction/step1_EN.png
[ext4]: instruction/step2_EN.png
[ext5]: instruction/step3_EN.png
[ext6]: instruction/step4.png
[ext7]: instruction/step5_EN.png