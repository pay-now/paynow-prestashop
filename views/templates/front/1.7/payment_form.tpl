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
        <div class="row">
            {foreach from=$paynowPbls item=method}
                <div class="col-lg-3 col-xs-4 paynow-payment-option-pbl {if !$method->isEnabled()}disabled{/if}">
                    <input type="radio" name="paymentMethodId" value="{$method->getId()|escape:'htmlall':'UTF-8'}" id="paynow_method_{$method->getId()|escape:'htmlall':'UTF-8'}" {if !$method->isEnabled()}disabled{/if}/>
                    <label for="paynow_method_{$method->getId()}">
                        <img src="{$method->getImage()|escape:'htmlall':'UTF-8'}" alt="{$method->getDescription()|escape:'htmlall':'UTF-8'}" />
                    </label>
                </div>
            {/foreach}
        </div>
    </form>
{/if}