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
<p class="payment_module">
    <a href="{$paynow_url|escape:'htmlall':'UTF-8'}" title="{$this->cta_text}" class="paynow">
        <img src="{$logo|escape:'htmlall':'UTF-8'}" alt="{$this->cta_text}">
        {$this->cta_text}
    </a>
</p>
