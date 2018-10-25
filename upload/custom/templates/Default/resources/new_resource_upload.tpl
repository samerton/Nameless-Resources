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

            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="btn btn-secondary" for="uploadFileButton">
                        <input name="resourceFile" id="uploadFileButton" type="file" style="display:none" accept=".zip,application/zip"
                               onchange="$('#uploadFileName').html(this.files[0].name)">
                        {$CHOOSE_FILE} ({$ZIP_ONLY})
                    </label>
                    <br />
                    <span class='badge badge-info' id="uploadFileName"></span>
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