<?php
/*
 *  Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr9
 *
 *  License: MIT
 *
 *  UserCP resources page
 */

// Must be logged in
if(!$user->isLoggedIn()){
    Redirect::to(URL::build('/'));
    die();
}

// Always define page name for navbar
define('PAGE', 'resources_settings');
$page_title = $language->get('user', 'user_cp');
require_once(ROOT_PATH . '/core/templates/frontend_init.php');

$timeago = new Timeago(TIMEZONE);

if(Input::exists()){
    if(Token::check(Input::get('token'))){
        $paypal_address = null;

        if(isset($_POST['paypal_email'])){
            if((strlen(str_replace(' ', '', $_POST['paypal_email'])) > 0 && strlen(str_replace(' ', '', $_POST['paypal_email'])) < 4) || strlen($_POST['paypal_email']) > 64)
                $error = $resource_language->get('resources', 'invalid_email_address');
            else
                $paypal_address = Output::getClean($_POST['paypal_email']);
        }

        if(!isset($error)){
            $user_id = DB::getInstance()->get('resources_users_premium_details', ['user_id', '=', $user->data()->id]);
            if ($user_id->count()) {
                $user_id = $user_id->first()->id;

                DB::getInstance()->update('resources_users_premium_details', $user_id, [
                    'paypal_email' => $paypal_address
                ]);

            } else {
                DB::getInstance()->insert('resources_users_premium_details', [
                    'user_id' => $user->data()->id,
                    'paypal_email' => $paypal_address
                ]);

            }

            $success = $resource_language->get('resources', 'settings_updated_successfully');
        }

    } else
        $error = $language->get('general', 'invalid_token');
}

$paypal_address_query = DB::getInstance()->get('resources_users_premium_details', ['user_id', '=', $user->data()->id]);
if ($paypal_address_query->count())
    $paypal_address = Output::getClean($paypal_address_query->first()->paypal_email);
else
    $paypal_address = '';

if(isset($success))
    $smarty->assign('SUCCESS', $success);

if(isset($error))
    $smarty->assign('ERROR', $error);

$purchased_resources = DB::getInstance()->query('SELECT nl2_resources.id as id, nl2_resources.name as name, nl2_resources.creator_id as author, nl2_resources.latest_version as version, nl2_resources.updated as updated FROM nl2_resources_payments LEFT JOIN nl2_resources ON nl2_resources.id = nl2_resources_payments.resource_id WHERE nl2_resources_payments.status = 1 AND nl2_resources_payments.user_id = ?', [$user->data()->id])->results();

$purchased_array = [];
if(count($purchased_resources)){
    foreach($purchased_resources as $resource){
        $author = new User($resource->author);

        $purchased_array[] = [
            'name' => Output::getClean($resource->name),
            'author_username' => $author->getDisplayname(true),
            'author_nickname' => $author->getDisplayname(),
            'author_avatar' => $author->getAvatar('', 256),
            'author_style' => $author->getGroupStyle(),
            'author_link' => $author->getProfileURL(),
            'latest_version' => Output::getClean($resource->version),
            'updated' => $timeago->inWords(date('d M Y, H:i', $resource->updated), $language),
            'updated_full' => date('d M Y, H:i', $resource->updated),
            'link' => URL::build('/resources/resource/' . Output::getClean($resource->id) . '-' . Util::stringToURL($resource->name))
        ];
    }
}

$premium_resources = DB::getInstance()->query('SELECT `id`, `name`, `latest_version` AS `version` FROM nl2_resources WHERE creator_id = ? AND `type` = 1', [$user->data()->id])->results();
$premium_array = [];
if (count($premium_resources)) {
    foreach($premium_resources as $resource){
        $purchase_count = DB::getInstance()->query('SELECT COUNT(*) as `count` FROM nl2_resources_payments WHERE resource_id = ? AND `status` = 1', [$resource->id])->first();
        $premium_array[] = [
            'name' => Output::getClean($resource->name),
            'latest_version' => Output::getClean($resource->version),
            'link' => URL::build('/user/resources/licenses/' . Output::getClean($resource->id) . '-' . Util::stringToURL($resource->name)),
            'license_count' => $purchase_count->count == 1 ? $resource_language->get('resources', '1_license') : $resource_language->get('resources', 'x_licenses', ['count' => $purchase_count->count])
        ];
    }
}

// Language values
$smarty->assign([
    'USER_CP' => $language->get('user', 'user_cp'),
    'RESOURCES' => $resource_language->get('resources', 'resources'),
    'MY_RESOURCES_LINK' => URL::build('/resources/author/' . Output::getClean($user->data()->id  . '-' . $user->data()->nickname)),
    'MY_RESOURCES' => $resource_language->get('resources', 'my_resources'),
    'PURCHASED_RESOURCES' => $resource_language->get('resources', 'purchased_resources'),
    'PURCHASED_RESOURCES_VALUE' => $purchased_array,
    'NO_PURCHASED_RESOURCES' => $resource_language->get('resources', 'no_purchased_resources'),
    'PAYPAL_EMAIL_ADDRESS' => $resource_language->get('resources', 'paypal_email_address'),
    'PAYPAL_EMAIL_ADDRESS_INFO' => $resource_language->get('resources', 'paypal_email_address_info'),
    'PAYPAL_EMAIL_ADDRESS_VALUE' => $paypal_address,
    'SETTINGS' => $resource_language->get('resources', 'settings'),
    'MANAGE_LICENSES' => $resource_language->get('resources', 'manage_licenses'),
    'SELECT_RESOURCE' => $resource_language->get('resources', 'select_resource'),
    'PREMIUM_RESOURCES' => $premium_array,
    'NO_PREMIUM_RESOURCES' => $resource_language->get('resources', 'no_premium_resources'),
    'INFO' => $language->get('general', 'info'),
    'TOKEN' => Token::get(),
    'SUBMIT' => $language->get('general', 'submit')
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
$template->displayTemplate('resources/user/resources.tpl', $smarty);