<?php
/*
 *  Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr13
 *
 *  License: MIT
 *
 *  Resources creation page
 */
// Always define page name
define('PAGE', 'resources');

require(ROOT_PATH . '/modules/Resources/classes/Resources.php');
$resources = new Resources();

// Ensure user is logged in
if (!$user->isLoggedIn()) {
    Redirect::to(URL::build('/resources'));
}

$groups = [];
foreach ($user->getGroups() as $group) {
    $groups[] = $group->id;
}

// Handle input
if (Input::exists()) {
    if (Token::check(Input::get('token'))) {
        if (!isset($_GET['step'])) {
            // Initial step
            $validation = Validate::check($_POST, [
                'name' => [
                    'required' => true,
                    'min' => 2,
                    'max' => 64
                ],
                'short_description' => [
                    'required' => true,
                    'min' => 2,
                    'max' => 64
                ],
                'category' => [
                    'required' => true
                ],
                'content' => [
                    'required' => true,
                    'min' => 2,
                    'max' => 20000
                ],
                'contributors' => [
                    'max' => 255
                ]
            ])->messages([
                'name' => [
                    'required' => $resource_language->get('resources', 'name_required'),
                    'min' => $resource_language->get('resources', 'name_min_2'),
                    'max' => $resource_language->get('resources', 'name_max_64')
                ],
                'short_description' => [
                    'required' => $resource_language->get('resources', 'short_description_required'),
                    'min' => $resource_language->get('resources', 'short_description_min_2'),
                    'max' => $resource_language->get('resources', 'short_description_max_64')
                ],
                'category' => [
                    'required' => $resource_language->get('resources', 'category_required')
                ],
                'content' => [
                    'required' => $resource_language->get('resources', 'content_required'),
                    'min' => $resource_language->get('resources', 'content_min_2'),
                    'max' => $resource_language->get('resources', 'content_max_20000')
                ],
                'contributors' => [
                    'max' => $resource_language->get('resources', 'contributors_max_255')
                ]
            ]);

            if ($validation->passed()) {
                // Check permissions
                if (!$resources->canPostResourceInCategory($groups, $_POST['category'])) {
                    Redirect::to(URL::build('/resources'));
                }

                $_SESSION['new_resource'] = $_POST;

                if(isset($_POST['type']) && $_POST['type'] == 'github'){
                    Redirect::to(URL::build('/resources/new/', 'step=github'));
                } else {
                    Redirect::to(URL::build('/resources/new/', 'step=type'));
                }

            } else {
                $error = implode('<br />', $validation->errors());
            }

        } else {
            if($_GET['step'] == 'github'){
                // GitHub repository
                if(!isset($_SESSION['new_resource']) || !isset($_SESSION['new_resource']['type']) || (isset($_SESSION['new_resource']['type']) && $_SESSION['new_resource']['type'] != 'github')){
                    Redirect::to(URL::build('/resources/new'));
                }

                $validation = Validate::check($_POST, [
                    'github_username' => [
                        'required' => true,
                        'min' => 2,
                        'max' => 32
                    ],
                    'github_repo' => [
                        'required' => true,
                        'min' => 2,
                        'max' => 64
                    ],
                ])->messages([
                    'github_username' => [
                        'required' => $resource_language->get('resources', 'github_username_required'),
                        'min' => $resource_language->get('resources', 'github_username_min_2'),
                        'max' => $resource_language->get('resources', 'github_username_max_32')
                    ],
                    'github_repo' => [
                        'required' => $resource_language->get('resources', 'github_repo_required'),
                        'min' => $resource_language->get('resources', 'github_repo_min_2'),
                        'max' => $resource_language->get('resources', 'github_repo_max_64')
                    ],
                ]);

                if ($validation->passed()) {
                    // Check GitHub API
                    try {
                        // Use cURL
                        $ch = curl_init();

                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Accept: application/vnd.github.v3+json',
                            'User-Agent: NamelessMC-App'
                        ]);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/' . urlencode($_POST['github_username']) . '/' . urlencode($_POST['github_repo']) . '/releases');

                        if(!$github_query = curl_exec($ch)){
                            $error = curl_error($ch);
                        }

                        curl_close($ch);

                        $github_query = json_decode($github_query, true);

                        if (!isset($github_query[0])) {
                            $error = $resource_language->get('resources', 'unable_to_get_repo', ['repo' => Output::getClean($_POST['github_username']) . '/' . Output::getClean($_POST['github_repo'])]);
                        } else {
                            // Valid response
                            $releases_array = [];
                            foreach($github_query as $release){
                                // Select release
                                $releases_array[] = [
                                    'id' => $release['id'],
                                    'tag' => $release['tag_name'],
                                    'name' => $release['name'],
                                    'short_description' => $release['short_description'],
                                ];
                            }

                            $_SESSION['new_resource']['github'] = $_POST;
                            $_SESSION['github_releases'] = $releases_array;

                            Redirect::to(URL::build('/resources/new/', 'step=release'));

                        }

                    } catch(Exception $e){
                        $error = $e->getMessage();
                    }

                } else {
                    $error = implode('<br />', $validation->errors());
                }

            } else if($_GET['step'] == 'release'){
                // Validate release
                if(!isset($_SESSION['new_resource']) || !isset($_SESSION['new_resource']['type']) || (isset($_SESSION['new_resource']['type']) && $_SESSION['new_resource']['type'] != 'github')){
                    Redirect::to(URL::build('/resources/new'));
                }

                if(!isset($_SESSION['github_releases']) || (isset($_SESSION['github_releases']) && !count($_SESSION['github_releases']))){
                    Redirect::to(URL::build('/resources/new/', 'step=github'));
                }

                // Check permissions
                if (!$resources->canPostResourceInCategory($groups, $_SESSION['new_resource']['category'])) {
                    Redirect::to(URL::build('/resources'));
                }

                try {
                    // Use cURL
                    $ch = curl_init();

                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Accept: application/vnd.github.v3+json',
                        'User-Agent: NamelessMC-App'
                    ]);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/' . Output::getClean($_SESSION['new_resource']['github']['github_username']) . '/' . Output::getClean($_SESSION['new_resource']['github']['github_repo']) . '/releases/' . Output::getClean($_POST['release']));

                    if(!$github_query = curl_exec($ch)){
                        $error = curl_error($ch);
                    }

                    curl_close($ch);

                    $github_query = json_decode($github_query);

                    if(!isset($github_query->id)) $error = $resource_language->get('resources', 'unable_to_get_repo', ['repo' => Output::getClean($_SESSION['new_resource']['github']['github_username']) . '/' . Output::getClean($_SESSION['new_resource']['github']['github_repo'])]);
                    else {
                        // Valid response
                        // Create resource
                        // Format description
                        // TODO: hooks!
                        $content = $_SESSION['new_resource']['content'];

                        DB::getInstance()->insert('resources', [
                            'category_id' => $_SESSION['new_resource']['category'],
                            'creator_id' => $user->data()->id,
                            'name' => $_SESSION['new_resource']['name'],
                            'short_description' => $_SESSION['new_resource']['short_description'],
                            'description' => $content,
                            'contributors' => ((isset($_SESSION['new_resource']['contributors']) && !is_null($_SESSION['new_resource']['contributors'])) ? $_SESSION['new_resource']['contributors'] : null),
                            'created' => date('U'),
                            'updated' => date('U'),
                            'github_url' => 'https://github.com/' . urlencode($_SESSION['new_resource']['github']['github_username']) . '/' . urlencode($_SESSION['new_resource']['github']['github_repo']),
                            'github_username' => $_SESSION['new_resource']['github']['github_username'],
                            'github_repo_name' => $_SESSION['new_resource']['github']['github_repo'],
                            'latest_version' => $github_query->tag_name
                        ]);

                        $resource_id = DB::getInstance()->lastId();

                        DB::getInstance()->insert('resources_releases', [
                            'resource_id' => $resource_id,
                            'category_id' => $_SESSION['new_resource']['category'],
                            'release_title' => $github_query->name,
                            'release_description' => $github_query->body,
                            'release_tag' => $github_query->tag_name,
                            'created' => date('U'),
                            'download_link' => $github_query->html_url
                        ]);

                        // Hook
                        $new_resource_category = DB::getInstance()->get('resources_categories', ['id', '=', $_SESSION['new_resource']['category']]);

                        if ($new_resource_category->count())
                            $new_resource_category = $new_resource_category->first()->name;

                        else
                            $new_resource_category = 'Unknown';

                        EventHandler::executeEvent('newResource', [
                            'event' => 'newResource',
                            'username' => $user->data()->nickname,
                            'content' => $resource_language->get('resources', 'new_resource_text', ['category' => $new_resource_category, 'user' => Output::getClean($user->data()->nickname)]),
                            'content_full' => str_replace('&nbsp;', '', strip_tags(Output::getDecoded($content))),
                            'avatar_url' => $user->getAvatar(128, true),
                            'title' => $_SESSION['new_resource']['name'],
                            'url' => rtrim(Util::getSelfURL(), '/') . URL::build('/resources/resource/' . $resource_id . '-' . urlencode($_SESSION['new_resource']['name']))
                        ]);

                        unset($_SESSION['new_resource']);
                        unset($_SESSION['github_releases']);

                        Redirect::to(URL::build('/resources/resource/' . $resource_id));
                    }

                } catch(Exception $e){
                    $error = $e->getMessage();
                }

            } else if($_GET['step'] == 'type') {
                // Free or premium
                if(!isset($_SESSION['new_resource'])){
                    Redirect::to(URL::build('/resources/new'));
                }

                $category = DB::getInstance()->get('resources_categories', ['id', '=', $_SESSION['new_resource']['category']]);
                if (!$category->count()) {
                    Redirect::to(URL::build('/resources/new'));
                }
                $category = $category->first();

                $permission = $resources->getAvailableResourceTypes($groups, $category->id);

                if(!$permission->post){
                    Redirect::to(URL::build('/resources/new'));
                }

                if(!$permission->premium){
                    $type = $_SESSION['new_resource']['type'];

                    if($type == 'external'){
                        Redirect::to(URL::build('/resources/new/', 'step=link'));
                    } else {
                        Redirect::to(URL::build('/resources/new/', 'step=upload'));
                    }
                }

                if(Input::exists()){
                    if(Token::check(Input::get('token'))){
                        if(isset($_POST['type']) && $_POST['type'] == 'premium'){
                            $type = 'premium';

                            if(!isset($_POST['price']) || !is_numeric($_POST['price']) || $_POST['price'] < 0.01 || $_POST['price'] > 100 || !preg_match('/^\d+(?:\.\d{2})?$/', $_POST['price'])){
                                $error = $resource_language->get('resources', 'invalid_price');
                            } else {
                                $price = number_format($_POST['price'], 2, '.', '');
                            }

                        } else
                            $type = 'free';

                        if(!isset($error)){
                            // OK to continue
                            $to_continue = ['type' => $type];
                            if(isset($price)) $to_continue['price'] = $price;

                            $type = $_SESSION['new_resource']['type'];
                            $_SESSION['new_resource']['type'] = $to_continue;

                            if($type == 'external'){
                                Redirect::to(URL::build('/resources/new/', 'step=link'));
                            } else {
                                Redirect::to(URL::build('/resources/new/', 'step=upload'));
                            }
                        }

                    } else
                        $error = $language->get('general', 'invalid_token');
                }

            } else if($_GET['step'] == 'upload'){
                // Upload zip
                if(!isset($_SESSION['new_resource'])){
                    Redirect::to(URL::build('/resources/new'));
                }

                $category = DB::getInstance()->get('resources_categories', ['id', '=', $_SESSION['new_resource']['category']]);
                if(!$category->count()){
                    Redirect::to(URL::build('/resources/new'));
                }
                $category = $category->first();

                $permission = $resources->getAvailableResourceTypes($groups, $category->id);

                if(!$permission->post){
                    Redirect::to(URL::build('/resources/new'));
                }

                if(!isset($_SESSION['new_resource']['type'])){
                    Redirect::to(URL::build('/resources/new/', 'step=type'));
                }

                if(!isset($_POST['version']))
                    $version = '1.0.0';
                else
                    $version = $_POST['version'];

                if(!is_dir(ROOT_PATH . '/uploads/resources'))
                    mkdir(ROOT_PATH . '/uploads/resources');

                $user_dir = ROOT_PATH . '/uploads/resources/' . $user->data()->id;

                if(!is_dir($user_dir)){
                    if(!mkdir($user_dir)){
                        $error = $resource_language->get('resources', 'upload_directory_not_writable');
                    }
                }

                if(isset($_FILES['resourceFile']) && !isset($error)){
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

                        if($_FILES['resourceFile']['size'] > ($filesize * 1000) || $_FILES['resourceFile']['size'] == 0){
                            $error = $resource_language->get('resources', 'filesize_max_x', ['filesize' => Output::getClean($filesize)]);

                        } else {
                            // Create resource
                            // Format description
                            // TODO: hooks
                            $content = Output::getClean($_SESSION['new_resource']['content']);

                            $type = 0;
                            $price = null;

                            if(isset($_SESSION['new_resource']['type']['type'])){
                                if($_SESSION['new_resource']['type']['type'] == 'premium'){
                                    $type = 1;

                                    if(isset($_SESSION['new_resource']['type']['price']))
                                        $price = $_SESSION['new_resource']['type']['price'];
                                }
                            }

                            DB::getInstance()->insert('resources', [
                                'category_id' => $_SESSION['new_resource']['category'],
                                'creator_id' => $user->data()->id,
                                'name' => Output::getClean($_SESSION['new_resource']['name']),
                                'short_description' => Output::getClean($_SESSION['new_resource']['short_description']),
                                'description' => $content,
                                'contributors' => ((isset($_SESSION['new_resource']['contributors']) && !is_null($_SESSION['new_resource']['contributors'])) ? Output::getClean($_SESSION['new_resource']['contributors']) : null),
                                'created' => date('U'),
                                'updated' => date('U'),
                                'github_url' => 'none',
                                'github_username' => 'none',
                                'github_repo_name' => 'none',
                                'latest_version' => Output::getClean($version),
                                'type' => $type,
                                'price' => $price
                            ]);

                            $resource_id = DB::getInstance()->lastId();

                            // Create release
                            DB::getInstance()->insert('resources_releases', [
                                'resource_id' => $resource_id,
                                'category_id' => $_SESSION['new_resource']['category'],
                                'release_title' => Output::getClean($version),
                                'release_description' => $content,
                                'release_tag' => Output::getClean($version),
                                'created' => date('U'),
                                'download_link' => 'local'
                            ]);

                            $release_id = DB::getInstance()->lastId();

                            $uploadPath = $user_dir . DIRECTORY_SEPARATOR . $resource_id;

                            if(!is_dir($uploadPath))
                                mkdir($uploadPath);

                            $uploadPath .= DIRECTORY_SEPARATOR . $release_id;

                            if(!is_dir($uploadPath))
                                mkdir($uploadPath);

                            $uploadPath .= DIRECTORY_SEPARATOR . basename($_FILES['resourceFile']['name']);

                            if(move_uploaded_file($_FILES['resourceFile']['tmp_name'], $uploadPath)){
                                // File uploaded
                                // Hook
                                $new_resource_category = DB::getInstance()->get('resources_categories', ['id', '=', $_SESSION['new_resource']['category']]);

                                if ($new_resource_category->count())
                                    $new_resource_category = Output::getClean($new_resource_category->first()->name);
                                else
                                    $new_resource_category = 'Unknown';

                                EventHandler::executeEvent('newResource', [
                                    'event' => 'newResource',
                                    'username' => $user->getDisplayname(),
                                    'content' => $resource_language->get('resources', 'new_resource_text', ['category' => $new_resource_category, 'user' => Output::getClean($user->data()->nickname)]),
                                    'content_full' => str_replace('&nbsp;', '', strip_tags(Output::getDecoded($content))),
                                    'avatar_url' => $user->getAvatar(128, true),
                                    'title' => Output::getClean($_SESSION['new_resource']['name']),
                                    'url' => rtrim(Util::getSelfURL(), '/') . URL::build('/resources/resource/' . $resource_id . '-' . urlencode($_SESSION['new_resource']['name']))
                                ]);

                                unset($_SESSION['new_resource']);

                                Redirect::to(URL::build('/resources/resource/' . $resource_id));

                            } else {
                                // Unable to upload file
                                $error = $resource_language->get('resources', 'file_upload_failed', ['error' => Output::getClean($_FILES['resourceFile']['error'])]);

                                DB::getInstance()->delete('resources', ['id', '=', $resource_id]);
                                DB::getInstance()->delete('resources_releases', ['id', '=', $release_id]);
                            }
                        }
                    }
                }
            } else if($_GET['step'] == 'link'){
                if(Token::check(Input::get('token'))){
                    // TODO: better error messages
                    $errorMessage = $resource_language->get('resources', 'external_link_error', ['min' => 4, 'max' => 256]);

                    // Validate link
                    $validation = Validate::check($_POST, [
                        'link' => [
                            'required' => true,
                            'min' => 4,
                            'max' => 256
                        ]
                    ])->messages([
                        'link' => [
                            'required' => $errorMessage,
                            'min' => $errorMessage,
                            'max' => $errorMessage
                        ]
                    ]);

                    if ($validation->passed()) {
                        // TODO: hooks
                        $content = Output::getClean($_SESSION['new_resource']['content']);

                        if(!isset($_POST['version']))
                            $version = '1.0.0';
                        else
                            $version = $_POST['version'];

                        $type = 0;
                        $price = null;

                        if(isset($_SESSION['new_resource']['type']['type'])){
                            if($_SESSION['new_resource']['type']['type'] == 'premium'){
                                $type = 1;

                                if(isset($_SESSION['new_resource']['type']['price']))
                                    $price = $_SESSION['new_resource']['type']['price'];
                            }
                        }

                        DB::getInstance()->insert('resources', [
                            'category_id' => $_SESSION['new_resource']['category'],
                            'creator_id' => $user->data()->id,
                            'name' => Output::getClean($_SESSION['new_resource']['name']),
                            'short_description' => Output::getClean($_SESSION['new_resource']['short_description']),
                            'description' => $content,
                            'contributors' => ((isset($_SESSION['new_resource']['contributors']) && !is_null($_SESSION['new_resource']['contributors'])) ? Output::getClean($_SESSION['new_resource']['contributors']) : null),
                            'created' => date('U'),
                            'updated' => date('U'),
                            'github_url' => 'none',
                            'github_username' => 'none',
                            'github_repo_name' => 'none',
                            'latest_version' => Output::getClean($version),
                            'type' => $type,
                            'price' => $price
                        ]);

                        $resource_id = DB::getInstance()->lastId();

                        DB::getInstance()->insert('resources_releases', [
                            'resource_id' => $resource_id,
                            'category_id' => $_SESSION['new_resource']['category'],
                            'release_title' => Output::getClean($version),
                            'release_description' => $content,
                            'release_tag' => Output::getClean($version),
                            'created' => date('U'),
                            'download_link' => Output::getClean($_POST['link'])
                        ]);

                        // Hook
                        $new_resource_category = DB::getInstance()->get('resources_categories', ['id', '=', $_SESSION['new_resource']['category']]);

                        if ($new_resource_category->count())
                            $new_resource_category = Output::getClean($new_resource_category->first()->name);

                        else
                            $new_resource_category = 'Unknown';

                        EventHandler::executeEvent('newResource', [
                            'event' => 'newResource',
                            'username' => $user->getDisplayname(),
                            'content' => $resource_language->get('resources', 'new_resource_text', ['category' => $new_resource_category, 'user' => Output::getClean($user->data()->nickname)]),
                            'content_full' => str_replace('&nbsp;', '', strip_tags(Output::getDecoded($content))),
                            'avatar_url' => $user->getAvatar(128, true),
                            'title' => Output::getClean($_SESSION['new_resource']['name']),
                            'url' => rtrim(Util::getSelfURL(), '/') . URL::build('/resources/resource/' . $resource_id . '-' . urlencode($_SESSION['new_resource']['name']))
                        ]);

                        unset($_SESSION['new_resource']);

                        Redirect::to(URL::build('/resources/resource/' . $resource_id));

                    } else {
                        $error = $errorMessage;
                    }
                } else {
                    $error = $language->get('general', 'invalid_token');
                }
            }
        }
    } else
        $error = $language->get('general', 'invalid_token');
}

