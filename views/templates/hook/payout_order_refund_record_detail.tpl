<div class="modal fade" id="payout-order-refund-record-{$refund['id_refund']}" tabindex="-1">
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
                        {$refund['date']|escape:'html':'UTF-8'}
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>{l s='Checkout ID' mod='payout'}</b>
                    </td>
                    <td>
                        {$refund['id_checkout']|escape:'html':'UTF-8'}
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>{l s='Employee' mod='payout'}</b>
                    </td>
                    <td>
                        {$refund['employee_info']|escape:'html':'UTF-8'},
                        id: {$refund['id_employee']|escape:'html':'UTF-8'}
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>{l s='Withdrawal id' mod='payout'}</b>
                    </td>
                    <td>
                        {$refund['id_withdrawal']|escape:'html':'UTF-8'}
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>{l s='Amount' mod='payout'}</b>
                    </td>
                    <td>
                        {$refund['amount_text']|escape:'html':'UTF-8'}
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>{l s='Refund response data' mod='payout'}</b>
                    </td>
                    <td>
                        <p style="word-break: break-word">
                            {$refund['response']|escape:'html':'UTF-8'}
                        </p>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
