<?php
/**
 *  Resources StaffCP Settings page
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

if (!$user->handlePanelPageLoad('admincp.resources.settings')) {
    require_once ROOT_PATH . '/403.php';
    die();
}

const PAGE = 'panel';
const PARENT_PAGE = 'resources';
const PANEL_PAGE = 'resources_settings';

$page_title = $resource_language->get('resources', 'settings');
require_once ROOT_PATH . '/core/templates/backend_init.php';

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, [$navigation, $cc_nav, $staffcp_nav], $widgets, $template);

if (Input::exists()) {
    $errors = [];
    if (Token::check(Input::get('token'))) {
        $currencyMessage = $resource_language->get(
            'resources',
            'invalid_currency',
            [
                'linkStart' =>'<a href="https://en.wikipedia.org/wiki/ISO_4217#Active_codes" target="_blank" rel="noopener nofollow">',
                'linkEnd' => '</a>'
            ]
        );
        $filesizeMessage = $resource_language->get('resources', 'invalid_filesize');
        $infoMessage = $resource_language->get('resources', 'invalid_pre_purchase_info');

        $validation = Validate::check($_POST, [
            'currency' => [
                'required' => true,
                'min' => 3,
                'max' => 3
            ],
            'filesize' => [
                'required' => true,
                'min' => 1
            ],
            'pre_purchase_info' => [
                'max' => 100000
            ]
        ])->messages([
            'currency' => [
                'required' => $currencyMessage,
                'min' => $currencyMessage,
                'max' => $currencyMessage
            ],
            'filesize' => [
                'required' => $filesizeMessage,
                'min' => $filesizeMessage
            ],
            'pre_purchase_info' => [
                'max' => $infoMessage
            ]
        ]);

        if ($validation->passed()) {
            Settings::set('currency', Input::get('currency'), 'resources');
            Settings::set('filesize', Input::get('filesize'), 'resources');
            Settings::set('pre_purchase_info', Input::get('pre_purchase_info'), 'resources');

            // TODO - move PayPal to gateway
            if (
                isset($_POST['client_id']) &&
                isset($_POST['client_secret']) &&
                strlen($_POST['client_id']) &&
                strlen($_POST['client_secret']))
            {
                $to_config = file_get_contents(ROOT_PATH . '/modules/Resources/paypal_default.php');
                $to_config = str_replace(['{client_id}', '{client_secret}'], [$_POST['client_id'], $_POST['client_secret']], $to_config);

                if (
                    (
                        !file_exists(ROOT_PATH . '/modules/Resources/paypal.php') &&
                        is_writable(ROOT_PATH . '/modules/Resources')
                    ) || (
                        file_exists(ROOT_PATH . '/modules/Resources/paypal.php') &&
                        is_writable(ROOT_PATH . '/modules/Resources/paypal.php')
                    )
                ){
                    file_put_contents(ROOT_PATH . '/modules/Resources/paypal.php', $to_config);
                } else {
                    $errors[] = $resource_language->get('resources', 'paypal_config_not_writable');
                }
            }

            $success = $resource_language->get('resources', 'settings_updated_successfully');

        } else {
            $errors = $validation->errors();
        }

    } else {
        $errors[] = $language->get('general', 'invalid_token');
    }

}

if (isset($success)) {
    $smarty->assign([
        'SUCCESS' => $success,
        'SUCCESS_TITLE' => $language->get('general', 'success')
    ]);
}

if (isset($errors) && count($errors)) {
    $smarty->assign([
        'ERRORS' => $errors,
        'ERRORS_TITLE' => $language->get('general', 'error')
    ]);
}

$currency = Resources::currency();
$filesize = Resources::filesize();
$prePurchaseInfo = Resources::prePurchaseInfo();

// TODO - move PayPal to a gateway!

$smarty->assign([
    'PARENT_PAGE' => PARENT_PAGE,
    'DASHBOARD' => $language->get('admin', 'dashboard'),
    'RESOURCES' => $resource_language->get('resources', 'resources'),
    'SETTINGS' => $resource_language->get('resources', 'settings'),
    'PAGE' => PANEL_PAGE,
    'TOKEN' => Token::get(),
    'SUBMIT' => $language->get('general', 'submit'),
    'CURRENCY' => $resource_language->get('resources', 'currency'),
    'CURRENCY_VALUE' => $currency,
    'FILESIZE' => $resource_language->get('resources', 'maximum_filesize'),
    'FILESIZE_VALUE' => $filesize,
    'PRE_PURCHASE_INFO' => $resource_language->get('resources', 'pre_purchase_information'),
    'PRE_PURCHASE_INFO_VALUE' => Output::getPurified($prePurchaseInfo),
    'PAYPAL_API_DETAILS' => $resource_language->get('resources', 'paypal_api_details'),
    'PAYPAL_API_DETAILS_INFO' => $resource_language->get('resources', 'paypal_api_details_info'),
    'INFO' => $language->get('general', 'info'),
    'PAYPAL_CLIENT_ID' => $resource_language->get('resources', 'paypal_client_id'),
    'PAYPAL_CLIENT_SECRET' => $resource_language->get('resources', 'paypal_client_secret')
]);

if (!is_dir(ROOT_PATH . '/uploads/resources')) {
    if (!mkdir(ROOT_PATH . '/uploads/resources')) {
        $smarty->assign([
            'WARNING' => $language->get('general', 'warning'),
            'UPLOADS_DIRECTORY_WRITABLE_INFO' => $resource_language->get('resources', 'upload_directory_not_writable')
        ]);
    }
} else if (!is_writable(ROOT_PATH . '/uploads/resources')) {
    $smarty->assign([
        'WARNING' => $language->get('general', 'warning'),
        'UPLOADS_DIRECTORY_WRITABLE_INFO' => $resource_language->get('resources', 'upload_directory_not_writable')
    ]);
}

$template->assets()->include([
    AssetTree::TINYMCE,
]);

$template->addJSScript(Input::createTinyEditor($language, 'inputPrePurchaseInfo'));

$template->onPageLoad();

require ROOT_PATH . '/core/templates/panel_navbar.php';

// Display template
$template->displayTemplate('resources/settings.tpl', $smarty);
