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
<button name="paymentMethodId"{if isset($method.id)} value="{$method.id}" {/if}type="{if $method.authorization == 'CODE'}button{else}submit{/if}" class="payment-option paynow-option-{$method.type}" {if isset($method.enabled) && !$method.enabled}disabled{/if}
        data-toggle="collapse" data-target="#paynow-option-{$method.type}" aria-expanded="false" aria-controls="paynow-option-{$method.type}"{if $method.authorization == 'CODE' && $method.type == 'BLIK'} onclick="{literal}setTimeout(function () {enableBlikSupport()}, 200){/literal}"{/if}>
    <img src="{$method.image|escape:'htmlall':'UTF-8'}" alt="{$method.name|escape:'htmlall':'UTF-8'}"/>
    {$method.name|escape:'htmlall':'UTF-8'}
</button>