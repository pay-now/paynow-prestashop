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
{capture name=path}{l s='Pay by online transfer or BLIK' mod='paynow'}{/capture}

<div class="box clearfix paynow-return">
    <img src="{$logo|escape:'htmlall':'UTF-8'}" alt="{l s='Pay by online transfer or BLIK' mod='paynow'}" class="pull-right">
    <h2>{l s='Thank you for your order!' mod='paynow'}</h2>
    <p>
        {l s='Your order number:' mod='paynow'} {$order_reference|escape:'htmlall':'UTF-8'} <br />
        {l s='Current order status:' mod='paynow'} {$order_status|escape:'htmlall':'UTF-8'}
    </p>
    {if $show_details_button}
        <p class="cart_navigation" style="margin-top: 15px">
          <a class="button btn btn-default button-medium pull-left" href="{$details_url|escape:'htmlall':'UTF-8'}">
              <span>
                  {l s='Order details' mod='paynow'}
                  <i class="icon-chevron-right"></i>
              </span>
          </a>
        </p>
    {/if}
</div>
{*HTML CONTENT*}{$HOOK_ORDER_CONFIRMATION nofilter}