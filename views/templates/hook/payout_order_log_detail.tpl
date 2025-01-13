<div class="modal fade" id="payout-order-log-{$log['id_payout_log']}" tabindex="-1">
    <div class="modal-dialog {if isset($modal_class)}{$modal_class}{/if}">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <table class="table">
                <tbody>
                <tr>
                    <td>
                        <b>{l s='Timestamp' mod='payout'}</b>
                    </td>
                    <td>
                        {$log['date_added']|escape:'html':'UTF-8'}
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>{l s='Checkout ID' mod='payout'}</b>
                    </td>
                    <td>
                        {$log['id_checkout']|escape:'html':'UTF-8'}
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>{l s='Source' mod='payout'}</b>
                    </td>
                    <td>
                        {$log['data_type']|escape:'html':'UTF-8'}
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>{l s='Type' mod='payout'}</b>
                    </td>
                    <td>
                        {$log['type']|escape:'html':'UTF-8'}
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>{l s='Source data' mod='payout'}</b>
                    </td>
                    <td>
                        <p style="word-break: break-word">
                            {$log['data']|escape:'html':'UTF-8'}
                        </p>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
