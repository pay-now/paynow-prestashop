{*
* NOTICE OF LICENSE
*
* This source file is subject to the MIT License (MIT)
* that is bundled with this package in the file LICENSE.md.
*
* @author mElements S.A.
* @copyright mElements S.A.
* @license   MIT License
*}
<form class="payment-form paynow-blik-form" data-method="POST" data-action="{$action_blik}" data-token="{$action_token}" data-error-message="{$error_message}" data-terms-message="{$terms_message}" data-blik-autofocus="{$blik_autofocus}">
    <div class="paynow-option-content-{$method.type} collapse" id="paynow-option-{$method.type}">
        <div class="paynow-payment-option-blik">
            <div class="form-group row">
                <label for="paynow_blik_code" class="col-md-2 form-control-label required">
                    {l s='Enter the BLIK code' mod='paynow'}
                </label>
                <div class="col-md-2">
                    <input autocomplete="off" inputmode="numeric" pattern="[0-9]{'{3}'|escape:'htmlall':'UTF-8'} [0-9]{'{3}'|escape:'htmlall':'UTF-8'}" minlength="6" maxlength="6" size="6" id="paynow_blik_code" name="blikCode" type="text" value="" class="required form-control">
                    <span class="error"></span>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary" disabled>
                        {l s='Pay' mod='paynow'}
                    </button>
                </div>
            </div>
            {include file="./payment_data_processing_info.tpl"}
        </div>
    </div>
</form>
