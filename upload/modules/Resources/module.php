<?php 
/*
 *  Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.1.0
 *
 *  License: MIT
 *
 *  Resource module class
 */

class Resources_Module extends Module {
    private string $_name;
    private Language $_resource_language, $_language;

    public function __construct(Pages $pages, Language $language, Language $resource_language) {
        $name = 'Resources';
        $author = '<a href="https://samerton.me" target="_blank">Samerton</a>';
        $module_version = '2.0.0';
        $nameless_version = '2.1.0';

        $this->_language = $language;
        $this->_name = $name;
        $this->_resource_language = $resource_language;

        parent::__construct($this, $name, $author, $module_version, $nameless_version);

        try {
            $upToDate = PhinxAdapter::ensureUpToDate(
                $name,
                __DIR__ . '/includes/migrations',
                true
            );
        } catch (PDOException $e) {
            // Not yet installed! What should happen here?
            return;
        }

        if ($upToDate['missing']) {
            // Run migrations
            PhinxAdapter::migrate($name, __DIR__ . '/includes/migrations');
        }

        // Define URLs which belong to this module
        // StaffCP
        $pages->add('Resources', '/panel/resources/categories', 'pages/panel/categories.php');
        // TODO: $pages->add('Resources', '/panel/resources/downloads', 'pages/panel/downloads.php');
        $pages->add('Resources', '/panel/resources/settings', 'pages/panel/settings.php');

        // Frontend
        $pages->add('Resources', '/resources', 'pages/resources/index.php', 'resources', true);
        $pages->add('Resources', '/resources/category', 'pages/resources/category.php', 'resources', true);
        $pages->add('Resources', '/resources/resource', 'pages/resources/resource.php');
        $pages->add('Resources', '/resources/new', 'pages/resources/new.php');
        $pages->add('Resources', '/resources/author', 'pages/resources/author.php');
        $pages->add('Resources', '/resources/purchase', 'pages/resources/purchase.php');
        $pages->add('Resources', '/resources/icon_upload', 'pages/resources/icon_upload.php');

        // User panel
        $pages->add('Resources', '/user/resources', 'pages/user/resources.php');
        $pages->add('Resources', '/user/resources/licenses', 'pages/user/licenses.php');

        // Listener
        $pages->add('Resources', '/resources/listener', 'pages/resources/listener.php');

        return;

        // Hooks
        EventHandler::registerEvent('newResource', $resource_language->get('resources', 'new_resource'));
        EventHandler::registerEvent('updateResource', $resource_language->get('resources', 'update'));

        EventHandler::registerListener('deleteUser', 'DeleteUserResourcesHook::execute');
        EventHandler::registerListener('cloneGroup', 'CloneGroupResourcesHook::execute');

    }

    public function onInstall() {
        PhinxAdapter::migrate($this->_name, __DIR__ . '/includes/migrations');

        mkdir(ROOT_PATH . '/uploads/resources');
        mkdir(ROOT_PATH . '/uploads/resources_icons');
    }

    public function onUninstall(){
        PhinxAdapter::rollback($this->_name, __DIR__ . '/includes/migrations');
    }

    public function onEnable(){

    }

    public function onDisable(){

    }

