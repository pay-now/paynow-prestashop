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
  <div class="paynow-return">
    <img src="{$logo|escape:'htmlall':'UTF-8'}" alt="{l s='Pay by online transfer or BLIK' mod='paynow'}">
    <h2>{l s='Thank you for your order!' mod='paynow'}</h2>
    <div class="order-data clearfix">
      <p>
        {l s='Your order number:' mod='paynow'} {$order_reference|escape:'htmlall':'UTF-8'}
      </p>
      <p>
        {l s='Order status:' mod='paynow'} {$order_status|escape:'htmlall':'UTF-8'}
      </p>
      {if $show_retry_button}
        <p>
          <a href="{$retry_url|escape:'htmlall':'UTF-8'}" title="{l s='Retry payment with paynow.pl' mod='paynow'}" class="btn btn-primary">
            {l s='Retry payment with paynow.pl' mod='paynow'}
          </a>
        </p>
      {/if}
      {if $show_details_button}
        <p>
          <a class="button btn btn-primary button-medium pull-left" href="{$details_url|escape:'htmlall':'UTF-8'}">
            <span>
              {l s='Order details' mod='paynow'}
              <i class="icon-chevron-right"></i>
            </span>
          </a>
        </p>
      {/if}
    </div>
    {*HTML CONTENT*}{$HOOK_ORDER_CONFIRMATION nofilter}
  </div>
{/block}
