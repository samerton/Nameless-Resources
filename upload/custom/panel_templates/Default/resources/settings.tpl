{include file='header.tpl'}

<body id="page-top">

    <!-- Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        {include file='sidebar.tpl'}

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main content -->
            <div id="content">

                <!-- Topbar -->
                {include file='navbar.tpl'}

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">{$SETTINGS}</h1>
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                            <li class="breadcrumb-item active">{$RESOURCES}</li>
                            <li class="breadcrumb-item active">{$SETTINGS}</li>
                        </ol>
                    </div>

                    <!-- Update Notification -->
                    {include file='includes/update.tpl'}

                    <div class="card shadow mb-4">
                        <div class="card-body">

                            <!-- Success and Error Alerts -->
                            {include file='includes/alerts.tpl'}

                            {if isset($UPLOADS_DIRECTORY_WRITABLE_INFO)}
                            <div class="callout callout-danger">
                                <h5><i class="icon fa fa-exclamation-triangle"></i> {$WARNING}</h5>
                                {$UPLOADS_DIRECTORY_WRITABLE_INFO}
                            </div>
                            {/if}

                            <form action="" method="post">
                                <div class="form-group">
                                    <label for="inputCurrency">{$CURRENCY}</label>
                                    <input class="form-control" type="text" value="{$CURRENCY_VALUE}" id="inputCurrency"
                                        name="currency">
                                </div>
                                <div class="form-group">
                                    <label for="inputFilesize">{$FILESIZE}</label>
                                    <input class="form-control" type="number" value="{$FILESIZE_VALUE}"
                                        id="inputFilesize" name="filesize" min="1">
                                </div>
                                <div class="form-group">
                                    <label for="inputPrePurchaseInfo">{$PRE_PURCHASE_INFO}</label>
                                    <textarea id="inputPrePurchaseInfo"
                                        name="pre_purchase_info">{$PRE_PURCHASE_INFO_VALUE}</textarea>
                                </div>

                                <h5>{$PAYPAL_API_DETAILS}</h5>

                                <div class="card shadow border-left-primary">
                                    <div class="card-body">
                                        <h5><i class="icon fa fa-info-circle"></i> {$INFO}</h5>
                                        {$PAYPAL_API_DETAILS_INFO}
                                    </div>
                                </div>
                                <br />

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

                    <!-- End Page Content -->
                </div>

                <!-- End Main Content -->
            </div>

            {include file='footer.tpl'}

            <!-- End Content Wrapper -->
        </div>

        <!-- End Wrapper -->
    </div>

    {include file='scripts.tpl'}

</body>

</html>