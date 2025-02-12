{if isset($notifications) && (!empty($notifications['errors']) || !empty($notifications['info']))}
    <div class="payout-notifications sf-contener clearfix col-lg-12">
        {if !empty($notifications['errors'])}
            {foreach from=$notifications['errors'] item=error}
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    {$error}
                </div>
            {/foreach}
        {/if}

        {if !empty($notifications['info'])}
            {foreach from=$notifications['info'] item=info}
                <div class="alert alert-info alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    {$info}
                </div>
            {/foreach}
        {/if}
    </div>
{/if}
