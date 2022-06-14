<?php
/*
 *  Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr12
 *
 *  License: MIT
 *
 *  View resource page
 */

// Always define page name
define('PAGE', 'resources');
define('RESOURCE_PAGE', 'view_resource');

// Initialise
$timeago = new TimeAgo(TIMEZONE);

require(ROOT_PATH . '/modules/Resources/classes/Resources.php');
$resources = new Resources();

if ($user->isLoggedIn()) {
    $groups = [];
    foreach ($user->getGroups() as $group) {
        $groups[] = $group->id;
    }
} else {
    $groups = [0];
}

// Get resource
$rid = explode('/', $route);
$rid = $rid[count($rid) - 1];

if (!strlen($rid)) {
    Redirect::to(URL::build('/resources'));
}

$rid = explode('-', $rid);
if(!is_numeric($rid[0])){
    Redirect::to(URL::build('/resources'));
}
$rid = $rid[0];

// Get page
if(isset($_GET['p'])){
    if(!is_numeric($_GET['p'])){
        Redirect::to(URL::build('/resources/resource/' . $rid));
    } else {
        $p = $_GET['p'];
    }
} else {
    $p = 1;
}

$resource = DB::getInstance()->get('resources', ['id', '=', $rid]);

if(!$resource->count()){
    // Doesn't exist
    Redirect::to(URL::build('/resources'));
} else $resource = $resource->first();

if (!$resources->canViewCategory($resource->category_id, $groups)) {
    Redirect::to(URL::build('/resources'));
}

// Get latest release
$latest_release = DB::getInstance()->orderWhere('resources_releases', 'resource_id = ' . $resource->id, 'created', 'DESC');
if (!$latest_release->count()) die('Unable to get latest release');
else $latest_release = $latest_release->first();

// View count
if ($user->isLoggedIn() || Cookie::exists('alert-box')) {
    if(!Cookie::exists('nl-resource-' . $resource->id)) {
        DB::getInstance()->increment('resources', $resource->id, 'views');
        Cookie::put('nl-resource-' . $resource->id, "true", 3600);
    }
} else {
    if(!Session::exists('nl-resource-' . $resource->id)){
        DB::getInstance()->increment('resources', $resource->id, 'views');
        Session::put("nl-resource-" . $resource->id, "true");
    }
}

$category = DB::getInstance()->get('resources_categories', ['id', '=', $resource->category_id]);
if ($category->count()){
    $category = Output::getClean($category->first()->name);
} else {
    $category = 'Unknown';
}

// Get metadata
$page_metadata = DB::getInstance()->get('page_descriptions', ['page', '=', '/resources/resource']);
if ($page_metadata->count()) {
    $description = strip_tags(str_ireplace(['<br />', '<br>', '<br/>', '&nbsp;'], ["\n", "\n", "\n", ' '], Output::getDecoded($resource->description)));

    define('PAGE_DESCRIPTION', str_replace(['{site}', '{title}', '{author}', '{category_title}', '{page}', '{description}'], [SITE_NAME, Output::getClean($resource->name), Output::getClean($user->idToName($resource->creator_id)), $category, Output::getClean($p), mb_substr($description, 0, 160) . '...'], $page_metadata->first()->description));
    define('PAGE_KEYWORDS', $page_metadata->first()->tags);
}

$page_title = Output::getClean($resource->name);
require_once(ROOT_PATH . '/core/templates/frontend_init.php');

$template->assets()->include(AssetTree::TINYMCE);

