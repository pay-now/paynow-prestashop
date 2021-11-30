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
<div class="col-lg-3 col-xs-4 paynow-payment-option-pbl">
    <button name="paymentMethodId" value="{$pbl->getId()}" type="submit" {if !$pbl->isEnabled()}disabled{/if}>
        <img src="{$pbl->getImage()}" alt="{$pbl->getName()}" />
    </button>
</div>