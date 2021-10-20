## 2.1.3
- Added new statuses for payment

## 2.1.2
- Added PSR17 client discovery support

## 2.1.1
- Added filters to retrieve payment methods
- Added Google Pay to payment method types
- Updated dependencies

## 2.1.0
- Added payment's refund support
- Added retrieve available payment methods

## 2.0.2
- Initialize `$errors` in `PaynowException` as empty list

## 2.0.1
- Fixed PHP version in composer.json
- Fixed typo in `Payment`

## 2.0.0
- Introduced PSR-17 and PSR-18 to HTTP Client
- Updated README

**Breaking Changes:**
- Changed type of `$errors` in `PaynowException` to `Error`
- Changed the name of method `getErrorType` for `Error`
- Changed type of `$data` to string for `SignatureCalculator`
- Changed `Payment::authorize` response to `Authorize`
- Changed `Payment::status` response to `Status`
- Required PHP since 7.1

## 1.0.6
- Marked `getErrorType` for `Error` as deprecated

## 1.0.5
- Fixed missing headers for payment status

## 1.0.4
- Added support for `signature` from headers

## 1.0.3
- Fixed dependencies
- Fixed examples for README file
- Added Travis CI support

## 1.0.2
- Changed Http Client

## 1.0.1
- Added implicit Idempotency Key for payment authorize
- Added Payment Status enums
- Removed User-agent version

## 1.0.0
- Initial release
