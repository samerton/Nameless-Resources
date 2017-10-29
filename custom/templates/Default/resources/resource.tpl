{include file='navbar.tpl'}

<div class="container">
  <div class="card">
    <div class="card-body">
	  <h2 style="display:inline;">{$VIEWING_RESOURCE}</h2> {$RELEASE_TAG}
	  <span class="pull-right">
	    {if isset($CAN_UPDATE)}
		  <a href="{$UPDATE_LINK}" class="btn btn-info">{$UPDATE}</a>
		{/if}
		<a href="{$BACK_LINK}" class="btn btn-danger">{$RESOURCE_INDEX}</a>
	  </span>
	  
	  <hr />
	  
	  <div class="row">
	    <div class="col-md-9">
	      <div class="forum-post">
	        {$DESCRIPTION}
	      </div>
		  
		  <hr />
		  
		  <a href="{$DOWNLOAD_URL}" class="btn btn-primary" target="_blank">{$DOWNLOAD}</a>
		  
		  <span class="pull-right">
		    <a href="{$OTHER_RELEASES_LINK}" class="btn btn-info">{$OTHER_RELEASES}</a>
		  </span>
		  
		</div>
		
		<div class="col-md-3">
		  <div class="card">
		    <div class="card-header">
			  {$RESOURCE}
			</div>
			
		    <div class="card-body">
			  <center>
				<div class="star-rating view"> 
				  <span class="fa fa-star-o" data-rating="1" style="color:gold;"></span>
				  <span class="fa fa-star-o" data-rating="2" style="color:gold"></span>
				  <span class="fa fa-star-o" data-rating="3" style="color:gold;"></span>
				  <span class="fa fa-star-o" data-rating="4" style="color:gold;"></span>
				  <span class="fa fa-star-o" data-rating="5" style="color:gold;"></span>
				  <input type="hidden" name="rating" class="rating-value" value="{$RATING}">
				</div>
			    {$VIEWS}<br />
			    {$DOWNLOADS}
			  </center>
			</div>
		  </div>
		  <br />
		  <div class="card">
		    <div class="card-header">
			  {$AUTHOR}
			</div>
			
		    <div class="card-block">
			  <center>
			    <a href="{$AUTHOR_PROFILE}"><img src="{$AUTHOR_AVATAR}" class="rounded" alt="{$AUTHOR_NICKNAME}" style="max-height:80px; max-width:80px;" /></a><br />
				<a href="{$AUTHOR_PROFILE}" style="{$AUTHOR_STYLE}">{$AUTHOR_NICKNAME}</a>
				<hr />
			  </center>
			
			  <a href="{$AUTHOR_RESOURCES}">&raquo; {$VIEW_OTHER_RESOURCES}</a>
			  
			</div>
		  </div>
		</div>
	  </div>
	  
	  <hr />
		
	  <h3>{$REVIEWS}</h3>
	  
	  {if count($COMMENT_ARRAY)}
	    {foreach from=$COMMENT_ARRAY item=comment}
		  <div class="card">
			<div class="card-body">
			  {$comment.content}
			  <hr />
		      <div class="star-rating view" style="display:inline;">
		        <span class="fa fa-star-o" data-rating="1" style="color:gold;"></span>
		        <span class="fa fa-star-o" data-rating="2" style="color:gold"></span>
		        <span class="fa fa-star-o" data-rating="3" style="color:gold;"></span>
		        <span class="fa fa-star-o" data-rating="4" style="color:gold;"></span>
		        <span class="fa fa-star-o" data-rating="5" style="color:gold;"></span>
		        <input type="hidden" name="rating" class="rating-value" value="{$comment.rating}">
		      </div> | {$comment.release_tag} | <span data-toggle="tooltip" data-original-title="{$comment.date_full}">{$comment.date}</span>
			  <span class="pull-right">
			    <a href="{$comment.user_profile}"><img class="rounded-circle" src="{$comment.user_avatar}" style="height:25px;width:25px;" alt="{$comment.username}" /></a> <a href="{$comment.user_profile}" style="{$comment.user_style}">{$comment.username}</a>
			  </span>
			</div>
		  </div>
		  <br />
		{/foreach}
		{$PAGINATION}
	  {else}
	    <p>{$NO_REVIEWS}</p>
	  {/if}
	  
	  {if $LOGGED_IN == true}
	  <h4>{$NEW_REVIEW}</h4>
	  <form action="" method="post">
	    <div class="form-group">
		  <div class="star-rating set"> 
		    <span class="fa fa-star-o" data-rating="1" style="color:gold;"></span>
		    <span class="fa fa-star-o" data-rating="2" style="color:gold"></span>
		    <span class="fa fa-star-o" data-rating="3" style="color:gold;"></span>
		    <span class="fa fa-star-o" data-rating="4" style="color:gold;"></span>
		    <span class="fa fa-star-o" data-rating="5" style="color:gold;"></span>
		    <input type="hidden" name="rating" class="rating-value" value="0">
		  </div>
		</div>
	    <div class="form-group">
		  {if !isset($MARKDOWN)}
		  <textarea style="width:100%" name="content" id="editor" rows="15"></textarea>
		  {else}
		  <textarea class="form-control" style="width:100%" id="markdown" name="content" rows="20"></textarea>
		  <span class="pull-right"><i data-toggle="popover" data-placement="top" data-html="true" data-content="{$MARKDOWN_HELP}" class="fa fa-question-circle text-info" aria-hidden="true"></i></span>
		  {/if}
	    </div>
		<div class="form-group">
		  <input type="hidden" name="token" value="{$TOKEN}">
		  <input type="submit" class="btn btn-primary" value="{$SUBMIT}">
		</div>
	  </form>
	  {/if}
	  
    </div>
  </div>
</div>

{include file='footer.tpl'}