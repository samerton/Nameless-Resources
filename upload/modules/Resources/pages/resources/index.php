<?php 
/*
 *  Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr13
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
$timeago = new TimeAgo(TIMEZONE);

$sort_types = [];
$sort_types['updated'] = ['type' => 'updated', 'sort' => $resource_language->get('resources', 'last_updated'), 'link' => URL::build('/resources', 'sort=updated')];
$sort_types['newest'] = ['type' => 'created', 'sort' => $resource_language->get('resources', 'newest'), 'link' => URL::build('/resources', 'sort=newest')];
$sort_types['downloads'] = ['type' => 'downloads', 'sort' => $resource_language->get('resources', 'downloads'), 'link' => URL::build('/resources', 'sort=downloads')];

if(isset($_GET['sort']) && array_key_exists($_GET['sort'], $sort_types)){
    $sort_type = $_GET['sort'];
    $sort_by = $sort_types[$sort_type]['type'];
    $sort_by_text = $sort_types[$sort_type]['sort'];
    $url = $sort_types[$sort_type]['link'];
} else {
    $sort_by = 'updated';
    $sort_by_text = $resource_language->get('resources', 'last_updated');
    $url = URL::build('/resources', true);
}

// Get page
if(isset($_GET['p'])){
    if(!is_numeric($_GET['p'])){
        Redirect::to($url);
    } else {
        if($_GET['p'] == 1){ 
            // Avoid bug in pagination class
            Redirect::to($url);
        }
        $p = $_GET['p'];
    }
} else {
    $p = 1;
}

if ($user->isLoggedIn()) {
    $groups = [];
    foreach ($user->getGroups() as $group) {
        $groups[] = $group->id;
    }
} else {
    $groups = [0];
}

$page_title = $resource_language->get('resources', 'resources') . ' - ' . $language->get('general', 'page_x', ['page' => $p]);
require_once(ROOT_PATH . '/core/templates/frontend_init.php');

$categories = $resources->getCategories($groups);
// Assign to Smarty array
$category_array = [];
foreach($categories as $category){
    // Get category count
    $category_count = DB::getInstance()->get('resources', ['category_id', '=', $category->id]);
    $category_count = $category_count->count();
    $category_array[] = [
        'name' => Output::getClean($category->name),
        'link' => URL::build('/resources/category/' . $category->id . '-' . Util::stringToURL($category->name)),
        'count' => Output::getClean($category_count)
    ];
}
$categories = null;

// Get latest releases
$latest_releases = $resources->getResourcesList($groups, $sort_by);

// Pagination
$paginator = new Paginator((isset($template_pagination) ? $template_pagination : []));
$results = $paginator->getLimited($latest_releases, 10, $p, count($latest_releases));
$pagination = $paginator->generate(7, $url);

$smarty->assign('PAGINATION', $pagination);

// Array to pass to template
$releases_array = [];

if(count($latest_releases)){
    // Display the correct number of resources
    $n = 0;

    while ($n < count($results->data)) {
        // Get actual resource info
        $resource = $results->data[$n];

        // Get category
        $category = DB::getInstance()->get('resources_categories', ['id', '=', $resource->category_id]);
        if ($category->count()){
              $category = Output::getClean($category->first()->name);
        } else {
              $category = 'n/a';
        }

        if(!isset($releases_array[$resource->id])){
            $resource_author = new User($resource->creator_id);
            $releases_array[$resource->id] = [
                'link' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)),
                'name' => Output::getClean($resource->name),
                'short_description' => Output::getClean($resource->short_description),
                'description' => mb_substr(strip_tags(Output::getDecoded($resource->description)), 0, 60) . '...',
                'author' => Output::getClean($resource_author->getDisplayname()),
                'author_style' => $resource_author->getGroupStyle(),
                'author_profile' => URL::build('/profile/' . Output::getClean($resource_author->getDisplayname(true))),
                'author_avatar' => $resource_author->getAvatar(),
                'downloads' => $resource_language->get('resources', 'x_downloads', ['count' => $resource->downloads]),
                'views' => $resource_language->get('resources', 'x_views', ['count' => $resource->views]),
                'rating' => round($resource->rating / 10),
                'version' => $resource->latest_version,
                'category' => $resource_language->get('resources', 'in_category_x', ['category' => $category]),
                'updated' => $resource_language->get('resources', 'updated_x', ['updated' => $timeago->inWords(date('d M Y, H:i', $resource->updated), $language)]),
                'updated_full' => date('d M Y, H:i', $resource->updated)
            ];

            if ($resource->type == 1) {
                if ($resource->discount > 0 and $resource->discount < 100) {
                    $releases_array[$resource->id]['discount'] = Output::getClean($resource->discount);
                }
                $releases_array[$resource->id]['price'] = Output::getClean(Resources::getPricePercent($resource->price, $resource->discount));
            }
            // Check if resource icon uploaded
            if ($resource->has_icon == 1) {
                $releases_array[$resource->id]['icon'] = $resource->icon;
            } else {
                $releases_array[$resource->id]['icon'] = rtrim(Util::getSelfURL(), '/') . (defined('CONFIG_PATH') ? CONFIG_PATH . '/' : '/') . 'uploads/resources_icons/default.png';
            }
        }

        $n++;
    }
} else $releases_array = null;

// Get currency
$currency = DB::getInstance()->get('settings', ['name', '=', 'resources_currency']);
if (!$currency->count()) {
    DB::getInstance()->insert('settings', [
        'name' => 'resources_currency',
        'value' => 'GBP'
    ]);
    $currency = 'GBP';

} else
    $currency = $currency->first()->value;

// Assign Smarty variables
$smarty->assign([
    'RESOURCES' => $resource_language->get('resources', 'resources'),
    'CATEGORIES_TITLE' => $resource_language->get('resources', 'categories'),
    'CATEGORIES' => $category_array,
    'LATEST_RESOURCES' => $releases_array,
    'PAGINATION' => $pagination,
    'NO_RESOURCES' => $resource_language->get('resources', 'no_resources'),
    'RESOURCE' => $resource_language->get('resources', 'resource'),
    'STATS' => $resource_language->get('resources', 'stats'),
    'AUTHOR' => $resource_language->get('resources', 'author'),
    'SORT_BY' => $resource_language->get('resources', 'sort_by'),
    'SORT_BY_VALUE' => $sort_by_text,
    'SORT_TYPES' => $sort_types,
    'NEWEST' => $resource_language->get('resources', 'newest'),
    'LAST_UPDATED' => $resource_language->get('resources', 'last_updated'),
    'DOWNLOADS' => $resource_language->get('resources', 'downloads'),
    'RESOURCES_LINK' => URL::build('/resources'),
    'CURRENCY' => Output::getClean($currency)
]);

if($user->isLoggedIn() && $resources->canPostResourceInAnyCategory($groups)){
    $smarty->assign([
        'NEW_RESOURCE_LINK' => URL::build('/resources/new'),
        'NEW_RESOURCE' => $resource_language->get('resources', 'new_resource')
    ]);
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
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

$template->onPageLoad();

$smarty->assign('WIDGETS_LEFT', $widgets->getWidgets('left'));
$smarty->assign('WIDGETS_RIGHT', $widgets->getWidgets('right'));

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

$template->displayTemplate('resources/index.tpl', $smarty);
