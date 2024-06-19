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
// jQuery Mask Plugin v1.14.16
// github.com/igorescobar/jQuery-Mask-Plugin
var $jscomp=$jscomp||{};$jscomp.scope={};$jscomp.findInternal=function(a,n,f){a instanceof String&&(a=String(a));for(var p=a.length,k=0;k<p;k++){var b=a[k];if(n.call(f,b,k,a))return{i:k,v:b}}return{i:-1,v:void 0}};$jscomp.ASSUME_ES5=!1;$jscomp.ASSUME_NO_NATIVE_MAP=!1;$jscomp.ASSUME_NO_NATIVE_SET=!1;$jscomp.SIMPLE_FROUND_POLYFILL=!1;
$jscomp.defineProperty=$jscomp.ASSUME_ES5||"function"==typeof Object.defineProperties?Object.defineProperty:function(a,n,f){a!=Array.prototype&&a!=Object.prototype&&(a[n]=f.value)};$jscomp.getGlobal=function(a){return"undefined"!=typeof window&&window===a?a:"undefined"!=typeof global&&null!=global?global:a};$jscomp.global=$jscomp.getGlobal(this);
$jscomp.polyfill=function(a,n,f,p){if(n){f=$jscomp.global;a=a.split(".");for(p=0;p<a.length-1;p++){var k=a[p];k in f||(f[k]={});f=f[k]}a=a[a.length-1];p=f[a];n=n(p);n!=p&&null!=n&&$jscomp.defineProperty(f,a,{configurable:!0,writable:!0,value:n})}};$jscomp.polyfill("Array.prototype.find",function(a){return a?a:function(a,f){return $jscomp.findInternal(this,a,f).v}},"es6","es3");
(function(a,n,f){"function"===typeof define&&define.amd?define(["jquery"],a):"object"===typeof exports&&"undefined"===typeof Meteor?module.exports=a(require("jquery")):a(n||f)})(function(a){var n=function(b,d,e){var c={invalid:[],getCaret:function(){try{var a=0,r=b.get(0),h=document.selection,d=r.selectionStart;if(h&&-1===navigator.appVersion.indexOf("MSIE 10")){var e=h.createRange();e.moveStart("character",-c.val().length);a=e.text.length}else if(d||"0"===d)a=d;return a}catch(C){}},setCaret:function(a){try{if(b.is(":focus")){var c=
        b.get(0);if(c.setSelectionRange)c.setSelectionRange(a,a);else{var g=c.createTextRange();g.collapse(!0);g.moveEnd("character",a);g.moveStart("character",a);g.select()}}}catch(B){}},events:function(){b.on("keydown.mask",function(a){b.data("mask-keycode",a.keyCode||a.which);b.data("mask-previus-value",b.val());b.data("mask-previus-caret-pos",c.getCaret());c.maskDigitPosMapOld=c.maskDigitPosMap}).on(a.jMaskGlobals.useInput?"input.mask":"keyup.mask",c.behaviour).on("paste.mask drop.mask",function(){setTimeout(function(){b.keydown().keyup()},
        100)}).on("change.mask",function(){b.data("changed",!0)}).on("blur.mask",function(){f===c.val()||b.data("changed")||b.trigger("change");b.data("changed",!1)}).on("blur.mask",function(){f=c.val()}).on("focus.mask",function(b){!0===e.selectOnFocus&&a(b.target).select()}).on("focusout.mask",function(){e.clearIfNotMatch&&!k.test(c.val())&&c.val("")})},getRegexMask:function(){for(var a=[],b,c,e,t,f=0;f<d.length;f++)(b=l.translation[d.charAt(f)])?(c=b.pattern.toString().replace(/.{1}$|^.{1}/g,""),e=b.optional,
        (b=b.recursive)?(a.push(d.charAt(f)),t={digit:d.charAt(f),pattern:c}):a.push(e||b?c+"?":c)):a.push(d.charAt(f).replace(/[-\/\\^$*+?.()|[\]{}]/g,"\\$&"));a=a.join("");t&&(a=a.replace(new RegExp("("+t.digit+"(.*"+t.digit+")?)"),"($1)?").replace(new RegExp(t.digit,"g"),t.pattern));return new RegExp(a)},destroyEvents:function(){b.off("input keydown keyup paste drop blur focusout ".split(" ").join(".mask "))},val:function(a){var c=b.is("input")?"val":"text";if(0<arguments.length){if(b[c]()!==a)b[c](a);
        c=b}else c=b[c]();return c},calculateCaretPosition:function(a){var d=c.getMasked(),h=c.getCaret();if(a!==d){var e=b.data("mask-previus-caret-pos")||0;d=d.length;var g=a.length,f=a=0,l=0,k=0,m;for(m=h;m<d&&c.maskDigitPosMap[m];m++)f++;for(m=h-1;0<=m&&c.maskDigitPosMap[m];m--)a++;for(m=h-1;0<=m;m--)c.maskDigitPosMap[m]&&l++;for(m=e-1;0<=m;m--)c.maskDigitPosMapOld[m]&&k++;h>g?h=10*d:e>=h&&e!==g?c.maskDigitPosMapOld[h]||(e=h,h=h-(k-l)-a,c.maskDigitPosMap[h]&&(h=e)):h>e&&(h=h+(l-k)+f)}return h},behaviour:function(d){d=
        d||window.event;c.invalid=[];var e=b.data("mask-keycode");if(-1===a.inArray(e,l.byPassKeys)){e=c.getMasked();var h=c.getCaret(),g=b.data("mask-previus-value")||"";setTimeout(function(){c.setCaret(c.calculateCaretPosition(g))},a.jMaskGlobals.keyStrokeCompensation);c.val(e);c.setCaret(h);return c.callbacks(d)}},getMasked:function(a,b){var h=[],f=void 0===b?c.val():b+"",g=0,k=d.length,n=0,p=f.length,m=1,r="push",u=-1,w=0;b=[];if(e.reverse){r="unshift";m=-1;var x=0;g=k-1;n=p-1;var A=function(){return-1<
        g&&-1<n}}else x=k-1,A=function(){return g<k&&n<p};for(var z;A();){var y=d.charAt(g),v=f.charAt(n),q=l.translation[y];if(q)v.match(q.pattern)?(h[r](v),q.recursive&&(-1===u?u=g:g===x&&g!==u&&(g=u-m),x===u&&(g-=m)),g+=m):v===z?(w--,z=void 0):q.optional?(g+=m,n-=m):q.fallback?(h[r](q.fallback),g+=m,n-=m):c.invalid.push({p:n,v:v,e:q.pattern}),n+=m;else{if(!a)h[r](y);v===y?(b.push(n),n+=m):(z=y,b.push(n+w),w++);g+=m}}a=d.charAt(x);k!==p+1||l.translation[a]||h.push(a);h=h.join("");c.mapMaskdigitPositions(h,
        b,p);return h},mapMaskdigitPositions:function(a,b,d){a=e.reverse?a.length-d:0;c.maskDigitPosMap={};for(d=0;d<b.length;d++)c.maskDigitPosMap[b[d]+a]=1},callbacks:function(a){var g=c.val(),h=g!==f,k=[g,a,b,e],l=function(a,b,c){"function"===typeof e[a]&&b&&e[a].apply(this,c)};l("onChange",!0===h,k);l("onKeyPress",!0===h,k);l("onComplete",g.length===d.length,k);l("onInvalid",0<c.invalid.length,[g,a,b,c.invalid,e])}};b=a(b);var l=this,f=c.val(),k;d="function"===typeof d?d(c.val(),void 0,b,e):d;l.mask=
    d;l.options=e;l.remove=function(){var a=c.getCaret();l.options.placeholder&&b.removeAttr("placeholder");b.data("mask-maxlength")&&b.removeAttr("maxlength");c.destroyEvents();c.val(l.getCleanVal());c.setCaret(a);return b};l.getCleanVal=function(){return c.getMasked(!0)};l.getMaskedVal=function(a){return c.getMasked(!1,a)};l.init=function(g){g=g||!1;e=e||{};l.clearIfNotMatch=a.jMaskGlobals.clearIfNotMatch;l.byPassKeys=a.jMaskGlobals.byPassKeys;l.translation=a.extend({},a.jMaskGlobals.translation,e.translation);
    l=a.extend(!0,{},l,e);k=c.getRegexMask();if(g)c.events(),c.val(c.getMasked());else{e.placeholder&&b.attr("placeholder",e.placeholder);b.data("mask")&&b.attr("autocomplete","off");g=0;for(var f=!0;g<d.length;g++){var h=l.translation[d.charAt(g)];if(h&&h.recursive){f=!1;break}}f&&b.attr("maxlength",d.length).data("mask-maxlength",!0);c.destroyEvents();c.events();g=c.getCaret();c.val(c.getMasked());c.setCaret(g)}};l.init(!b.is("input"))};a.maskWatchers={};var f=function(){var b=a(this),d={},e=b.attr("data-mask");
    b.attr("data-mask-reverse")&&(d.reverse=!0);b.attr("data-mask-clearifnotmatch")&&(d.clearIfNotMatch=!0);"true"===b.attr("data-mask-selectonfocus")&&(d.selectOnFocus=!0);if(p(b,e,d))return b.data("mask",new n(this,e,d))},p=function(b,d,e){e=e||{};var c=a(b).data("mask"),f=JSON.stringify;b=a(b).val()||a(b).text();try{return"function"===typeof d&&(d=d(b)),"object"!==typeof c||f(c.options)!==f(e)||c.mask!==d}catch(w){}},k=function(a){var b=document.createElement("div");a="on"+a;var e=a in b;e||(b.setAttribute(a,
    "return;"),e="function"===typeof b[a]);return e};a.fn.mask=function(b,d){d=d||{};var e=this.selector,c=a.jMaskGlobals,f=c.watchInterval;c=d.watchInputs||c.watchInputs;var k=function(){if(p(this,b,d))return a(this).data("mask",new n(this,b,d))};a(this).each(k);e&&""!==e&&c&&(clearInterval(a.maskWatchers[e]),a.maskWatchers[e]=setInterval(function(){a(document).find(e).each(k)},f));return this};a.fn.masked=function(a){return this.data("mask").getMaskedVal(a)};a.fn.unmask=function(){clearInterval(a.maskWatchers[this.selector]);
    delete a.maskWatchers[this.selector];return this.each(function(){var b=a(this).data("mask");b&&b.remove().removeData("mask")})};a.fn.cleanVal=function(){return this.data("mask").getCleanVal()};a.applyDataMask=function(b){b=b||a.jMaskGlobals.maskElements;(b instanceof a?b:a(b)).filter(a.jMaskGlobals.dataMaskAttr).each(f)};k={maskElements:"input,td,span,div",dataMaskAttr:"*[data-mask]",dataMask:!0,watchInterval:300,watchInputs:!0,keyStrokeCompensation:10,useInput:!/Chrome\/[2-4][0-9]|SamsungBrowser/.test(window.navigator.userAgent)&&
        k("input"),watchDataMask:!1,byPassKeys:[9,16,17,18,36,37,38,39,40,91],translation:{0:{pattern:/\d/},9:{pattern:/\d/,optional:!0},"#":{pattern:/\d/,recursive:!0},A:{pattern:/[a-zA-Z0-9]/},S:{pattern:/[a-zA-Z]/}}};a.jMaskGlobals=a.jMaskGlobals||{};k=a.jMaskGlobals=a.extend(!0,{},k,a.jMaskGlobals);k.dataMask&&a.applyDataMask();setInterval(function(){a.jMaskGlobals.watchDataMask&&a.applyDataMask()},k.watchInterval)},window.jQuery,window.Zepto);


