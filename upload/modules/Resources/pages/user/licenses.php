<?php
/*
 *  Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr10
 *
 *  License: MIT
 *
 *  UserCP resource licenses page
 */

// Must be logged in
if (!$user->isLoggedIn()) {
    Redirect::to(URL::build('/login'));
    die();
}

// Always define page name
define('PAGE', 'resources_licenses');
$page_title = $language->get('user', 'user_cp');
require_once(ROOT_PATH . '/core/templates/frontend_init.php');

require(ROOT_PATH . '/modules/Resources/classes/Resources.php');
$resources = new Resources();

// Get resource
$rid = explode('/', $route);
$rid = $rid[count($rid) - 1];

if (!strlen($rid)) {
    Redirect::to(URL::build('/user/resources'));
}

$rid = explode('-', $rid);
if (!is_numeric($rid[0])) {
    Redirect::to(URL::build('/user/resources'));
}
$rid = $rid[0];

$resource = DB::getInstance()->get('resources', ['id', '=', $rid]);

if (!$resource->count()) {
    // Doesn't exist
    Redirect::to(URL::build('/user/resources'));
} else $resource = $resource->first();

if (!Resources::canManageLicenses($resource->id, $user)) {
    Redirect::to(URL::build('/user/resources'));
}

if (Input::exists()) {
    if (Token::check(Input::get('token'))) {
        if (isset($_POST['action']) && $_POST['action'] == 'add') {
            if ($_POST['user']) {
                // Check user is not adding themselves, or someone that's already added
                if ($user->data()->id != $_POST['user']) {
                    $existing_license = DB::getInstance()->query('SELECT id FROM nl2_resources_payments WHERE resource_id = ? AND user_id = ?', [$resource->id, $_POST['user']]);

                    if (!$existing_license->count()) {
                        $user_exists = DB::getInstance()->query('SELECT id FROM nl2_users WHERE id = ?', [$_POST['user']]);

                        if ($user_exists->count()) {
                            // Add license
                            DB::getInstance()->insert('resources_payments', [
                                'user_id' => $_POST['user'],
                                'resource_id' => $resource->id,
                                'transaction_id' => 'manual',
                                'created' => date('U'),
                                'status' => 1
                            ]);
                            $success = $resource_language->get('resources', 'license_added_successfully');

                            // Alert
                            Alert::create($user_exists->first()->id, 'resource_purchased', ['path' => ROOT_PATH . '/modules/Resources/language', 'file' => 'resources', 'term' => 'resource_purchased'], ['path' => ROOT_PATH . '/modules/Resources/language', 'file' => 'resources', 'term' => 'resource_purchased_full', 'replace' => '{{resource}}', 'replace_with' => $resource->name], Resources::buildURL($resource->id, $resource->name));

                        } else
                            $error = $language->get('api', 'unable_to_find_user');

                    } else
                        $error = $resource_language->get('resources', 'user_already_has_license');

                } else
                    $error = $resource_language->get('resources', 'unable_to_add_license_for_yourself');

            } else
                $error = $language->get('api', 'unable_to_find_user');

        } else if (isset($_POST['license'])) {
            // Ensure license ID is for current resource
            $license = DB::getInstance()->get('resources_payments', ['id', '=', $_POST['license']]);

            if ($license->count() && $license->first()->resource_id == $resource->id) {
                switch (Input::get('action')) {
                    case 'reinstate':
                        DB::getInstance()->update('resources_payments', $license->first()->id, [
                            'status' => 1
                        ]);

                        // Alert
                        Alert::create($license->first()->user_id, 'resource_purchased', ['path' => ROOT_PATH . '/modules/Resources/language', 'file' => 'resources', 'term' => 'resource_purchased'], ['path' => ROOT_PATH . '/modules/Resources/language', 'file' => 'resources', 'term' => 'resource_purchased_full', 'replace' => '{{resource}}', 'replace_with' => $resource->name], Resources::buildURL($resource->id, $resource->name));
                        break;

                    case 'revoke':
                        DB::getInstance()->update('resources_payments', $license->first()->id, [
                            'status' => 3
                        ]);

                        // Alert
                        Alert::create($license->first()->user_id, 'resource_license_cancelled', ['path' => ROOT_PATH . '/modules/Resources/language', 'file' => 'resources', 'term' => 'resource_license_cancelled'], ['path' => ROOT_PATH . '/modules/Resources/language', 'file' => 'resources', 'term' => 'resource_license_cancelled_full', 'replace' => '{{resource}}', 'replace_with' => $resource->name], Resources::buildURL($resource->id, $resource->name));
                        break;
                }
            } else
                $error = $language->get('resources', 'unable_to_update_license');
        }

    } else
        $error = $language->get('general', 'invalid_token');
}

if (isset($success))
    $smarty->assign('SUCCESS', $success);

if (isset($error))
    $smarty->assign('ERROR', $error);

$licenses = DB::getInstance()->query('SELECT `id`, `user_id`, `status`, `transaction_id`, `created` FROM nl2_resources_payments WHERE resource_id = ?', [$resource->id])->results();

$licenses_array = [];
if (count($licenses)) {
    foreach ($licenses as $license) {
        $customer = new User($license->user_id);

        $licenses_array[] = [
            'id' => Output::getClean($license->id),
            'username' => $customer->getDisplayName(),
            'profile' => $customer->getProfileURL(),
            'avatar' => $customer->getAvatar(),
            'style' => $customer->getGroupStyle(),
            'payment_id' => Output::getClean($license->payment_id),
            'status' => $license->status,
            'status_text' => $resource_language->get('resources', 'status_' . Resources::mapPaymentStatus($license->status)),
            'can_revoke' => $license->status == '1',
            'date' => date('d M Y, H:i', $license->created),
            'transaction_id' => Output::getClean($license->transaction_id),
            'can_reinstate' => $license->status != '1'
        ];
    }
}

// Language values
$smarty->assign([
    'USER_CP' => $language->get('user', 'user_cp'),
    'MANAGING_LICENSES' => $resource_language->get('resources', 'managing_licenses_for', ['resource' => Output::getClean($resource->name)]),
    'NO_LICENSES' => $resource_language->get('resources', 'no_licenses'),
    'LICENSES' => $licenses_array,
    'ADD_LICENSE' => $resource_language->get('resources', 'add_license'),
    'ADD_LICENSE_USERS_ENDPOINT' => URL::build('/queries/users/', 'search='),
    'REINSTATE' => $resource_language->get('resources', 'reinstate'),
    'REVOKE' => $resource_language->get('resources', 'revoke'),
    'INFO' => $language->get('general', 'info'),
    'TOKEN' => Token::get(),
    'SUBMIT' => $language->get('general', 'submit'),
    'CANCEL' => $language->get('general', 'cancel'),
    'FIND_USER' => $resource_language->get('resources', 'find_user'),
    'USER' => $resource_language->get('resources', 'user'),
    'PURCHASED' => $resource_language->get('resources', 'purchased'),
    'STATUS' => $resource_language->get('resources', 'status'),
    'ACTIONS' => $resource_language->get('resources', 'actions'),
]);

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

require(ROOT_PATH . '/core/templates/cc_navbar.php');

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

// Display template
$template->displayTemplate('resources/user/licenses.tpl', $smarty);