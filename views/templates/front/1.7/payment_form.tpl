{if $paynowPbls}
    <form class="payment-form paynow-payment-form" method="POST" action="{$action}">
        <div class="row">
            {foreach from=$paynowPbls item=method}
                <div class="col-lg-3 col-xs-4 paynow-payment-option-pbl">
                    <input type="radio" name="paymentMethodId" value="{$method->getId()}" id="paynow_method_{$method->getId()}" />
                    <label for="paynow_method_{$method->getId()}">
                        <img src="{$method->getImage()}" alt="{$method->getDescription()}" />
                    </label>
                </div>
            {/foreach}
        </div>
    </form>
{/if}