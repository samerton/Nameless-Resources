<?php 
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr2
 *
 *  License: MIT
 *
 *  Resource initialisation file
 */

// Ensure module has been installed
$cache->setCache('module_cache');
$module_installed = $cache->retrieve('module_resources');
if(!$module_installed){
	// Hasn't been installed
	// Need to run the installer
	
	// Database stuff
	$exists = $queries->tableExists('resources_categories');
	if(empty($exists)){
		// Create tables
		try {
			$data = $queries->createTable("resources_categories", " `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(32) NOT NULL, `description` text, `display_order` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`)", "ENGINE=InnoDB DEFAULT CHARSET=latin1");
			$data = $queries->createTable("resources", " `id` int(11) NOT NULL AUTO_INCREMENT, `category_id` int(11) NOT NULL, `creator_id` int(11) NOT NULL, `name` varchar(64) NOT NULL, `description` mediumtext NOT NULL, `contributors` text, `views` int(11) NOT NULL DEFAULT '0', `downloads` int(11) NOT NULL DEFAULT '0', `created` int(11) NOT NULL, `updated` int(11) NOT NULL, `github_url` varchar(128) DEFAULT NULL, `github_username` varchar(64) DEFAULT NULL, `github_repo_name` varchar(64) DEFAULT NULL, `rating` int(11) NOT NULL DEFAULT '0', `latest_version` varchar(32) DEFAULT NULL, PRIMARY KEY (`id`)", "ENGINE=InnoDB DEFAULT CHARSET=latin1");
			$data = $queries->createTable("resources_releases", " `id` int(11) NOT NULL AUTO_INCREMENT, `resource_id` int(11) NOT NULL, `category_id` int(11) NOT NULL, `release_title` varchar(128) NOT NULL, `release_description` mediumtext NOT NULL, `release_tag` varchar(16) NOT NULL, `created` int(11) NOT NULL, `downloads` int(11) NOT NULL DEFAULT '0', `rating` int(11) NOT NULL DEFAULT '0', `download_link` varchar(255) DEFAULT NULL, PRIMARY KEY (`id`)", "ENGINE=InnoDB DEFAULT CHARSET=latin1");
			$data = $queries->createTable("resources_comments", " `id` int(11) NOT NULL AUTO_INCREMENT, `resource_id` int(11) NOT NULL, `author_id` int(11) NOT NULL, `content` mediumtext NOT NULL, `release_tag` varchar(16) NOT NULL, `created` int(11) NOT NULL, `reply_id` int(11) DEFAULT NULL, `rating` int(11) NOT NULL DEFAULT '0', `hidden` tinyint(1) NOT NULL DEFAULT '0', PRIMARY KEY (`id`)", "ENGINE=InnoDB DEFAULT CHARSET=latin1");
			$data = $queries->createTable("resources_categories_permissions", " `id` int(11) NOT NULL AUTO_INCREMENT, `category_id` int(11) NOT NULL, `group_id` int(11) NOT NULL, `view` tinyint(1) NOT NULL DEFAULT '1', `post` tinyint(1) NOT NULL DEFAULT '1', `move_resource` tinyint(1) NOT NULL DEFAULT '1', `edit_resource` tinyint(1) NOT NULL DEFAULT '1', `delete_resource` tinyint(1) NOT NULL DEFAULT '1', `edit_review` tinyint(1) NOT NULL DEFAULT '1', `delete_review` tinyint(1) NOT NULL DEFAULT '1', PRIMARY KEY (`id`)", "ENGINE=InnoDB DEFAULT CHARSET=latin1");
		} catch(Exception $e){
			// Error
		}
	}
	
	// Add to cache
	$cache->store('module_resources', 'true');
	
}

// Initialise module language
$resource_language = new Language(ROOT_PATH . '/modules/Resources/language', LANGUAGE);

// Define URLs which belong to this module
$pages->add('Resources', '/admin/resources', 'pages/admin/resources.php');
$pages->add('Resources', '/resources', 'pages/resources/index.php');
$pages->add('Resources', '/resources/category/', 'pages/resources/category.php');
$pages->add('Resources', '/resources/resource', 'pages/resources/resource.php');
$pages->add('Resources', '/resources/new', 'pages/resources/new.php');

// Add link to navbar
$navigation->add('resources', $resource_language->get('resources', 'resources'), URL::build('/resources'));

// Add link to admin sidebar
if(!isset($admin_sidebar)) $admin_sidebar = array();
$admin_sidebar['resources'] = array(
	'title' => $resource_language->get('resources', 'resources'),
	'url' => URL::build('/admin/resources')
);

// Front page module
if(!isset($front_page_modules)) $front_page_modules = array();
$front_page_modules[] = 'modules/Resources/front_page.php';
