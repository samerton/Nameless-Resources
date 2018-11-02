<?php 
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr5
 *
 *  License: MIT
 *
 *  Resource module class
 */

class Resources_Module extends Module {
	private $_resource_language, $_language;

	public function __construct($pages, $language, $resource_language){
		$this->_resource_language = $resource_language;
		$this->_language = $language;

		$name = 'Resources';
		$author = '<a href="https://samerton.me" target="_blank">Samerton</a>';
		$module_version = '1.1.0';
		$nameless_version = '2.0.0-pr5';

		parent::__construct($this, $name, $author, $module_version, $nameless_version);

		// Hooks
		HookHandler::registerEvent('newResource', $resource_language->get('resources', 'new_resource'));
		HookHandler::registerEvent('updateResource', $resource_language->get('resources', 'update'));

		// Define URLs which belong to this module
		$pages->add('Resources', '/panel/resources/categories', 'pages/panel/categories.php');
		$pages->add('Resources', '/panel/resources/downloads', 'pages/panel/downloads.php');
		$pages->add('Resources', '/panel/resources/settings', 'pages/panel/settings.php');
		$pages->add('Resources', '/resources', 'pages/resources/index.php');
		$pages->add('Resources', '/resources/category', 'pages/resources/category.php');
		$pages->add('Resources', '/resources/resource', 'pages/resources/resource.php');
		$pages->add('Resources', '/resources/new', 'pages/resources/new.php');
		$pages->add('Resources', '/resources/author', 'pages/resources/author.php');
		$pages->add('Resources', '/resources/purchase', 'pages/resources/purchase.php');
		$pages->add('Resources', '/resources/listener', 'pages/resources/listener.php');
		$pages->add('Resources', '/user/resources', 'pages/user/resources.php');
	}

	public function onInstall(){
		$queries = new Queries();
		try {
			$data = $queries->createTable("resources_categories", " `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(32) NOT NULL, `description` text, `display_order` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`)", "ENGINE=InnoDB DEFAULT CHARSET=latin1");
			$data = $queries->createTable("resources", " `id` int(11) NOT NULL AUTO_INCREMENT, `category_id` int(11) NOT NULL, `creator_id` int(11) NOT NULL, `name` varchar(64) NOT NULL, `description` mediumtext NOT NULL, `contributors` text, `views` int(11) NOT NULL DEFAULT '0', `downloads` int(11) NOT NULL DEFAULT '0', `created` int(11) NOT NULL, `updated` int(11) NOT NULL, `github_url` varchar(128) DEFAULT NULL, `github_username` varchar(64) DEFAULT NULL, `github_repo_name` varchar(64) DEFAULT NULL, `rating` int(11) NOT NULL DEFAULT '0', `latest_version` varchar(32) DEFAULT NULL, `type` tinyint(1) NOT NULL DEFAULT '0', `price` varchar(16) DEFAULT NULL, `payment_email` varchar(256) DEFAULT NULL, PRIMARY KEY (`id`)", "ENGINE=InnoDB DEFAULT CHARSET=latin1");
			$data = $queries->createTable("resources_releases", " `id` int(11) NOT NULL AUTO_INCREMENT, `resource_id` int(11) NOT NULL, `category_id` int(11) NOT NULL, `release_title` varchar(128) NOT NULL, `release_description` mediumtext NOT NULL, `release_tag` varchar(16) NOT NULL, `created` int(11) NOT NULL, `downloads` int(11) NOT NULL DEFAULT '0', `rating` int(11) NOT NULL DEFAULT '0', `download_link` varchar(255) DEFAULT NULL, PRIMARY KEY (`id`)", "ENGINE=InnoDB DEFAULT CHARSET=latin1");
			$data = $queries->createTable("resources_comments", " `id` int(11) NOT NULL AUTO_INCREMENT, `resource_id` int(11) NOT NULL, `author_id` int(11) NOT NULL, `content` mediumtext NOT NULL, `release_tag` varchar(16) NOT NULL, `created` int(11) NOT NULL, `reply_id` int(11) DEFAULT NULL, `rating` int(11) NOT NULL DEFAULT '0', `hidden` tinyint(1) NOT NULL DEFAULT '0', PRIMARY KEY (`id`)", "ENGINE=InnoDB DEFAULT CHARSET=latin1");
			$data = $queries->createTable("resources_categories_permissions", " `id` int(11) NOT NULL AUTO_INCREMENT, `category_id` int(11) NOT NULL, `group_id` int(11) NOT NULL, `view` tinyint(1) NOT NULL DEFAULT '1', `post` tinyint(1) NOT NULL DEFAULT '1', `move_resource` tinyint(1) NOT NULL DEFAULT '1', `edit_resource` tinyint(1) NOT NULL DEFAULT '1', `delete_resource` tinyint(1) NOT NULL DEFAULT '1', `edit_review` tinyint(1) NOT NULL DEFAULT '1', `delete_review` tinyint(1) NOT NULL DEFAULT '1', `download` tinyint(1) NOT NULL DEFAULT '0', `premium` tinyint(1) NOT NULL DEFAULT '0', PRIMARY KEY (`id`)", "ENGINE=InnoDB DEFAULT CHARSET=latin1");
			$data = $queries->createTable("resources_payments", " `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `resource_id` int(11) NOT NULL, `transaction_id` varchar(32) NOT NULL, `created` int(11) NOT NULL, `status` tinyint(1) NOT NULL DEFAULT '0', PRIMARY KEY (`id`)", "ENGINE=InnoDB DEFAULT CHARSET=latin1");
			$data = $queries->createTable("resources_users_premium_details", " `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `paypal_email` varchar(256) DEFAULT NULL, PRIMARY KEY (`id`)", "ENGINE=InnoDB DEFAULT CHARSET=latin1");
		} catch(Exception $e){
			// Error
		}

		mkdir(ROOT_PATH . '/uploads/resources');
	}

