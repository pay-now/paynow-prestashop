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
    var paymentValidityTimeSwitchOn = $('#PAYNOW_PAYMENT_VALIDITY_TIME_ENABLED_on');
    var paymentValidityTimeSwitchOff = $('#PAYNOW_PAYMENT_VALIDITY_TIME_ENABLED_off');
    var paymentValidityTimeInput = $('input[name="PAYNOW_PAYMENT_VALIDITY_TIME"]');

    if (!paymentValidityTimeSwitchOn.is(':checked')) {
        paymentValidityTimeInput.prop('disabled', true);
    }

    paymentValidityTimeSwitchOn.on("change", function(){
        paymentValidityTimeInput.prop('disabled', false);
    });

    paymentValidityTimeSwitchOff.on("change", function(){
        paymentValidityTimeInput.prop('disabled', true);
    });
});
