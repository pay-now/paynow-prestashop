/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License (MIT)
 * that is bundled with this package in the file LICENSE.md.
 *
 * @author mElements S.A.
 * @copyright mElements S.A.
 * @license   MIT License
 **/
$(document).ready(function () {
    let paymentValidityTimeSwitchOn = $('#PAYNOW_PAYMENT_VALIDITY_TIME_ENABLED_on');
    let paymentValidityTimeSwitchOnLabel = $('label[for="PAYNOW_PAYMENT_VALIDITY_TIME_ENABLED_on"]');
    let paymentValidityTimeSwitchOffLabel = $('label[for="PAYNOW_PAYMENT_VALIDITY_TIME_ENABLED_off"]');
    let paymentValidityTimeInput = $('input[name="PAYNOW_PAYMENT_VALIDITY_TIME"]');

    if (!paymentValidityTimeSwitchOn.is(':checked')) {
        paymentValidityTimeInput.prop('disabled', true);
    }

    paymentValidityTimeSwitchOnLabel.on("click", function () {
        paymentValidityTimeInput.prop('disabled', false);
    });

    paymentValidityTimeSwitchOffLabel.on("click", function () {
        paymentValidityTimeInput.prop('disabled', true);
    })
});
