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
{if $data_processing_notices}
    {assign var=unique_id value=1|mt_rand:20}
    <div class="row">
        <div class="col-lg-12">
            <div class="paynow-data-processing-info">
                {foreach from=$data_processing_notices item=notice}
                    <div class="paynow-data-processing-info-less">
                        {$notice.title nofilter}
                        {if $notice.content}
                            &nbsp;<span data-toggle="collapse" data-target="#paynow_disclaimer_{$unique_id}">{l s='Read more' mod='paynow'}</span>
                        {/if}
                    </div>
                    {if $notice.content}
                        <div class="paynow-data-processing-info-more collapse" id="paynow_disclaimer_{$unique_id}">
                            {$notice.content nofilter}
                        </div>
                    {/if}
                {/foreach}
            </div>
        </div>
    </div>
{/if}