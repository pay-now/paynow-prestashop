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
<script>
{literal}
$(document).ready(() => {
    let makePaynowRefundCheckbox = `
        <div class="cancel-product-element form-group refund-paynow" style="display: block;">
                <div class="checkbox">
                    <div class="md-checkbox md-checkbox-inline">
                      <label>
                          <input type="checkbox" id="makeRefundViaPaynow" name="makeRefundViaPaynow" material_design="material_design" value="1" checked="checked">
                          <i class="md-checkbox-control"></i>
                          {/literal}{$makePaynowRefundCheckboxLabel|escape:'htmlall':'UTF-8'}{literal}
                        </label>
                    </div>
                </div>
         </div>
    `;
    $('.refund-checkboxes-container, div.partial_refund_fields').prepend(makePaynowRefundCheckbox);
});
{/literal}
</script>