$template->addCSSStyle('
    .star-rating.set {
        line-height:32px;
        font-size:1.25em;
        cursor: pointer;
    }
');

if(!isset($_GET['releases']) && !isset($_GET['do']) && !isset($_GET['versions']) && !isset($_GET['reviews'])){
    // Handle input
    if(Input::exists()){
        if($user->isLoggedIn()){
            if(Token::check(Input::get('token'))){
                $errorMessage = $resource_language->get('resources', 'invalid_review');

                $validation = Validate::check($_POST, [
                    'rating' => [
                        'required' => true,
                        'min' => 1,
                        'max' => 5
                    ],
                    'content' => [
                        'required' => true,
                        'min' => 1,
                        'max' => 20000
                    ]
                ])->messages([
                    'rating' => [
                        'required' => $errorMessage
                    ],
                    'content' => [
                        'required' => $errorMessage,
                        'min' => $errorMessage,
                        'max' => $errorMessage
                    ]
                ]);

                if ($validation->passed()) {
                    // Create review
                    // Validate rating
                    $rating = round($_POST['rating']);

                    if ($rating < 1 || $rating > 5) {
                        // Invalid rating

                    } else {
                        // Get latest release tag
                        $release_tag = $latest_release->release_tag;

                        // Create comment
                        DB::getInstance()->insert('resources_comments', [
                            'resource_id' => $resource->id,
                            'author_id' => $user->data()->id,
                            'content' => Output::getClean(Input::get('content')),
                            'release_tag' => $release_tag,
                            'created' => date('U'),
                            'rating' => $rating
                        ]);
                        $rating_id = DB::getInstance()->lastId();

                        // Calculate overall rating
                        // Ensure user hasn't already rated, and if so, hide their rating
                        $ratings = DB::getInstance()->get('resources_comments', ['resource_id', '=', $resource->id]);
                        if ($ratings->count()) {
                            $overall_rating = 0;
                            $overall_rating_count = 0;
                            $release_rating = 0;
                            $release_rating_count = 0;

                            foreach($ratings as $rating){
                                if($rating_id != $rating->id && $rating->author_id == $user->data()->id && $rating->hidden == 0){
                                    // Hide rating
                                    DB::getInstance()->update('resources_comments', $rating->id, [
                                        'hidden' => 1
                                    ]);
                                } else if($rating->hidden == 0){
                                    // Update rating
                                    // Overall
                                    $overall_rating = $overall_rating + $rating->rating;
                                    $overall_rating_count++;

                                    if($rating->release_tag == $release_tag){
                                        // Release
                                        $release_rating = $release_rating + $rating->rating;
                                        $release_rating_count++;
                                    }
                                }
                            }

                            $overall_rating = $overall_rating / $overall_rating_count;
                            $overall_rating = round($overall_rating * 10);

                            $release_rating = $release_rating / $release_rating_count;
                            $release_rating = round($release_rating * 10);

                            DB::getInstance()->update('resources', $resource->id, [
                                'rating' => $overall_rating
                            ]);
                            DB::getInstance()->update('resources_releases', $latest_release->id, [
                                'rating' => $release_rating
                            ]);
                        }

                        $cache->setCache('resource-comments-' . $resource->id);
                        $cache->erase('comments');

                        Redirect::to(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name)));
                    }

                } else {
                    // Errors
                    $error = $resource_language->get('resources', 'invalid_review');

                }
            }
        }
    }

    // Check comment cache
    $cache->setCache('resource-comments-' . $resource->id);

    if(!$cache->isCached('comments')){
        // Get comments
        $comments = DB::getInstance()->orderWhere('resources_comments', 'resource_id = ' . $resource->id . ' AND hidden = 0', 'created', 'DESC')->results();

        // Remove replies
        $replies_array = [];
        foreach($comments as $key => $comment){
            if(!is_null($comment->reply_id)){
                $replies_array[$comment->reply_id][] = $comment;
                unset($comments[$key]);
            }
        }

        // Cache
        $cache->store('comments', $comments, 120);

    } else $comments = (array) $cache->retrieve('comments');

    // Pagination
    $paginator = new Paginator((isset($template_pagination) ? $template_pagination : []));
    $results = $paginator->getLimited($comments, 10, $p, count($comments));
    $pagination = $paginator->generate(7, URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', true));

    if(count($comments))
        $smarty->assign('PAGINATION', $pagination);
    else
        $smarty->assign('PAGINATION', '');

    // Array to pass to template
    $comments_array = [];

    // Can the user delete reviews?
    if ($user->isLoggedIn() && $resources->canDeleteReviews($resource->category_id, $groups)) {
        $can_delete_reviews = true;
        $smarty->assign([
            'DELETE_REVIEW' => $resource_language->get('resources', 'delete_review'),
            'CONFIRM_DELETE_REVIEW' => $resource_language->get('resources', 'confirm_delete_review')
        ]);
    }

    if(count($comments)){
        // Display the correct number of comments
        $n = 0;

        while($n < count($results->data)){
            $author = new User($results->data[$n]->author_id);

            if ($author && $author->exists()) {
                $comments_array[] = [
                    'username' => $author->getDisplayname(),
                    'user_avatar' => $author->getAvatar(),
                    'user_style' => $author->getGroupStyle(),
                    'user_profile' => URL::build('/profile/' . $author->getDisplayname(true)),
                    'content' => Output::getPurified(Output::getDecoded($results->data[$n]->content)), // TODO: hooks
                    'date' => $timeago->inWords(date('d M Y, H:i', $results->data[$n]->created), $language),
                    'date_full' => date('d M Y, H:i', $results->data[$n]->created),
                    'replies' => (isset($replies_array[$results->data[$n]->id]) ? $replies_array[$results->data[$n]->id] : []),
                    'rating' => $results->data[$n]->rating,
                    'release_tag' => Output::getClean($results->data[$n]->release_tag),
                    'delete_link' => Output::getClean((isset($can_delete_reviews) ? URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'do=delete_review&review=' . $results->data[$n]->id) : ''))
                ];
            }

            $n++;
        }
    }

    // Get latest update
    $latest_update = DB::getInstance()->orderWhere('resources_releases', 'resource_id = ' . $resource->id, 'created', 'DESC LIMIT 1');

    if (!$latest_update->count()) {
        Redirect::to(URL::build('/resources'));
    } else $latest_update = $latest_update->first();

    $author = new User($resource->creator_id);

    // Get Releases Count
    $releases = DB::getInstance()->query('SELECT COUNT(*) AS c FROM nl2_resources_releases WHERE resource_id = ?', [$resource->id])->first()->c;

    // Get Reviews Count
    $reviews = DB::getInstance()->query('SELECT COUNT(*) AS c FROM nl2_resources_comments WHERE resource_id = ? AND hidden = 0', [$resource->id])->first()->c;

    // Assign Smarty variables
    $smarty->assign([
        'VIEWING_RESOURCE' => $resource_language->get('resources', 'viewing_resource_x', ['resource' => Output::getClean($resource->name)]),
        'UPLOAD_ICON' => $resource_language->get('resources', 'resource_upload_icon'),
        'CHANGE_ICON' => $resource_language->get('resources', 'resource_change_icon'),
        'CHANGE_ICON_ACTION' => URL::build('/resources/icon_upload'),
        'BACK_LINK' => URL::build('/resources'),
        'OVERVIEW_TITLE' => $resource_language->get('resources', 'overview'),
        'OVERVIEW_LINK' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name))),
        'RELEASES_TITLE' => $resource_language->get('resources', 'releases_x', ['count' => Output::getClean($releases)]),
        'VERSIONS_TITLE' => $resource_language->get('resources', 'versions_x', ['count' => Output::getClean($releases)]),
        'VERSIONS_LINK' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'versions')),
        'REVIEWS_TITLE' => $resource_language->get('resources', 'reviews_x', ['count' => Output::getClean($reviews)]),
        'REVIEWS_LINK' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'reviews')),
        'RESOURCE_NAME' => Output::getClean($resource->name),
        'RESOURCE_ID' => Output::getClean($resource->id),
        'RESOURCE_SHORT_DESCRIPTION' => Output::getClean($resource->short_description),
        'RESOURCE_INDEX' => $resource_language->get('resources', 'resource_index'),
        'AUTHOR' => $resource_language->get('resources', 'author'),
        'AUTHOR_RESOURCES' => Output::getClean(URL::build('/resources/author/' . $resource->creator_id . '-' . urlencode($author->getDisplayname(true)))),
        'VIEW_OTHER_RESOURCES' => $resource_language->get('resources', 'view_other_resources', ['user' => $author->getDisplayname()]),
        'DESCRIPTION' => Output::getPurified($resource->description),
        'CREATED' => $timeago->inWords(date('d M Y, H:i', $resource->created), $language),
        'CREATED_FULL' => date('d M Y, H:i', $resource->created),
        'REVIEWS' => $resource_language->get('resources', 'reviews'),
        'COMMENT_ARRAY' => $comments_array,
        'NO_REVIEWS' => $resource_language->get('resources', 'no_reviews'),
        'NEW_REVIEW' => $resource_language->get('resources', 'new_review'),
        'AUTHOR_NICKNAME' => $author->getDisplayname(),
        'AUTHOR_NAME' => $author->getDisplayname(true),
        'AUTHOR_STYLE' => $author->getGroupStyle(),
        'AUTHOR_AVATAR' => $author->getAvatar(),
        'AUTHOR_PROFILE' => URL::build('/profile/' . $author->getDisplayname(true)),
        'RESOURCE' => $resource_language->get('resources', 'resource'),
        'FIRST_RELEASE' => $resource_language->get('resources', 'first_release'),
        'FIRST_RELEASE_DATE' => date('d M Y', $resource->created),
        'LAST_RELEASE' => $resource_language->get('resources', 'last_release'),
        'LAST_RELEASE_DATE' => date('d M Y', $latest_update->created),
        'VIEWS' => $resource_language->get('resources', 'views'),
        'VIEWS_VALUE' => Output::getClean($resource->views),
        'DOWNLOADS' => $resource_language->get('resources', 'downloads'),
        'TOTAL_DOWNLOADS' => $resource_language->get('resources', 'total_downloads'),
        'TOTAL_DOWNLOADS_VALUE' => Output::getClean($resource->downloads),
        'CATEGORY' => $resource_language->get('resources', 'category'),
        'CATEGORY_VALUE' => Output::getClean($category),
        'RATING' => $resource_language->get('resources', 'rating'),
        'RATING_VALUE' => round($resource->rating / 10),
        'OTHER_RELEASES' => $resource_language->get('resources', 'other_releases'),
        'OTHER_RELEASES_LINK' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'releases=all')),
        'RELEASE' => $resource_language->get('resources', 'release'),
        'RELEASE_TITLE' => Output::getClean($latest_update->release_title),
        'RELEASE_DESCRIPTION' => Output::getPurified($latest_update->release_description),
        'RELEASE_VERSION' => $resource_language->get('resources', 'version_x', ['version' => Output::getClean($latest_update->release_tag)]),
        'RELEASE_TAG' => Output::getClean($latest_update->release_tag),
        'RELEASE_RATING' => round($latest_update->rating / 10),
        'RELEASE_DOWNLOADS' => $latest_update->downloads,
        'RELEASE_DATE' => $timeago->inWords(date('d M Y, H:i', $latest_update->created), $language),
        'RELEASE_DATE_FULL' => date('d M Y, H:i', $latest_update->created),
        'LOGGED_IN' => ($user->isLoggedIn() ? true : false),
        'CAN_REVIEW' => (($user->isLoggedIn() && $user->data()->id != $resource->creator_id) ? true : false),
        'TOKEN' => Token::get(),
        'CANCEL' => $language->get('general', 'cancel'),
        'SUBMIT' => $language->get('general', 'submit'),
        'CONTRIBUTORS' => $resource_language->get('resources', 'contributors_x', ['contributors' => Output::getClean($resource->contributors)]),
        'HAS_CONTRIBUTORS' => (strlen(trim($resource->contributors)) > 0) ? 1 : 0
    ]);

    if(isset($error))
        $smarty->assign('ERROR', $error);

    // Check if resource icon uploaded
    if($resource->has_icon == 1 ) {
        $smarty->assign([
            'RESOURCE_ICON' => $resource->icon
        ]);
    } else {
        $smarty->assign([
            'RESOURCE_ICON' => rtrim(Util::getSelfURL(), '/') . (defined('CONFIG_PATH') ? CONFIG_PATH . '/' : '/') . 'uploads/resources_icons/default.png'
        ]);
    }

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

    if ($resource->type == 0) {
        if ($resources->canDownloadResourceFromCategory($groups, $resource->category_id)) {
            $smarty->assign([
                'DOWNLOAD' => $resource_language->get('resources', 'download'),
                'DOWNLOAD_URL' => URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'do=download')
            ]);
        }
    } else {
        // Can the user download?
        if($user->isLoggedIn()){
            if ($resources->canDownloadResourceFromCategory($groups, $resource->category_id)) {
                if($user->data()->id == $resource->creator_id){
                    // Author can download their own resources
                    $smarty->assign([
                        'DOWNLOAD' => $resource_language->get('resources', 'download'),
                        'DOWNLOAD_URL' => URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'do=download')
                    ]);

                } else {
                    // Check purchases
                    $paid = DB::getInstance()->query('SELECT status FROM nl2_resources_payments WHERE resource_id = ? AND user_id = ?', [$resource->id, $user->data()->id])->results();

                    if(count($paid)){
                        $paid = $paid[0];

                        if($paid->status == 1){
                            // Purchased
                            $smarty->assign([
                                'DOWNLOAD' => $resource_language->get('resources', 'download'),
                                'DOWNLOAD_URL' => URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'do=download')
                            ]);

                        } else if($paid->status == 0){
                            // Pending
                            $smarty->assign([
                                'PAYMENT_PENDING' => $resource_language->get('resources', 'payment_pending')
                            ]);

                        } else if($paid->status == 2 || $paid->status == 3){
                            // Cancelled
                            $smarty->assign([
                                'PURCHASE_FOR_PRICE' => $resource_language->get('resources', 'purchase_for_x', ['price' => Output::getClean($resource->price) . ' ' . Output::getClean($currency)]),
                                'PURCHASE_LINK' => Output::getClean(URL::build('/resources/purchase/' . $resource->id . '-' . urlencode($resource->name)))
                            ]);

                        }
                    } else {
                        // Needs to purchase
                        $smarty->assign([
                            'PURCHASE_FOR_PRICE' => $resource_language->get('resources', 'purchase_for_x', ['price' => Output::getClean($resource->price) . ' ' . Output::getClean($currency)]),
                            'PURCHASE_LINK' => Output::getClean(URL::build('/resources/purchase/' . $resource->id . '-' . urlencode($resource->name))))
                        ]);
                    }
                }
            }

        } else {
            $smarty->assign([
                'PURCHASE_FOR_PRICE' => $resource_language->get('resources', 'purchase_for_x', ['price' => Output::getClean($resource->price) . ' ' . $currency])
            ]);
        }
    }

    if($user->isLoggedIn() && $resource->creator_id == $user->data()->id){
        // Allow updating
        $smarty->assign([
            'CAN_UPDATE' => true,
            'UPDATE' => $resource_language->get('resources', 'update'),
            'UPDATE_LINK' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'do=update')),
        ]);
    }

    if($user->isLoggedIn()){
        if($resource->creator_id == $user->data()->id || $resources->canEditResources($resource->category_id, $groups)){
            $smarty->assign([
                'CAN_EDIT' => true,
                'EDIT' => $language->get('general', 'edit'),
                'EDIT_LINK' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'do=edit')),
                'CHANGE_ICON' => $resource_language->get('resources', 'resource_change_icon')
            ]);
        }

        // Moderation
        $moderation = [];
        if($resources->canMoveResources($resource->category_id, $groups)){
            $moderation[] = [
                'link' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'do=move')),
                'title' => $resource_language->get('resources', 'move_resource')
            ];
        }
        if($resources->canDeleteResources($resource->category_id, $groups)){
            $moderation[] = [
                'link' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'do=delete')),
                'title' => $resource_language->get('resources', 'delete_resource')
            ];
        }

        if (Resources::canManageLicenses($resource->id, $user)) {
            $moderation[] = [
                'link' => Output::getClean(URL::build('/user/resources/licenses/' . $resource->id . '-' . urlencode($resource->name))),
                'title' => $resource_language->get('resources', 'manage_licenses')
            ];
        }

        $smarty->assign('MODERATION', $moderation);
        $smarty->assign('MODERATION_TEXT', $resource_language->get('resources', 'moderation'));
    } else {
        $smarty->assign('LOG_IN_TO_DOWNLOAD', $resource_language->get('resources', 'log_in_to_download'));
    }

    $template_file = 'resources/resource.tpl';

} else {
    if (isset($_GET['reviews'])) {
        // Check comment cache
        $cache->setCache('resource-comments-' . $resource->id);

        if (!$cache->isCached('comments')) {
            // Get comments
            $comments = DB::getInstance()->orderWhere('resources_comments', 'resource_id = ' . $resource->id . ' AND hidden = 0', 'created', 'DESC')->results();

            // Remove replies
            $replies_array = [];
            foreach ($comments as $key => $comment) {
                if (!is_null($comment->reply_id)) {
                    $replies_array[$comment->reply_id][] = Output::getPurified($comment);
                    unset($comments[$key]);
                }
            }

            // Cache
            $cache->store('comments', $comments, 120);

        } else $comments = (array) $cache->retrieve('comments');

        // Pagination
        $paginator = new Paginator((isset($template_pagination) ? $template_pagination : []));
        $results = $paginator->getLimited($comments, 10, $p, count($comments));
        $pagination = $paginator->generate(7, URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'reviews=all&amp;'));

        if (count($comments)) {
            $smarty->assign('PAGINATION', $pagination);
        } else {
            $smarty->assign('PAGINATION', '');
        }

        // Array to pass to template
        $comments_array = [];

        if (count($comments)) {
            // Display the correct number of comments
            $n = 0;

            while($n < count($results->data)){
                $author = new User($results->data[$n]->author_id);

                if ($author && $author->exists()) {
                    $comments_array[] = [
                        'username' => $author->getDisplayname(),
                        'user_avatar' => $author->getAvatar(),
                        'user_style' => $author->getGroupStyle(),
                        'user_profile' => URL::build('/profile/' . $author->getDisplayname(true)),
                        'content' => Output::getPurified(Output::getDecoded($results->data[$n]->content)), // TODO: hooks
                        'date' => $timeago->inWords(date('d M Y, H:i', $results->data[$n]->created), $language),
                        'date_full' => date('d M Y, H:i', $results->data[$n]->created),
                        'replies' => (isset($replies_array[$results->data[$n]->id]) ? $replies_array[$results->data[$n]->id] : []),
                        'rating' => $results->data[$n]->rating,
                        'release_tag' => Output::getClean($results->data[$n]->release_tag),
                        'delete_link' => (isset($can_delete_reviews) ? URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'do=delete_review&amp;review=' . $results->data[$n]->id) : '')
                    ];
                }
                $n++;
            }
        }

        // Get latest update
        $latest_update = DB::getInstance()->orderWhere('resources_releases', 'resource_id = ' . $resource->id, 'created', 'DESC LIMIT 1');

        if (!$latest_update->count()){
            Redirect::to(URL::build('/resources'));
        } else $latest_update = $latest_update->first();

        $author = new User($resource->creator_id);

        // Get Releases Count
        $releases = DB::getInstance()->query('SELECT COUNT(*) AS c FROM nl2_resources_releases WHERE resource_id = ?', [$resource->id])->first()->c;

        // Get Reviews Count
        $reviews = DB::getInstance()->query('SELECT COUNT(*) AS c FROM nl2_resources_comments WHERE resource_id = ? AND hidden = 0', [$resource->id])->first()->c;

        if ($resource->type == 1) {
            $resource_purchases = DB::getInstance()->get('resources_payments', ['resource_id', '=', $resource->id]); // TODO: replace with count query
            $resource_purchases = $resource_purchases->count();
            $currency = DB::getInstance()->get('settings', ['name', '=', 'resources_currency']);
            $currency = Output::getClean($currency->first()->value);
            $smarty->assign([
                'PURCHASES' => $resource_language->get('resources', 'purchases'),
                'PURCHASES_VALUE' => $resource_purchases,
                'PRICE' => $resource_language->get('resources', 'price'),
                'PRICE_VALUE' => Output::getClean($resource->price),
                'CURRENCY' => $currency,
            ]);
        }

        // Assign Smarty variables
        $smarty->assign([
            'VIEWING_ALL_REVIEWS' => $resource_language->get('resources', 'viewing_all_reviews', ['resource' => Output::getClean($resource->name)]),
            'RESOURCE_NAME' => Output::getClean($resource->name),
            'RESOURCE_SHORT_DESCRIPTION' => Output::getClean($resource->short_description),
            'COMMENT_ARRAY' => $comments_array,
            'AUTHOR' => $resource_language->get('resources', 'author'),
            'AUTHOR_RESOURCES' => Output::getClean(URL::build('/resources/author/' . $resource->creator_id . '-' . urlencode($author->getDisplayname(true)))),
            'VIEW_OTHER_RESOURCES' => $resource_language->get('resources', 'view_other_resources', ['user' => $author->getDisplayname()]),
            'AUTHOR_NICKNAME' => $author->getDisplayname(),
            'AUTHOR_NAME' => $author->getDisplayname(true),
            'AUTHOR_STYLE' => $author->getGroupStyle(),
            'AUTHOR_AVATAR' => $author->getAvatar(),
            'AUTHOR_PROFILE' => Output::getClean(URL::build('/profile/' . $author->getDisplayname(true))),
            'NO_REVIEWS' => $resource_language->get('resources', 'no_reviews'),
            'BACK' => $language->get('general', 'back'),
            'BACK_LINK' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name))),
            'OVERVIEW_TITLE' => $resource_language->get('resources', 'overview'),
            'OVERVIEW_LINK' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name))),
            'RELEASES_TITLE' => $resource_language->get('resources', 'releases_x', ['count' => Output::getClean($releases)]),
            'VERSIONS_TITLE' => $resource_language->get('resources', 'versions_x', ['count' => Output::getClean($releases)]),
            'VERSIONS_LINK' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'versions')),
            'REVIEWS_TITLE' => $resource_language->get('resources', 'reviews_x', ['count' => Output::getClean($reviews)]),
            'REVIEWS_LINK' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'reviews')),
            'RESOURCE' => $resource_language->get('resources', 'resource'),
            'FIRST_RELEASE' => $resource_language->get('resources', 'first_release'),
            'FIRST_RELEASE_DATE' => date('d M Y', $resource->created),
            'LAST_RELEASE' => $resource_language->get('resources', 'last_release'),
            'LAST_RELEASE_DATE' => date('d M Y', $latest_update->created),
            'VIEWS' => $resource_language->get('resources', 'views'),
            'VIEWS_VALUE' => Output::getClean($resource->views),
            'DOWNLOAD' => $resource_language->get('resources', 'download'),
            'DOWNLOADS' => $resource_language->get('resources', 'downloads'),
            'TOTAL_DOWNLOADS' => $resource_language->get('resources', 'total_downloads'),
            'TOTAL_DOWNLOADS_VALUE' => Output::getClean($resource->downloads),
            'CATEGORY' => $resource_language->get('resources', 'category'),
            'CATEGORY_VALUE' => Output::getClean($category),
            'RATING' => $resource_language->get('resources', 'rating'),
            'RATING_VALUE' => round($resource->rating / 10),
            'OTHER_RELEASES' => $resource_language->get('resources', 'other_releases'),
            'OTHER_RELEASES_LINK' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'releases=all')),
            'RELEASE' => $resource_language->get('resources', 'release'),
            'RELEASE_TITLE' => Output::getClean($latest_update->release_title),
            'RELEASE_DESCRIPTION' => Output::getPurified($latest_update->release_description),
            'RELEASE_VERSION' => $resource_language->get('resources', 'version_x', ['version' => Output::getClean($latest_update->release_tag)]),
            'RELEASE_TAG' => Output::getClean($latest_update->release_tag),
            'RELEASE_RATING' => round($latest_update->rating / 10),
            'RELEASE_DOWNLOADS' => $latest_update->downloads,
            'RELEASE_DATE' => $timeago->inWords(date('d M Y, H:i', $latest_update->created), $language),
            'RELEASE_DATE_FULL' => date('d M Y, H:i', $latest_update->created),
        ]);

        // Check if resource icon uploaded
        if($resource->has_icon == 1 ) {
            $smarty->assign([
                'RESOURCE_ICON' => $resource->icon
            ]);
        } else {
            $smarty->assign([
                'RESOURCE_ICON' => rtrim(Util::getSelfURL(), '/') . (defined('CONFIG_PATH') ? CONFIG_PATH . '/' : '/') . 'uploads/resources_icons/default.png'
            ]);
        }

            // Ensure user has download permission
            if($resource->type == 0){
                // Can the user download?
                if ($resources->canDownloadResourceFromCategory($groups, $resource->category_id)) {
                    $smarty->assign([
                        'DOWNLOAD' => $resource_language->get('resources', 'download'),
                        'DOWNLOAD_URL' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'do=download'))
                    ]);
                }
            } else {
                // Can the user download?
                if($user->isLoggedIn()){
                    if ($resources->canDownloadResourceFromCategory($groups, $resource->category_id)) {
                        if($user->data()->id == $resource->creator_id){
                            // Author can download their own resources
                            $smarty->assign([
                                'DOWNLOAD' => $resource_language->get('resources', 'download'),
                                'DOWNLOAD_URL' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'do=download'))
                            ]);

                        } else {
                            // Check purchases
                            $paid = DB::getInstance()->query('SELECT status FROM nl2_resources_payments WHERE resource_id = ? AND user_id = ?', [$resource->id, $user->data()->id])->results();

                            if(count($paid)){
                                $paid = $paid[0];

                                if($paid->status == 1){
                                    // Purchased
                                    $smarty->assign([
                                        'DOWNLOAD' => $resource_language->get('resources', 'download'),
                                        'DOWNLOAD_URL' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'do=download'))
                                    ]);

                                } else if($paid->status == 0){
                                    // Pending
                                    $smarty->assign([
                                        'PAYMENT_PENDING' => $resource_language->get('resources', 'payment_pending')
                                    ]);

                                } else if($paid->status == 2){
                                    // Cancelled
                                    $smarty->assign([
                                        'PURCHASE_FOR_PRICE' => $resource_language->get('resources', 'purchase_for_x', ['price' => Output::getClean($resource->price) . ' ' . Output::getClean($currency)]),
                                        'PURCHASE_LINK' => Output::getClean(URL::build('/resources/purchase/' . urlencode($resource->id) . '-' . urlencode($resource->name)))
                                    ]);

                                }
                            } else {
                                // Needs to purchase
                                $smarty->assign([
                                    'PURCHASE_FOR_PRICE' => $resource_language->get('resources', 'purchase_for_x', ['price' => Output::getClean($resource->price) . ' ' . Output::getClean($currency)]),
                                    'PURCHASE_LINK' => Ouput::getClean(URL::build('/resources/purchase/' . $resource->id . '-' . urlencode($resource->name)))
                                ]);
                            }
                        }
                    }

                } else {
                    $smarty->assign([
                        'PURCHASE_FOR_PRICE' => $resource_language->get('resources', 'purchase_for_x', ['price' => Output::getClean($resource->price) . ' ' . $currency])
                    ]);
                }
            }

        $template_file = 'resources/resource_all_reviews.tpl';

    } else if(isset($_GET['versions'])){
        // Display list of all versions
        $releases = DB::getInstance()->query('SELECT * FROM nl2_resources_releases WHERE resource_id = ? ORDER BY `created` DESC', [$resource->id]);
        $release_count = $releases->count();

        if (!$release_count) {
            Redirect::to('/resources/resource/' . $resource->id . '-' . urlencode($resource->name));
        }

        $releases = $releases->results();

        // Pagination
        $paginator = new Paginator((isset($template_pagination) ? $template_pagination : []));
        $results = $paginator->getLimited($releases, 10, $p, $release_count);
        $pagination = $paginator->generate(7, URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'versions=all&amp;'));

        $smarty->assign('PAGINATION', $pagination);

        // Assign releases to new array for Smarty
        $releases_array = [];
        foreach($results->data as $release){
            $releases_array[] = [
                'id' => $release->id,
                'url' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'releases=' . $release->id)),
                'download_url' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'do=download&release=' . $release->id)),
                'tag' => Output::getClean($release->release_tag),
                'name' => Output::getClean($release->release_title),
                'description' => Output::getPurified(nl2br($release->release_description)),
                'date' => $timeago->inWords(date('d M Y, H:i', $release->created), $language),
                'date_full' => date('d M Y, H:i', $release->created),
                'rating' => round($release->rating / 10),
                'downloads' => $resource_language->get('resources', 'x_downloads', ['count' => $release->downloads])
            ];
        }

        // Get latest update
        $latest_update = DB::getInstance()->orderWhere('resources_releases', 'resource_id = ' . $resource->id, 'created', 'DESC LIMIT 1');

        if (!$latest_update->count()) {
            Redirect::to(URL::build('/resources'));
        } else $latest_update = $latest_update->first();

        $author = new User($resource->creator_id);

        // Get Releases Count
        $releases = DB::getInstance()->query('SELECT COUNT(*) AS c FROM nl2_resources_releases WHERE resource_id = ?', [$resource->id])->first()->c;

        // Get Reviews Count
        $reviews = DB::getInstance()->query('SELECT COUNT(*) AS c FROM nl2_resources_comments WHERE resource_id = ? AND hidden = 0', [$resource->id])->first()->c;

        if ($resource->type == 1) {
            $resource_purchases = DB::getInstance()->get('resources_payments', ['resource_id', '=', $resource->id]); // TODO: replace with count query
            $resource_purchases = $resource_purchases->count();
            $currency = DB::getInstance()->get('settings', ['name', '=', 'resources_currency']);
            $currency = Output::getClean($currency->first()->value);
            $smarty->assign([
                'PURCHASES' => $resource_language->get('resources', 'purchases'),
                'PURCHASES_VALUE' => $resource_purchases,
                'PRICE' => $resource_language->get('resources', 'price'),
                'PRICE_VALUE' => Output::getClean($resource->price),
                'CURRENCY' => $currency,
            ]);
        }

        // Assign Smarty variables
        $smarty->assign([
            'VIEWING_ALL_VERSIONS' => $resource_language->get('resources', 'viewing_all_versions', ['resource' => Output::getClean($resource->name)]),
            'RESOURCE_NAME' => Output::getClean($resource->name),
            'RESOURCE_SHORT_DESCRIPTION' => Output::getClean($resource->short_description),
            'AUTHOR' => $resource_language->get('resources', 'author'),
            'AUTHOR_RESOURCES' => Output::getClean(URL::build('/resources/author/' . $resource->creator_id . '-' . urlencode($author->getDisplayname(true)))),
            'VIEW_OTHER_RESOURCES' => $resource_language->get('resources', 'view_other_resources', ['user' => $author->getDisplayname()]),
            'AUTHOR_NICKNAME' => $author->getDisplayname(),
            'AUTHOR_NAME' => $author->getDisplayname(true),
            'AUTHOR_STYLE' => $author->getGroupStyle(),
            'AUTHOR_AVATAR' => $author->getAvatar(),
            'AUTHOR_PROFILE' => Output::getClean(URL::build('/profile/' . urlencode($author->getDisplayname(true)))),
            'RELEASES' => $releases_array,
            'BACK' => $language->get('general', 'back'),
            'BACK_LINK' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name))),
            'OVERVIEW_TITLE' => $resource_language->get('resources', 'overview'),
            'OVERVIEW_LINK' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name))),
            'RELEASES_TITLE' => $resource_language->get('resources', 'releases_x', ['count' => Output::getClean($releases)]),
            'VERSIONS_TITLE' => $resource_language->get('resources', 'versions_x', ['count' => Output::getClean($releases)]),
            'VERSIONS_LINK' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'versions')),
            'REVIEWS_TITLE' =>  $resource_language->get('resources', 'reviews_x', ['count' => Output::getClean($reviews)]),
            'REVIEWS_LINK' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'reviews')),
            'RESOURCE' => $resource_language->get('resources', 'resource'),
            'FIRST_RELEASE' => $resource_language->get('resources', 'first_release'),
            'FIRST_RELEASE_DATE' => date('d M Y', $resource->created),
            'LAST_RELEASE' => $resource_language->get('resources', 'last_release'),
            'LAST_RELEASE_DATE' => date('d M Y', $latest_update->created),
            'VIEWS' => $resource_language->get('resources', 'views'),
            'VIEWS_VALUE' => Output::getClean($resource->views),
            'DOWNLOAD' => $resource_language->get('resources', 'download'),
            'DOWNLOADS' => $resource_language->get('resources', 'downloads'),
            'TOTAL_DOWNLOADS' => $resource_language->get('resources', 'total_downloads'),
            'TOTAL_DOWNLOADS_VALUE' => Output::getClean($resource->downloads),
            'CATEGORY' => $resource_language->get('resources', 'category'),
            'CATEGORY_VALUE' => Output::getClean($category),
            'RATING' => $resource_language->get('resources', 'rating'),
            'RATING_VALUE' => round($resource->rating / 10),
            'OTHER_RELEASES' => $resource_language->get('resources', 'other_releases'),
            'OTHER_RELEASES_LINK' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'releases=all')),
            'RELEASE' => $resource_language->get('resources', 'release'),
            'RELEASE_TITLE' => Output::getClean($latest_update->release_title),
            'RELEASE_DESCRIPTION' => Output::getPurified(Output::getDecoded($latest_update->release_description)),
            'RELEASE_VERSION' => $resource_language->get('resources', 'version_x', ['version' => Output::getClean($latest_update->release_tag)]),
            'RELEASE_TAG' => Output::getClean($latest_update->release_tag),
            'RELEASE_RATING' => round($latest_update->rating / 10),
            'RELEASE_DOWNLOADS' => $latest_update->downloads,
            'RELEASE_DATE' => $timeago->inWords(date('d M Y, H:i', $latest_update->created), $language),
            'RELEASE_DATE_FULL' => date('d M Y, H:i', $latest_update->created),
        ]);

        // Check if resource icon uploaded
        if($resource->has_icon == 1 ) {
            $smarty->assign([
                'RESOURCE_ICON' => $resource->icon
            ]);
        } else {
            $smarty->assign([
                'RESOURCE_ICON' => rtrim(Util::getSelfURL(), '/') . (defined('CONFIG_PATH') ? CONFIG_PATH . '/' : '/') . 'uploads/resources_icons/default.png'
            ]);
        }

            // Ensure user has download permission
            if($resource->type == 0){
                // Can the user download?
                if ($resources->canDownloadResourceFromCategory($groups, $resource->category_id)) {
                    $smarty->assign([
                        'DOWNLOAD' => $resource_language->get('resources', 'download'),
                        'DOWNLOAD_URL' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'do=download'))
                    ]);
                }
            } else {
                // Can the user download?
                if($user->isLoggedIn()){
                    if ($resources->canDownloadResourceFromCategory($groups, $resource->category_id)) {
                        if($user->data()->id == $resource->creator_id){
                            // Author can download their own resources
                            $smarty->assign([
                                'DOWNLOAD' => $resource_language->get('resources', 'download'),
                                'DOWNLOAD_URL' => Ouput::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'do=download'))
                            ]);

                        } else {
                            // Check purchases
                            $paid = DB::getInstance()->query('SELECT status FROM nl2_resources_payments WHERE resource_id = ? AND user_id = ?', [$resource->id, $user->data()->id])->results();

                            if(count($paid)){
                                $paid = $paid[0];

                                if($paid->status == 1){
                                    // Purchased
                                    $smarty->assign([
                                        'DOWNLOAD' => $resource_language->get('resources', 'download'),
                                        'DOWNLOAD_URL' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'do=download'))
                                    ]);

                                } else if($paid->status == 0){
                                    // Pending
                                    $smarty->assign([
                                        'PAYMENT_PENDING' => $resource_language->get('resources', 'payment_pending')
                                    ]);

                                } else if($paid->status == 2){
                                    // Cancelled
                                    $smarty->assign([
                                        'PURCHASE_FOR_PRICE' => $resource_language->get('resources', 'purchase_for_x', ['price' => Output::getClean($resource->price) . ' ' . Output::getClean($currency)]),
                                        'PURCHASE_LINK' => Ouput::getClean(URL::build('/resources/purchase/' . $resource->id . '-' . urlencode($resource->name))),
                                    ]);
                                }
                            } else {
                                // Needs to purchase
                                $smarty->assign([
                                    'PURCHASE_FOR_PRICE' => $resource_language->get('resources', 'purchase_for_x', ['price' => Output::getClean($resource->price) . ' ' . Output::getClean($currency)]),
                                    'PURCHASE_LINK' => Output::getClean(URL::build('/resources/purchase/' . $resource->id . '-' . urlencode($resource->name)))
                                ]);
                            }
                        }
                    }

                } else {
                    $smarty->assign([
                        'PURCHASE_FOR_PRICE' => $resource_language->get('resources', 'purchase_for_x', ['price' => Output::getClean($resource->price) . ' ' . $currency])
                    ]);
                }
            }

        $template_file = 'resources/resource_all_versions.tpl';

    } else if(isset($_GET['releases'])){
        if($_GET['releases'] == 'all'){
            // Display list of all releases
            $releases = DB::getInstance()->orderWhere('resources_releases', 'resource_id = ' . $resource->id, 'created', 'DESC')->results();

            if (!count($releases)){
                Redirect::to('/resources/resource/' . $resource->id . '-' . urlencode($resource->name));
                die();
            }

            // Pagination
            $paginator = new Paginator((isset($template_pagination) ? $template_pagination : []));
            $results = $paginator->getLimited($releases, 10, $p, count($releases));
            $pagination = $paginator->generate(7, URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'releases=all&amp;'));

            $smarty->assign('PAGINATION', $pagination);

            // Assign releases to new array for Smarty
            $releases_array = [];
            foreach($releases as $release){
                $releases_array[] = [
                    'id' => $release->id,
                    'url' => Output::getClean(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name) . '/', 'releases=' . $release->id)),
                    'tag' => Output::getClean($release->release_tag),
                    'name' => Output::getClean($release->release_title),
                    'description' => Output::getPurified(nl2br($release->release_description)),
                    'date' => $timeago->inWords(date('d M Y, H:i', $release->created), $language),
                    'date_full' => date('d M Y, H:i', $release->created),
                    'rating' => round($release->rating / 10),
                    'downloads' => $resource_language->get('resources', 'x_downloads', ['count' => $release->downloads])
                ];
            }

            // Get latest update
            $latest_update = DB::getInstance()->orderWhere('resources_releases', 'resource_id = ' . $resource->id, 'created', 'DESC LIMIT 1');

            if (!$latest_update->count()) {
                Redirect::to(URL::build('/resources'));
            } else $latest_update = $latest_update->first();

            $author = new User($resource->creator_id);

            // Get Releases Count
            $releases = DB::getInstance()->query('SELECT COUNT(*) AS c FROM nl2_resources_releases WHERE resource_id = ?', [$resource->id])->first()->c;

            // Get Reviews Count
            $reviews = DB::getInstance()->query('SELECT COUNT(*) AS c FROM nl2_resources_comments WHERE resource_id = ? AND hidden = 0', [$resource->id])->first()->c;

            // Assign Smarty variables
            $smarty->assign([
                'VIEWING_ALL_RELEASES' => $resource_language->get('resources', 'viewing_all_releases', ['resource' => Output::getClean($resource->name)]),
                'RELEASES' => $releases_array,
                'RESOURCE_NAME' => Output::getClean($resource->name),
                'RESOURCE_SHORT_DESCRIPTION' => Output::getClean($resource->short_description),
                'AUTHOR' => $resource_language->get('resources', 'author'),
                'AUTHOR_RESOURCES' => URL::build('/resources/author/' . $resource->creator_id . '-' . Util::stringToURL($author->getDisplayname(true))),
                'VIEW_OTHER_RESOURCES' => $resource_language->get('resources', 'view_other_resources', ['user' => $author->getDisplayname()]),
                'AUTHOR_NICKNAME' => $author->getDisplayname(),
                'AUTHOR_NAME' => $author->getDisplayname(true),
                'AUTHOR_STYLE' => $author->getGroupStyle(),
                'AUTHOR_AVATAR' => $author->getAvatar(),
                'AUTHOR_PROFILE' => URL::build('/profile/' . $author->getDisplayname(true)),
                'OVERVIEW_TITLE' => $resource_language->get('resources', 'overview'),
                'OVERVIEW_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)),
                'RELEASES_TITLE' => $resource_language->get('resources', 'releases_x', ['count' => Output::getClean($releases)]),
                'VERSIONS_TITLE' => $resource_language->get('resources', 'versions_x', ['count' => Output::getClean($releases)]),
                'VERSIONS_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name) . '/', 'versions'),
                'REVIEWS_TITLE' => $resource_language->get('resources', 'reviews_x', ['count' => Output::getClean($reviews)]),
                'REVIEWS_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name) . '/', 'reviews'),
                'RESOURCE' => $resource_language->get('resources', 'resource'),
                'FIRST_RELEASE' => $resource_language->get('resources', 'first_release'),
                'FIRST_RELEASE_DATE' => date('d M Y', $resource->created),
                'LAST_RELEASE' => $resource_language->get('resources', 'last_release'),
                'LAST_RELEASE_DATE' => date('d M Y', $latest_update->created),
                'VIEWS' => $resource_language->get('resources', 'views'),
                'VIEWS_VALUE' => Output::getClean($resource->views),
                'DOWNLOADS' => $resource_language->get('resources', 'downloads'),
                'TOTAL_DOWNLOADS' => $resource_language->get('resources', 'total_downloads'),
                'TOTAL_DOWNLOADS_VALUE' => Output::getClean($resource->downloads),
                'CATEGORY' => $resource_language->get('resources', 'category'),
                'CATEGORY_VALUE' => Output::getClean($category),
                'RATING' => $resource_language->get('resources', 'rating'),
                'RATING_VALUE' => round($resource->rating / 10),
                'OTHER_RELEASES' => $resource_language->get('resources', 'other_releases'),
                'OTHER_RELEASES_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name) . '/', 'releases=all'),
                'RELEASE' => $resource_language->get('resources', 'release'),
                'RELEASE_TITLE' => Output::getClean($latest_update->release_title),
                'RELEASE_DESCRIPTION' => Output::getPurified(Output::getDecoded($latest_update->release_description)),
                'RELEASE_VERSION' => $resource_language->get('resources', 'version_x', ['version' => Output::getClean($latest_update->release_tag)]),
                'RELEASE_TAG' => Output::getClean($latest_update->release_tag),
                'RELEASE_RATING' => round($latest_update->rating / 10),
                'RELEASE_DOWNLOADS' => $latest_update->downloads,
                'RELEASE_DATE' => $timeago->inWords(date('d M Y, H:i', $latest_update->created), $language),
                'RELEASE_DATE_FULL' => date('d M Y, H:i', $latest_update->created),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name))
            ]);

            // Check if resource icon uploaded
            if($resource->has_icon == 1 ) {
                $smarty->assign([
                    'RESOURCE_ICON' => $resource->icon
                ]);
            } else {
                $smarty->assign([
                    'RESOURCE_ICON' => rtrim(Util::getSelfURL(), '/') . (defined('CONFIG_PATH') ? CONFIG_PATH . '/' : '/') . 'uploads/resources_icons/default.png'
                ]);
            }

            $template_file = 'resources/resource_all_releases.tpl';

        } else {
            if(!is_numeric($_GET['releases'])){
                Redirect::to(URL::build('/resources'));
            }

            // Get info about a specific release
            $release = DB::getInstance()->get('resources_releases', ['id', '=', $_GET['releases']]);

            if (!$release->count()) {
                Redirect::to(URL::build('/resources'));
            } else $release = $release->first();

            // Get latest update
            $latest_update = DB::getInstance()->orderWhere('resources_releases', 'resource_id = ' . $resource->id, 'created', 'DESC LIMIT 1');

            if (!$latest_update->count()) {
                Redirect::to(URL::build('/resources'));
            } else $latest_update = $latest_update->first();

            $author = new User($resource->creator_id);

            // Get Releases Count
            $releases = DB::getInstance()->query('SELECT COUNT(*) AS c FROM nl2_resources_releases WHERE resource_id = ?', [$resource->id])->first()->c;

            // Get Reviews Count
            $reviews = DB::getInstance()->query('SELECT COUNT(*) AS c FROM nl2_resources_comments WHERE resource_id = ? AND hidden = 0', [$resource->id])->first()->c;

            // Assign Smarty variables
            $smarty->assign([
                'VIEWING_RELEASE' => $resource_language->get('resources', 'viewing_release', ['release' => Output::getClean($release->release_title), 'resource' => Output::getClean($resource->name)]),
                'RESOURCE_SHORT_DESCRIPTION' => Output::getClean($resource->short_description),
                'RESOURCE_NAME' => Output::getClean($resource->name),
                'BACK' => $language->get('general', 'back'),
                'BACK_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)),
                'OVERVIEW_TITLE' => $resource_language->get('resources', 'overview'),
                'OVERVIEW_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)),
                'RELEASES_TITLE' => $resource_language->get('resources', 'releases_x', ['count' => Output::getClean($releases)]),
                'VERSIONS_TITLE' => $resource_language->get('resources', 'versions_x', ['count' => Output::getClean($releases)]),
                'VERSIONS_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name) . '/', 'versions'),
                'REVIEWS_TITLE' => $resource_language->get('resources', 'reviews_x', ['count' => Output::getClean($reviews)]),
                'REVIEWS_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name) . '/', 'reviews'),
                'DOWNLOADS' => $resource_language->get('resources', 'x_downloads', ['count' => $release->downloads]),
                'RATING' => round($release->rating / 10),
                'DESCRIPTION' => Output::getPurified(nl2br(Output::getDecoded($release->release_description))),
                'AUTHOR' => $resource_language->get('resources', 'author'),
                'AUTHOR_RESOURCES' => URL::build('/resources/author/' . $resource->creator_id . '-' . Util::stringToURL($author->getDisplayname(true))),
                'VIEW_OTHER_RESOURCES' => $resource_language->get('resources', 'view_other_resources', ['user' => $author->getDisplayname()]),
                'AUTHOR_NICKNAME' => $author->getDisplayname(),
                'AUTHOR_NAME' => $author->getDisplayname(true),
                'AUTHOR_STYLE' => $author->getGroupStyle(),
                'AUTHOR_AVATAR' => $author->getAvatar(),
                'AUTHOR_PROFILE' => URL::build('/profile/' . $author->getDisplayname(true)),
                'RESOURCE' => $resource_language->get('resources', 'resource'),
                'FIRST_RELEASE' => $resource_language->get('resources', 'first_release'),
                'FIRST_RELEASE_DATE' => date('d M Y', $resource->created),
                'LAST_RELEASE' => $resource_language->get('resources', 'last_release'),
                'LAST_RELEASE_DATE' => date('d M Y', $latest_update->created),
                'VIEWS' => $resource_language->get('resources', 'views'),
                'VIEWS_VALUE' => Output::getClean($resource->views),
                'DOWNLOADS' => $resource_language->get('resources', 'downloads'),
                'TOTAL_DOWNLOADS' => $resource_language->get('resources', 'total_downloads'),
                'TOTAL_DOWNLOADS_VALUE' => Output::getClean($resource->downloads),
                'CATEGORY' => $resource_language->get('resources', 'category'),
                'CATEGORY_VALUE' => Output::getClean($category),
                'RATING' => $resource_language->get('resources', 'rating'),
                'RATING_VALUE' => round($resource->rating / 10),
                'OTHER_RELEASES' => $resource_language->get('resources', 'other_releases'),
                'OTHER_RELEASES_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name) . '/', 'releases=all'),
                'RELEASE' => $resource_language->get('resources', 'release'),
                'RELEASE_TITLE' => Output::getClean($latest_update->release_title),
                'RELEASE_DESCRIPTION' => Output::getPurified(Output::getDecoded($latest_update->release_description)),
                'RELEASE_VERSION' => $resource_language->get('resources', 'version_x', ['version' => Output::getClean($latest_update->release_tag)]),
                'RELEASE_TAG' => Output::getClean($latest_update->release_tag),
                'RELEASE_RATING' => round($latest_update->rating / 10),
                'RELEASE_DOWNLOADS' => $latest_update->downloads,
                'RELEASE_DATE' => $timeago->inWords(date('d M Y, H:i', $latest_update->created), $language),
                'RELEASE_DATE_FULL' => date('d M Y, H:i', $latest_update->created),
                'DATE' => $timeago->inWords(date('d M Y, H:i', $release->created), $language),
                'DATE_FULL' => date('d M Y, H:i', $release->created)
            ]);

            // Check if resource icon uploaded
            if($resource->has_icon == 1 ) {
                $smarty->assign([
                    'RESOURCE_ICON' => $resource->icon
                ]);
            } else {
                $smarty->assign([
                    'RESOURCE_ICON' => rtrim(Util::getSelfURL(), '/') . (defined('CONFIG_PATH') ? CONFIG_PATH . '/' : '/') . 'uploads/resources_icons/default.png'
                ]);
            }

            $currency = DB::getInstance()->get('settings', ['name', '=', 'resources_currency']);
            if (!$currency->count()) {
                DB::getInstance()->insert('settings', [
                    'name' => 'resources_currency',
                    'value' => 'GBP'
                ]);
                $currency = 'GBP';

            } else
                $currency = $currency->first()->value;

            // Ensure user has download permission
            if($resource->type == 0){
                // Can the user download?
                if ($resources->canDownloadResourceFromCategory($groups, $resource->category_id)) {
                    $smarty->assign([
                        'DOWNLOAD' => $resource_language->get('resources', 'download'),
                        'DOWNLOAD_URL' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name) . '/', 'do=download&release=' . $release->id)
                    ]);
                }
            } else {
                // Can the user download?
                if($user->isLoggedIn()){
                    if ($resources->canDownloadResourceFromCategory($groups, $resource->category_id)) {
                        if($user->data()->id == $resource->creator_id){
                            // Author can download their own resources
                            $smarty->assign([
                                'DOWNLOAD' => $resource_language->get('resources', 'download'),
                                'DOWNLOAD_URL' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name) . '/', 'do=download&release=' . $release->id)
                            ]);

                        } else {
                            // Check purchases
                            $paid = DB::getInstance()->query('SELECT status FROM nl2_resources_payments WHERE resource_id = ? AND user_id = ?', [$resource->id, $user->data()->id])->results();

                            if(count($paid)){
                                $paid = $paid[0];

                                if($paid->status == 1){
                                    // Purchased
                                    $smarty->assign([
                                        'DOWNLOAD' => $resource_language->get('resources', 'download'),
                                        'DOWNLOAD_URL' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name) . '/', 'do=download&release=' . $release->id)
                                    ]);

                                } else if($paid->status == 0){
                                    // Pending
                                    $smarty->assign([
                                        'PAYMENT_PENDING' => $resource_language->get('resources', 'payment_pending')
                                    ]);

                                } else if($paid->status == 2 || $paid->status == 3){
                                    // Cancelled
                                    $smarty->assign([
                                        'PURCHASE_FOR_PRICE' => $resource_language->get('resources', 'purchase_for_x', ['price' => Output::getClean($resource->price) . ' ' . Output::getClean($currency)]),
                                        'PURCHASE_LINK' => URL::build('/resources/purchase/' . Output::getClean($resource->id) . '-' . Output::getClean(Util::stringToURL($resource->name)))
                                    ]);

                                }
                            } else {
                                // Needs to purchase
                                $smarty->assign([
                                    'PURCHASE_FOR_PRICE' => $resource_language->get('resources', 'purchase_for_x', ['price' => Output::getClean($resource->price) . ' ' . Output::getClean($currency)]),
                                    'PURCHASE_LINK' => URL::build('/resources/purchase/' . Output::getClean($resource->id) . '-' . Output::getClean(Util::stringToURL($resource->name)))
                                ]);
                            }
                        }
                    }

                } else {
                    $smarty->assign([
                        'PURCHASE_FOR_PRICE' => $resource_language->get('resources', 'purchase_for_x', ['price' => Output::getClean($resource->price) . ' ' . $currency])
                    ]);
                }
            }

            $template_file = 'resources/resource_view_release.tpl';;

        }
    } else if(isset($_GET['do'])){
        if($_GET['do'] == 'download'){
            if(!isset($_GET['release'])){
                // Get latest release
                $release = DB::getInstance()->orderWhere('resources_releases', 'resource_id = ' . $resource->id, 'created', 'DESC LIMIT 3');
                if (!$release->count()) {
                    Redirect::to(URL::build('/resources'));
                } else $release = $release->first();

            } else {
                // Get specific release
                if(!is_numeric($_GET['release'])){
                    Redirect::to(URL::build('/resources'));
                }

                $release = DB::getInstance()->get('resources_releases', ['id', '=', $_GET['release']]);
                if(!$release->count() || $release->first()->resource_id != $resource->id){
                    Redirect::to(URL::build('/resources'));
                } else $release = $release->first();
            }

            // Download permission?
            if ($user->isLoggedIn() && $user->data()->id == $resource->creator_id) {
                $can_download = true;
            }
            if (!isset($can_download) && !$resources->canDownloadResourceFromCategory($groups, $resource->category_id)) {
                Redirect::to(URL::build('/resources'));
            }

            if($release->download_link != 'local'){
                // Increment download counter
                if(!$user->isLoggedIn() || $user->data()->id != $resource->creator_id){
                    if($user->isLoggedIn() || Cookie::exists('accept')){
                        if(!Cookie::exists('nl-resource-download-' . $resource->id)) {
                            DB::getInstance()->increment('resources', $resource->id, 'downloads');
                            DB::getInstance()->increment('resources_releases', $release->id, 'downloads');
                            Cookie::put('nl-resource-download-' . $resource->id, "true", 3600);
                        }
                    } else {
                        if(!Session::exists('nl-resource-download-' . $resource->id)) {
                            DB::getInstance()->increment('resources', $resource->id, 'downloads');
                            DB::getInstance()->increment('resources_releases', $release->id, 'downloads');
                            Session::put('nl-resource-download-' . $resource->id, "true", 3600);
                        }
                    }
                }

                // Redirect to download
                Redirect::to(Output::getClean($release->download_link));

            } else {
                // Local zip
                $dir = ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $resource->creator_id . DIRECTORY_SEPARATOR . $resource->id . DIRECTORY_SEPARATOR . $release->id;
                $files = scandir($dir);

                if(!count($files)){
                    // Unable to find files
                    Redirect::to(URL::build('/resources/resource/' . $resource->id));
                }

                $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type
                foreach($files as $file){
                    // Ensure file is zip
                    if($file == '.' || $file == '..')
                        continue;

                    if(finfo_file($finfo, $dir . DIRECTORY_SEPARATOR . $file) == 'application/zip')
                        $zip = $dir . DIRECTORY_SEPARATOR . $file;
                }
                finfo_close($finfo);

                if(!isset($zip)){
                    // No valid .zip
                    Redirect::to(URL::build('/resources/resource/' . $resource->id));
                }

                // Get resource type
                if($resource->type == 0){
                    if(!$user->isLoggedIn() || $user->data()->id != $resource->creator_id){
                        if($user->isLoggedIn() || Cookie::exists('accept')){
                            if(!Cookie::exists('nl-resource-download-' . $resource->id)) {
                                DB::getInstance()->increment('resources', $resource->id, 'downloads');
                                DB::getInstance()->increment('resources_releases', $release->id, 'downloads');
                                Cookie::put('nl-resource-download-' . $resource->id, "true", 3600);
                            }
                        } else {
                            if(!Session::exists('nl-resource-download-' . $resource->id)) {
                                DB::getInstance()->increment('resources', $resource->id, 'downloads');
                                DB::getInstance()->increment('resources_releases', $release->id, 'downloads');
                                Session::put('nl-resource-download-' . $resource->id, "true", 3600);
                            }
                        }
                    }

                    // Free, continue
                    header('Content-Type: application/octet-stream');
                    header('Content-Transfer-Encoding: Binary');
                    header('Content-disposition: attachment; filename="' . basename($zip) . '"');
                    ob_clean();
                    flush();
                    readfile($zip);

                    die();

                } else {
                    // Premium, ensure user is logged in and has purchased this resource
                    if(!$user->isLoggedIn()){
                        Redirect::to(URL::build('/resources'));
                    }

                    if(isset($can_download)){
                        if(!$user->isLoggedIn() || $user->data()->id != $resource->creator_id){
                            if($user->isLoggedIn() || Cookie::exists('accept')){
                                if(!Cookie::exists('nl-resource-download-' . $resource->id)) {
                                    DB::getInstance()->increment('resources', $resource->id, 'downloads');
                                    DB::getInstance()->increment('resources_releases', $release->id, 'downloads');
                                    Cookie::put('nl-resource-download-' . $resource->id, "true", 3600);
                                }
                            } else {
                                if(!Session::exists('nl-resource-download-' . $resource->id)) {
                                    DB::getInstance()->increment('resources', $resource->id, 'downloads');
                                    DB::getInstance()->increment('resources_releases', $release->id, 'downloads');
                                    Session::put('nl-resource-download-' . $resource->id, "true", 3600);
                                }
                            }
                        }

                        header('Content-Type: application/octet-stream');
                        header('Content-Transfer-Encoding: Binary');
                        header('Content-disposition: attachment; filename="' . basename($zip) . '"');
                        ob_clean();
                        flush();
                        readfile($zip);

                        die();
                    }

                    $paid = DB::getInstance()->query('SELECT status FROM nl2_resources_payments WHERE resource_id = ? AND user_id = ?', [$resource->id, $user->data()->id])->results();
                    if(count($paid)){
                        $paid = $paid[0];

                        if($paid->status == 1){
                            if(!$user->isLoggedIn() || $user->data()->id != $resource->creator_id){
                                if($user->isLoggedIn() || Cookie::exists('accept')){
                                    if(!Cookie::exists('nl-resource-download-' . $resource->id)) {
                                        DB::getInstance()->increment('resources', $resource->id, 'downloads');
                                        DB::getInstance()->increment('resources_releases', $release->id, 'downloads');
                                        Cookie::put('nl-resource-download-' . $resource->id, "true", 3600);
                                    }
                                } else {
                                    if(!Session::exists('nl-resource-download-' . $resource->id)) {
                                        DB::getInstance()->increment('resources', $resource->id, 'downloads');
                                        DB::getInstance()->increment('resources_releases', $release->id, 'downloads');
                                        Session::put('nl-resource-download-' . $resource->id, "true", 3600);
                                    }
                                }
                            }

                            // Purchased
                            header('Content-Type: application/octet-stream');
                            header('Content-Transfer-Encoding: Binary');
                            header('Content-disposition: attachment; filename="' . basename($zip) . '"');
                            ob_clean();
                            flush();
                            readfile($zip);

                            die();

                        } else {
                            Redirect::to(URL::build('/resources/resource/' . Output::getClean($resource->id)));

                        }
                    } else {
                        Redirect::to(URL::build('/resources/resource/' . Output::getClean($resource->id)));

                    }
                }
            }

        } else if($_GET['do'] == 'update'){
            // Update resource
            if($user->isLoggedIn() && $resource->creator_id == $user->data()->id){
                // Can update
                if(Input::exists()){
                    if(Token::check(Input::get('token'))){
                        // Validate release
                        // TODO: hooks
                        $content = Output::getClean($_POST['content']);

                        // Release type
                        switch(strtolower($_POST['type'])) {
                            case 'local':
                                // Upload zip
                                if(!isset($_POST['version']))
                                    $version = '1.0.0';
                                else
                                    $version = $_POST['version'];

                                $user_dir = ROOT_PATH . '/uploads/resources/' . $user->data()->id;

                                if(!is_dir($user_dir)){
                                    if(!mkdir($user_dir)){
                                        $error = $resource_language->get('resources', 'upload_directory_not_writable');
                                    }
                                }

                                if(isset($_FILES['resourceFile'])){
                                    $filename = $_FILES['resourceFile']['name'];
                                    $fileext = pathinfo($filename, PATHINFO_EXTENSION);

                                    if(strtolower($fileext) != 'zip'){
                                        $error = $resource_language->get('resources', 'file_not_zip');
                                    } else {
                                        // Check file size
                                        $filesize = DB::getInstance()->get('settings', ['name', '=', 'resources_filesize']);
                                        if (!$filesize->count()) {
                                            DB::getInstance()->insert('settings', [
                                                'name' => 'resources_filesize',
                                                'value' => '2048'
                                            ]);
                                            $filesize = '2048';

                                        } else {
                                            $filesize = $filesize->first()->value;

                                            if(!is_numeric($filesize))
                                                $filesize = '2048';
                                        }

                                        if($_FILES['resourceFile']['size'] > ($filesize * 1000)){
                                            $error = $resource_language->get('resources', 'filesize_max_x', ['filesize' => Output::getClean($filesize)]);

                                        } else {
                                            // Create release
                                            DB::getInstance()->insert('resources_releases', [
                                                'resource_id' => $resource->id,
                                                'category_id' => $resource->category_id,
                                                'release_title' => Output::getClean((empty($_POST['title']) ? $version : $_POST['title'])),
                                                'release_description' => $content,
                                                'release_tag' => Output::getClean($version),
                                                'created' => date('U'),
                                                'download_link' => 'local'
                                            ]);

                                            $release_id = DB::getInstance()->lastId();

                                            $uploadPath = $user_dir . DIRECTORY_SEPARATOR . $resource->id;

                                            if(!is_dir($uploadPath))
                                                mkdir($uploadPath);

                                            $uploadPath .= DIRECTORY_SEPARATOR . $release_id;

                                            if(!is_dir($uploadPath))
                                                mkdir($uploadPath);

                                            $uploadPath .= DIRECTORY_SEPARATOR . basename($_FILES['resourceFile']['name']);

                                            if(move_uploaded_file($_FILES['resourceFile']['tmp_name'], $uploadPath)){
                                                // File uploaded
                                                DB::getInstance()->update('resources', $resource->id, [
                                                    'updated' => date('U'),
                                                    'latest_version' => Output::getClean($version)
                                                ]);

                                                $success = true;
                                            } else {
                                                // Unable to upload file
                                                $error = $resource_language->get('resources', 'file_upload_failed', ['error' => Output::getClean($_FILES['resourceFile']['error'])]);

                                                DB::getInstance()->delete('resources_releases', ['id', '=', $release_id]);
                                            }
                                        }
                                    }
                                }
                            break;
                            case 'github':
                                // Github release
                                if($resource->type == 0 && $resource->github_url != 'none'){
                                    try {
                                        // Use cURL
                                        $ch = curl_init();

                                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                            'Accept: application/vnd.github.v3+json',
                                            'User-Agent: NamelessMC-App'
                                        ]);
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                        curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/' . Output::getClean($resource->github_username) . '/' . Output::getClean($resource->github_repo_name) . '/releases/' . Output::getClean($_POST['release']));

                                        if(!$github_query = curl_exec($ch)){
                                            $error = curl_error($ch);
                                        }

                                        curl_close($ch);

                                        $github_query = json_decode($github_query);

                                        if(!isset($github_query->id)) $error = $resource_language->get('resources', 'unable_to_get_repo', ['repo' => Output::getClean($resource->github_username) . '/' . Output::getClean($resource->github_repo_name)]);
                                        else {
                                            // Valid response
                                            // Check update doesn't already exist
                                            $exists = DB::getInstance()->get('resources_releases', ['release_tag', '=', Output::getClean($github_query->tag_name)]);
                                            if ($exists->count()){
                                                foreach($exists->results() as $item){
                                                    if($item->resource_id == $resource->id){
                                                        $update_exists = true;
                                                    }
                                                }
                                            }

                                            if(isset($update_exists)){
                                                $error = $resource_language->get('resources', 'update_already_exists');
                                            } else {
                                                // Content is empty, Load from github instead
                                                if(empty($content)) {
                                                    $content = $github_query->body;
                                                }

                                                DB::getInstance()->update('resources', $resource->id, [
                                                    'updated' => date('U'),
                                                    'latest_version' => Output::getClean($github_query->tag_name)
                                                ]);

                                                DB::getInstance()->insert('resources_releases', [
                                                    'resource_id' => $resource->id,
                                                    'category_id' => $resource->category_id,
                                                    'release_title' => Output::getClean((empty($_POST['title']) ? $github_query->name : $_POST['title'])),
                                                    'release_description' => Output::getPurified($content),
                                                    'release_tag' => Output::getClean($github_query->tag_name),
                                                    'created' => date('U'),
                                                    'download_link' => Output::getClean($github_query->html_url)
                                                ]);

                                                $success = true;
                                            }
                                        }

                                    } catch(Exception $e){
                                        $error = $e->getMessage();
                                    }
                                }
                            break;
                            case 'external_link':
                                // External link

                                // TODO: improve error
                                $errorMessage = $resource_language->get('resources', 'external_link_error', ['min' => 4, 'max' => 256]);

                                // Validate link
                                $validation = Validate::check($_POST, [
                                    'link' => [
                                        'required' => true,
                                        'min' => 4,
                                        'max' => 256
                                    ],
                                    'title' => [
                                        'max' => 128
                                    ]
                                ])->messages([
                                    'link' => [
                                        'required' => $errorMessage,
                                        'min' => $errorMessage,
                                        'max' => $errorMessage
                                    ],
                                    'title' => [
                                        'max' => $errorMessage
                                    ]
                                ]);

                                if($validation->passed()){
                                    if(!isset($_POST['version']))
                                        $version = '1.0.0';
                                    else
                                        $version = $_POST['version'];

                                    DB::getInstance()->update('resources', $resource->id, [
                                        'updated' => date('U'),
                                        'latest_version' => Output::getClean($version)
                                    ]);

                                    DB::getInstance()->insert('resources_releases', [
                                        'resource_id' => $resource->id,
                                        'category_id' => $resource->category_id,
                                        'release_title' => Output::getClean((empty($_POST['title']) ? $version : $_POST['title'])),
                                        'release_description' => $content,
                                        'release_tag' => Output::getClean($version),
                                        'created' => date('U'),
                                        'download_link' => Output::getClean($_POST['link'])
                                    ]);

                                    $success = true;
                                } else {
                                    $error = $resource_language->get('resources', 'external_link_error', ['min' => 4, 'max' => 256]);
                                }
                            break;
                            default:
                                $error = $resource_language->get('resources', 'select_release_type_error');
                            break;
                        }

                        if($success) {
                            // Hook
                            $new_resource_category = DB::getInstance()->get('resources_categories', ['id', '=', $resource->category_id]);
                            if ($new_resource_category->count())
                                $new_resource_category = Output::getClean($new_resource_category->first()->name);
                            else
                                $new_resource_category = 'Unknown';

                            EventHandler::executeEvent('updateResource', [
                                'event' => 'updateResource',
                                'username' => $user->getDisplayname(),
                                'content' => $resource_language->get('resources', 'updated_resource_text', ['category' => $new_resource_category, 'user' => $user->getDisplayname()]),
                                'content_full' => str_replace(['&amp', '&nbsp;', '&#39;'], ['&', '', '\''], strip_tags($content)),
                                'avatar_url' => $user->getAvatar(128, true),
                                'title' => Output::getClean($resource->name),
                                'url' => Util::getSelfURL() . ltrim(URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)), '/')
                            ]);

                            Redirect::to(URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)));
                        }
                    } else {
                        $error = $language->get('general', 'invalid_token');
                    }
                }

                // Github Integration
                if($resource->type == 0 && $resource->github_url != 'none'){
                    // Github API
                    try {
                        $ch = curl_init();

                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Accept: application/vnd.github.v3+json',
                            'User-Agent: NamelessMC-App'
                        ]);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/' . Output::getClean($resource->github_username) . '/' . Output::getClean($resource->github_repo_name) . '/releases');

                        if(!$github_query = curl_exec($ch)){
                            $error = curl_error($ch);
                        }

                        curl_close($ch);

                    } catch(Exception $e){
                        die($e->getMessage());
                    }

                    // Get list of all releases
                    $github_query = json_decode($github_query);

                    if(!isset($github_query[0])) $error = $resource_language->get('resources', 'unable_to_get_repo', ['repo' => Output::getClean($resource->github_username) . '/' . Output::getClean($resource->github_repo)]);
                    else {
                        // Valid response
                        $releases_array = [];
                        foreach($github_query as $release){
                            // Select release
                            $releases_array[] = [
                                'id' => $release->id,
                                'tag' => Output::getClean($release->tag_name),
                                'name' => Output::getClean($release->name)
                            ];
                        }
                    }

                    // Assign Smarty variables
                    $smarty->assign([
                        'GITHUB_LINKED' => true,
                        'GITHUB_RELEASE' => $resource_language->get('resources', 'github_release'),
                        'RELEASES' => $releases_array
                    ]);
                }

                // Upload new zip
                if(isset($error)) $smarty->assign('ERROR', $error);

                // Assign Smarty variables
                $smarty->assign([
                    'UPDATE_RESOURCE' => $resource_language->get('resources', 'update'),
                    'CANCEL' => $language->get('general', 'cancel'),
                    'CANCEL_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)),
                    'CONFIRM_CANCEL' => $language->get('general', 'confirm_cancel'),
                    'RELEASE_TYPE' => $resource_language->get('resources', 'release_type'),
                    'CHOOSE_FILE' => $resource_language->get('resources', 'choose_file'),
                    'ZIP_ONLY' => $resource_language->get('resources', 'zip_only'),
                    'EXTERNAL_LINK' => $resource_language->get('resources', 'external_link'),
                    'VERSION_TAG' => $resource_language->get('resources', 'version_tag'),
                    'ZIP_FILE' => $resource_language->get('resources', 'zip_file'),
                    'EXTERNAL_DOWNLOAD' => $resource_language->get('resources', 'external_download'),
                    'VERSION_VALUE' => ((isset($_POST['version']) && $_POST['version']) ? Output::getClean(Input::get('version')) : '1.0.0'),
                    'TITLE_VALUE' => ((isset($_POST['title']) && $_POST['title']) ? Output::getClean(Input::get('title')) : ''),
                    'CONTENT_VALUE' => ((isset($_POST['content']) && $_POST['content']) ? Output::getClean(Input::get('content')) : ''),
                    'SUBMIT' => $language->get('general', 'submit'),
                    'TOKEN' => Token::get(),
                    'UPDATE_TITLE' => $resource_language->get('resources', 'update_title'),
                    'UPDATE_INFORMATION' => $resource_language->get('resources', 'update_information')
                ]);

                $template->addJSScript(Input::createTinyEditor($language, 'editor'));

                $template_file = 'resources/update_resource.tpl';
            } else {
                // Can't update, redirect
                Redirect::to(URL::build('/resources'));
            }
        } else if($_GET['do'] == 'edit'){
            // Check user can edit
            if(!$user->isLoggedIn()){
                Redirect::to(URL::build('/resources'));
            }
            if($resource->creator_id == $user->data()->id || $resources->canEditResources($resource->category_id, $groups)){
                // Can edit
                $errors = [];

                if(Input::exists()){
                    if(Token::check(Input::get('token'))){
                        $validation = Validate::check($_POST, [
                            'title' => [
                                'min' => 2,
                                'max' => 64,
                                'required' => true
                            ],
                            'short_description' => [
                                'min' => 2,
                                'max' => 64,
                                'required' => true
                            ],
                            'description' => [
                                'min' => 2,
                                'max' => 20000,
                                'required' => true
                            ],
                            'contributors' => [
                                'max' => 255
                            ]
                        ])->messages([
                            'title' => [
                                'min' => $resource_language->get('resources', 'name_min_2'),
                                'max' => $resource_language->get('resources', 'name_max_64'),
                                'required' => $resource_language->get('resources', 'name_required')
                            ],
                            'short_description' => [
                                'min' => $resource_language->get('resources', 'short_description_min_2'),
                                'max' => $resource_language->get('resources', 'short_description_max_64'),
                                'required' => $resource_language->get('resources', 'short_description_required')
                            ],
                            'description' => [
                                'min' => $resource_language->get('resources', 'content_min_2'),
                                'max' => $resource_language->get('resources', 'content_max_20000'),
                                'required' => $resource_language->get('resources', 'content_required')
                            ],
                            'contributors' => [
                                'max' => $resource_language->get('resources', 'contributors_max_255')
                            ]
                        ]);

                        if ($validation->passed()) {
                            if($resource->type == 1 && isset($_POST['price']) && !empty($_POST['price']) && is_numeric($_POST['price']) && $_POST['price'] >= 0.01 && $_POST['price'] < 100 && preg_match('/^\d+(?:\.\d{2})?$/', $_POST['price'])){
                                $price = number_format($_POST['price'], 2, '.', '');
                            } else
                                $price = $resource->price;

                            try {
                                // TODO: hooks
                                $content = Output::getClean($_POST['description']);

                                DB::getInstance()->update('resources', $resource->id, [
                                    'name' => Output::getClean(Input::get('title')),
                                    'short_description' => Output::getClean(Input::get('short_description')),
                                    'description' => $content,
                                    'contributors' => Output::getClean(Input::get('contributors')),
                                    'price' => $price
                                ]);

                                Redirect::to(URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL(Input::get('title'))));

                            } catch(Exception $e){
                                $errors[] = $e->getMessage();
                            }

                        } else {
                            $errors = $validation->errors();
                        }

                    } else {
                        $errors[] = $language->get('general', 'invalid_token');
                    }
                }
            } else {
                Redirect::to(URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)));
            }

            if(isset($errors) && count($errors))
                $smarty->assign('ERRORS', $errors);

            // Get latest update
            $latest_update = DB::getInstance()->orderWhere('resources_releases', 'resource_id = ' . $resource->id, 'created', 'DESC LIMIT 1');

            if (!$latest_update->count()) {
                Redirect::to(URL::build('/resources'));

            } else $latest_update = $latest_update->first();

            $author = new User($resource->creator_id);

            // Smarty variables
            $smarty->assign([
                'EDITING_RESOURCE' => $resource_language->get('resources', 'editing_resource'),
                'NAME' => $resource_language->get('resources', 'resource_name'),
                'SHORT_DESCRIPTION' => $resource_language->get('resources', 'resource_short_description'),
                'DESCRIPTION' => $resource_language->get('resources', 'resource_description'),
                'CONTRIBUTORS' => $resource_language->get('resources', 'contributors'),
                'RESOURCE_NAME' => Output::getClean($resource->name),
                'RELEASE_TAG' => Output::getClean($latest_update->release_tag),
                'RESOURCE_SHORT_DESCRIPTION' => Output::getClean($resource->short_description),
                'RESOURCE_DESCRIPTION' => Output::getPurified(htmlspecialchars_decode($resource->description)),
                'RESOURCE_CONTRIBUTORS' => Output::getClean($resource->contributors),
                'CANCEL' => $language->get('general', 'cancel'),
                'CONFIRM_CANCEL' => $language->get('general', 'confirm_cancel'),
                'CANCEL_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)),
                'TOKEN' => Token::get(),
                'SUBMIT' => $language->get('general', 'submit')
            ]);

            if($resource->type == 1){
                $currency = DB::getInstance()->get('settings', ['name', '=', 'resources_currency']);
                if (!$currency->count()) {
                    DB::getInstance()->insert('settings', [
                        'name' => 'resources_currency',
                        'value' => 'GBP'
                    ]);
                    $currency = 'GBP';

                } else
                    $currency = $currency->first()->value;

                $smarty->assign([
                    'PRICE' => $resource_language->get('resources', 'price'),
                    'RESOURCE_PRICE' => Output::getClean($resource->price),
                    'CURRENCY' => $currency
                ]);
            }

            $template_file = 'resources/edit_resource.tpl';

        } else if($_GET['do'] == 'move') {
            // Check user can move
            if (!$user->isLoggedIn()) {
                Redirect::to(URL::build('/resources'));
            }
            if ($resources->canMoveResources($resource->category_id, $groups)) {
                $errors = [];

                // Get categories
                $categories = DB::getInstance()->get('resources_categories', ['id', '<>', $resource->category_id]);
                if (!$categories->count()) {
                    $smarty->assign('NO_CATEGORIES', $resource_language->get('resources', 'no_categories_available'));
                } else {
                    $smarty->assign('CATEGORIES', $categories->results());
                }

                if(Input::exists()){
                    if(Token::check(Input::get('token'))){
                        if(isset($_POST['category_id']) && is_numeric($_POST['category_id'])) {
                            // Move resource
                            $category = DB::getInstance()->get('resources_categories', ['id', '=', $_POST['category_id']]);
                            if ($category->count()) {
                                try {
                                    DB::getInstance()->update('resources', $resource->id, [
                                        'category_id' => $_POST['category_id']
                                    ]);

                                    $releases = DB::getInstance()->get('resources_releases', ['resource_id', '=', $resource->id]);
                                    if ($releases->count()) {
                                        foreach ($releases as $release) {
                                            DB::getInstance()->update('resources_releases', $release->id, [
                                                'category_id' => $_POST['category_id']
                                            ]);
                                        }
                                    }

                                    Redirect::to(URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)));

                                } catch (Exception $e) {
                                    $errors[] = $e->getMessage();
                                }
                            } else
                                $errors[] = $resource_language->get('resources', 'invalid_category');
                        } else
                            $errors[] = $resource_language->get('resources', 'invalid_category');

                    } else
                        $errors[] = $language->get('general', 'invalid_token');
                }

                if(count($errors))
                    $smarty->assign('ERRORS', $errors);

                $smarty->assign([
                    'MOVE_RESOURCE' => $resource_language->get('resources', 'move_resource'),
                    'TOKEN' => Token::get(),
                    'CANCEL' => $language->get('general', 'cancel'),
                    'CONFIRM_CANCEL' => $language->get('general', 'confirm_cancel'),
                    'CANCEL_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)),
                    'SUBMIT' => $language->get('general', 'submit'),
                    'MOVE_TO' => $resource_language->get('resources', 'move_to')
                ]);

                $template_file = 'resources/move.tpl';

            } else {
                Redirect::to(URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)));
            }
        } else if($_GET['do'] == 'delete'){
            // Check user can delete
            if (!$user->isLoggedIn()) {
                Redirect::to(URL::build('/resources'));
            }

            if ($resources->canDeleteResources($resource->category_id, $groups)) {
                $errors = [];

                if(Input::exists()){
                    if(Token::check(Input::get('token'))){
                        // Delete resource
                        try {
                            DB::getInstance()->delete('resources', ['id', '=', $resource->id]);
                            DB::getInstance()->delete('resources_comments', ['resource_id', '=', $resource->id]);
                            DB::getInstance()->delete('resources_releases', ['resource_id', '=', $resource->id]);
                            DB::getInstance()->delete('resources_payments', ['resource_id', '=', $resource->id]);

                            if(is_dir(ROOT_PATH . '/uploads/resources/' . $resource->creator_id . '/' . $resource->id)){
                                Util::recursiveRemoveDirectory(ROOT_PATH . '/uploads/resources/' . $resource->creator_id . '/' . $resource->id);
                            }

                            Redirect::to(URL::build('/resources'));
                        } catch(Exception $e){
                            $errors[] = $e->getMessage();
                        }

                    } else
                        $errors[] = $language->get('general', 'invalid_token');
                }

                if(count($errors))
                    $smarty->assign('ERRORS', $errors);

                $smarty->assign([
                    'CONFIRM_DELETE_RESOURCE' => $resource_language->get('resources', 'confirm_delete_resource', ['resource' => Output::getClean($resource->name)]),
                    'TOKEN' => Token::get(),
                    'CANCEL' => $language->get('general', 'cancel'),
                    'CANCEL_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)),
                    'DELETE' => $language->get('general', 'delete')
                ]);

                $template_file = 'resources/delete.tpl';

            } else {
                Redirect::to(URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)));
            }

        } else if($_GET['do'] == 'delete_review'){
            // Check user can delete reviews
            if (!$user->isLoggedIn()) {
                Redirect::to(URL::build('/resources'));
            }
            if($resources->canDeleteReviews($resource->category_id, $groups)){
                if(!isset($_GET['review']) || !is_numeric($_GET['review'])){
                    Redirect::to(URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)));
                }
                // Ensure review exists
                $review = DB::getInstance()->get('resources_comments', ['id', '=', $_GET['review']]);
                if ($review->count()) {
                    // Delete it
                    try {
                        DB::getInstance()->delete('resources_comments', ['id', '=', $_GET['review']]);

                        // Re-calculate rating
                        // Unhide user's previous rating if it exists
                        $ratings = DB::getInstance()->get('resources_comments', ['resource_id', '=', $resource->id]);
                        if ($ratings->count()){
                            $overall_rating = 0;
                            $overall_rating_count = 0;
                            $release_rating = 0;
                            $release_rating_count = 0;
                            $last_rating = 0;
                            $last_rating_created = 0;
                            $last_rating_value = 0;

                            foreach($ratings->results() as $rating){
                                if($rating->author_id == $user->data()->id && $rating->hidden == 1 && $rating->created > $last_rating_created){
                                    // Unhide rating
                                    $last_rating = $rating->id;
                                    $last_rating_created = $rating->created;

                                    if($rating->release_tag == $resource->latest_version)
                                        $last_rating_value = $rating->rating;

                                } else if($rating->hidden == 0){
                                    // Update rating
                                    // Overall
                                    $overall_rating = $overall_rating + $rating->rating;
                                    $overall_rating_count++;

                                    if($rating->release_tag == $resource->latest_version){
                                        // Release
                                        $release_rating = $release_rating + $rating->rating;
                                        $release_rating_count++;
                                    }
                                }
                            }

                            if($last_rating > 0){
                                DB::getInstance()->update('resources_comments', $last_rating, [
                                    'hidden' => 0
                                ]);

                                if($last_rating_value > 0){
                                    $overall_rating += $last_rating_value;
                                    $overall_rating_count++;
                                    $release_rating = $release_rating += $last_rating_value;
                                    $release_rating_count++;
                                }

                            }

                            if($overall_rating > 0) {
                                $overall_rating = $overall_rating / $overall_rating_count;
                                $overall_rating = round($overall_rating * 10);
                            }

                            if($release_rating > 0) {
                                $release_rating = $release_rating / $release_rating_count;
                                $release_rating = round($release_rating * 10);
                            }
                        } else {
                            $overall_rating = 0;
                            $release_rating = 0;
                        }

                        DB::getInstance()->update('resources', $resource->id, [
                            'rating' => $overall_rating
                        ]);
                        DB::getInstance()->update('resources_releases', $latest_release->id, [
                            'rating' => $release_rating
                        ]);

                        $cache->setCache('resource-comments-' . $resource->id);
                        $cache->erase('comments');

                    } catch(Exception $e){
                        // error
                    }
                }
                Redirect::to(URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)));
            }
        } else {
            Redirect::to(URL::build('/resources'));
        }
    }
}

