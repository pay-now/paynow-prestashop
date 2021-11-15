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
    $('input[name="payment-option"]').on("change", function () {
        setTimeout(function () {
            enableBlikValidation()
            enablePblValidation();
        }, 200);
    });
});

function enableBlikValidation() {
    let blik_code_input = $('#paynow_blik_code');
    if (blik_code_input.is(':visible')) {
        validateBlik(blik_code_input.val())
    } else {
        $('#payment-confirmation button').prop('disabled', false);
    }
    blik_code_input.keyup(function () {
        validateBlik(blik_code_input.val())
    });
}

function enablePblValidation() {
    let payment_button = $('#payment-confirmation button');
    if ($('.paynow-payment-pbls').is(':visible')) {
        payment_button.prop('disabled', $('input[name="paymentMethodId"]:checked').length === 0)
    }
    $('input[name="paymentMethodId"]').on('change', function () {
        payment_button.prop('disabled', $('input[name="paymentMethodId"]:checked').length === 0)
    })
}

function validateBlik(blik_code_value) {
    let payment_button = $('#payment-confirmation button');
    if (blik_code_value.length === 6 && !isNaN(parseInt(blik_code_value)) && parseInt(blik_code_value)) {
        payment_button.prop('disabled', false);
    } else {
        payment_button.prop('disabled', true);
        $('#paynow_blik_code').focus();
    }
}