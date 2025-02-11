{*
* 2007-2022 PayPal
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author 2007-2022 PayPal
*  @copyright PayPal
*  @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*
*}
<script>
    {literal}
    // add checkbox
    $(document).ready(() => {

            // Make partial order refund in Order page in BO
            $(document).on('click', '#desc-order-partial_refund', function () {
                // Create checkbox and insert for Payout refund
                if ($('#payout_partial_refund').length === 0) {
                    let newCheckBox = `<p class="checkbox"><label for="payout_partial_refund">
                        <input type="checkbox" id="payout_partial_refund" name="payout_partial_refund" value="1">
                          Return on payout</label></p>`;
                    $('button[name=partialRefund]').parent('.partial_refund_fields').prepend(newCheckBox);
                }
            });

            // $(document).on('click', '.partial-refund-display,.standard-refund-display', function () {
            //     // Create checkbox and insert for Paypal refund
            //     if ($('#payout_partial_refund').length === 0) {
            //         let newCheckBox = `
            //             <div class="cancel-product-element form-group" style="display: block;">
            //                     <div class="checkbox">
            //                         <div class="md-checkbox md-checkbox-inline">
            //                           <label>
            //                               <input type="checkbox" id="payout_partial_refund" name="payout_partial_refund" material_design="material_design" value="1">
            //                               <i class="md-checkbox-control"></i>
            //                                 Return on payout
            //                             </label>
            //                         </div>
            //                     </div>
            //              </div>`;
            //
            //         $('.refund-checkboxes-container').prepend(newCheckBox);
            //     }
            // });
            $(document).on('click', '#cancel_product_save,.partial_refund_fields > button[name="partialRefund"]', function () {
                    if ($('#payout_partial_refund').is(":checked")) {
                        // let amount = 0;
                        // const form = $('#cancel_product_save').closest('form');
                        //
                        // if (form.hasClass('partial-refund')) {
                        //     amount += $('#cancel_product_shipping_amount').val();
                        // } else { // form.hasClass('standard-refund')
                        //     const cancelProductShippingQuerySelector = $('#cancel_product_shipping');
                        //     if (cancelProductShippingQuerySelector.is(':checked')) {
                        //
                        //     }
                        // }
                        // $('#payout_partial_refund').prop("checked", false);
                        // $('button[name="partialRefund"]').attr('namee') === "partialRefund"
                        // if ($(this).attr('name') === "partialRefund") {
                        //     submitRefund($(this));
                        // } else {
                        //     submitRefund($(this).closest('form'));
                        // }

                        if ($(this).attr('name') === "partialRefund") {
                            if ($(this).data('payout-refund-confirmed') === true) {
                                return true;
                            }
                            submitRefund($(this), true);
                        } else {
                            submitRefund($(this).closest('form'), false);
                        }

                        return false;
                    }
                }
            )
            ;

            // $(document).on('click', '#update_order_status_action_btn', function () {
            //     if ($('#update_order_status_action_btn').is(":checked")) {
            //         return confirm("Určite chcete vykonať refundáciu cez payout?");
            //     }
            // });

            $(document).on('change', '#cancel_product_credit_slip', function () {
                const payoutRefundCheckboxQuerySelector = $('#payout_partial_refund');
                const payoutRefundDivQuerySelector = $('#payout_partial_refund_outer_div');
                if ($(this).is(':checked')) {
                    if (payoutRefundCheckboxQuerySelector.length === 0) {
                        // Create checkbox and insert for Paypal refund
                        let newCheckBox = `
                        <div id="payout_partial_refund_outer_div" class="cancel-product-element form-group" style="display: block;">
                                <div class="checkbox">
                                    <div class="md-checkbox md-checkbox-inline">
                                      <label>
                                          <input type="checkbox" id="payout_partial_refund" name="payout_partial_refund" material_design="material_design" value="1">
                                          <i class="md-checkbox-control"></i>
                                            Return on payout
                                        </label>
                                    </div>
                                </div>
                         </div>`;
                        $('.refund-checkboxes-container').prepend(newCheckBox);
                    } else {
                        payoutRefundDivQuerySelector.show();
                        payoutRefundCheckboxQuerySelector.prop("checked", false);
                    }
                } else if (payoutRefundCheckboxQuerySelector.length !== 0) {
                    payoutRefundDivQuerySelector.hide();
                    payoutRefundCheckboxQuerySelector.prop("checked", false);
                }
            });

            function submitRefund(element, isSubmitButton) {
                // let querySelector = $('#check-filled-credentials-message');
                // querySelector.hide();
                $.ajax({
                    url: payoutRefundControllerUrl,
                    type: 'post',
                    dataType: 'json',
                    data: {
                        ajax: true,
                        action: 'RefundableAmount',
                        id_order: orderId,
                    },
                    success(response) {
                        let amount = 0;
                        const message = JSON.parse(response.message);
                        if (message.refundPossible) {
                            amount = message.refundable;
                        }

                        const alertMessage = `{/literal}{l s='Are you sure to process refund via Payout? Remaining amount to refund for this order is' mod='payout'}: {literal}${formatPrice(amount)}{/literal}{$currencySign}{literal}. {/literal}{l s='If refund amount will be higher, refund via Payout will not be processed' mod='payout'}{literal}.`;
                        if (confirm(alertMessage)) {
                            if (isSubmitButton) {
                                element.data('payout-refund-confirmed', true);
                                element.click();
                            } else { // form
                                element.trigger("submit");
                            }
                            // form.submit();
                        }
                    },
                });
            }
        }
    )
    ;
    {/literal}
</script>