if($user->isLoggedIn()){
    $template->addJSScript(Input::createTinyEditor($language, 'editor'));

    $template->addJSScript('
      var $star_rating = $(\'.star-rating.view .far\');
      var $star_rating_set = $(\'.star-rating.set .far\');

      var SetRatingStar = function(type = 0) {
          if(type === 0) {
              return $star_rating.each(function () {
                  if (parseInt($(this).parent().children(\'input.rating-value\').val()) >= parseInt($(this).data(\'rating\'))) {
                      return $(this).removeClass(\'far\').addClass(\'fas\');
                  } else {
                      return $(this).removeClass(\'fas\').addClass(\'far\');
                  }
              });
          } else {
              return $star_rating_set.each(function () {
                  if (parseInt($star_rating_set.siblings(\'input.rating-value\').val()) >= parseInt($(this).data(\'rating\'))) {
                      return $(this).removeClass(\'far\').addClass(\'fas\');
                  } else {
                      return $(this).removeClass(\'fas\').addClass(\'far\');
                  }
              });
          }
      };

      $star_rating_set.on(\'click\', function() {
          $star_rating_set.siblings(\'input.rating-value\').val($(this).data(\'rating\'));
          return SetRatingStar(1);
      });

      SetRatingStar();
    ');

} else {
    $template->addJSScript('
      var $star_rating = $(\'.star-rating.view .far\');
      var $star_rating_set = $(\'.star-rating.set .far\');

      var SetRatingStar = function(type = 0) {
          if(type === 0) {
              return $star_rating.each(function () {
                  if (parseInt($(this).parent().children(\'input.rating-value\').val()) >= parseInt($(this).data(\'rating\'))) {
                      return $(this).removeClass(\'far\').addClass(\'fas\');
                  } else {
                      return $(this).removeClass(\'fas\').addClass(\'far\');
                  }
              });
          } else {
              return $star_rating_set.each(function () {
                  if (parseInt($star_rating_set.siblings(\'input.rating-value\').val()) >= parseInt($(this).data(\'rating\'))) {
                      return $(this).removeClass(\'far\').addClass(\'fas\');
                  } else {
                      return $(this).removeClass(\'fas\').addClass(\'far\');
                  }
              });
          }
      };

      SetRatingStar();
    ');
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

$template->displayTemplate($template_file, $smarty);
