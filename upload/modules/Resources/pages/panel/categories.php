<?php
/*
 *  Made by Samerton
 *  https://github.com/samerton/Nameless-Resources
 *  NamelessMC version 2.0.0-pr13
 *
 *  License: MIT
 *
 *  Panel resources categories page
 */

if (!$user->handlePanelPageLoad('admincp.resources.categories')) {
    require_once(ROOT_PATH . '/403.php');
    die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'resources');
define('PANEL_PAGE', 'resources_categories');
$page_title = $resource_language->get('resources', 'categories');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

if(!isset($_GET['action'])){
    // Get categories
    $categories = DB::getInstance()->orderAll('resources_categories', 'display_order', 'ASC');
    $template_array = [];

    if($categories->count()){
        foreach($categories->results() as $category){
            $template_array[] = [
                'edit_link' => URL::build('/panel/resources/categories/', 'action=edit&cid=' . Output::getClean($category->id)),
                'name' => Output::getPurified(Output::getDecoded($category->name)),
                'description' => Output::getPurified(Output::getDecoded($category->description)),
                'order_up' => URL::build('/panel/resources/categories/', 'action=order&dir=up&cid=' . Output::getClean($category->id)),
                'order_down' => URL::build('/panel/resources/categories/', 'action=order&dir=down&cid=' . Output::getClean($category->id)),
                'delete_link' => URL::build('/panel/resources/categories/', 'action=delete&cid=' . Output::getClean($category->id))
            ];
        }
    }

    $smarty->assign([
        'CATEGORIES_LIST' => $template_array,
        'NO_CATEGORIES' => $resource_language->get('resources', 'no_categories'),
        'NEW_CATEGORY' => $resource_language->get('resources', 'new_category'),
        'NEW_CATEGORY_LINK' => URL::build('/panel/resources/categories/', 'action=create')
    ]);

    $template_file = 'resources/categories.tpl';

} else {
    switch($_GET['action']){
        case 'create':
            if(Input::exists()){
                $errors = [];

                if(Token::check(Input::get('token'))){
                    // Validate input
                    $validation = Validate::check($_POST, [
                        'catname' => [
                            'required' => true,
                            'min' => 2,
                            'max' => 150
                        ],
                        'catdesc' => [
                            'max' => 255
                        ]
                    ])->messages([
                        'catname' => [
                            'required' => $resource_language->get('resources', 'input_category_title'),
                            'min' => $resource_language->get('resources', 'category_name_minimum'),
                            'max' => $resource_language->get('resources', 'category_name_maximum')
                        ],
                        'catdesc' => [
                            'max' => $resource_language->get('resources', 'category_description_maximum')
                        ]
                    ]);

                    if ($validation->passed()) {
                        // Create the category
                        try {
                            $description = Input::get('catdesc');

                            $last_cat_order = DB::getInstance()->orderAll('resources_categories', 'display_order', 'DESC');
                            if ($last_cat_order->count()) $last_cat_order = $last_cat_order->first()->display_order;
                            else $last_cat_order = 0;

                            DB::getInstance()->insert('resources_categories', [
                                'name' => Output::getClean(Input::get('catname')),
                                'description' => Output::getClean($description),
                                'display_order' => $last_cat_order + 1
                            ]);

                            $cat_id = DB::getInstance()->lastId();

                            Redirect::to(URL::build('/panel/resources/categories', 'action=edit&cid=' . $cat_id));

                        } catch(Exception $e){
                            $errors = ['<div class="alert alert-danger">Unable to create category: ' . $e->getMessage() . '</div>'];
                        }
                    } else {
                        $errors = $validation->errors();
                    }
                } else
                    $errors[] = $language->get('general', 'invalid_token');
            }

            $smarty->assign([
                'CREATING_CATEGORY' => $resource_language->get('resources', 'creating_category'),
                'CANCEL' => $language->get('general', 'cancel'),
                'CANCEL_LINK' => URL::build('/panel/resources/categories'),
                'ARE_YOU_SURE' => $language->get('general', 'are_you_sure'),
                'CONFIRM_CANCEL' => $language->get('general', 'confirm_cancel'),
                'YES' => $language->get('general', 'yes'),
                'NO' => $language->get('general', 'no'),
                'CATEGORY_NAME' => $resource_language->get('resources', 'category_name'),
                'CATEGORY_NAME_VALUE' => Output::getClean(Input::get('catname')),
                'CATEGORY_DESCRIPTION' => $resource_language->get('resources', 'category_description'),
                'CATEGORY_DESCRIPTION_VALUE' => Output::getClean(Input::get('catdesc'))
            ]);

            $template_file = 'resources/categories_create.tpl';

            break;

        case 'edit':
            // Get category
            if(!isset($_GET['cid']) || !is_numeric($_GET['cid'])){
                Redirect::to(URL::build('/panel/resources/categories'));
            }

            $category = DB::getInstance()->get('resources_categories', ['id', '=', $_GET['cid']]);
            if(!$category->count()) {
                Redirect::to(URL::build('/panel/resources/categories'));
            }
            $category = $category->first();

            $groups = DB::getInstance()->get('groups', ['id', '<>', '0'])->results(); // Get a list of all groups
            $group_perms = DB::getInstance()->get('resources_categories_permissions', ['category_id', '=', $category->id])->results();

            if(Input::exists()){
                $errors = [];

                if(Token::check(Input::get('token'))){
                    $validation = Validate::check($_POST, [
                        'title' => [
                            Validate::REQUIRED => true,
                            Validate::MIN => 2,
                            Validate::MAX => 150
                        ],
                        'description' => [
                            Validate::MAX => 255
                        ]
                    ])->messages([
                        'title' => [
                            Validate::REQUIRED => $resource_language->get('resources', 'input_category_title'),
                            Validate::MIN => $resource_language->get('resources', 'category_name_minimum'),
                            Validate::MAX => $resource_language->get('resources', 'category_name_maximum')
                        ],
                        'description' => [
                            Validate::MAX => $resource_language->get('resources', 'category_description_maximum')
                        ]
                    ]);

                    if($validation->passed()){
                        try {
                            // Update the category
                            DB::getInstance()->update('resources_categories', $_GET['cid'], [
                                'name' => Output::getClean(Input::get('title')),
                                'description' => Output::getClean(Input::get('description'))
                            ]);

                        } catch(Exception $e) {
                            $errors[] = $e->getMessage();
                        }

                        // Guest category permissions
                        $view = Input::get('perm-view-0');
                        $post = 0;
                        $move = 0;
                        $edit_resource = 0;
                        $delete_resource = 0;
                        $edit_review = 0;
                        $delete_review = 0;
                        $download = Input::get('perm-download-0');
                        $premium = 0;

                        if(!($view)) $view = 0;
                        if(!($download)) $download = 0;

                        $cat_perm_exists = 0;

                        $cat_perm_query = DB::getInstance()->get('resources_categories_permissions', ['category_id', '=', $category->id])->results();
                        if (count($cat_perm_query)) {
                            foreach ($cat_perm_query as $query) {
                                if ($query->group_id == 0) {
                                    $cat_perm_exists = 1;
                                    $update_id = $query->id;
                                    break;
                                }
                            }
                        }

                        try {
                            if ($cat_perm_exists != 0) { // Permission already exists, update
                                // Update the category
                                DB::getInstance()->update('resources_categories_permissions', $update_id, [
                                    'view' => $view,
                                    'post' => $post,
                                    'move_resource' => $move,
                                    'edit_resource' => $edit_resource,
                                    'delete_resource' => $delete_resource,
                                    'edit_review' => $edit_review,
                                    'delete_review' => $delete_review,
                                    'download' => $download,
                                    'premium' => $premium
                                ]);
                            } else { // Permission doesn't exist, create
                                DB::getInstance()->insert('resources_categories_permissions', [
                                    'group_id' => 0,
                                    'category_id' => $category->id,
                                    'view' => $view,
                                    'post' => $post,
                                    'move_resource' => $move,
                                    'edit_resource' => $edit_resource,
                                    'delete_resource' => $delete_resource,
                                    'edit_review' => $edit_review,
                                    'delete_review' => $delete_review,
                                    'download' => $download,
                                    'premium' => $premium
                                ]);
                            }

                        } catch(Exception $e) {
                            $errors[] = $e->getMessage();
                        }

                        // Group category permissions
                        foreach($groups as $group){
                            $view = Input::get('perm-view-' . $group->id);
                            $post = Input::get('perm-post-' . $group->id);
                            $move = Input::get('perm-move_resource-' . $group->id);
                            $edit_resource = Input::get('perm-edit_resource-' . $group->id);
                            $delete_resource = Input::get('perm-delete_resource-' . $group->id);
                            $edit_review = Input::get('perm-edit_review-' . $group->id);
                            $delete_review = Input::get('perm-delete_review-' . $group->id);
                            $download = Input::get('perm-download-' . $group->id);
                            $premium = Input::get('perm-premium-' . $group->id);

                            if(!($view)) $view = 0;
                            if(!($post)) $post = 0;
                            if(!($move)) $move = 0;
                            if(!($edit_resource)) $edit_resource = 0;
                            if(!($delete_resource)) $delete_resource = 0;
                            if(!($edit_review)) $edit_review = 0;
                            if(!($delete_review)) $delete_review = 0;
                            if(!($download)) $download = 0;
                            if(!($premium)) $premium = 0;

                            $cat_perm_exists = 0;

                            if (count($cat_perm_query)) {
                                foreach ($cat_perm_query as $query) {
                                    if ($query->group_id == $group->id) {
                                        $cat_perm_exists = 1;
                                        $update_id = $query->id;
                                        break;
                                    }
                                }
                            }

                            try {
                                if ($cat_perm_exists != 0) { // Permission already exists, update
                                    // Update the category
                                    DB::getInstance()->update('resources_categories_permissions', $update_id, [
                                        'view' => $view,
                                        'post' => $post,
                                        'move_resource' => $move,
                                        'edit_resource' => $edit_resource,
                                        'delete_resource' => $delete_resource,
                                        'edit_review' => $edit_review,
                                        'delete_review' => $delete_review,
                                        'download' => $download,
                                        'premium' => $premium
                                    ]);
                                } else { // Permission doesn't exist, create
                                    DB::getInstance()->insert('resources_categories_permissions', [
                                        'group_id' => $group->id,
                                        'category_id' => $category->id,
                                        'view' => $view,
                                        'post' => $post,
                                        'move_resource' => $move,
                                        'edit_resource' => $edit_resource,
                                        'delete_resource' => $delete_resource,
                                        'edit_review' => $edit_review,
                                        'delete_review' => $delete_review,
                                        'download' => $download,
                                        'premium' => $premium
                                    ]);
                                }

                            } catch(Exception $e) {
                                die($e->getMessage());
                            }
                        }

                        Session::flash('resources_categories_success', $resource_language->get('resources', 'category_updated_successfully'));
                        Redirect::to(URL::build('/panel/resources/categories'));

                    } else {
                        $errors = $validation->errors();
                    }
                } else
                    $errors[] = $language->get('general', 'invalid_token');
            }

            $guest_query = DB::getInstance()->query('SELECT 0 AS id, `view` AS can_view, 0 AS can_post, download AS can_download, premium AS can_post_premium FROM nl2_resources_categories_permissions WHERE group_id = 0 AND category_id = ?', [$category->id])->results();
            $group_query = DB::getInstance()->query('SELECT id, name, can_view, can_post, can_move, can_edit, can_delete, can_edit_review, can_delete_review, can_download, can_post_premium FROM nl2_groups A LEFT JOIN (SELECT group_id, `view` AS can_view, post AS can_post, move_resource AS can_move, edit_resource AS can_edit, delete_resource AS can_delete, edit_review AS can_edit_review, delete_review AS can_delete_review, download AS can_download, premium AS can_post_premium FROM nl2_resources_categories_permissions WHERE category_id = ?) B ON A.id = B.group_id ORDER BY `order` ASC', [$category->id])->results();

            $smarty->assign([
                'EDITING_CATEGORY' => $resource_language->get('resources', 'editing_category'),
                'CANCEL' => $language->get('general', 'cancel'),
                'CANCEL_LINK' => URL::build('/panel/resources/categories'),
                'ARE_YOU_SURE' => $language->get('general', 'are_you_sure'),
                'CONFIRM_CANCEL' => $language->get('general', 'confirm_cancel'),
                'YES' => $language->get('general', 'yes'),
                'NO' => $language->get('general', 'no'),
                'CATEGORY_NAME' => $resource_language->get('resources', 'category_name'),
                'CATEGORY_NAME_VALUE' => Output::getClean(Output::getDecoded($category->name)),
                'CATEGORY_DESCRIPTION' => $resource_language->get('resources', 'category_description'),
                'CATEGORY_DESCRIPTION_VALUE' => Output::getClean(Output::getDecoded($category->description)),
                'CATEGORY_PERMISSIONS' => $resource_language->get('resources', 'category_permissions'),
                'GROUP' => $resource_language->get('resources', 'group'),
                'CAN_VIEW_CATEGORY' => $resource_language->get('resources', 'can_view_category'),
                'CAN_DOWNLOAD_RESOURCES' => $resource_language->get('resources', 'can_download_resources'),
                'CAN_POST_RESOURCES' => $resource_language->get('resources', 'can_post_resource'),
                'CAN_POST_PREMIUM_RESOURCES' => $resource_language->get('resources', 'can_post_premium_resource'),
                'GUESTS' => $language->get('user', 'guests'),
                'GUEST_PERMISSIONS' => $guest_query,
                'GROUP_PERMISSIONS' => $group_query,
                'MODERATION' => $resource_language->get('resources', 'moderation'),
                'CAN_MOVE_RESOURCES' => $resource_language->get('resources', 'can_move_resources'),
                'CAN_EDIT_RESOURCES' => $resource_language->get('resources', 'can_edit_resources'),
                'CAN_DELETE_RESOURCES' => $resource_language->get('resources', 'can_delete_resources'),
                'CAN_EDIT_REVIEWS' => $resource_language->get('resources', 'can_edit_reviews'),
                'CAN_DELETE_REVIEWS' => $resource_language->get('resources', 'can_delete_reviews')
            ]);

            $template_file = 'resources/categories_edit.tpl';

            break;

        case 'delete':
            if (!isset($_GET['cid']) || !is_numeric($_GET['cid'])) {
                Redirect::to(URL::build('/panel/resources/categories'));
            }

            $category = DB::getInstance()->get('resources_categories', ['id', '=', $_GET['cid']]);
            if (!$category->count()) {
                Redirect::to(URL::build('/panel/resources/categories'));
            }
            $category = $category->first();

            if (Input::exists()) {
                if (Token::check(Input::get('token'))) {
                    $errors = [];

                    if(Input::get('confirm') === 'true'){
                        if (Input::get('move_resources') === 'none') {
                            $resources = DB::getInstance()->get('resources', ['category_id', '=', $_GET['cid']])->results();
                            $releases = DB::getInstance()->get('resources_releases', ['category_id', '=', $_GET['cid']])->results();
                            try {
                                foreach ($resources as $resource) {
                                    DB::getInstance()->delete('resources', ['id', '=', $resource->id]);
                                    DB::getInstance()->delete('resources_comments', ['resource_id', '=', $resource->id]);
                                }
                                foreach($releases as $release){
                                    DB::getInstance()->delete('resources_releases', ['id', '=', $release->id]);
                                }
                                DB::getInstance()->delete('resources_categories', ['id', '=', $_GET['cid']]);

                                // Category perm deletion
                                DB::getInstance()->delete('resources_categories_permissions', ['category_id', '=', $_GET['cid']]);

                                Session::flash('resources_categories_success', $resource_language->get('resources', 'category_deleted_successfully'));
                                Redirect::to(URL::build('/panel/resources/categories'));

                            } catch(Exception $e){
                                $errors[] = $e->getMessage();
                            }

                        } else {
                            $new_category = Input::get('move_resources');
                            $resources = DB::getInstance()->getWhere('resources', ['category_id', '=', $_GET['cid']])->results();
                            $releases = DB::getInstance()->get('resources_releases', ['category_id', '=', $_GET['cid']])->results();
                            try {
                                foreach ($resources as $resource) {
                                    DB::getInstance()->update('resources', $resource->id, [
                                        'category_id' => $new_category
                                    ]);
                                }
                                foreach ($releases as $release) {
                                    DB::getInstance()->update('resources_releases', $release->id, [
                                        'category_id' => $new_category
                                    ]);
                                }

                                DB::getInstance()->delete('resources_categories', ['id', '=', $_GET['cid']]);

                                // Category perm deletion
                                DB::getInstance()->delete('resources_categories_permissions', ['category_id', '=', $_GET['cid']]);

                                Session::flash('resources_categories_success', $resource_language->get('resources', 'category_deleted_successfully'));
                                Redirect::to(URL::build('/panel/resources/categories'));

                            } catch(Exception $e){
                                $errors[] = $e->getMessage();
                            }
                        }
                    }
                } else
                    $errors[] = $language->get('general', 'invalid_token');

            }

            $categories = DB::getInstance()->orderWhere('resources_categories', 'id <> '. $category->id, 'display_order', 'ASC');

            $smarty->assign([
                'DELETE_CATEGORY' => $resource_language->get('resources', 'delete_category'),
                'BACK_LINK' => URL::build('/panel/resources/categories'),
                'BACK' => $language->get('general', 'back'),
                'MOVE_RESOURCES_TO' => $resource_language->get('resources', 'move_resources_to'),
                'CATEGORIES_LIST' => $categories->results(),
                'DELETE_RESOURCES' => $resource_language->get('resources', 'delete_resources')
            ]);

            $template_file = 'resources/categories_delete.tpl';

            break;

        case 'order':
            if (!isset($_GET['dir']) || !isset($_GET['cid']) || !is_numeric($_GET['cid'])) {
                Redirect::to(URL::build('/panel/resources/categories'));
            }
            if ($_GET['dir'] == 'up' || $_GET['dir'] == 'down') {
                $dir = $_GET['dir'];
            } else {
                Redirect::to(URL::build('/panel/resources/categories'));
            }

            $cat_id = DB::getInstance()->get('resources_categories', ['id', '=', $_GET['cid']]);
            if (!$cat_id->count()) {
                Redirect::to(URL::build('/panel/resources/categories'));
            }
            $cat_order = $cat_id->first()->display_order;
            $cat_id = $cat_id->first()->id;

            $previous_cats = DB::getInstance()->orderAll('resources_categories', 'display_order', 'ASC')->results();

            if ($dir == 'up') {
                $n = 0;
                foreach ($previous_cats as $previous_cat) {
                    if($previous_cat->id == $_GET['cid']){
                        $previous_cid = $previous_cats[$n - 1]->id;
                        $previous_c_order = $previous_cats[$n - 1]->display_order;
                        break;
                    }
                    $n++;
                }

                try {
                    DB::getInstance()->update('resources_categories', $cat_id, [
                        'display_order' => $previous_c_order
                    ]);
                    DB::getInstance()->update('resources_categories', $previous_cid, [
                        'display_order' => $previous_c_order + 1
                    ]);
                } catch(Exception $e){
                    die($e->getMessage());
                }

                Redirect::to(URL::build('/panel/resources/categories'));

            } else if ($dir == 'down') {
                $n = 0;
                foreach($previous_cats as $previous_cat){
                    if($previous_cat->id == $_GET['cid']){
                        $previous_cid = $previous_cats[$n + 1]->id;
                        $previous_c_order = $previous_cats[$n + 1]->display_order;
                        break;
                    }
                    $n++;
                }
                try {
                    DB::getInstance()->update('resources_categories', $cat_id, [
                        'display_order' => $previous_c_order
                    ]);
                    DB::getInstance()->update('resources_categories', $previous_cid, [
                        'display_order' => $previous_c_order - 1
                    ]);
                } catch(Exception $e){
                    die($e->getMessage());
                }

                Redirect::to(URL::build('/panel/resources/categories'));

            }

            break;

        default:
            Redirect::to(URL::build('/panel/resources/categories'));

    }
}

if(Session::exists('resources_categories_success'))
    $success = Session::flash('resources_categories_success');

if(isset($success))
    $smarty->assign([
        'SUCCESS' => $success,
        'SUCCESS_TITLE' => $language->get('general', 'success')
    ]);

if(isset($errors) && count($errors))
    $smarty->assign([
        'ERRORS' => $errors,
        'ERRORS_TITLE' => $language->get('general', 'error')
    ]);

$smarty->assign([
    'PARENT_PAGE' => PARENT_PAGE,
    'DASHBOARD' => $language->get('admin', 'dashboard'),
    'RESOURCES' => $resource_language->get('resources', 'resources'),
    'CATEGORIES' => $resource_language->get('resources', 'categories'),
    'PAGE' => PANEL_PAGE,
    'TOKEN' => Token::get(),
    'SUBMIT' => $language->get('general', 'submit')
]);

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate($template_file, $smarty);
