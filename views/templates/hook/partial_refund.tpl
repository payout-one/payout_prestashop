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

            $(document).on('click', '#cancel_product_save,.partial_refund_fields > button[name="partialRefund"]', function () {
                    if ($('#payout_partial_refund').is(":checked")) {
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
            );

            $(document).on('change', '#cancel_product_credit_slip', function () {
                const payoutRefundCheckboxQuerySelector = $('#payout_partial_refund');
                const payoutRefundDivQuerySelector = $('#payout_partial_refund_outer_div');
                if ($(this).is(':checked') && ($('.partial-refund-display').length > 0 || $('.standard-refund-display').length > 0)) {
                    if (payoutRefundCheckboxQuerySelector.length === 0) {
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
                        }
                    },
                });
            }
        }
    )
    ;
    {/literal}
</script>