    public function onPageLoad(
        User $user,
        Pages $pages,
        Cache $cache,
        Smarty $smarty,
        iterable $navs,
        Widgets $widgets,
        ?TemplateBase $template
    ){
        $cache->setCache('resources_cache');

        // Add link to navbar
        $cache->setCache('navbar_order');
        if (!$cache->isCached('resources_order')) {
            $resources_order = 2;
            $cache->store('resources_order', 2);
        } else {
            $resources_order = $cache->retrieve('resources_order');
        }

        $cache->setCache('navbar_icons');
        if (!$cache->isCached('resources_icon')) {
            $icon = '';
        } else {
            $icon = $cache->retrieve('resources_icon');
        }

        // Top menu link - TODO: customise link location
        $navs[0]->add(
            'resources',
            $this->_resource_language->get('resources', 'resources'),
            URL::build('/resources'),
            'top',
            null,
            $resources_order,
            $icon
        );

        // User settings sidebar link
        $navs[1]->add(
            'resources_settings',
            $this->_resource_language->get('resources', 'resources'),
            URL::build('/user/resources'),
            'top',
            null,
            10
        );

        // TODO
        /*
        $pages->registerSitemapMethod([Resources_Sitemap::class, 'generateSitemap']);

        // Widgets
        // Latest Resources
        require_once(__DIR__ . '/widgets/LatestResources.php');
        $widgets->add(new LatestResourcesWidget($user, $this->_language, $this->_resource_language, $smarty, $cache));

        // Top Resources
        require_once(__DIR__ . '/widgets/TopResources.php');
        $widgets->add(new TopResourcesWidget($user, $this->_language, $this->_resource_language, $smarty, $cache));
        */

        // StaffCP setup
        if (defined('BACK_END')) {
            // Check if upload dir is writable
            if (!is_writable(ROOT_PATH . '/uploads/resources')) {
                Core_Module::addNotice(
                    URL::build('/panel/resources/settings'),
                    $this->_resource_language->get('resources', 'upload_directory_not_writable')
                );
            }

            // Add links to sidebar
            if ($user->hasPermission('admincp.resources')) {
                $order = Settings::get('panel_resources_order', 20, 'resources');

                $navs[2]->add(
                    'resources_divider',
                    mb_strtoupper($this->_resource_language->get('resources', 'resources')),
                    'divider',
                    'top',
                    null,
                    $order, ''
                );

                if ($user->hasPermission('admincp.resources.categories')) {
                    $icon = '<i class="nav-icon fa fa-list" aria-hidden="true"></i>';

                    $navs[2]->add(
                        'resources_categories',
                        $this->_resource_language->get('resources', 'categories'),
                        URL::build('/panel/resources/categories'),
                        'top',
                        null,
                        ($order + 0.1),
                        $icon
                    );
                }

                // TODO: resource management/moderation page
                /*if($user->getMainGroup()->id == 2 || $user->hasPermission('admincp.resources.download')){
                    if(!$cache->isCached('resources_downloads_icon')){
                        $icon = '<i class="nav-icon fa fa-download" aria-hidden="true"></i>';
                        $cache->store('resources_downloads_icon', $icon);
                    } else
                        $icon = $cache->retrieve('resources_downloads_icon');

                    $navs[2]->add('resources_downloads', $this->_resource_language->get('resources', 'downloads'), URL::build('/panel/resources/downloads'), 'top', null, ($order + 0.2), $icon);
                }*/

                if ($user->hasPermission('admincp.resources.settings')) {
                    $icon = '<i class="nav-icon fa fa-cog" aria-hidden="true"></i>';

                    $navs[2]->add(
                        'resources_settings',
                        $this->_resource_language->get('resources', 'settings'),
                        URL::build('/panel/resources/settings'),
                        'top',
                        null,
                        ($order + 0.3),
                        $icon
                    );
                }
            }
        }

        // Register permissions
        PermissionHandler::registerPermissions('Resources', [
            'admincp.resources' =>
                $this->_language->get('moderator', 'staff_cp') . ' &raquo; ' . $this->_resource_language->get('resources', 'resources'),
            'admincp.resources.categories' =>
                $this->_language->get('moderator', 'staff_cp') . ' &raquo; ' . $this->_resource_language->get('resources', 'categories'),
            'admincp.resources.downloads' =>
                $this->_language->get('moderator', 'staff_cp') . ' &raquo; ' . $this->_resource_language->get('resources', 'downloads'),
            'admincp.resources.settings' =>
                $this->_language->get('moderator', 'staff_cp') . ' &raquo; ' . $this->_resource_language->get('resources', 'settings'),
            'admincp.resources.licenses' =>
                $this->_language->get('moderator', 'staff_cp') . ' &raquo; ' . $this->_resource_language->get('resources', 'manage_licenses'),
        ]);
    }

    public function getDebugInfo(): array {
        return [];
    }
}
