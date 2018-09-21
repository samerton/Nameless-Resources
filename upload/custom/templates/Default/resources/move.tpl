{include file='header.tpl'}
{include file='navbar.tpl'}

<div class="container">
    <div class="card">
        <div class="card-body">
            <div class="container">
                <h3>{$MOVE_RESOURCE}</h3>

                {if !isset($NO_CATEGORIES)}
                <form action="" method="post">
                    <div class="form-group">
                        <label for="InputCategory">{$MOVE_TO}</label>
                        <select class="form-control" name="category_id" id="InputCategory">
                            {foreach from=$CATEGORIES item=category}
                                <option value="{$category->id}">{$category->name|escape}</option>
                            {/foreach}
                        </select>
                    </div>

                    <div class="form-group">
                        <input type="hidden" name="token" value="{$TOKEN}">
                        <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
                        <a class="btn btn-danger" href="{$CANCEL_LINK}" onclick="return confirm('{$CONFIRM_CANCEL}')">{$CANCEL}</a>
                    </div>
                </form>
                {else}
                <div class="alert alert-danger">{$NO_CATEGORIES}</div>
                {/if}

            </div>
        </div>
    </div>
</div>

{include file='footer.tpl'}