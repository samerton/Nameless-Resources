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
                        <h5 style="display:inline">{$EDITING_CATEGORY}</h5>
                        <div class="float-md-right">
                            <button role="button" class="btn btn-warning" onclick="showCancelModal()">{$CANCEL}</button>
                        </div>
                        <hr />

                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}

                        <form role="form" action="" method="post">
                            <div class="form-group">
                                <label for="catname">{$CATEGORY_NAME}</label>
                                <input class="form-control" type="text" name="title" id="catname" value="{$CATEGORY_NAME_VALUE}" placeholder="{$CATEGORY_NAME}" autocomplete="off">
                            </div>
                            <div class="form-group">
                                <label for="catdesc">{$CATEGORY_DESCRIPTION}</label>
                                <textarea id="catdesc" name="description" placeholder="{$CATEGORY_DESCRIPTION}" class="form-control" rows="3">{$CATEGORY_DESCRIPTION_VALUE}</textarea>
                            </div>
                            <script>
                              var groups = [];
                              groups.push("0");
                            </script>
                            <input type="hidden" name="perm-post-0" value="0" />
                            <input type="hidden" name="perm-move_resource-0" value="0" />
                            <input type="hidden" name="perm-edit_resource-0" value="0" />
                            <input type="hidden" name="perm-delete_resource-0" value="0" />
                            <input type="hidden" name="perm-edit_review-0" value="0" />
                            <input type="hidden" name="perm-delete_review-0" value="0" />
                            <input type="hidden" name="perm-premium-0" value="0" />

                            <strong>{$CATEGORY_PERMISSIONS}</strong>
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>{$GROUP}</th>
                                    <th>{$CAN_VIEW_CATEGORY}</th>
                                    <th>{$CAN_DOWNLOAD_RESOURCES}</th>
                                    <th>{$CAN_POST_RESOURCES}</th>
                                    <th>{$CAN_POST_PREMIUM_RESOURCES}</th>
                                </tr>
                                </thead>
                                <tbody>
                                {* Guest first *}
                                <tr>
                                    <td>{$GUESTS}</td>
                                    <td><input type="hidden" name="perm-view-0" value="0" /><input onclick="colourUpdate(this);" name="perm-view-0" id="Input-view-0" value="1" type="checkbox"{if $GUEST_PERMISSIONS|count && $GUEST_PERMISSIONS.0->can_view eq 1} checked{/if} /></td>
                                    <td><input type="hidden" name="perm-download-0" value="0" /><input onclick="colourUpdate(this);" name="perm-download-0" id="Input-download-0" value="1" type="checkbox"{if $GUEST_PERMISSIONS|count && $GUEST_PERMISSIONS.0->can_download eq 1} checked{/if} /></td>
                                    <td class="bg-danger"></td>
                                    <td class="bg-danger"></td>
                                </tr>
                                {foreach from=$GROUP_PERMISSIONS item=group}
                                    <tr>
                                        <td onclick="toggleAll(this);">{$group->name|escape}</td>
                                        <td><input type="hidden" name="perm-view-{$group->id}" value="0" /><input onclick="colourUpdate(this);" name="perm-view-{$group->id}" id="Input-view-{$group->id}" value="1" type="checkbox"{if $group->can_view eq 1} checked{/if} /></td>
                                        <td><input type="hidden" name="perm-download-{$group->id}" value="0" /><input onclick="colourUpdate(this);" name="perm-download-{$group->id}" id="Input-download-{$group->id}" value="1" type="checkbox"{if $group->can_download eq 1} checked{/if} /></td>
                                        <td><input type="hidden" name="perm-post-{$group->id}" value="0" /><input onclick="colourUpdate(this);" name="perm-post-{$group->id}" id="Input-post-{$group->id}" value="1" type="checkbox"{if $group->can_post eq 1} checked{/if} /></td>
                                        <td><input type="hidden" name="perm-premium-{$group->id}" value="0" /><input onclick="colourUpdate(this);" name="perm-premium-{$group->id}" id="Input-premium-{$group->id}" value="1" type="checkbox"{if $group->can_post_premium eq 1} checked{/if} /></td>
                                    </tr>
                                    <script>groups.push("{$group->id}");</script>
                                {/foreach}
                                </tbody>
                            </table>
                            <strong>{$MODERATION}</strong>
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>{$GROUP}</th>
                                    <th>{$CAN_MOVE_RESOURCES}</th>
                                    <th>{$CAN_EDIT_RESOURCES}</th>
                                    <th>{$CAN_DELETE_RESOURCES}</th>
                                    <th>{$CAN_EDIT_REVIEWS}</th>
                                    <th>{$CAN_DELETE_REVIEWS}</th>
                                </tr>
                                </thead>
                                <tbody>
                                {foreach from=$GROUP_PERMISSIONS item=group}
                                    <tr>
                                        <td onclick="toggleAll(this);">{$group->name|escape}</td>
                                        <td><input type="hidden" name="perm-move_resource-{$group->id}" value="0" /><input onclick="colourUpdate(this);" name="perm-move_resource-{$group->id}" id="Input-move_resource-{$group->id}" value="1" type="checkbox"{if $group->can_move eq 1} checked{/if} /></td>
                                        <td><input type="hidden" name="perm-edit_resource-{$group->id}" value="0" /><input onclick="colourUpdate(this);" name="perm-edit_resource-{$group->id}" id="Input-edit_resource-{$group->id}" value="1" type="checkbox"{if $group->can_edit eq 1} checked{/if} /></td>
                                        <td><input type="hidden" name="perm-delete_resource-{$group->id}" value="0" /><input onclick="colourUpdate(this);" name="perm-delete_resource-{$group->id}" id="Input-delete_resource-{$group->id}" value="1" type="checkbox"{if $group->can_delete eq 1} checked{/if} /></td>
                                        <td><input type="hidden" name="perm-edit_review-{$group->id}" value="0" /><input onclick="colourUpdate(this);" name="perm-edit_review-{$group->id}" id="Input-edit_review-{$group->id}" value="1" type="checkbox"{if $group->can_edit_review eq 1} checked{/if} /></td>
                                        <td><input type="hidden" name="perm-delete_review-{$group->id}" value="0" /><input onclick="colourUpdate(this);" name="perm-delete_review-{$group->id}" id="Input-delete_review-{$group->id}" value="1" type="checkbox"{if $group->can_delete_review eq 1} checked{/if} /></td>
                                    </tr>
                                {/foreach}
                                </tbody>
                            </table>
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

    <div class="modal fade" id="cancelModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{$ARE_YOU_SURE}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    {$CONFIRM_CANCEL}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{$NO}</button>
                    <a href="{$CANCEL_LINK}" class="btn btn-primary">{$YES}</a>
                </div>
            </div>
        </div>
    </div>

    <!-- End Wrapper -->
