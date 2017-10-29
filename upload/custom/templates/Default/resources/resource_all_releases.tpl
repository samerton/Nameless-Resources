{include file='navbar.tpl'}

<div class="container">
  <div class="card">
    <div class="card-body">
	  <h2 style="display:inline;">{$VIEWING_ALL_RELEASES}</h2>
	  <span class="pull-right">
		<a href="{$BACK_LINK}" class="btn btn-danger">{$BACK}</a>
	  </span>
	  
	  <br /><br />
	  
	  {foreach from=$RELEASES item=release}
	  
	  <a href="{$release.url}">{$release.name}</a>
	  <span class="pull-right">
	    <span data-toggle="tooltip" data-trigger="hover" data-original-title="{$release.date_full}">{$release.date}</span>
	  </span>
	  <hr />
	  <div class="forum-post">
	    {$release.description}
	  </div>
	  <br />
	  {$release.downloads} |
	  <div class="star-rating view" style="display:inline;">
	    <span class="fa fa-star-o" data-rating="1" style="color:gold;"></span>
	    <span class="fa fa-star-o" data-rating="2" style="color:gold"></span>
	    <span class="fa fa-star-o" data-rating="3" style="color:gold;"></span>
	    <span class="fa fa-star-o" data-rating="4" style="color:gold;"></span>
	    <span class="fa fa-star-o" data-rating="5" style="color:gold;"></span>
	    <input type="hidden" name="rating" class="rating-value" value="{$release.rating}">
	  </div>
	  <hr />
	  
	  {/foreach}
	  
	  {$PAGINATION}
	  
    </div>
  </div>
</div>

{include file='footer.tpl'}