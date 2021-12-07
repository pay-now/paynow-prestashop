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
{if $paynowPbls}
    <form class="payment-form paynow-payment-form" method="POST" action="{$action}">
        <div class="paynow-payment-option-container">
            <p>{l s='Choose bank:' mod='paynow'}</p>
            <div class="row paynow-payment-pbls">
                {foreach from=$paynowPbls item=method}
                    {include file="module:paynow/views/templates/front/1.7/_partials/payment_method_pbl.tpl"}
                {/foreach}
            </div>
            {include file="module:paynow/views/templates/front/1.7/_partials/payment_data_processing_info.tpl"}
        </div>
    </form>
{/if}