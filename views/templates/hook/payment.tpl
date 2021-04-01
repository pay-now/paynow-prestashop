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
<form action="{$paynow_url|escape:'htmlall':'UTF-8'}" method="POST">
{if !empty($payment_options)}
    {foreach from=$payment_options item=method}
        <p class="payment_module paynow{if !empty($method.pbls)} with-pbls{/if}">
            {if !empty($method.pbls)}
                <button name="paymentMethodId" type="button" class="payment-option-pbls payment-option">
                    <img src="{$method.image|escape:'htmlall':'UTF-8'}" alt="{$method.name|escape:'htmlall':'UTF-8'}">
                    {$method.name|escape:'htmlall':'UTF-8'}
                </button>
                <div class="paynow-payment-option-pbls">
                    {foreach from=$method.pbls item=pbl}
                        <div class="col-lg-3 col-xs-4 paynow-payment-option-pbl">
                            <button name="paymentMethodId" value="{$pbl->getId()}" type="submit" {if !$pbl->isEnabled()}disabled{/if}>
                                <img src="{$pbl->getImage()}" alt="{$pbl->getName()}" />
                            </button>
                        </div>
                    {/foreach}
                </div>
            {else}
                <button name="paymentMethodId" value="{$method.id}" type="submit" class="payment-option" {if !$method.enabled}disabled{/if}>
                    <img src="{$method.image}" alt="{$method.name}" />
                    {$method.name|escape:'htmlall':'UTF-8'}
                </button>
            {/if}
        </p>
    {/foreach}
{else}
    <p class="payment_module">
        <button name="paymentMethodId" type="submit" class="payment-option">
            <img src="{$logo|escape:'htmlall':'UTF-8'}" alt="{$cta_text|escape:'htmlall':'UTF-8'}">
            {$cta_text|escape:'htmlall':'UTF-8'}
        </button>
    </p>
{/if}
</form>
