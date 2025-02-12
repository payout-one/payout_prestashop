<li class="nav-item">
    <a href="#payout" class="nav-link" data-toggle="tab" role="tab">
        {l s='Payout' mod='payout'}
        {if version_compare($ps_version, '1.7.7', '>=')}
            ({if !empty($payout_order_logs)}{$payout_order_logs|@count}{else}0{/if})
        {else}
            <span class="badge">{if !empty($payout_order_logs)}{$payout_order_logs|@count}{else}0{/if}</span>
        {/if}
    </a>
</li>
