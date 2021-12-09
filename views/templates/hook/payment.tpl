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
{if !empty($payment_options)}
    {foreach from=$payment_options item=method}
        {if ($method.type !== 'BLIK' AND $method.type !== 'PBL' ||  ($method.type == 'BLIK' AND $method.authorization != 'CODE')) }
            <form action="{$paynow_url|escape:'htmlall':'UTF-8'}" method="POST">
        {/if}
        <p class="payment_module paynow{if !empty($method.pbls)} with-pbls{/if}">
            {include file="./_partials/payment_method_button.tpl"}
            {if !empty($method.pbls)}
                {include file="./_partials/payment_pbls.tpl"}
            {else}
                {if $method.authorization == 'CODE'}
                    {include file="./_partials/payment_method_blik_form.tpl"}
                {/if}
            {/if}
        </p>
        {if ($method.type !== 'BLIK' AND $method.type !== 'PBL' ||  ($method.type == 'BLIK' AND $method.authorization != 'CODE')) }
            </form>
        {/if}
    {/foreach}
{else}
    <form action="{$paynow_url|escape:'htmlall':'UTF-8'}" method="POST">
        <p class="payment_module paynow">
            <button name="paymentMethodId" type="submit" class="payment-option">
                <img src="{$logo|escape:'htmlall':'UTF-8'}" alt="{$cta_text|escape:'htmlall':'UTF-8'}">
                {$cta_text|escape:'htmlall':'UTF-8'}
            </button>
        </p>
    </form>
{/if}
