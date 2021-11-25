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
<div class="paynow-confirm-blik">
    <p class="headline">{l s='Confirm the payment using the app on your phone.' mod='paynow'}</p>
    <img src="{$link->getMediaLink("`$module_dir`views/img/blik-confirm.png")}" alt="{l s='Confirm the BLIK payment' mod='paynow'}">
    <div class="order-data clearfix">
        <p>{l s='Your order number:' mod='paynow'} {$order_reference|escape:'htmlall':'UTF-8'}</p>
        <p>{l s='Current order status:' mod='paynow'} <span class="status">{$order_status|escape:'htmlall':'UTF-8'}</span></p>
    </div>
</div>
