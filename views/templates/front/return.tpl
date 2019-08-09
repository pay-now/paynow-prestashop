{*
* NOTICE OF LICENSE
*
* This source file is subject to the MIT License (MIT)
* that is bundled with this package in the file LICENSE.md.
*
* @copyright mBank S.A.
* @license   MIT License
*}
{capture name=path}{l s='Pay by online transfer or BLIK' mod='paynow'}{/capture}

<div class="box clearfix">
    <p>
        {l s='Order number:' mod='paynow'} {$reference|escape:'htmlall':'UTF-8'} <br />{l s='Status:' mod='paynow'} {$order_status|escape:'htmlall':'UTF-8'}
    </p>

    <p>
      <a class="button btn btn-default button-medium pull-left" href="{$redirect_url|escape:'htmlall':'UTF-8'}">
          <span>
              {l s='Order details' mod='paynow'}
              <i class="icon-chevron-right"></i>
          </span>
      </a>
    </p>
</div>