var paynow = {

    config: {
        allTermsHaveToBeChecked: false,
        useCssClassDisabled: false,
        validateTerms: true
    },

    selectors: {
        form: '.paynow-blik-form',
        terms: '#conditions_to_approve\\[terms-and-conditions\\], #cgv',
        termsLabel: 'label[for="conditions_to_approve\\[terms-and-conditions\\]"], label[for="cgv"]',
        termsErrorLabel: '#js-paynow-terms-error',
        paymentButton: '#payment-confirmation button',
        blikCode: '#paynow_blik_code',
        blikButton: 'form.paynow-blik-form div.paynow-payment-option-container button',
        blikErrorLabel: 'form.paynow-blik-form span.error',

        pblMethod: 'div.paynow-payment-pbls input[name="paymentMethodId"]',
        cardMethod: 'div.paynow-payment-card input[name="paymentMethodToken"]',
        cardMethodOptions: 'div.paynow-payment-card .paynow-payment-card-option',
        paymentMethod: 'input[name="payment-option"]',

        cardMethodMiniMenuOpen: '.paynow-payment-card-menu .paynow-payment-card-menu-button',
        cardMethodRemove: '[data-remove-saved-instrument]',
    },

    init: function(){
        paynow.overrideDefaults();

        paynow.config.useCssClassDisabled = $(paynow.selectors.paymentButton).hasClass('disabled');

        $(document).on('click', paynow.selectors.blikButton, paynow.blikFormSubmit);
        $(document).on('keyup', paynow.selectors.blikCode, paynow.blikValidate);
        $(document).on('change', paynow.selectors.pblMethod, paynow.pblValidate);
        $(document).on('change', paynow.selectors.cardMethod, paynow.cardValidate);
        $(document).on('change', paynow.selectors.paymentMethod, paynow.onPaymentOptionChange);
        $(document).on('change', paynow.selectors.terms, function(){
            paynow.onPaymentOptionChange()
            paynow.termsValidate()
        });
        $(document).on('click', paynow.selectors.cardMethodMiniMenuOpen, paynow.toggleCardMiniMenu);
        $(document).on('click', paynow.selectors.cardMethodRemove, paynow.removeSavedInstrument);
        $(document).on('click', function (ev) {
            paynow.closeMiniMenu(ev)
        });

        var termsErrorPlaceholderExists = $(paynow.selectors.terms).length !== 0
            && $(paynow.selectors.termsLabel).length !== 0
            && $(paynow.selectors.termsErrorLabel).length === 0

        if (termsErrorPlaceholderExists) {
            $(paynow.selectors.termsLabel).after('<span id="js-paynow-terms-error" class="paynow-terms-error"></span>')
        }

        paynow.addApplePayEnabledToCookie();
        paynow.addFingerprintToCardPayment();
    },

    overrideDefaults: function() {
        if (!window.hasOwnProperty('paynowOverrides')) {
            return;
        }

        if (window.paynowOverrides.hasOwnProperty('selectors')) {
            for (const [key, value] of Object.entries(window.paynowOverrides.selectors)) {
                if (paynow.selectors.hasOwnProperty(key)) {
                    paynow.selectors[key] = value;
                }
            }
        }

        if (window.paynowOverrides.hasOwnProperty('config')) {
            for (const [key, value] of Object.entries(window.paynowOverrides.config)) {
                if (paynow.config.hasOwnProperty(key)) {
                    paynow.config[key] = value;
                }
            }
        }
    },

    termsValidate: function() {
        if (paynow.config.validateTerms == false) {
            return true
        }

        if (paynow.isTermsChecked()) {
            $(paynow.selectors.termsErrorLabel).text('')
            return true
        } else {
            return false
        }
    },

    blikFormSubmit: function (e) {
        if (e && e.preventDefault) {
            e.preventDefault()
        }

        $(paynow.selectors.blikErrorLabel).text('')
        $(paynow.selectors.termsErrorLabel).text('')

        if (paynow.termsValidate() == false) {
            $(paynow.selectors.termsErrorLabel).text($(paynow.selectors.form).data('terms-message'))
            prestashop.emit('paynow_event_blik_submit_fail', {
                type: 'terms_not_accepted',
            })
            return
        }

        if (paynow.isOnePageCheckout()) {
            paynow.triggerOnePageCheckoutPlaceOrder()
            return;
        }

        paynow.blikButton.disable()
        paynow.chargeBlik(
            function (data, textStatus, jqXHR) {
                window.location.href = data.redirect_url
            },
            function (data, textStatus, jqXHR) {
                paynow.blikButton.enable()
                $(paynow.selectors.blikErrorLabel).text(data.message)
                prestashop.emit('paynow_event_blik_submit_fail', {
                    type: 'xhr_ok_but_error',
                    response: data,
                    textStatus: textStatus,
                    jqXHR: jqXHR,
                })
            },
            function (jqXHR, textStatus, errorThrown) {
                paynow.blikButton.enable()
                $(paynow.selectors.blikErrorLabel).text($(paynow.selectors.form).data('error-message'))
                prestashop.emit('paynow_event_blik_submit_fail', {
                    type: 'xhr_error',
                    jqXHR: jqXHR,
                    textStatus: textStatus,
                    errorThrown: errorThrown
                })
            }
        )
    },

    onPaymentOptionChange: function () {
        if ($(paynow.selectors.blikCode).length === 1) {
            $(paynow.selectors.blikCode).mask('000 000', {placeholder: "___ ___"})
        }

        if ($(paynow.selectors.form).data('blik-autofocus') === '1') {
            $(paynow.selectors.blikCode).focus();
        }

        if ($(paynow.selectors.blikCode).is(':visible')) {
            paynow.paymentButton.disable()
            paynow.paymentButton.hide()
        } else if ($(paynow.selectors.cardMethodOptions).is(':visible') && !$(paynow.selectors.cardMethod + ':checked').length) {
            paynow.paymentButton.disable()
        } else if ($('div.paynow-payment-pbls .paynow-payment-option-pbl').is(':visible')) {
            paynow.pblValidate()
        } else {
            paynow.paymentButton.enable()
            paynow.paymentButton.show()
        }
    },

    // backward compatibility
    blikFormPrepare: function() {
        paynow.onPaymentOptionChange()
    },

    blikValidate: function () {
        const blik_code_value = $(paynow.selectors.blikCode).val().replace(/\s/g, '');

        if (blik_code_value.length === 6 && !isNaN(parseInt(blik_code_value)) && parseInt(blik_code_value)) {
            $(paynow.selectors.blikErrorLabel).text('');
            paynow.blikButton.enable()
            return true
        } else {
            paynow.blikButton.disable()
            return false
        }
    },

    cardValidate: function () {
        const checkedCardOption = $(paynow.selectors.cardMethod + ':checked');

        if (checkedCardOption.length && (!paynow.config.validateTerms || $(paynow.selectors.terms).is(':checked'))) {
            paynow.paymentButton.enable();
            return true
        } else {
            paynow.paymentButton.disable();
            return false
        }
    },

    pblValidate: function () {
        if (!(paynow.config.validateTerms && !paynow.isTermsChecked()) && $(paynow.selectors.pblMethod + ':checked').length > 0) {
            paynow.paymentButton.enable();
            return true
        } else {
            paynow.paymentButton.disable();
            return false
        }
    },

    isOnePageCheckout: function () {
        // support for: Supercheckout by Knownband
        if ($('#velsof_supercheckout_form').length) {
            return true
        }

        return false
    },

    closeMiniMenu: function (e) {
        if (!$(e.target).is(paynow.selectors.cardMethodRemove) && !$(e.target).is(paynow.selectors.cardMethodMiniMenuOpen)) {
            $(paynow.selectors.cardMethodRemove).addClass('--hidden')
        }
    },

    toggleCardMiniMenu: function (e) {
        $(e.currentTarget).siblings().toggleClass('--hidden')
    },

    isTermsChecked: function () {
        if (paynow.config.allTermsHaveToBeChecked) {
            return !$(paynow.selectors.terms).is(':not(:checked)')
        } else {
            return $(paynow.selectors.terms).is(':checked')
        }
    },

    triggerOnePageCheckoutPlaceOrder: function () {
        // support for: Supercheckout by Knownband
        if ($('#velsof_supercheckout_form').length) {
            $("#supercheckout_confirm_order").trigger('click')
        }
    },

    chargeBlik: function (onSuccess, onFail, onError) {
        $.ajax($(paynow.selectors.form).data('action'), {
            method: 'POST', type: 'POST',
            data: {
                'blikCode': $(paynow.selectors.blikCode).val().replace(/\s/g, ""),
                'token': $(paynow.selectors.form).data('token')
            },
        }).success(function (data, textStatus, jqXHR) {
            if (data.success === true) {
                if (typeof onSuccess == 'function') {
                    onSuccess(data, textStatus, jqXHR)
                }
            } else {
                if (typeof onFail == 'function') {
                    onFail(data, textStatus, jqXHR)
                }
            }
        }).error(function (jqXHR, textStatus, errorThrown) {
            if (typeof onError == 'function') {
                onError(jqXHR, textStatus, errorThrown)
            }
        });
    },

    addApplePayEnabledToCookie: function () {
        let applePayEnabled = false;

        if (window.ApplePaySession) {
            applePayEnabled = window.ApplePaySession.canMakePayments();
        }

        document.cookie = 'applePayEnabled=' + (applePayEnabled ? '1' : '0');
    },

    addFingerprintToCardPayment: function () {
        const input = $('#payment-method-fingerprint');

        if (!input.length) {
            return;
        }

        try {
            const fpPromise = import('https://static.paynow.pl/scripts/PyG5QjFDUI.min.js')
                .then(FingerprintJS => FingerprintJS.load())

            fpPromise
                .then(fp => fp.get())
                .then(result => {
                    input.val(result.visitorId);
                })
        } catch (e) {
            console.error('Cannot get fingerprint');
        }
    },

    removeSavedInstrument: function (e) {
        const target = $(e.currentTarget);
        const savedInstrument = target.data('removeSavedInstrument');
        const errorMessage = target.data('errorMessage');
        const cardMethodOption = $('#wrapper-' + savedInstrument);

        cardMethodOption.addClass('loading');
        $.ajax(target.data('action'), {
            method: 'POST', type: 'POST',
            data: {
                'savedInstrumentToken': savedInstrument,
                'token': target.data('token'),
            },
        }).success(function (data, textStatus, jqXHR) {
            if (data.success === true) {
                cardMethodOption.remove();
            } else {
                cardMethodOption.removeClass('loading');
                paynow.showRemoveSavedInstrumentErrorMessage(savedInstrument, errorMessage);
            }
        }).error(function (jqXHR, textStatus, errorThrown) {
            cardMethodOption.removeClass('loading');
            paynow.showRemoveSavedInstrumentErrorMessage(savedInstrument, errorMessage);
        });
    },

    showRemoveSavedInstrumentErrorMessage: function (savedInstrument, errorMessage) {
        const errorMessageWrapper = jQuery('#wrapper-' + savedInstrument + ' .paynow-payment-card-error');

        errorMessageWrapper.text(errorMessage);

        setTimeout(() => {
            errorMessageWrapper.text('');
        }, 5000)
    },

    paymentButton: {
        show: function () {
            $(paynow.selectors.paymentButton).show();
        },
        hide: function () {
            $(paynow.selectors.paymentButton).hide();
        },
        disable: function () {
            $(paynow.selectors.paymentButton).prop('disabled', true);
            if (paynow.config.useCssClassDisabled) {
                $(paynow.selectors.paymentButton).addClass('disabled');
            }
        },
        enable: function () {
            $(paynow.selectors.paymentButton).prop('disabled', false);
            if (paynow.config.useCssClassDisabled) {
                $(paynow.selectors.paymentButton).removeClass('disabled');
            }
        }
    },

    blikButton: {
        disable: function () {
            $(paynow.selectors.blikButton).prop('disabled', true);
            if (paynow.config.useCssClassDisabled) {
                $(paynow.selectors.blikButton).addClass('disabled');
            }
        },
        enable: function () {
            $(paynow.selectors.blikButton).prop('disabled', false);
            if (paynow.config.useCssClassDisabled) {
                $(paynow.selectors.blikButton).removeClass('disabled');
            }
        },
        show: function () {
            $(paynow.selectors.blikButton).show();
        },
        hide: function () {
            $(paynow.selectors.blikButton).hide();
        }
    }
};


$(document).ready(paynow.init);

// backward compatibility; in case someone is using old functions in own code
function enableBlikSupport() { paynow.blikFormPrepare() }
function paynowPblPaymentBtnCheck() { paynow.pblValidate() }
function enablePblSupport() {}