$page_title = $resource_language->get('resources', 'new_resource');
require_once(ROOT_PATH . '/core/templates/frontend_init.php');

if (!isset($_GET['step'])){
    $categories = $resources->getCategories($groups);

    // Assign to Smarty array
    $categories_array = [];
    foreach($categories as $category){
        $categories_array[] = [
            'name' => Output::getClean($category->name),
            'id' => $category->id
        ];
    }
    $categories = null;

    // Assign post content if it already exists
    $smarty->assign('CONTENT', isset($_POST['description']) ? Output::getClean($_POST['description']) : '');

    // Errors?
    if (isset($error)) {
        $smarty->assign('ERROR', $error);
    }

    // Assign Smarty variables
    $smarty->assign([
        'IN_CATEGORY' => $resource_language->get('resources', 'in_category'),
        'CATEGORIES' => $categories_array,
        'SELECT_CATEGORY' => $resource_language->get('resources', 'select_category'),
        'REQUIRED' => $resource_language->get('resources', 'required'),
        'RESOURCE_NAME' => $resource_language->get('resources', 'resource_name'),
        'RESOURCE_SHORT_DESCRIPTION' => $resource_language->get('resources', 'resource_short_description'),
        'RESOURCE_DESCRIPTION' => $resource_language->get('resources', 'resource_description'),
        'CONTRIBUTORS' => $resource_language->get('resources', 'contributors'),
        'RELEASE_TYPE' => $resource_language->get('resources', 'release_type'),
        'ZIP_FILE' => $resource_language->get('resources', 'zip_file'),
        'GITHUB_RELEASE' => $resource_language->get('resources', 'github_release'),
        'EXTERNAL_DOWNLOAD' => $resource_language->get('resources', 'external_download')
    ]);

    $template_file = 'resources/new_resource.tpl';

} else {
    switch($_GET['step']){
        case 'github':
            // Errors?
            if(isset($error)) $smarty->assign('ERROR', $error);

            $smarty->assign([
                'GITHUB_USERNAME' => $resource_language->get('resources', 'github_username'),
                'GITHUB_REPO_NAME' => $resource_language->get('resources', 'github_repo_name'),
                'REQUIRED' => $resource_language->get('resources', 'required')
            ]);

            $template_file = 'resources/new_resource_github.tpl';

            break;

        case 'release':
            // Select release
            if(isset($error)) $smarty->assign('ERROR', $error);

            // Assign Smarty variables
            $smarty->assign([
                'NEW_RESOURCE' => $resource_language->get('resources', 'new_resource'),
                'CANCEL' => $language->get('general', 'cancel'),
                'CANCEL_LINK' => URL::build('/resources'),
                'CONFIRM_CANCEL' => $language->get('general', 'confirm_cancel'),
                'SELECT_RELEASE' => $resource_language->get('resources', 'select_release'),
                'RELEASES' => $_SESSION['github_releases']
            ]);

            $template_file = 'resources/new_resource_select_release.tpl';

            break;

        case 'type':
            if(!isset($_SESSION['new_resource'])){
                Redirect::to(URL::build('/resources/new'));
            }

            $category = DB::getInstance()->get('resources_categories', ['id', '=', $_SESSION['new_resource']['category']]);
            if (!$category->count()) {
                Redirect::to(URL::build('/resources/new'));
            }
            $category = $category->first();

            $permission = $resources->getAvailableResourceTypes($groups, $category->id);

            if (!$permission->post) {
                Redirect::to(URL::build('/resources/new'));
            }

            if(!$permission->premium){
                $type = $_SESSION['new_resource']['type'];

                if($type == 'external'){
                    Redirect::to(URL::build('/resources/new/', 'step=link'));
                } else {
                    Redirect::to(URL::build('/resources/new/', 'step=upload'));
                }
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

            $smarty->assign([
                'TYPE' => $resource_language->get('resources', 'type'),
                'FREE_RESOURCE' => $resource_language->get('resources', 'free_resource'),
                'PREMIUM_RESOURCE' => $resource_language->get('resources', 'premium_resource'),
                'PREMIUM_RESOURCE_PRICE' => $resource_language->get('resources', 'price'),
                'CURRENCY' => Output::getClean($currency)
            ]);

            if(isset($error)) $smarty->assign('ERROR', $error);

            $user_premium_details = DB::getInstance()->get('resources_users_premium_details', ['user_id', '=', $user->data()->id]);
            if(!$user_premium_details->count() || !$user_premium_details->first()->paypal_email){
                $smarty->assign('NO_PAYMENT_EMAIL', $resource_language->get('resources', 'no_payment_email'));
            }

            $template_file = 'resources/new_resource_type.tpl';

            break;

        case 'upload':
            if(isset($error)) $smarty->assign('ERROR', $error);

            $smarty->assign([
                'CHOOSE_FILE' => $resource_language->get('resources', 'choose_file'),
                'ZIP_ONLY' => $resource_language->get('resources', 'zip_only'),
                'VERSION_TAG' => $resource_language->get('resources', 'version_tag')
            ]);

            $template_file = 'resources/new_resource_upload.tpl';

            break;

        case 'link':
            if(isset($error)) $smarty->assign('ERROR', $error);

            $smarty->assign([
                'EXTERNAL_LINK' => $resource_language->get('resources', 'external_link'),
                'VERSION_TAG' => $resource_language->get('resources', 'version_tag')
            ]);

            $template_file = 'resources/new_resource_external_link.tpl';

            break;

        default:
            Redirect::to(URL::build('/resources/new'));

    }

}

$smarty->assign([
    'NEW_RESOURCE' => $resource_language->get('resources', 'new_resource'),
    'CANCEL' => $language->get('general', 'cancel'),
    'CANCEL_LINK' => URL::build('/resources'),
    'CONFIRM_CANCEL' => $language->get('general', 'confirm_cancel'),
    'SUBMIT' => $language->get('general', 'submit'),
    'TOKEN' => Token::get()
]);

$template->assets()->include(AssetTree::TINYMCE);

$template->addJSScript(Input::createTinyEditor($language, 'reply'));

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

$template->displayTemplate($template_file, $smarty);
