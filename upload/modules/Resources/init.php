<?php 
/*
 *  Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr13
 *
 *  License: MIT
 *
 *  Resource initialisation file
 */

// Initialise module language
$resource_language = new Language(ROOT_PATH . '/modules/Resources/language', LANGUAGE);

require_once ROOT_PATH . '/modules/Resources/classes/Resources_Sitemap.php';
require_once ROOT_PATH . '/modules/Resources/hooks/CloneGroupResourcesHook.php';
require_once ROOT_PATH . '/modules/Resources/hooks/DeleteUserResourcesHook.php';

require_once ROOT_PATH . '/modules/Resources/module.php';
$module = new Resources_Module($pages, $language, $resource_language, $endpoints);
