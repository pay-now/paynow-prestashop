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
<div class="alert alert-warning">
    <button type="button" class="close" data-dismiss="alert">×</button>
    <p><span class="badge badge-warning">{l s='A new version of the Pay by paynow.pl module is available.' mod='paynow'}</span> {if !empty($download_url)}<a href="{$download_url|escape:'htmlall':'UTF-8'}" target="_blank" class="btn">{l s='Click here to download' mod='paynow'}{if !empty($changelog_url)} {$version_name|escape:'htmlall':'UTF-8'}{/if}</a>{/if}</p>
    {if !empty($changelog_url)}
        <p><a href="{$changelog_url|escape:'htmlall':'UTF-8'}">{l s='Open changelog' mod='paynow'}</a></p>
    {/if}
</div>