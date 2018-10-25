{include file='header.tpl'}
{include file='navbar.tpl'}

<div class="container">
  <div class="card">
    <div class="card-body">

      <div class="row">
        <div class="col-md-9">
          <h2 style="display:inline;">{$VIEWING_RELEASE}</h2>
        </div>
        <div class="col-md-3">
          <span class="float-right"><a href="{$BACK_LINK}" class="btn btn-danger">{$BACK}</a></span>
        </div>
      </div>

	  <br /><br />

	  <div class="forum-post">
	    {$DESCRIPTION}
	  </div>

	  <hr />

	  <span data-toggle="tooltip" data-trigger="hover" data-original-title="{$DATE_FULL}">{$DATE}</span>

	  <div class="float-md-right">
		  {if isset($DOWNLOAD_URL)}
              <a href="{$DOWNLOAD_URL}" class="btn btn-primary" target="_blank">{$DOWNLOAD}</a>
		  {elseif isset($PURCHASE_FOR_PRICE)}
			  <a {if isset($PURCHASE_LINK)}href="{$PURCHASE_LINK}" {else}disabled {/if}class="btn btn-primary">{$PURCHASE_FOR_PRICE}</a>
		  {elseif isset($PAYMENT_PENDING)}
			  <button type="button" disabled class="btn btn-primary">{$PAYMENT_PENDING}</button>
          {/if}
      </div>

    </div>
  </div>
</div>

{include file='footer.tpl'}
