<?php 
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr5
 *
 *  License: MIT
 *
 *  Resource initialisation file
 */

// Initialise module language
$resource_language = new Language(ROOT_PATH . '/modules/Resources/language', LANGUAGE);

// Add link to admin sidebar - temp
if($user->data()->group_id == 2 || $user->hasPermission('admincp.resources')) {
	if(!isset($admin_sidebar)) $admin_sidebar = array();
	$admin_sidebar['resources'] = array(
		'title' => $resource_language->get('resources', 'resources'),
		'url' => URL::build('/admin/resources')
	);
}

require_once(ROOT_PATH . '/modules/Resources/module.php');
$module = new Resources_Module($pages, $language, $resource_language);