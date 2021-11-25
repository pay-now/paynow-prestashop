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
<div class="paynow-payment-option-pbls collapse" id="paynow-option-{$method.type}">
    <form action="{$paynow_url|escape:'htmlall':'UTF-8'}" method="POST">
        <div class="row">
            {foreach from=$method.pbls item=pbl}
                {include file="./payment_method_pbl.tpl"}
            {/foreach}
        </div>
        {include file="./payment_data_processing_info.tpl"}
    </form>
</div>