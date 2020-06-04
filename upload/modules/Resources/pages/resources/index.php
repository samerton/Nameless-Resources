<?php 
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr5
 *
 *  License: MIT
 *
 *  Resources index page
 */

// Always define page name
define('PAGE', 'resources');

require(ROOT_PATH . '/modules/Resources/classes/Resources.php');
$resources = new Resources();

// Initialise
$timeago = new Timeago();

// Get page
if(isset($_GET['p'])){
	if(!is_numeric($_GET['p'])){
		Redirect::to(URL::build('/resources'));
		die();
	} else {
		if($_GET['p'] == 1){ 
			// Avoid bug in pagination class
			Redirect::to(URL::build('/resources'));
			die();
		}
		$p = $_GET['p'];
	}
} else {
	$p = 1;
}

// Get user group ID
if($user->isLoggedIn()) $user_group = $user->data()->group_id; else $user_group = 0;

$page_title = $resource_language->get('resources', 'resources') . ' - ' . str_replace('{x}', $p, $language->get('general', 'page_x'));
require_once(ROOT_PATH . '/core/templates/frontend_init.php');

// Obtain categories + permissions from database
$categories = $queries->orderWhere('resources_categories', 'id <> 0', 'display_order');
$permissions = $queries->getWhere('resources_categories_permissions', array('group_id', '=', $user_group));

// Assign to Smarty array
$category_array = array();
foreach($categories as $category){
  // Check permissions
  foreach($permissions as $permission){
	if($permission->category_id == $category->id && $permission->view == 1)
		$category_array[] = array(
			'name' => Output::getClean($category->name),
			'link' => URL::build('/resources/category/' . $category->id . '-' . Util::stringToURL($category->name))
		);
  }
}
$categories = null;

// Get latest releases
$latest_releases = DB::getInstance()->query('SELECT * FROM nl2_resources WHERE category_id IN (SELECT category_id FROM nl2_resources_categories_permissions WHERE group_id = ? AND view = 1) ORDER BY updated DESC', array($user_group))->results();

// Pagination
$paginator = new Paginator((isset($template_pagination) ? $template_pagination : array()));
$results = $paginator->getLimited($latest_releases, 10, $p, count($latest_releases));
$pagination = $paginator->generate(7, URL::build('/resources/', true));

$smarty->assign('PAGINATION', $pagination);

// Array to pass to template
$releases_array = array();

if(count($latest_releases)){
	// Display the correct number of resources
	$n = 0;

	while($n < count($results->data) && isset($results->data[$n]->id)){
		// Get actual resource info
		$resource = $results->data[$n];

		// Get category
		$category = $queries->getWhere('resources_categories', array('id', '=', $resource->category_id));
		if(count($category)){
			  $category = Output::getClean($category[0]->name);
		} else {
			  $category = 'n/a';
		}

		if(!isset($releases_array[$resource->id])){
			$releases_array[$resource->id] = array(
				'link' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)),
				'name' => Output::getClean($resource->name),
				'description' => substr(strip_tags(htmlspecialchars_decode($resource->description)), 0, 60) . '...',
				'author' => Output::getClean($user->idToNickname($resource->creator_id)),
				'author_style' => $user->getGroupClass($resource->creator_id),
				'author_profile' => URL::build('/profile/' . Output::getClean($user->idToName($resource->creator_id))),
				'author_avatar' => $user->getAvatar($resource->creator_id, '../', 30),
				'downloads' => str_replace('{x}', $resource->downloads, $resource_language->get('resources', 'x_downloads')),
				'views' => str_replace('{x}', $resource->views, $resource_language->get('resources', 'x_views')),
				'rating' => round($resource->rating / 10),
				'version' => $resource->latest_version,
				'category' => str_replace('{x}', $category, $resource_language->get('resources', 'in_category_x')),
				'updated' => str_replace('{x}', $timeago->inWords(date('d M Y, H:i', $resource->updated), $language->getTimeLanguage()), $resource_language->get('resources', 'updated_x')),
				'updated_full' => date('d M Y, H:i', $resource->updated)
			);
		}

		$n++;
	}
} else $releases_array = null;

// Assign Smarty variables
$smarty->assign(array(
	'RESOURCES' => $resource_language->get('resources', 'resources'),
	'CATEGORIES_TITLE' => $resource_language->get('resources', 'categories'),
	'CATEGORIES' => $category_array,
	'LATEST_RESOURCES' => $releases_array,
	'PAGINATION' => $pagination,
	'NO_RESOURCES' => $resource_language->get('resources', 'no_resources'),
	'RESOURCE' => $resource_language->get('resources', 'resource'),
	'STATS' => $resource_language->get('resources', 'stats'),
	'AUTHOR' => $resource_language->get('resources', 'author')
));

if($user->isLoggedIn() && $resources->canPostResourceInAnyCategory($user->data()->group_id, $user->data()->secondary_groups)){
	$smarty->assign(array(
		'NEW_RESOURCE_LINK' => URL::build('/resources/new'),
		'NEW_RESOURCE' => $resource_language->get('resources', 'new_resource')
	));
}

$template->addJSScript('
var $star_rating = $(\'.star-rating.view .far\');

var SetRatingStar = function(type = 0) {
	if(type === 0) {
		return $star_rating.each(function () {
			if (parseInt($(this).parent().children(\'input.rating-value\').val()) >= parseInt($(this).data(\'rating\'))) {
				return $(this).removeClass(\'far\').addClass(\'fas\');
			} else {
				return $(this).removeClass(\'fas\').addClass(\'far\');
			}
		});
	}
};

SetRatingStar();
');

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets, $template);

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

$template->displayTemplate('resources/index.tpl', $smarty);