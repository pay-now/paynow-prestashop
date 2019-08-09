{*
* NOTICE OF LICENSE
*
* This source file is subject to the MIT License (MIT)
* that is bundled with this package in the file LICENSE.md.
*
* @copyright mBank S.A.
* @license   MIT License
*}
<div class="panel">
    <div class="panel-heading">{l s='Information' mod='paynow'}</div>
    <p>
        {l s='You have to update your Shop configuration into the' mod='paynow'} <a href="https://panel.paynow.pl/merchant/settings/shops-and-pos" target="_blank">{l s='Merchant Panel' mod='paynow'}</a>:<br/>
        {l s='notification URL:' mod='paynow'} <code>{$notificationUrl|escape:'htmlall':'UTF-8'}</code> <br/>
        {l s='continue URL:' mod='paynow'} <code>{$continueUrl|escape:'htmlall':'UTF-8'}</code>
    </p>
</div>
