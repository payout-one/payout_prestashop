<li class="nav-item">
    <a href="#payout-checkout" class="nav-link" data-toggle="tab" role="tab">
        {l s='Payout checkout' mod='payout'}
        {if version_compare($ps_version, '1.7.7', '>=')}
            ({if !empty($payout_order_logs)}{$payout_order_logs|@count}{else}0{/if})
        {else}
            <span class="badge">{if !empty($payout_order_logs)}{$payout_order_logs|@count}{else}0{/if}</span>
        {/if}
    </a>
</li>

<li class="nav-item">
    <a href="#payout-refund" class="nav-link" data-toggle="tab" role="tab">
        {l s='Payout refund' mod='payout'}
        {if version_compare($ps_version, '1.7.7', '>=')}
            ({if !empty($payout_order_refund_records)}{$payout_order_refund_records|@count}{else}0{/if})
        {else}
            <span class="badge">{if !empty($payout_order_refund_records)}{$payout_order_refund_records|@count}{else}0{/if}</span>
        {/if}
    </a>
</li>
