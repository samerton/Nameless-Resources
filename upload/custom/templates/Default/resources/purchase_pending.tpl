{include file='header.tpl'}
{include file='navbar.tpl'}

<div class="container">
    <div class="card">
        <div class="card-body">
            <h2 style="display:inline;">{$PURCHASING_RESOURCE}</h2>

            <hr />

            <div class="alert alert-info">{$PURCHASE_COMPLETE}</div>

            <hr />

            <a href="{$BACK_LINK}" class="btn btn-primary">{$BACK}</a>

        </div>
    </div>
</div>

{include file='footer.tpl'}
