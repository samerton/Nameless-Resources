<div class="card card-inverse">
  <div class="card-block">
    <h2>{$CATEGORIES_TITLE}</h2>
    <ul class="nav nav-pills nav-stacked">
	  {foreach from=$CATEGORIES item=item}
	  <li class="nav-item">
		<a class="nav-link{if isset($item.active)} active{/if}" href="{$item.link}">{$item.name}</a>
	  </li>
      {/foreach}
    </ul>
  </div>
</div>