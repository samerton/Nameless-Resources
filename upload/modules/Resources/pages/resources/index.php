<?php
/**
 *  Resources index
 *
 * @author Samerton
 * @license MIT
 *
 * @var Cache $cache
 * @var Language $language
 * @var Language $resource_language
 * @var Navigation $cc_nav
 * @var Navigation $navigation
 * @var Navigation $staffcp_nav
 * @var Pages $pages
 * @var Smarty $smarty
 * @var TemplateBase $template
 * @var User $user
 * @var Widgets $widgets
 */

use Resources\Classes\Categories;
use Resources\Classes\Category;
use Resources\Classes\Resource;
use Resources\Classes\Resources;
use Resources\Classes\Types\ResourceType;

const PAGE = 'resources';
$timeAgo = new TimeAgo(TIMEZONE);

// Possible sort values
$sort_types = [
    'updated' => [
        'type' => 'updated',
        'sort' => $resource_language->get('resources', 'last_updated'),
        'action' => URL::build('/resources', 'sort=updated'),
    ],
    'newest' => [
        'type' => 'created',
        'sort' => $resource_language->get('resources', 'newest'),
        'action' => URL::build('/resources', 'sort=newest'),
    ],
    'downloads' => [
        'type' => 'downloads',
        'sort' => $resource_language->get('resources', 'downloads'),
        'action' => URL::build('/resources', 'sort=downloads'),
    ],
];

$sort_by = null;
$sort_by_text = null;

if (isset($_GET['sort'])) {
    $sort = array_key_exists($_GET['sort'], $sort_types) ? $_GET['sort'] : null;

    if ($sort) {
        $sort_by = $sort_types[$sort]['type'];
        $sort_by_text = $sort_types[$sort]['sort'];
    }
}

$page = $_GET['p'];

if (!isset($page) || !is_numeric($page)) {
    $page = 1;
}

$page_title =
    $resource_language->get('resources', 'resources') .
    ' - ' .
    $language->get('general', 'page_x', ['page' => $page]);

require_once ROOT_PATH . '/core/templates/frontend_init.php';

$categories = Categories::list($user);

$options = new QueryOptions(
    [
        // ['name', 'LIKE', 'b'],
        // ['short_description', 'LIKE', 'a', 'OR'],
    ],
    $sort_by,
    'desc',
    10, // TODO: allow customisation
    $page
);
$resources = Resources::list($options);

// Pagination
$paginator = new Paginator($template_pagination ?? []);
$paginator->setValues(count($resources), 10, $page);
$pagination = $paginator->generate(7);
$smarty->assign('PAGINATION', $pagination);

$template_categories = array_map(static function (Category $category) {
    return [
        'name' => Output::getClean($category->getName()),
        'link' => Categories::buildViewUrl($category),
        'count' => $category->getResourceCount() ?? 0,
    ];
}, $categories);

$template_resources = [];

if (count($resources)) {
    /** @var Resource $resource */
    foreach ($resources as $resource) {
        $category = $categories[$resource->getCategory()->getId()];

        $rating = !is_null($resource->getRating()) ? round($resource->getRating() / 10) : 0;

        $template_resource = [
            'author' => $resource->getCreator()->getDisplayname(),
            'author_avatar' => $resource->getCreator()->getAvatar(),
            'author_profile' => $resource->getCreator()->getProfileURL(),
            'author_style' => $resource->getCreator()->getGroupStyle(),
            'category' => Output::getClean($category->getName() ?? 'n/a'),
            'created' => $resource_language->get(
                'resources',
                'created_x',
                [
                    'created' =>
                        $timeAgo->inWords($resource->getCreated()->format('U'), $language)
                ]
            ),
            'created_full' => $resource->getCreated()->format(DATE_FORMAT),
            'description' => Output::getPurified($resource->getDescription()),
            'description_trimmed' => Text::truncate($resource->getDescription(), 60),
            'downloads' => $resource->getDownloadCount() ?? 0,
            'link' => Resources::buildViewUrl($resource),
            'icon' => URL::buildAssetPath($resource->getIcon()),
            'in_category' => $resource_language->get(
                'resources',
                'in_category_x',
                ['category' => Output::getClean($category->getName() ?? 'n/a')]
            ),
            'short_description' => Output::getClean(strip_tags($resource->getShortDescription())),
            'rating' => $rating,
            'updated' => $resource_language->get(
                'resources',
                'updated_x',
                [
                    'updated' =>
                        $timeAgo->inWords($resource->getUpdated()->format('U'), $language)
                ]
            ),
            'updated_full' => $resource->getUpdated()->format(DATE_FORMAT),
            'version' => Output::getClean($resource->getLatestVersion()),
            'views' => $resource->getViewCount() ?? 0,
        ];

        if ($resource->getType() === ResourceType::PREMIUM) {
            $template_resource['price'] = $resource->getPrice();
            $template_resource['sale_pct'] = $resource->getSalePct();
            $template_resource['sale_price'] = $resource->getSalePrice();
        }

        $template_resources[] = $template_resource;
    }
}

$currency = Output::getClean(Resources::currency());

// Assign Smarty variables
$smarty->assign([
    'RESOURCES' => $resource_language->get('resources', 'resources'),
    'CATEGORIES_TITLE' => $resource_language->get('resources', 'categories'),
    'CATEGORIES' => $template_categories,
    'LATEST_RESOURCES' => $template_resources,
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
    'RESOURCES_LINK' => URL::build(RESOURCES_ROOT),
    'CURRENCY' => $currency,
]);

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

$template->onPageLoad();

$smarty->assign('WIDGETS_LEFT', $widgets->getWidgets('left'));
$smarty->assign('WIDGETS_RIGHT', $widgets->getWidgets());

require_once ROOT_PATH . '/core/templates/navbar.php';
require_once ROOT_PATH . '/core/templates/footer.php';

$template->displayTemplate('resources/index.tpl', $smarty);
