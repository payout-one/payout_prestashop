<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog {if isset($modal_class)}{$modal_class}{/if}">
        <div class="modal-content">
            <div class="modal-header">
                <h2>{$orderRefundText}</h2>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="payout-refund-modal-body">
                <div class="payout-refund-amounts">
                    <p>{l s='Total paid amount' mod='payout'}: <strong
                                id="payoutOrderAmount">{$step}</strong><strong> {$currencySign}</strong></p>
                    <p>{l s='Refunded amount' mod='payout'}: <strong
                                id="payoutRefundedAmount">{$step}</strong><strong> {$currencySign}</strong></p>
                    <p>{l s='Remaining amount to refund' mod='payout'}: <strong
                                id="payoutMaxRefundAmount">{$step}</strong><strong> {$currencySign}</strong></p>
                </div>

                <div id="payoutRefundLoader" class="payout-refund-div-disabled">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">{l s='Loading' mod='payout'}...</span>
                    </div>
                </div>
                <div id="payoutRefundMessages" class="payout-refund-div-disabled">
                    <div class="alert alert-success alert-dismissible">
                        <p></p>
                    </div>
                    <div class="alert alert-danger alert-dismissible">
                        <p></p>
                    </div>
                </div>
                <div class="payoutRefundForm payout-refund-div-disabled">
                    <label for="payoutRefundAmount">{l s='Amount to refund' mod='payout'}:</label>
                    <input type="number" name="payoutRefundAmount" id="payoutRefundAmount" min="{$step}"
                           max="{$step}"
                           step="{$step}"
                           value="{$step}">
                </div>
                <div class="payoutRefundForm payout-refund-div-disabled">
                    <button id="confirmRefund"
                            class="btn btn-primary">{l s='Process refund' mod='payout'}</button>
                </div>
                <div id="payoutRefundNotPossible" class="payout-refund-div-enabled">
                    <div class="alert alert-info alert-dismissible">
                        <p>{l s='Another refund is not possible, entire amount was already refunded' mod='payout'}.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
s