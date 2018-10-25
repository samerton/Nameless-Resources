{include file='header.tpl'}
{include file='navbar.tpl'}

<div class="container">
    <div class="card">
        <div class="card-body">
            <div class="container">
                <h3>{$EDITING_RESOURCE}</h3>

                {if isset($ERRORS)}
                    <div class="alert alert-danger">
                        {foreach from=$ERRORS item=error}
                            {$error}<br />
                        {/foreach}
                    </div>
                {/if}

                <form action="" method="post">
                    <div class="form-group">
                        <label for="inputTitle">{$NAME}</label>
                        <input type="text" id="inputTitle" class="form-control" name="title" placeholder="{$NAME}" value="{$RESOURCE_NAME}">
                    </div>

                    {if isset($MARKDOWN)}
                        <div class="form-group">
                            <label for="markdown">{$DESCRIPTION}</label>
                            <textarea class="form-control" style="width:100%" id="markdown" name="description" rows="20"></textarea>
                            <span class="pull-right"><i data-toggle="popover" data-placement="top" data-html="true" data-content="{$MARKDOWN_HELP}" class="fa fa-question-circle text-info" aria-hidden="true"></i></span>
                        </div>
                    {else}
                        <div class="form-group">
                            <label for="editor">{$DESCRIPTION}</label>
                            <textarea name="description" id="editor" rows="3">{$RESOURCE_DESCRIPTION}</textarea>
                        </div>
                    {/if}

                    <div class="form-group">
                        <label for="inputContributors">{$CONTRIBUTORS}</label>
                        <input type="text" class="form-control" name="contributors" id="inputContributors" placeholder="{$CONTRIBUTORS}" value="{$RESOURCE_CONTRIBUTORS}">
                    </div>

                    {if isset($PRICE)}
                        <div class="form-group" id="priceFormGroup">
                            <label for="inputPrice">{$PRICE}</label>
                            <div class="input-group mb-3">
                                <input type="number" step="0.01" min="0.01" class="form-control" id="inputPrice" name="price" value="{$RESOURCE_PRICE}">
                                <div class="input-group-append">
                                    <span class="input-group-text">{$CURRENCY}</span>
                                </div>
                            </div>
                        </div>
                    {/if}

                    <input type="hidden" name="token" value="{$TOKEN}">
                    <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
                    <a class="btn btn-danger" href="{$CANCEL_LINK}" onclick="return confirm('{$CONFIRM_CANCEL}')">{$CANCEL}</a>
                </form>


            </div>
        </div>
    </div>
</div>

{include file='footer.tpl'}