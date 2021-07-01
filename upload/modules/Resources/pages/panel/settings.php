<?php
/*
 *	Made by Samerton
 *  https://github.com/samerton/Nameless-Resources
 *  NamelessMC version 2.0.0-pr5
 *
 *  License: MIT
 *
 *  Panel resources settings page
 */

// Can the user view the panel?
if($user->isLoggedIn()){
	if(!$user->canViewStaffCP()){
		// No
		Redirect::to(URL::build('/'));
		die();
	}
	if(!$user->isAdmLoggedIn()){
		// Needs to authenticate
		Redirect::to(URL::build('/panel/auth'));
		die();
	} else {
		if($user->getMainGroup()->id != 2 && !$user->hasPermission('admincp.resources.settings')){
			require_once(ROOT_PATH . '/403.php');
			die();
		}
	}
} else {
	// Not logged in
	Redirect::to(URL::build('/login'));
	die();
}

define('PAGE', 'panel');
define('PARENT_PAGE', 'resources');
define('PANEL_PAGE', 'resources_settings');
$page_title = $resource_language->get('resources', 'settings');
require_once(ROOT_PATH . '/core/templates/backend_init.php');

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets);

if(Input::exists()){
	$errors = array();
	if(Token::check(Input::get('token'))){
		$validate = new Validate();
		$validation = $validate->check($_POST, array(
			'currency' => array(
				'required' => true,
				'min' => 3,
				'max' => 3
			),
			'filesize' => array(
				'required' => true,
				'min' => 1
			),
			'pre_purchase_info' => array(
				'max' => 100000
			)
		));

		if($validation->passed()){
			$currency_id = $queries->getWhere('settings', array('name', '=', 'resources_currency'));
			$currency_id = $currency_id[0]->id;

			$queries->update('settings', $currency_id, array(
				'value' => $_POST['currency']
			));

			$filesize_id = $queries->getWhere('settings', array('name', '=', 'resources_filesize'));
			$filesize_id = $filesize_id[0]->id;

			$queries->update('settings', $filesize_id, array(
				'value' => $_POST['filesize']
			));

			$pre_purchase_info = '';
			if(isset($_POST['pre_purchase_info'])){
				$pre_purchase_info = $_POST['pre_purchase_info'];
			}
			$pre_purchase_info_id = $queries->getWhere('privacy_terms', array('name', '=', 'resource'));

			$queries->update('privacy_terms', $pre_purchase_info_id[0]->id, array(
				'value' => $_POST['pre_purchase_info']
			));

			if(isset($_POST['client_id']) && isset($_POST['client_secret']) && strlen($_POST['client_secret']) && strlen($_POST['client_secret'])){
				$to_config = file_get_contents(ROOT_PATH . '/modules/Resources/paypal_default.php');
				$to_config = str_replace(array('{client_id}', '{client_secret}'), array($_POST['client_id'], $_POST['client_secret']), $to_config);

				if((!file_exists(ROOT_PATH . '/modules/Resources/paypal.php') && is_writable(ROOT_PATH . '/modules/Resources')) || (file_exists(ROOT_PATH . '/modules/Resources/paypal.php') && is_writable(ROOT_PATH . '/modules/Resources/paypal.php'))){
					file_put_contents(ROOT_PATH . '/modules/Resources/paypal.php', $to_config);
				} else {
					$errors[] = $resource_language->get('resources', 'paypal_config_not_writable');
				}
			}

			$success = $resource_language->get('resources', 'settings_updated_successfully');

		} else {
			foreach($validation->errors() as $error){
				if(strpos($error, 'currency') !== false){
					$errors[] = $resource_language->get('resources', 'invalid_currency');
				} else if(strpos($error, 'filesize') !== false){
					$errors[] = $resource_language->get('resources', 'invalid_filesize');
				} else {
					$errors[] = $resource_language->get('resources', 'invalid_pre_purchase_info');
				}
			}
		}
	} else
		$errors[] = $language->get('general', 'invalid_token');
}

if(isset($success))
	$smarty->assign(array(
		'SUCCESS' => $success,
		'SUCCESS_TITLE' => $language->get('general', 'success')
	));

if(isset($errors) && count($errors))
	$smarty->assign(array(
		'ERRORS' => $errors,
		'ERRORS_TITLE' => $language->get('general', 'error')
	));

$currency = $queries->getWhere('settings', array('name', '=', 'resources_currency'));
if(!count($currency)){
	$queries->create('settings', array(
		'name' => 'resources_currency',
		'value' => 'GBP'
	));
	$currency = 'GBP';

} else {
	$currency = Output::getClean($currency[0]->value);
}

$filesize = $queries->getWhere('settings', array('name', '=', 'resources_filesize'));
if(!count($filesize)){
	$queries->create('settings', array(
		'name' => 'resources_filesize',
		'value' => '2048'
	));
	$filesize = '2048';

} else {
	$filesize = $filesize[0]->value;

	if(!is_numeric($filesize))
		$filesize = '2048';
}

$pre_purchase_info = $queries->getWhere('privacy_terms', array('name', '=', 'resource'));
if(!count($pre_purchase_info)){
	$pre_purchase_info = '<p>You will be redirected to PayPal to complete your purchase.</p><p>Access to the download will only be granted once the payment has been completed, this may take a while.</p><p>Please note, ' . SITE_NAME . ' can\'t take any responsibility for purchases that occur through our resources section. If you experience any issues with the resource, please contact the resource author directly.</p><p>If your access to ' . SITE_NAME . ' is revoked (for example, your account is banned), you will lose access to any purchased resources.</p>';

	$queries->create('privacy_terms', array(
		'name' => 'resource',
		'value' => $pre_purchase_info
	));

} else {
	$pre_purchase_info = $pre_purchase_info[0]->value;
}

$smarty->assign(array(
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
	'PRE_PURCHASE_INFO_VALUE' => Output::getClean($pre_purchase_info),
	'PAYPAL_API_DETAILS' => $resource_language->get('resources', 'paypal_api_details'),
	'PAYPAL_API_DETAILS_INFO' => $resource_language->get('resources', 'paypal_api_details_info'),
	'INFO' => $language->get('general', 'info'),
	'PAYPAL_CLIENT_ID' => $resource_language->get('resources', 'paypal_client_id'),
	'PAYPAL_CLIENT_SECRET' => $resource_language->get('resources', 'paypal_client_secret')
));

if(!is_dir(ROOT_PATH . '/uploads/resources')){
	if(!mkdir(ROOT_PATH . '/uploads/resources')){
		$smarty->assign(array(
			'WARNING' => $language->get('general', 'warning'),
			'UPLOADS_DIRECTORY_WRITABLE_INFO' => $resource_language->get('resources', 'upload_directory_not_writable')
		));
	}
} else if(!is_writable(ROOT_PATH . '/uploads/resources')){
	$smarty->assign(array(
		'WARNING' => $language->get('general', 'warning'),
		'UPLOADS_DIRECTORY_WRITABLE_INFO' => $resource_language->get('resources', 'upload_directory_not_writable')
	));
}

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->addCSSFiles(array(
	(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/plugins/spoiler/css/spoiler.css' => array()
));

$template->addJSFiles(array(
	(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/emoji/js/emojione.min.js' => array(),
	(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/plugins/spoiler/js/spoiler.js' => array(),
	(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/ckeditor.js' => array(),
	(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/plugins/emojione/dialogs/emojione.json' => array()
));

$template->addJSScript(Input::createEditor('inputPrePurchaseInfo', true));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/panel_navbar.php');

// Display template
$template->displayTemplate('resources/settings.tpl', $smarty);
