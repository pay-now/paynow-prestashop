{if $data_processing_notices}
    {assign var=unique_id value=1|mt_rand:20}
    <div class="paynow-data-processing-info col-lg-12">
        {foreach from=$data_processing_notices item=notice}
            <div class="paynow-data-processing-info-less">
                {$notice->getTitle() nofilter} <a href="#paynow_disclaimer_{$unique_id}" data-toggle="collapse" data-target="#paynow_disclaimer_{$unique_id}">{l s='Read more' mod='paynow'}</a>
            </div>
            <div class="paynow-data-processing-info-more collapse" id="paynow_disclaimer_{$unique_id}">
                {$notice->getContent() nofilter}
            </div>
        {/foreach}
    </div>
{/if}