</div>

{include file='scripts.tpl'}
<script type="text/javascript">
  function showCancelModal(){
    $('#cancelModal').modal().show();
  }

  function colourUpdate(that) {
    var x = that.parentElement;
    if(that.checked) {
      x.className = "bg-success";
    } else {
      x.className = "bg-danger";
    }
  }
  function toggle(group) {
    if(document.getElementById('Input-view-' + group).checked) {
      document.getElementById('Input-view-' + group).checked = false;
    } else {
      document.getElementById('Input-view-' + group).checked = true;
    }
    if(document.getElementById('Input-post-' + group).checked) {
      document.getElementById('Input-post-' + group).checked = false;
    } else {
      document.getElementById('Input-post-' + group).checked = true;
    }
    if(document.getElementById('Input-premium-' + group).checked) {
      document.getElementById('Input-premium-' + group).checked = false;
    } else {
      document.getElementById('Input-premium-' + group).checked = true;
    }
    if(document.getElementById('Input-download-' + group).checked) {
      document.getElementById('Input-download-' + group).checked = false;
    } else {
      document.getElementById('Input-download-' + group).checked = true;
    }
    if(document.getElementById('Input-move_resource-' + group).checked) {
      document.getElementById('Input-move_resource-' + group).checked = false;
    } else {
      document.getElementById('Input-move_resource-' + group).checked = true;
    }
    if(document.getElementById('Input-edit_resource-' + group).checked) {
      document.getElementById('Input-edit_resource-' + group).checked = false;
    } else {
      document.getElementById('Input-edit_resource-' + group).checked = true;
    }
    if(document.getElementById('Input-delete_resource-' + group).checked) {
      document.getElementById('Input-delete_resource-' + group).checked = false;
    } else {
      document.getElementById('Input-delete_resource-' + group).checked = true;
    }
    if(document.getElementById('Input-edit_review-' + group).checked) {
      document.getElementById('Input-edit_review-' + group).checked = false;
    } else {
      document.getElementById('Input-edit_review-' + group).checked = true;
    }
    if(document.getElementById('Input-delete_review-' + group).checked) {
      document.getElementById('Input-delete_review-' + group).checked = false;
    } else {
      document.getElementById('Input-delete_review-' + group).checked = true;
    }

    colourUpdate(document.getElementById('Input-view-' + group));
    colourUpdate(document.getElementById('Input-post-' + group));
    colourUpdate(document.getElementById('Input-move_resource-' + group));
    colourUpdate(document.getElementById('Input-edit_resource-' + group));
    colourUpdate(document.getElementById('Input-delete_resource-' + group));
    colourUpdate(document.getElementById('Input-edit_review-' + group));
    colourUpdate(document.getElementById('Input-delete_review-' + group));
    colourUpdate(document.getElementById('Input-premium-' + group));
    colourUpdate(document.getElementById('Input-download-' + group));
  }
  for(var g in groups) {
    colourUpdate(document.getElementById('Input-view-' + groups[g]));
    colourUpdate(document.getElementById('Input-download-' + groups[g]));
    if(groups[g] != "0") {
      colourUpdate(document.getElementById('Input-post-' + groups[g]));
      colourUpdate(document.getElementById('Input-move_resource-' + groups[g]));
      colourUpdate(document.getElementById('Input-edit_resource-' + groups[g]));
      colourUpdate(document.getElementById('Input-delete_resource-' + groups[g]));
      colourUpdate(document.getElementById('Input-edit_review-' + groups[g]));
      colourUpdate(document.getElementById('Input-delete_review-' + groups[g]));
      colourUpdate(document.getElementById('Input-premium-' + groups[g]));
    }
  }

  // Toggle all columns in row
  function toggleAll(that){
    var first = (($(that).parents('tr').find(':checkbox').first().is(':checked') == true) ? false : true);
    $(that).parents('tr').find(':checkbox').each(function(){
      $(this).prop('checked', first);
      colourUpdate(this);
    });
  }

  $(document).ready(function(){
    $('td').click(function() {
      let checkbox = $(this).find('input:checkbox');
      let id = checkbox.attr('id');

      if(checkbox.is(':checked')){
        checkbox.prop('checked', false);

        colourUpdate(document.getElementById(id));
      } else {
        checkbox.prop('checked', true);

        colourUpdate(document.getElementById(id));
      }
    }).children().click(function(e) {
      e.stopPropagation();
    });
  });
</script>

</body>

</html>
