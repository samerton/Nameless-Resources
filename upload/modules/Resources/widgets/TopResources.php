<?php
/*
 *  Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr12
 *
 *  License: MIT
 *
 *  Top Resources Widget
 */
class TopResourcesWidget extends WidgetBase {

    private $_language,
            $_cache,
            $_user;

    public function __construct($user, $language, $resources_language, $smarty, $cache) {

        $this->_user = $user;
        $this->_language = $language;
        $this->_resources_language = $resources_language;
        $this->_smarty = $smarty;
        $this->_cache = $cache;

        $widget_query = self::getData('Top Resources');

        parent::__construct(self::parsePages($widget_query));
        
        // Get widget
        $widget_query = DB::getInstance()->query('SELECT `location`, `order` FROM nl2_widgets WHERE `name` = ?', ['Top Resources'])->first();

        // Set widget variables
        $this->_module = 'Resources';
        $this->_name = 'Top Resources';
        $this->_location = isset($widget_query->location) ? $widget_query->location : null;
        $this->_description = 'Displays top resources';
        $this->_order = isset($widget_query->order) ? $widget_query->order : null;

    }

    public function initialise(): void {

        $timeago = new TimeAgo(TIMEZONE);

        $topResources = DB::getInstance()->orderAll('resources', 'rating', 'DESC LIMIT 5');
        $topResourcesArr = [];

        foreach ($topResources->results() as $resource) {
            // check if resource rating > 0
            if ($resource->rating == 0) continue;

            $author = new User($resource->creator_id);

            if (!$author->exists()) {
                continue;
            }

            $topResourcesArr[$resource->id] = [
                'name' => Output::getClean($resource->name),
                'short_description' => Output::getClean($resource->short_description),
                'link' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)),
                'creator_id' => $resource->creator_id,
                'creator_username' => $author->getDisplayname(),
                'creator_style' => $author->getGroupStyle(),
                'creator_profile' => URL::build('/profile/' . $author->getDisplayname(true)),
                'rating' => round($resource->rating / 10),
                'released' => $timeago->inWords(date('d M Y, H:i', $resource->updated), $this->_language),
                'released_full' => date('d M Y, H:i', $resource->updated),
            ];

            // Check if resource icon uploaded
            if($resource->has_icon == 1 ) {
                $topResourcesArr[$resource->id]['icon'] = Output::getClean($resource->icon);
            } else {
                $topResourcesArr[$resource->id]['icon'] = rtrim(Util::getSelfURL(), '/') . (defined('CONFIG_PATH') ? CONFIG_PATH . '/' : '/') . 'uploads/resources_icons/default.png';
            }
        }

        $this->_smarty->assign([
            'TOP_RESOURCES_TITLE' => $this->_resources_language->get('resources', 'top_resources'),
            'TOP_RESOURCES' => $topResourcesArr,
            'NO_TOP_RESOURCES' => $this->_resources_language->get('resources', 'no_top_resources'),
        ]);

        $this->_content = $this->_smarty->fetch('widgets/resources/top_resources.tpl');
    }
}