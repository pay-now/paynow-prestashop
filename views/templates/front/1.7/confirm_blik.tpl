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
{extends file=$layout}

{block name='content'}
<div class="paynow-confirm-blik">
    <h2>{l s='Confirm the payment using the app on your phone.' mod='paynow'}</h2>
    <img src="{$link->getMediaLink("`$module_dir`views/img/blik-confirm.png")}" alt="{l s='Confirm the BLIK payment' mod='paynow'}">
    <div class="order-data clearfix">
        {if !empty($order_reference)}
            <p>{l s='Your order number:' mod='paynow'} {$order_reference|escape:'htmlall':'UTF-8'}</p>
        {/if}
        {if !empty($order_status)}
            <p>{l s='Current order status:' mod='paynow'} <span class="status">{$order_status|escape:'htmlall':'UTF-8'}</span></p>
        {/if}
    </div>
</div>
{/block}
