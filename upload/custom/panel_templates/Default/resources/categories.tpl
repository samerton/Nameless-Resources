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
                        <h1 class="m-0 text-dark">{$CATEGORIES}</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                            <li class="breadcrumb-item active">{$RESOURCES}</li>
                            <li class="breadcrumb-item active">{$CATEGORIES}</li>
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
                            <a class="btn btn-primary" href="{$NEW_CATEGORY_LINK}">{$NEW_CATEGORY}</a>

                            <hr />

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

                </div>
        </section>
    </div>

    {include file='footer.tpl'}

</div>
<!-- ./wrapper -->

{include file='scripts.tpl'}

</body>
</html>