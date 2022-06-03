<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr13
 *
 *  License: MIT
 *
 *  Latest Resources Widget
 */
class LatestResourcesWidget extends WidgetBase {

    private $_language,
            $_cache, 
            $_user;

    public function __construct($user, $language, $resources_language, $smarty, $cache) {

    	$this->_user = $user;
		$this->_language = $language;
		$this->_resources_language = $resources_language;
    	$this->_smarty = $smarty;
    	$this->_cache = $cache;

        $widget_query = self::getData('Latest Resources');

        parent::__construct(self::parsePages($widget_query));

        // Get widget
        $widget_query = DB::getInstance()->query('SELECT `location`, `order` FROM nl2_widgets WHERE `name` = ?', array('Latest Resources'))->first();

        // Set widget variables
        $this->_module = 'Resources';
        $this->_name = 'Latest Resources';
        $this->_location = isset($widget_query->location) ? $widget_query->location : null;
        $this->_description = 'Display latest published and updated resources';
        $this->_order = isset($widget_query->order) ? $widget_query->order : null;

    }

    public function initialise(): void {

		$timeago = new TimeAgo(TIMEZONE);

		$latestResources = DB::getInstance()->orderAll('resources', 'updated', 'DESC LIMIT 5');
		$latestResourcesArr = array();

		foreach ($latestResources->results() as $resource) {
            $author = new User($resource->creator_id);

            if (!$author->exists()) {
                continue;
            }

			$latestResourcesArr[$resource->id] = array(
				'name' => Output::getClean($resource->name),
				'short_description' => Output::getClean($resource->short_description),
				'link' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)),
				'creator_id' => $resource->creator_id,
				'creator_username' => $author->getDisplayname(),
				'creator_style' => $author->getGroupStyle(),
				'creator_profile' => URL::build('/profile/' . $author->getDisplayname(true)),
				'released' => $timeago->inWords(date('d M Y, H:i', $resource->updated), $this->_language),
				'released_full' => date('d M Y, H:i', $resource->updated),
			);

			// Check if resource icon uploaded
			if($resource->has_icon == 1 ) {
				$latestResourcesArr[$resource->id]['icon'] = Output::getClean($resource->icon);
			} else {
				$latestResourcesArr[$resource->id]['icon'] = rtrim(Util::getSelfURL(), '/') . (defined('CONFIG_PATH') ? CONFIG_PATH . '/' : '/') . 'uploads/resources_icons/default.png';
			}
		}

		$this->_smarty->assign(array(
			'UPDATED_RESOURCES_TITLE' => $this->_resources_language->get('resources', 'latest_resources'),
			'UPDATED_RESOURCES' => $latestResourcesArr,
			'NO_UPDATED_RESOURCES' => $this->_resources_language->get('resources', 'no_latest_resources'),
		));

		$this->_content = $this->_smarty->fetch('widgets/resources/latest_resources.tpl');
    }
}
