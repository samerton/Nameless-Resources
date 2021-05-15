<?php
/*
 *	Made by Samerton
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

require(ROOT_PATH . '/core/includes/emojione/autoload.php'); // Emojione
require(ROOT_PATH . '/core/includes/markdown/tohtml/Markdown.inc.php'); // Markdown to HTML
$emojione = new Emojione\Client(new Emojione\Ruleset());

require(ROOT_PATH . '/modules/Resources/classes/Resources.php');
$resources = new Resources();

// Get resource
$rid = explode('/', $route);
$rid = $rid[count($rid) - 1];

if (!strlen($rid)) {
    Redirect::to(URL::build('/user/resources'));
    die();
}

$rid = explode('-', $rid);
if (!is_numeric($rid[0])) {
    Redirect::to(URL::build('/user/resources'));
    die();
}
$rid = $rid[0];

$resource = $queries->getWhere('resources', array('id', '=', $rid));

if (!count($resource)) {
    // Doesn't exist
    Redirect::to(URL::build('/user/resources'));
    die();
} else $resource = $resource[0];

if (!Resources::canManageLicenses($resource->id, $user)) {
    Redirect::to(URL::build('/user/resources'));
    die();
}

if (Input::exists()) {
    if (Token::check(Input::get('token'))) {
        if (isset($_POST['action']) && $_POST['action'] == 'add') {
            if ($_POST['user']) {
                // Check user is not adding themselves, or someone that's already added
                if ($user->data()->id != $_POST['user']) {
                    $existing_license = DB::getInstance()->query('SELECT id FROM nl2_resources_payments WHERE resource_id = ? AND user_id = ?', array($resource->id, $_POST['user']));

                    if (!$existing_license->count()) {
                        $user_exists = DB::getInstance()->query('SELECT id FROM nl2_users WHERE id = ?', array($_POST['user']));

                        if ($user_exists->count()) {
                            // Add license
                            $queries->create('resources_payments', array(
                                'user_id' => $_POST['user'],
                                'resource_id' => $resource->id,
                                'transaction_id' => 'manual',
                                'created' => date('U'),
                                'status' => 1
                            ));
                            $success = $resource_language->get('resources', 'license_added_successfully');

                            // Alert
                            Alert::create($user_exists->first()->id, 'resource_purchased', array('path' => ROOT_PATH . '/modules/Resources/language', 'file' => 'resources', 'term' => 'resource_purchased'), array('path' => ROOT_PATH . '/modules/Resources/language', 'file' => 'resources', 'term' => 'resource_purchased_full', 'replace' => '{x}', 'replace_with' => $resource->name), Resources::buildURL($resource->id, $resource->name));

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
            $license = $queries->getWhere('resources_payments', array('id', '=', $_POST['license']));

            if ($license && count($license) && $license[0]->resource_id == $resource->id) {
                switch (Input::get('action')) {
                    case 'reinstate':
                        $queries->update('resources_payments', $resource->id, array(
                            'status' => 1
                        ));

                        // Alert
                        Alert::create($license[0]->user_id, 'resource_purchased', array('path' => ROOT_PATH . '/modules/Resources/language', 'file' => 'resources', 'term' => 'resource_purchased'), array('path' => ROOT_PATH . '/modules/Resources/language', 'file' => 'resources', 'term' => 'resource_purchased_full', 'replace' => '{x}', 'replace_with' => $resource->name), Resources::buildURL($resource->id, $resource->name));
                        break;

                    case 'revoke':
                        $queries->update('resources_payments', $resource->id, array(
                            'status' => 3
                        ));

                        // Alert
                        Alert::create($license[0]->user_id, 'resource_license_cancelled', array('path' => ROOT_PATH . '/modules/Resources/language', 'file' => 'resources', 'term' => 'resource_license_cancelled'), array('path' => ROOT_PATH . '/modules/Resources/language', 'file' => 'resources', 'term' => 'resource_license_cancelled_full', 'replace' => '{x}', 'replace_with' => $resource->name), Resources::buildURL($resource->id, $resource->name));
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

$licenses = DB::getInstance()->query('SELECT `id`, `user_id`, `status`, `transaction_id`, `created` FROM nl2_resources_payments WHERE resource_id = ?', array($resource->id))->results();

$licenses_array = array();
if (count($licenses)) {
    foreach ($licenses as $license) {
        $customer = new User($license->user_id);

        $licenses_array[] = array(
            'id' => Output::getClean($license->id),
            'username' => $customer->getDisplayName(),
            'profile' => $customer->getProfileURL(),
            'avatar' => $customer->getAvatar(),
            'style' => $customer->getGroupClass(),
            'payment_id' => Output::getClean($license->payment_id),
            'status' => $license->status,
            'status_text' => $resource_language->get('resources', 'status_' . Resources::mapPaymentStatus($license->status)),
            'can_revoke' => $license->status == '1',
            'date' => date('d M Y, H:i', $license->created),
            'transaction_id' => Output::getClean($license->transaction_id),
            'can_reinstate' => $license->status != '1'
        );
    }
}

// Language values
$smarty->assign(array(
    'USER_CP' => $language->get('user', 'user_cp'),
    'MANAGING_LICENSES' => str_replace('{x}', Output::getClean($resource->name), $resource_language->get('resources', 'managing_licenses_for')),
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
));

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets);

require(ROOT_PATH . '/core/templates/cc_navbar.php');

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

// Display template
$template->displayTemplate('resources/user/licenses.tpl', $smarty);