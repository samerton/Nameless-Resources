{include file='header.tpl'}
{include file='navbar.tpl'}

<div class="container">
    <div class="card">
        <div class="card-body">
            <h2 class="card-title" style="display:inline;">{$NEW_RESOURCE}</h2>

            <div class="float-md-right">
                <a href="{$CANCEL_LINK}" class="btn btn-danger" onclick="return confirm('{$CONFIRM_CANCEL}');">{$CANCEL}</a>
            </div>
            <br /><br />

            {if isset($ERROR)}
                <div class="alert alert-danger">
                    {$ERROR}
                </div>
            {/if}

            <form action="" method="post">
                <div class="form-group">
                    <label for="inputLink">{$EXTERNAL_LINK}</label>
                    <input class="form-control" type="text" name="link" id="inputLink" placeholder="{$EXTERNAL_LINK}">
                </div>

                <div class="form-group">
                    <label for="inputVersion">{$VERSION_TAG}</label>
                    <input class="form-control" type="text" name="version" id="inputVersion" value="1.0.0">
                </div>

                <div class="form-group">
                    <input type="hidden" name="token" value="{$TOKEN}">
                    <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
                </div>

            </form>

        </div>
    </div>
</div>

{include file='footer.tpl'}