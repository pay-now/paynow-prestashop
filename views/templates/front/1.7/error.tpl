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
  <h2>{$cta_text|escape:'htmlall':'UTF-8'}</h2>
  <div class="table-responsive-row clearfix">
    <div class="clearfix">
      <p class="amount-info">
        {l s='Pay for your order' mod='paynow'}:
        <strong>{$total_to_pay|escape:'htmlall':'UTF-8'}</strong>
      </p>
    </div>

    <div class="alert alert-warning">
      {l s='An error occurred while processing your payment.' mod='paynow'}
    </div>

    <p class="cart_navigation clearfix" id="cart_navigation">
      <a class="button btn btn-primary button-medium" href="{$button_action|escape:'htmlall':'UTF-8'}">
        <span>{l s='Retry payment with paynow.pl' mod='paynow'}<i class="icon-chevron-right right"></i></span>
      </a>
    </p>
  </div>
{/block}
