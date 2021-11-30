<div class="col-lg-3 col-xs-4 paynow-payment-option-pbl {if !$method->isEnabled()}disabled{/if}">
    <input type="radio" name="paymentMethodId" value="{$method->getId()|escape:'htmlall':'UTF-8'}" id="paynow_method_{$method->getId()|escape:'htmlall':'UTF-8'}" {if !$method->isEnabled()}disabled{/if}>
    <label for="paynow_method_{$method->getId()}">
        <img src="{$method->getImage()|escape:'htmlall':'UTF-8'}" alt="{$method->getDescription()|escape:'htmlall':'UTF-8'}" />
    </label>
</div>