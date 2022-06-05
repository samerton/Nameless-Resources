<?php
/*
 *  Made by Samerton
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
$timeago = new TimeAgo(TIMEZONE);
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

// Get author
$aid = explode('/', $route);
$aid = $aid[count($aid) - 1];

if(!strlen($aid)){
    Redirect::to(URL::build('/resources'));
    die();
}

$aid = explode('-', $aid);
if(!is_numeric($aid[0])){
    Redirect::to(URL::build('/resources'));
    die();
}
$aid = $aid[0];

$author = new User($aid);
if(!$author){
    Redirect::to(URL::build('/resources'));
    die();
}

$page_title = $resource_language->get('resources', 'viewing_resources_by_x', ['user' => $author->getDisplayname()]);
require_once(ROOT_PATH . '/core/templates/frontend_init.php');

// Obtain categories + permissions from database
if ($user->isLoggedIn()) {
    $groups = [];
    foreach ($user->getGroups() as $group) {
        $groups[] = $group->id;
    }
} else {
    $groups = [0];
}
$categories = $resources->getCategories($groups);

// Assign to Smarty array
$category_array = [];
foreach($categories as $category){
    // Get category count
    $category_count = DB::getInstance()->get('resources', ['category_id', '=', $category->id]); // TODO: replace with count query
    $category_count = $category_count->count();
    $to_array = [
        'name' => Output::getClean($category->name),
        'link' => URL::build('/resources/category/' . $category->id . '-' . Util::stringToURL($category->name)),
    'count' => Output::getClean($category_count)
    ];
    $category_array[] = $to_array;
}
$categories = null;

// Get latest releases
$latest_releases = $resources->getAuthorLatestResources($aid, $groups);

// Pagination
$paginator = new Paginator((isset($template_pagination) ? $template_pagination : []));
$results = $paginator->getLimited($latest_releases, 10, $p, count($latest_releases));
$pagination = $paginator->generate(7, URL::build('/resources/author/' . $author->data()->id . '-' . Util::stringToURL($author->getDisplayname(true)) . '/', true));

$smarty->assign('PAGINATION', $pagination);

// Array to pass to template
$releases_array = [];

if (count($latest_releases)) {
    // Display the correct number of resources
    $n = 0;

    while ($n < count($results->data)) {
        // Get category
        $category = DB::getInstance()->get('resources_categories', ['id', '=', $results->data[$n]->category_id]);
        if ($category->count()) {
            $category = Output::getClean($category->first()->name);
        } else {
            $category = 'n/a';
        }

        if (!isset($releases_array[$results->data[$n]->id])) {
            $releases_array[$results->data[$n]->id] = [
                'link' => URL::build('/resources/resource/' . $results->data[$n]->id . '-' . Util::stringToURL($results->data[$n]->name)),
                'name' => Output::getClean($results->data[$n]->name),
                'short_description' => Output::getClean($results->data[$n]->short_description), 
                'description' => mb_substr(strip_tags(Output::getDecoded($results->data[$n]->description)), 0, 50) . '...',
                'author' => Output::getClean($author->getDisplayname()),
                'author_style' => $author->getGroupStyle(),
                'author_profile' => URL::build('/profile/' . Output::getClean($author->getDisplayname(true))),
                'author_avatar' => $author->getAvatar(),
                'downloads' => $resource_language->get('resources', 'x_downloads', ['count' => $results->data[$n]->downloads]),
                'views' => $resource_language->get('resources', 'x_views', ['count' => $results->data[$n]->views]),
                'rating' => round($results->data[$n]->rating / 10),
                'version' => $results->data[$n]->latest_version,
                'category' => $resource_language->get('resources', 'in_category_x', ['category' => $category]),
                'updated' => $resource_language->get('resources', 'updated_x', ['updated' => $timeago->inWords(date('d M Y, H:i', $results->data[$n]->updated), $language)]),
                'updated_full' => date('d M Y, H:i', $results->data[$n]->updated)
            ];
        
            if($results->data[$n]->type == 1 ) {
                $releases_array[$results->data[$n]->id]['price'] = Output::getClean($results->data[$n]->price);
            }
        
            // Check if resource icon uploaded
            if($results->data[$n]->has_icon == 1 ) {
                $releases_array[$results->data[$n]->id]['icon'] = $results->data[$n]->icon;
            } else {
                $releases_array[$results->data[$n]->id]['icon'] = rtrim(Util::getSelfURL(), '/') . (defined('CONFIG_PATH') ? CONFIG_PATH . '/' : '/') . 'uploads/resources_icons/default.png';
            }
            
        }

        $n++;
    }
} else $releases_array = null;

// Assign Smarty variables
$smarty->assign([
    'RESOURCES' => $resource_language->get('resources', 'resources'),
    'CATEGORIES_TITLE' => $resource_language->get('resources', 'categories'),
    'CATEGORIES' => $category_array,
    'VIEWING_RESOURCES_BY' => $resource_language->get('resources', 'viewing_resources_by_x', ['user' => Output::getClean($author->getDisplayname())]),
    'LATEST_RESOURCES' => $releases_array,
    'PAGINATION' => $pagination,
    'NO_RESOURCES' => $resource_language->get('resources', 'no_resources'),
    'RESOURCE' => $resource_language->get('resources', 'resource'),
    'STATS' => $resource_language->get('resources', 'stats'),
    'AUTHOR' => $resource_language->get('resources', 'author'),
    'BACK' => $language->get('general', 'back'),
    'BACK_LINK' => URL::build('/resources')
]);

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
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

$template->onPageLoad();

$smarty->assign('WIDGETS_LEFT', $widgets->getWidgets('left'));
$smarty->assign('WIDGETS_RIGHT', $widgets->getWidgets('right'));

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

$template->displayTemplate('resources/author.tpl', $smarty);
