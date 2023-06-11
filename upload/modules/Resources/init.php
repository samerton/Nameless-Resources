<?php
/**
 * NamelessMC Resources module initialisation
 *
 * @author Samerton
 * @license MIT
 *
 * @var Language $language
 * @var Pages $pages
 */

// Initialise module language
$resource_language = new Language(__DIR__ . '/language', LANGUAGE);

require_once __DIR__ . '/autoload.php';

//require_once ROOT_PATH . '/modules/Resources/classes/Resources_Sitemap.php';
//require_once ROOT_PATH . '/modules/Resources/hooks/CloneGroupResourcesHook.php';
//require_once ROOT_PATH . '/modules/Resources/hooks/DeleteUserResourcesHook.php';

require_once __DIR__ . '/module.php';
$module = new Resources_Module($pages, $language, $resource_language);
