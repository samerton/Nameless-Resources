<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr12
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
		
		foreach ($topResources as $resource) {

			// check if resource rating > 0
			if ($resource->rating == 0) continue;

			$topResourcesArr[$resource->id] = array(
				'name' => Output::getClean($resource->name),
				'short_description' => Output::getClean($resource->short_description),
				'link' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)),
				'creator_id' => $resource->creator_id,
				'creator_username' => Output::getClean($this->_user->idToName($resource->creator_id)),
				'creator_style' => $this->_user->getGroupClass($resource->creator_id),
				'creator_profile' => URL::build('/profile/' . Output::getClean($this->_user->idToName($resource->creator_id))),
				'rating' => round($resource->rating / 10),
				'released' => $timeago->inWords(date('d M Y, H:i', $resource->updated), $this->_language->getTimeLanguage()),
				'released_full' => date('d M Y, H:i', $resource->updated),
			);

			// Check if resource icon uploaded
			if($resource->has_icon == 1 ) {
				$topResourcesArr[$resource->id]['icon'] = Output::getClean($resource->icon);
			} else {
				$topResourcesArr[$resource->id]['icon'] = rtrim(Util::getSelfURL(), '/') . (defined('CONFIG_PATH') ? CONFIG_PATH . '/' : '/') . 'uploads/resources_icons/default.png';
			}
		}

		$this->_smarty->assign(array(
			'TOP_RESOURCES_TITLE' => $this->_resources_language->get('resources', 'top_resources'),
			'TOP_RESOURCES' => $topResourcesArr,
			'NO_TOP_RESOURCES' => $this->_resources_language->get('resources', 'no_top_resources'),
		));

		$this->_content = $this->_smarty->fetch('widgets/resources/top_resources.tpl');
    }
}
