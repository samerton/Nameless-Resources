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
                    <h1 class="h3 mb-0 text-gray-800">{$CATEGORIES}</h1>
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                        <li class="breadcrumb-item active">{$RESOURCES}</li>
                        <li class="breadcrumb-item active">{$CATEGORIES}</li>
                    </ol>
                </div>

                <!-- Update Notification -->
                {include file='includes/update.tpl'}

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <a class="btn btn-primary" href="{$NEW_CATEGORY_LINK}">{$NEW_CATEGORY}</a>
                        <hr />

                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}

                        {if count($CATEGORIES_LIST)}
                            <div class="card card-default">
                                <div class="card-header">
                                    {$CATEGORIES}
                                </div>
                                <div class="card-body">
                                    {assign var="number" value=$CATEGORIES_LIST|count}
                                    {assign var="i" value=1}
                                    {foreach from=$CATEGORIES_LIST item=category}
                                        <a href="{$category.edit_link}">{$category.name}</a>
                                        <div class="float-md-right">
                                            {if $i > 1}
                                                <a href="{$category.order_up}" class="btn btn-primary"><i class="fa fa-chevron-up" aria-hidden="true"></i></a>
                                            {/if}
                                            {if $i < $number}
                                                <a href="{$category.order_down}" class="btn btn-info"><i class="fa fa-chevron-down" aria-hidden="true"></i></a>
                                            {/if}
                                            <a href="{$category.delete_link}" class="btn btn-danger"><i class="fa fa-trash" aria-hidden="true"></i></a>
                                        </div>
                                        <br />
                                        {$category.description}
                                        {if $i < $number}
                                            <hr />
                                        {/if}
                                        {assign var="i" value=$i+1}
                                    {/foreach}
                                </div>
                            </div>
                        {else}
                            {$NO_CATEGORIES}
                        {/if}

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
