<table class="table">
    <thead>
    <tr>
        <th>{l s='Timestamp' mod='payout'}</th>
        <th>{l s='Checkout ID' mod='payout'}</th>
        <th>{l s='Employee' mod='payout'}</th>
        <th>{l s='Withdrawal id' mod='payout'}</th>
        <th>{l s='Amount' mod='payout'}</th>
        <th>{l s='Detail' mod='payout'}</th>
    </tr>
    </thead>
    <tbody>
    {if !empty($payout_order_refund_records)}
        {foreach from=$payout_order_refund_records item=refund}
            <tr>
                <td>
                    {$refund['date']|escape:'html':'UTF-8'}
                </td>
                <td>
                    {$refund['id_checkout']|escape:'html':'UTF-8'}
                </td>
                <td>
                    {$refund['employee_info']|escape:'html':'UTF-8'},
                    id: {$refund['id_employee']|escape:'html':'UTF-8'}
                </td>
                <td>
                    {$refund['id_withdrawal']|escape:'html':'UTF-8'}
                </td>
                <td>
                    {$refund['amount_text']|escape:'html':'UTF-8'}
                </td>
                <td>
                    <a class="btn btn-primary pointer" data-toggle="modal" href="#" title="" data-toggle="modal"
                       data-placement="bottom" data-original-title="Upload a module"
                       data-target="#payout-order-refund-record-{$refund['id_refund']}">
                        {l s='Detail' mod='payout'}
                    </a>
                    {include file="./payout_order_refund_record_detail.tpl" refund=$refund}
                </td>
            </tr>
        {/foreach}
    {/if}
    </tbody>
</table>
