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
(function () {
    let status = $('.paynow-confirm-blik .status'),
        redirectToReturn = function () {
            window.location.replace('return' + window.location.search)
        },

        pollPaymentStatus = setInterval(function () {
            checkPaymentStatus();
        }, 3000),

        checkPaymentStatus = function () {
            $.ajax({
                url: 'status' + window.location.search,
                dataType: 'json',
                type: 'get',
                success: function (message) {
                    status.text(message.order_status);
                    if (message.payment_status === "CONFIRMED") {
                        clearInterval(pollPaymentStatus);
                        window.location.replace(message.redirect_url);
                    }
                },
                error: function () {
                    redirectToReturn();
                }
            });
        };

    setTimeout(() => {
        redirectToReturn()
    }, 60000);
})();