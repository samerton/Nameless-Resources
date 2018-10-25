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
	  
	  <form action="" method="post">
	    <div class="form-group">
		  <label for="inputCategory">{$SELECT_CATEGORY} <small>{$REQUIRED}</small></label>
		  <select name="category" class="form-control" id="inputCategory">
		    {foreach from=$CATEGORIES item=category}
			<option value="{$category.id}">{$category.name}</option>
			{/foreach}
		  </select>
		</div>
		
		<div class="form-group">
		  <label for="inputName">{$RESOURCE_NAME} <small>{$REQUIRED}</small></label>
		  <input type="text" class="form-control" name="name" id="inputName">
		</div>
		
		<div class="form-group">
		  <label for="inputDescription">{$RESOURCE_DESCRIPTION} <small>{$REQUIRED}</small></label>
		  
		  {if !isset($MARKDOWN)}
		  <textarea style="width:100%" name="content" id="reply" rows="15">{$CONTENT}</textarea>
		  
		  {else}

		  <textarea class="form-control" style="width:100%" id="markdown" name="content" rows="15">{$CONTENT}</textarea>
		  <span class="pull-right"><i data-toggle="popover" data-placement="top" data-html="true" data-content="{$MARKDOWN_HELP}" class="fa fa-question-circle text-info" aria-hidden="true"></i></span>
		  
		  {/if}
		</div>
		
		<div class="form-group">
		  <label for="inputContributors">{$CONTRIBUTORS}</label>
		  <input type="text" class="form-control" name="contributors" id="inputContributors">
		</div>

		<div class="form-group">
		  <label for="inputReleaseType">{$RELEASE_TYPE}</label>
		  <select class="form-control" id="inputReleaseType" name="type">
		    <option value="zip">{$ZIP_FILE}</option>
		    <option value="github">{$GITHUB_RELEASE}</option>
		  </select>
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