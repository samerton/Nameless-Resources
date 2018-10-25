{include file='header.tpl'}
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    {include file='navbar.tpl'}
    {include file='sidebar.tpl'}

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 text-dark">{$SETTINGS}</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                            <li class="breadcrumb-item active">{$RESOURCES}</li>
                            <li class="breadcrumb-item active">{$SETTINGS}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                {if isset($NEW_UPDATE)}
                {if $NEW_UPDATE_URGENT eq true}
                <div class="alert alert-danger">
                    {else}
                    <div class="alert alert-primary alert-dismissible" id="updateAlert">
                        <button type="button" class="close" id="closeUpdate" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        {/if}
                        {$NEW_UPDATE}
                        <br />
                        <a href="{$UPDATE_LINK}" class="btn btn-primary" style="text-decoration:none">{$UPDATE}</a>
                        <hr />
                        {$CURRENT_VERSION}<br />
                        {$NEW_VERSION}
                    </div>
                    {/if}

                    <div class="card">
                        <div class="card-body">
                            {if isset($SUCCESS)}
                                <div class="alert alert-success alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <h5><i class="icon fa fa-check"></i> {$SUCCESS_TITLE}</h5>
                                    {$SUCCESS}
                                </div>
                            {/if}

                            {if isset($ERRORS) && count($ERRORS)}
                                <div class="alert alert-danger alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    <h5><i class="icon fa fa-warning"></i> {$ERRORS_TITLE}</h5>
                                    <ul>
                                        {foreach from=$ERRORS item=error}
                                            <li>{$error}</li>
                                        {/foreach}
                                    </ul>
                                </div>
                            {/if}

                            {if isset($UPLOADS_DIRECTORY_WRITABLE_INFO)}
                                <div class="callout callout-danger">
                                    <h5><i class="icon fa fa-exclamation-triangle"></i> {$WARNING}</h5>
                                    {$UPLOADS_DIRECTORY_WRITABLE_INFO}
                                </div>
                            {/if}

                            <form action="" method="post">
                                <div class="form-group">
                                    <label for="inputCurrency">{$CURRENCY}</label>
                                    <input class="form-control" type="text" value="{$CURRENCY_VALUE}" id="inputCurrency" name="currency">
                                </div>
                                <div class="form-group">
                                    <label for="inputFilesize">{$FILESIZE}</label>
                                    <input class="form-control" type="number" value="{$FILESIZE_VALUE}" id="inputFilesize" name="filesize" min="1">
                                </div>
                                <div class="form-group">
                                    <label for="inputPrePurchaseInfo">{$PRE_PURCHASE_INFO}</label>
                                    <textarea id="inputPrePurchaseInfo" name="pre_purchase_info">{$PRE_PURCHASE_INFO_VALUE}</textarea>
                                </div>

                                <h5>{$PAYPAL_API_DETAILS}</h5>

                                <div class="callout callout-info">
                                    <h5><i class="icon fa fa-info-circle"></i> {$INFO}</h5>
                                    {$PAYPAL_API_DETAILS_INFO}
                                </div>

                                <div class="form-group">
                                    <label for="inputPaypalId">{$PAYPAL_CLIENT_ID}</label>
                                    <input class="form-control" type="text" id="inputPaypalId" name="client_id">
                                </div>

                                <div class="form-group">
                                    <label for="inputPaypalSecret">{$PAYPAL_CLIENT_SECRET}</label>
                                    <input class="form-control" type="text" id="inputPaypalSecret" name="client_secret">
                                </div>

                                <div class="form-group">
                                    <input type="hidden" name="token" value="{$TOKEN}">
                                    <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
                                </div>
                            </form>

                        </div>
                    </div>

                    <!-- Spacing -->
                    <div style="height:1rem;"></div>

                </div>
        </section>
    </div>

    {include file='footer.tpl'}

</div>
<!-- ./wrapper -->

{include file='scripts.tpl'}

</body>
</html>