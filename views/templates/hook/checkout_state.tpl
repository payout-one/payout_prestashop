<div class="box">
    <div class="payout-payment-block">
        {if $payout_order_status == 'confirmation_to_redirect'}
            <div class="countdown-container">
                <div class="payout-icon-container">
                    <div class="payout-redirect-icon">➡️</div>
                </div>
                <div class="payout-redirect-message">
                    <h2>{l s='We will redirect you to payment' mod='payout'}</h2>
                    <p>{l s='Remaining time to redirect:' mod='payout'}
                        <span id="payout-countdown">5</span> {l s='seconds' mod='payout'}
                    </p>
                </div>
                <div class="payout-actions">
                    <button id="cancelButton"
                            class="btn payout-redirection cancel-redirect">{l s='Cancel redirection' mod='payout'}</button>
                    <button id="redirectButton"
                            class="btn payout-redirection redirect"
                            data-url="{$payout_checkout_url}">{l s='Redirect now' mod='payout'}</button>
                </div>
            </div>
        {/if}

        {if $payout_order_status == 'success'}
            <div class="payout-payment-success payment-state">
                <div class="payout-icon-container">
                    <div class="payout-success-icon">✔</div>
                </div>
                <div class="payout-message">
                    <h2>{l s='Payment accepted' mod='payout'}</h2>
                    <p>{l s='Payment was successfully processed. Thank you for your order!' mod='payout'}</p>
                </div>
            </div>
        {/if}

        {if $payout_order_status == 'not_paid_yet'}
            <div class="payout-payment-not-started payment-state">
                <div class="payout-icon-container">
                    <div class="payout-not-started-icon">⌛</div>
                </div>
                <div class="payout-message">
                    <h2>{l s='Order was not paid yet' mod='payout'}</h2>
                    <p>{l s='Order was not paid yet. Click button down below to pay the order.' mod='payout'}</p>
                </div>
                <a href="{$payout_repay_url}" class="payout-pay-button">{l s='Pay' mod='payout'}</a>
            </div>
        {/if}

        {if $payout_order_status == 'expired'}
            <div class="payout-payment-expired payment-state">
                <div class="payout-icon-container">
                    <div class="payout-expired-icon">
                        <img src="{$module_dir}views/img/stopwatch-icon.png"
                             alt="{l s='Expired payment' mod='payout'}">
                    </div>
                </div>
                <div class="payout-message">
                    <h2>{l s='Expired payment' mod='payout'}</h2>
                    <p>{l s='The time to complete the payment has expired. If you paid it already, please wait for confirmation of payment receipt.' mod='payout'}</p>
                </div>
            </div>
        {/if}
    </div>
</div>