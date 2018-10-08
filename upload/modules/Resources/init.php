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

require_once(ROOT_PATH . '/modules/Resources/module.php');
$module = new Resources_Module($pages, $language, $resource_language);