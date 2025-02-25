<div class="tab-pane" id="payout-checkout">
    <table class="table">
        <thead>
        <tr>
            <th>{l s='Timestamp' mod='payout'}</th>
            <th>{l s='Checkout ID' mod='payout'}</th>
            <th>{l s='Source' mod='payout'}</th>
            <th>{l s='Type' mod='payout'}</th>
            <th>{l s='Detail' mod='payout'}</th>
        </tr>
        </thead>
        <tbody>
        {if !empty($payout_order_logs)}
            {foreach from=$payout_order_logs item=log}
                <tr>
                    <td>
                        {$log['date_added']|escape:'html':'UTF-8'}
                    </td>
                    <td>
                        {$log['id_checkout']|escape:'html':'UTF-8'}
                    </td>
                    <td>
                        {$log['data_type']|escape:'html':'UTF-8'}
                    </td>
                    <td>
                        {$log['type']|escape:'html':'UTF-8'}
                    </td>
                    <td>
                        <a class="btn btn-primary pointer" data-toggle="modal" href="#" title="" data-toggle="modal"
                           data-placement="bottom" data-original-title="Upload a module"
                           data-target="#payout-order-log-{$log['id_payout_log']}">
                            {l s='Detail' mod='payout'}
                        </a>
                        {include file="./payout_order_log_detail.tpl" log=$log}
                    </td>
                </tr>
            {/foreach}
        {/if}
        </tbody>
    </table>
</div>
