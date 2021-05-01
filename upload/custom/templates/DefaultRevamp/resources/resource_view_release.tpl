{include file='header.tpl'}
{include file='navbar.tpl'}

<div class="ui container">
  <div class="ui padded segment">
    <div class="ui stackable grid">

      <div class="ui row">
        <div class="ui ten wide column">
          <div class="description">
            <div class="ui relaxed list">
              <div class="item">
                <img class="ui rounded centered image" src="{$RESOURCE_ICON}" alt="{$RESOURCE_NAME}" style="max-height:64; max-width:64px;">
                <div class="content">
                  <h2 class="header" style="display: block;">{$VIEWING_RELEASE}</h2>
                  <span>[{$RELEASE_TAG}] {$RESOURCE_SHORT_DESCRIPTION}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
          <div class="ui six wide column">
              <div class="res right floated">
                  <a href="{$BACK_LINK}" class="ui button">{$BACK}</a>
              </div>
          </div>
      </div>
    </div>

  <div class="ui divider"></div>

    <div class="forum_post">
        {$DESCRIPTION}
    </div>

    <br />

    <span data-toggle="tooltip" data-content="{$DATE_FULL}">{$DATE}</span>

    <div class="res right floated">
        {if isset($DOWNLOAD_URL)}
            <a href="{$DOWNLOAD_URL}" class="ui blue button" target="_blank">{$DOWNLOAD}</a>
        {elseif isset($PURCHASE_FOR_PRICE)}
            <a {if isset($PURCHASE_LINK)}href="{$PURCHASE_LINK}" {else}disabled {/if}class="ui blue button">{$PURCHASE_FOR_PRICE}</a>
        {elseif isset($PAYMENT_PENDING)}
            <button type="button" disabled class="ui blue button">{$PAYMENT_PENDING}</button>
        {/if}
    </div>
  </div>
</div>

{include file='footer.tpl'}
