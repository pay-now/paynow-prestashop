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
            queryStr = window.location.search;
            urlParams = new URLSearchParams(queryStr);
            if ( urlParams.get('module') == 'paynow' && urlParams.get('controller') == 'confirmBlik' ) {
              queryStr = queryStr.replace('confirmBlik', 'status')
            }
            $.ajax({
                url: 'status' + queryStr,
                dataType: 'json',
                type: 'get',
                success: function (message) {
                    status.text(message.order_status);
                    if (message.payment_status !== "PENDING") {
                        clearInterval(pollPaymentStatus);
                        window.location.href = message.redirect_url;
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
