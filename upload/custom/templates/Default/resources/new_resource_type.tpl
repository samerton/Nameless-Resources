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

            {if isset($NO_PAYMENT_EMAIL)}
                <div class="alert alert-warning">
                    {$NO_PAYMENT_EMAIL}
                </div>
            {/if}

            <form action="" method="post">
                <div class="form-group">
                    <label for="inputType">{$TYPE}</label>
                    <select class="form-control" name="type" id="inputType">
                        <option value="free">{$FREE_RESOURCE}</option>
                        <option value="premium">{$PREMIUM_RESOURCE}</option>
                    </select>
                </div>

                <div class="form-group" id="priceFormGroup">
                    <label for="inputPrice">{$PREMIUM_RESOURCE_PRICE}</label>
                    <div class="input-group mb-3">
                        <input type="number" step="0.01" min="0.01" class="form-control" id="inputPrice" name="price" placeholder="{$PRICE}">
                        <div class="input-group-append">
                            <span class="input-group-text">{$CURRENCY}</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <input type="hidden" name="token" value="{$TOKEN}">
                    <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
                </div>

            </form>

        </div>
    </div>
</div>

{* don't include footer.tpl so we can add custom JS *}

{* Punishment modal if necessary *}
{if isset($GLOBAL_WARNING_TITLE)}
    <div class="modal fade show-punishment" data-keyboard="false" data-backdrop="static" id="acknowledgeModal" tabindex="-1" role="dialog" aria-labelledby="acknowledgeModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="acknowledgeModalLabel">{$GLOBAL_WARNING_TITLE}</h4>
                </div>
                <div class="modal-body">
                    {$GLOBAL_WARNING_REASON}
                </div>
                <div class="modal-footer">
                    <a href="{$GLOBAL_WARNING_ACKNOWLEDGE_LINK}" class="btn btn-warning">{$GLOBAL_WARNING_ACKNOWLEDGE}</a>
                </div>
            </div>
        </div>
    </div>
{/if}

<br />
<footer>
    <div class="container">
        <div class="card">
            <div class="card-body">
                {*Social media*}
                {if !empty($SOCIAL_MEDIA_ICONS)}
                    {foreach from=$SOCIAL_MEDIA_ICONS item=icon}
                        <a href="{$icon.link}" target="_blank"><i id="social-{$icon.short}" class="fa fa-{$icon.long}-square fa-3x social"></i></a>
                    {/foreach}
                {/if}
                <div class="float-md-right">
		            <ul class="nav nav-inline dropup">
		                {if $PAGE_LOAD_TIME}
                            <li class="nav-item">
			                    <a class="nav-link" href="#" onClick="return false;" data-toggle="tooltip" id="page_load_tooltip" title="Page loading.."><i class="fa fa-tachometer fa-fw"></i></a>
			                </li>
                        {/if}

                        {foreach from=$FOOTER_NAVIGATION key=name item=item}
                            {if isset($item.items)}
                                {* Dropup *}
                                <li class="nav-item">
				                    <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">{$item.icon} {$item.title}</a>
					                <div class="dropdown-menu">
					                {foreach from=$item.items item=dropdown}
                                        <a class="dropdown-item" href="{$dropdown.link}" target="{$dropdown.target}">{$dropdown.icon} {$dropdown.title}</a>
                                    {/foreach}
					                </div>
				                </li>
			                {else}
				                {* Normal link *}
				                <li class="nav-item">
				                    <a class="nav-link{if isset($item.active)} active{/if}" href="{$item.link}" target="{$item.target}">{$item.icon} {$item.title}</a>
                                </li>
                            {/if}
                        {/foreach}

                        <li class="nav-item">
			                <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
				                &copy; {$SITE_NAME} {'Y'|date}
			                 </a>
			                <div class="dropdown-menu">
				                <a class="dropdown-item" target="_blank" href="https://namelessmc.com/">Powered by NamelessMC</a>
				                <a class="dropdown-item" href="{$TERMS_LINK}">{$TERMS_TEXT}</a>
				                <a class="dropdown-item" href="{$PRIVACY_LINK}">{$PRIVACY_TEXT}</a>
			                </div>
			            </li>
		            </ul>
                </div>
            </div>
        </div>
    </div>
</footer>
<br />

{foreach from=$TEMPLATE_JS item=script}
    {$script}
{/foreach}

{if isset($NEW_UPDATE)}
    {if $NEW_UPDATE_URGENT ne true}
        <script type="text/javascript">
            $(document).ready(function(){
                $('#closeUpdate').click(function(event){
                    event.preventDefault();

                    let expiry = new Date();
                    let length = 3600000;
                    expiry.setTime(expiry.getTime() + length);

                    $.cookie('update-alert-closed', 'true', { path: '/', expires: expiry });
                });

                if($.cookie('update-alert-closed') === 'true'){
                    $('#updateAlert').hide();
                }
            });
        </script>
    {/if}
{/if}

<script type="text/javascript">
    $(document).ready(function() {
        $('#priceFormGroup').hide();
    });

    $('#inputType').change(function(){
        if($('#inputType').val() === "premium"){
            // Show price + email
            $('#priceFormGroup').show();
        } else {
            // Hide price + email
            $('#priceFormGroup').hide();
        }
    });
</script>

</body>
</html>