	public function onUninstall(){
		DB::getInstance()->createQuery('DROP TABLE resources_categories');
		DB::getInstance()->createQuery('DROP TABLE resources');
		DB::getInstance()->createQuery('DROP TABLE resources_releases');
		DB::getInstance()->createQuery('DROP TABLE resources_comments');
		DB::getInstance()->createQuery('DROP TABLE resources_categories_permissions');
		DB::getInstance()->createQuery('DROP TABLE resources_payments');
	}

	public function onEnable(){

	}

	public function onDisable(){

	}

	public function onPageLoad($user, $pages, $cache, $smarty, $navs, $widgets, $template){
		// Add link to navbar
		$cache->setCache('navbar_order');
		if(!$cache->isCached('resources_order')){
			$resources_order = 2;
			$cache->store('resources_order', 2);
		} else {
			$resources_order = $cache->retrieve('resources_order');
		}

		$cache->setCache('navbar_icons');
		if(!$cache->isCached('resources_icon')){
			$icon = '';
		} else {
			$icon = $cache->retrieve('resources_icon');
		}

		$navs[0]->add('resources', $this->_resource_language->get('resources', 'resources'), URL::build('/resources'), 'top', null, $resources_order, $icon);
		$navs[1]->add('resources_settings', $this->_resource_language->get('resources', 'resources'), URL::build('/user/resources'), 'top', null, 10);

		$pages->registerSitemapMethod(ROOT_PATH . '/modules/Resources/classes/Resources_Sitemap.php', 'Resources_Sitemap::generateSitemap');

		if(defined('BACK_END')){
			// Check if upload dir is writable
			if(!is_writable(ROOT_PATH . '/uploads/resources')){
				Core_Module::addNotice(URL::build('/panel/resources/settings'), $this->_resource_language->get('resources', 'upload_directory_not_writable'));
			}

			if($user->data()->group_id == 2 || $user->hasPermission('admincp.resources')){
				$cache->setCache('panel_sidebar');
				if(!$cache->isCached('resources_order')){
					$order = 20;
					$cache->store('resources_order', 20);
				} else {
					$order = $cache->retrieve('resources_order');
				}

				$navs[2]->add('resources_divider', mb_strtoupper($this->_resource_language->get('resources', 'resources')), 'divider', 'top', null, $order, '');

				if($user->data()->group_id == 2 || $user->hasPermission('admincp.resources.categories')){
					if(!$cache->isCached('resources_categories_icon')){
						$icon = '<i class="nav-icon fa fa-list" aria-hidden="true"></i>';
						$cache->store('resources_categories_icon', $icon);
					} else
						$icon = $cache->retrieve('resources_categories_icon');

					$navs[2]->add('resources_categories', $this->_resource_language->get('resources', 'categories'), URL::build('/panel/resources/categories'), 'top', null, $order, $icon);
				}

				if($user->data()->group_id == 2 || $user->hasPermission('admincp.resources.download')){
					if(!$cache->isCached('resources_downloads_icon')){
						$icon = '<i class="nav-icon fa fa-download" aria-hidden="true"></i>';
						$cache->store('resources_downloads_icon', $icon);
					} else
						$icon = $cache->retrieve('resources_downloads_icon');

					$navs[2]->add('resources_downloads', $this->_resource_language->get('resources', 'downloads'), URL::build('/panel/resources/downloads'), 'top', null, $order, $icon);
				}

				if($user->data()->group_id == 2 || $user->hasPermission('admincp.resources.settings')){
					if(!$cache->isCached('resources_settings_icon')){
						$icon = '<i class="nav-icon fa fa-cog" aria-hidden="true"></i>';
						$cache->store('resources_settings_icon', $icon);
					} else
						$icon = $cache->retrieve('resources_settings_icon');

					$navs[2]->add('resources_settings', $this->_resource_language->get('resources', 'settings'), URL::build('/panel/resources/settings'), 'top', null, $order, $icon);
				}

			}
		}

		// AdminCP
		PermissionHandler::registerPermissions('Resources', array(
			'admincp.resources' => $this->_language->get('moderator', 'staff_cp') . ' &raquo; ' . $this->_resource_language->get('resources', 'resources'),
			'admincp.resources.categories' => $this->_language->get('moderator', 'staff_cp') . ' &raquo; ' . $this->_resource_language->get('resources', 'categories'),
			'admincp.resources.downloads' => $this->_language->get('moderator', 'staff_cp') . ' &raquo; ' . $this->_resource_language->get('resources', 'downloads'),
			'admincp.resources.settings' => $this->_language->get('moderator', 'staff_cp') . ' &raquo; ' . $this->_resource_language->get('resources', 'settings')
		));
	}
}