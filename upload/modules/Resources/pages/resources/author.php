<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr5
 *
 *  License: MIT
 *
 *  Resources - author view
 */
// Always define page name
define('PAGE', 'resources');
define('RESOURCE_PAGE', 'view_author');

// Initialise
$timeago = new Timeago();
require(ROOT_PATH . '/modules/Resources/classes/Resources.php');
$resources = new Resources();

// Get page
if(isset($_GET['p'])){
    if(!is_numeric($_GET['p'])){
        Redirect::to(URL::build('/resources/'));
        die();
    } else {
        $p = $_GET['p'];
    }
} else {
    $p = 1;
}

// Get user group ID
if($user->isLoggedIn()) $user_group = $user->data()->group_id; else $user_group = 0;

// Get author
$aid = explode('/', $route);
$aid = $aid[count($aid) - 1];

if(!isset($aid[count($aid) - 1])){
    Redirect::to(URL::build('/resources'));
    die();
}

$aid = explode('-', $aid);
if(!is_numeric($aid[0])){
    Redirect::to(URL::build('/resources'));
    die();
}
$aid = $aid[0];

$author = $queries->getWhere('users', array('id', '=', $aid));
if(!count($author)){
    Redirect::to(URL::build('/resources'));
    die();
}
$author = $author[0];

$page_title = str_replace('{x}', Output::getClean($user->idToName($author->id)), $resource_language->get('resources', 'viewing_resources_by_x'));
require_once(ROOT_PATH . '/core/templates/frontend_init.php');

// Obtain categories + permissions from database
$categories = $queries->getWhere('resources_categories', array('id', '<>', 0));
$permissions = $queries->getWhere('resources_categories_permissions', array('group_id', '=', $user_group));

// Assign to Smarty array
$category_array = array();
foreach($categories as $category){
    // Check permissions
    foreach($permissions as $permission){
        if($permission->category_id == $category->id && $permission->view == 1) {
            $to_array = array(
                'name' => Output::getClean($category->name),
                'link' => URL::build('/resources/category/' . $category->id . '-' . Util::stringToURL($category->name))
            );
            $category_array[] = $to_array;
        }
    }
}
$categories = null;

// Get latest releases
$latest_releases = $queries->orderWhere('resources', 'creator_id =' . $author->id, 'updated', 'DESC');

// Pagination
$paginator = new Paginator((isset($template_pagination) ? $template_pagination : array()));
$results = $paginator->getLimited($latest_releases, 10, $p, count($latest_releases));
$pagination = $paginator->generate(7, URL::build('/resources/author/' . $author->id . '-' . Util::stringToURL($author->username) . '/', true));

$smarty->assign('PAGINATION', $pagination);

// Array to pass to template
$releases_array = array();

if(count($latest_releases)){
    // Display the correct number of resources
    $n = 0;

    while($n < count($results->data) && isset($results->data[$n]->id)){
        // Check permissions
        if(!$resources->canViewCategory($results->data[$n]->category_id, $user_group, ($user->isLoggedIn() ? $user->data()->secondary_groups : null))){
            $n++;
            continue;
        }

        // Get category
        $category = $queries->getWhere('resources_categories', array('id', '=', $results->data[$n]->category_id));
        if(count($category)){
            $category = Output::getClean($category[0]->name);
        } else {
            $category = 'n/a';
        }

        if(!isset($releases_array[$results->data[$n]->id])){
            $releases_array[$results->data[$n]->id] = array(
                'link' => URL::build('/resources/resource/' . $results->data[$n]->id . '-' . Util::stringToURL($results->data[$n]->name)),
                'name' => Output::getClean($results->data[$n]->name),
                'description' => mb_substr(strip_tags(htmlspecialchars_decode($results->data[$n]->description)), 0, 50) . '...',
                'author' => Output::getClean($author->nickname),
                'author_style' => $user->getGroupClass($author->id),
                'author_profile' => URL::build('/profile/' . Output::getClean($author->username)),
                'author_avatar' => $user->getAvatar($author->id, '../', 30),
                'downloads' => str_replace('{x}', $results->data[$n]->downloads, $resource_language->get('resources', 'x_downloads')),
                'views' => str_replace('{x}', $results->data[$n]->views, $resource_language->get('resources', 'x_views')),
                'rating' => round($results->data[$n]->rating / 10),
                'version' => $results->data[$n]->latest_version,
                'category' => str_replace('{x}', $category, $resource_language->get('resources', 'in_category_x')),
                'updated' => str_replace('{x}', $timeago->inWords(date('d M Y, H:i', $results->data[$n]->updated), $language->getTimeLanguage()), $resource_language->get('resources', 'updated_x')),
                'updated_full' => date('d M Y, H:i', $results->data[$n]->updated)
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
    'VIEWING_RESOURCES_BY' => str_replace('{x}', Output::getClean($user->idToName($author->id)), $resource_language->get('resources', 'viewing_resources_by_x')),
    'LATEST_RESOURCES' => $releases_array,
    'PAGINATION' => $pagination,
    'NO_RESOURCES' => $resource_language->get('resources', 'no_resources'),
    'RESOURCE' => $resource_language->get('resources', 'resource'),
    'STATS' => $resource_language->get('resources', 'stats'),
    'AUTHOR' => $resource_language->get('resources', 'author'),
    'BACK' => $language->get('general', 'back'),
    'BACK_LINK' => URL::build('/resources')
));

$template->addJSScript('
    var $star_rating = $(\'.star-rating.view .fa-star\');

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
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets);

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

$template->displayTemplate('resources/author.tpl', $smarty);