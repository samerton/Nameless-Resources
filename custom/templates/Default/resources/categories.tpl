<div class="card">
  <div class="card-block">
    <h2>{$CATEGORIES_TITLE}</h2>
    <ul class="nav nav-pills flex-column">
	  {foreach from=$CATEGORIES item=item}
	  <li class="nav-item">
		<a class="nav-link{if isset($item.active)} active{/if}" href="{$item.link}">{$item.name}</a>
	  </li>
      {/foreach}
    </ul>
  </div>
</div>
