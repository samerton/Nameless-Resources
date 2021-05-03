<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr9
 *
 *  License: MIT
 *
 *  Latest Resources Widget
 */
class LatestResourcesWidget extends WidgetBase {

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
        $widget_query = DB::getInstance()->query('SELECT `location`, `order` FROM nl2_widgets WHERE `name` = ?', array('Latest Resources'))->first();

        // Set widget variables
        $this->_module = 'Resources';
        $this->_name = 'Latest Resources';
        $this->_location = isset($widget_query->location) ? $widget_query->location : null;
        $this->_description = 'Display latest published and updated resources';
        $this->_order = isset($widget_query->order) ? $widget_query->order : null;

    }

    public function initialise() {

		$queries = new Queries;
		$timeago = new Timeago();

		$latestResources = $queries->orderAll('resources', 'updated', 'DESC LIMIT 5');
		$latestResourcesArr = array();

		$this->_cache->setCache('resources');

		if (!$this->_cache->isCached('latestResources')) {

			foreach ($latestResources as $resource) {

				//if ($resource->updated < time() - (7 * 86400)) continue;

				$latestResourcesArr[] = array(
					'name' => $resource->name,
					'short_description' => $resource->short_description,
					'link' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)),
					'creator_id' => $resource->creator_id,
					'creator_username' => Output::getClean($this->_user->idToName($resource->creator_id)),
					'creator_style' => $this->_user->getGroupClass($resource->creator_id),
					'creator_profile' => URL::build('/profile/' . Output::getClean($this->_user->idToName($resource->creator_id))),
					'released' => $timeago->inWords(date('d M Y, H:i', $resource->updated), $this->_language->getTimeLanguage()),
					'released_full' => date('d M Y, H:i', $resource->updated),
				);

	        		// Check if resource icon uploaded
	        		if($resource->has_icon == 1 ) {
	    	    			$latestResourcesArr[$resource->id]['icon'] = $resource->icon;
	        		} else {
	    	    			$latestResourcesArr[$resource->id]['icon'] = rtrim(Util::getSelfURL(), '/') . (defined('CONFIG_PATH') ? CONFIG_PATH . '/' : '/') . 'uploads/resources_icons/default.png';
	        		}
				
			}

			$this->_cache->store('latestResources', $latestResourcesArr, 5 * 60);

		} else {

			$latestResourcesArr = $this->_cache->retrieve('latestResources');

		}

		$this->_smarty->assign(array(
			'UPDATED_RESOURCES_TITLE' => $this->_resources_language->get('resources', 'latest_resources'),
			'UPDATED_RESOURCES' => $latestResourcesArr,
			'NO_UPDATED_RESOURCES' => $this->_resources_language->get('resources', 'no_latest_resources'),
		));

		$this->_content = $this->_smarty->fetch('widgets/resources/latest_resources.tpl');
		

    }
}
