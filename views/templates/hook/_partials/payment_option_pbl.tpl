<div class="col-lg-3 col-xs-4 paynow-payment-option-pbl">
    <button name="paymentMethodId" value="{$pbl->getId()}" type="submit" {if !$pbl->isEnabled()}disabled{/if}>
        <img src="{$pbl->getImage()}" alt="{$pbl->getName()}" />
    </button>
</div>