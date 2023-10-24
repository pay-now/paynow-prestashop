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
{if $paynow_card_instruments}
    <form class="payment-form paynow-payment-form" action="{$action_card}" method="POST">
        <div class="paynow-payment-option-container">
            <p>{l s='Select a saved card or enter new card details:' mod='paynow'}</p>
            <div class="paynow-payment-card">
                {foreach from=$paynow_card_instruments item=instrument}
                    <div class="paynow-payment-card-option">
                        <input type="radio" name="paymentMethodToken" value="{$instrument->getToken()}" id="{$instrument->getToken()}" {if $instrument->isExpired()} disabled {/if}>
                        <label for="{$instrument->getToken()}">
                            <div class="paynow-payment-card-image">
                                <img src="{$instrument->getImage()}" alt="{$instrument->getBrand()}">
                            </div>
                            <div class="paynow-payment-card-details">
                                {if $instrument->isExpired()}
                                    <p class="paynow-payment-card-details-card-name paynow-expired">{l s='Card:' mod='paynow'} {$instrument->getName()}</p>
                                    <p class="paynow-payment-card-details-expiration paynow-expired">{l s='Expired:' mod='paynow'} {$instrument->getExpirationDate()}</p>
                                {else}
                                    <p class="paynow-payment-card-details-card-name">{l s='Card:' mod='paynow'} {$instrument->getName()}</p>
                                    <p class="paynow-payment-card-details-expiration">{l s='Expires:' mod='paynow'} {$instrument->getExpirationDate()}</p>
                                {/if}
                            </div>
                        </label>
                        <div class="paynow-payment-card-menu">
                            <button class="paynow-payment-card-menu-button" type="button">
                                <img src="{$dots_image}" alt="three dots icon">
                            </button>
                            <button
                                    class="paynow-payment-card-remove --hidden" type="button"
                                    data-remove-saved-instrument="{$instrument->getToken()}"
                                    data-action="{$action_remove_saved_instrument}"
                                    data-token="{$action_remove_saved_instrument_token}"
                            >
                                {l s='Remove card' mod='paynow'}
                            </button>
                        </div>
                    </div>
                {/foreach}
                <div class="paynow-payment-card-option">
                    <input type="radio" name="paymentMethodToken" value="" id="paymentMethodToken-default">
                    <label for="paymentMethodToken-default">
                        <div class="paynow-payment-card-image --double">
                            <img src="https://static.sandbox.paynow.pl/payment-method-icons/visa.png" alt="VISA">
                            <img src="https://static.sandbox.paynow.pl/payment-method-icons/mastercard.png" alt="MASTERCARD">
                        </div>
                        <div class="paynow-payment-card-details">
                            <p class="paynow-payment-card-details-card-name">{l s='Enter your new card details' mod='paynow'}</p>
                            <p class="paynow-payment-card-details-expiration">{l s='You can save it in the next step' mod='paynow'}</p>
                        </div>
                    </label>
                </div>
            </div>
            {include file="module:paynow/views/templates/front/1.7/_partials/payment_data_processing_info.tpl"}
        </div>
    </form>
{/if}
