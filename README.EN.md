[**Wersja polska**][ext0]
# Paynow PrestaShop Plugin

Paynow plugin adds quick bank transfers and BLIK payment to PrestaShop.

This plugin supports PrestaShop 1.6.0 and higher.

## Installation
1. Download the plugin from [Github repository][ext1] to the local directory as zip file
2. Unzip the locally downloaded file
3. Rename the unzipped folder to `paynow`
4. Create a zip archive of that folder named paynow.zip
5. Go to the PrestaShop administration page
6. Go to `Modules > Module Manager`

![Step 6][ext3]

7. Use the `Upload a module` option in the top right corner and point to the archive containing the plugin (created in step 3)
8. Upload the plugin

## Configuration
1. Go to the PrestaShop administration page
2. Go to `Modules > Module Manager`
3. Search and select `Paynow` and click `Configure`
4. Credential Keys can be found in `Settings > Shops and poses > Authentication data` in Paynow merchant panel
5. Depending on the environment you want to connect with go to section `Production configuration` or `Sandbox configuration` and type `Api-Key` and `Signature-Key` in proper fields

## Sandbox
To be able to test our Paynow Sandbox environment register [here][ext2]

## Support
If you have any questions or issues, please contact our support at support@paynow.pl.

## More info
If you wish to learn more about Paynow visit our website: https://www.paynow.pl/

## License
MIT license. For more information, see the LICENSE file.

[ext0]: README.md
[ext1]: https://github.com/pay-now/paynow-prestashop/releases/latest
[ext2]: https://panel.sandbox.paynow.pl/auth/register
[ext3]: instruction/step1.png