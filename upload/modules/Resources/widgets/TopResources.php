<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr9
 *
 *  License: MIT
 *
 *  Top Resources Widget
 */
class TopResourcesWidget extends WidgetBase {

    private $_smarty, 
            $_language, 
            $_cache, 
            $_user;

    public function __construct($pages = array(), $user, $language, $resources_language, $smarty, $cache) {

    	$this->_user = $user;
		$this->_language = $language;
		$this->_resources_language = $resources_language;
    	$this->_smarty = $smarty;
    	$this->_cache = $cache;
		
        parent::__construct($pages);
        
        // Get widget
        $widget_query = DB::getInstance()->query('SELECT `location`, `order` FROM nl2_widgets WHERE `name` = ?', array('Top Resources'))->first();

        // Set widget variables
        $this->_module = 'Resources';
        $this->_name = 'Top Resources';
        $this->_location = isset($widget_query->location) ? $widget_query->location : null;
        $this->_description = 'Displays top resources';
        $this->_order = isset($widget_query->order) ? $widget_query->order : null;

    }

    public function initialise() {

		$queries = new Queries;
		$timeago = new Timeago();

		$topResources = $queries->orderAll('resources', 'rating', 'DESC LIMIT 5');
		$topResourcesArr = array();
		
		$this->_cache->setCache('resources');

		if (!$this->_cache->isCached('topResources')) {

			foreach ($topResources as $resource) {

				if ($resource->rating == 0) continue;

				$exts = array('gif','png','jpg','jpeg');
				foreach ($exts as $ext) {
					if (file_exists(ROOT_PATH . '/uploads/resources_icons/' . $resource->id . '.' . $ext)) {
						$resource_icon = rtrim(Util::getSelfURL(), '/') . ((defined('CONFIG_PATH')) ? CONFIG_PATH . '/' : '/') . 'uploads/resources_icons/' . $resource->id . '.' . $ext;
						break;
					} else {
						$resource_icon = rtrim(Util::getSelfURL(), '/') . (defined('CONFIG_PATH') ? CONFIG_PATH . '/' : '/') . 'uploads/resources_icons/default.png';
					}
				}


				$topResourcesArr[] = array(
					'name' => $resource->name,
					'tagline' => $resource->tagline,
					'icon' => $resource_icon,
					'link' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)),
					'creator_id' => $resource->creator_id,
					'creator_username' => Output::getClean($this->_user->idToName($resource->creator_id)),
					'creator_style' => $this->_user->getGroupClass($resource->creator_id),
					'creator_profile' => URL::build('/profile/' . Output::getClean($this->_user->idToName($resource->creator_id))),
					'released' => $timeago->inWords(date('d M Y, H:i', $resource->updated), $this->_language->getTimeLanguage()),
					'released_full' => date('d M Y, H:i', $resource->updated),
				);

				unset($resource_icon);

			}

			$this->_cache->store('topResources', $topResourcesArr, 5 * 60);

		} else {

			$topResourcesArr = $this->_cache->retrieve('topResources');

		}

		$this->_smarty->assign(array(
			'TOP_RESOURCES_TITLE' => $this->_resources_language->get('resources', 'top_resources'),
			'TOP_RESOURCES' => $topResourcesArr,
			'NO_TOP_RESOURCES' => $this->_resources_language->get('resources', 'no_top_resources'),
		));

		$this->_content = $this->_smarty->fetch('widgets/resources/top_resources.tpl');
		

    }
}