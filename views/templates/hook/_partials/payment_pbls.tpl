<div class="paynow-payment-option-pbls collapse" id="paynow-option-{$method.type}">
    {foreach from=$method.pbls item=pbl}
        {include file="./payment_option_pbl.tpl"}
    {/foreach}
    {include file="./data_processing_info.tpl"}
</div>