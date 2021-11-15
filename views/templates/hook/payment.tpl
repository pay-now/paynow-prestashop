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
                <button name="paymentMethodId" type="button" class="payment-option-pbls payment-option"
                        data-toggle="collapse" data-target="#paynow-option-{$method.type}" aria-expanded="false" aria-controls="paynow-option-{$method.type}">
                    <img src="{$method.image|escape:'htmlall':'UTF-8'}" alt="{$method.name|escape:'htmlall':'UTF-8'}">
                    {$method.name|escape:'htmlall':'UTF-8'}
                </button>
                {include file="./_partials/payment_pbls.tpl"}
            {else}
                <button name="paymentMethodId" value="{$method.id}" type="{if $method.authorization == 'CODE'}button{else}submit{/if}" class="payment-option paynow-option-{$method.type}" {if !$method.enabled}disabled{/if}
                        data-toggle="collapse" data-target="#paynow-option-{$method.type}" aria-expanded="false" aria-controls="paynow-option-{$method.type}"{if $method.authorization == 'CODE' && $method.type == 'BLIK'} onclick="enableBlikValidation()"{/if}>
                    <img src="{$method.image}" alt="{$method.name}" />
                    {$method.name|escape:'htmlall':'UTF-8'}
                </button>
                {if $method.authorization == 'CODE'}
                    {include file="./_partials/blik_payment_form.tpl"}
                {/if}
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
