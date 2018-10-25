{include file='header.tpl'}
{include file='navbar.tpl'}

<div class="container">
    <div class="card">
        <div class="card-body">
            <div class="container">
                <div style="text-align: center;"><h4>{$CONFIRM_DELETE_RESOURCE}</h4></div>

                {if isset($ERRORS)}
                    <div class="alert alert-danger">
                        {foreach from=$ERRORS item=error}
                            {$error}<br />
                        {/foreach}
                    </div>
                {/if}

                <form action="" method="post">
                    <div style="text-align: center;">
                      <input type="hidden" name="token" value="{$TOKEN}">
                      <a class="btn btn-warning" href="{$CANCEL_LINK}">{$CANCEL}</a>
                      <input type="submit" class="btn btn-danger" value="{$DELETE}">
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{include file='footer.tpl'}