<div class="paynow-option-content-{$method.type} collapse" id="paynow-option-{$method.type}">
    <div class="paynow-payment-option-blik">
        <div class="form-group row">
            <label for="paynow_blik_code" class="col-md-3 form-control-label required">
                {l s='Enter the BLIK code' mod='paynow'}
            </label>
            <div class="col-md-3">
                <input autocomplete="off" inputmode="numeric" pattern="[0-9]*" minlength="6" maxlength="6" size="6" id="paynow_blik_code" name="blikCode" type="text" placeholder="___ ___" value="" class="required form-control">
            </div>
            <div class="col-md-2" id="payment-confirmation">
                <button type="submit" class="btn btn-primary" disabled>
                    {l s='Pay' mod='paynow'}
                </button>
            </div>
        </div>
        {include file="./data_processing_info.tpl"}
    </div>
</div>