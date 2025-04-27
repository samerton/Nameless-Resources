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
            $_resources_language;

    public function __construct($language, $resources_language, $engine) {
        $this->_language = $language;
        $this->_resources_language = $resources_language;
        $this->_engine = $engine;

        // Set widget variables
        $this->_module = 'Resources';
        $this->_name = 'Top Resources';
        $this->_description = 'Displays top resources';
    }

    public function initialise(): void {

        $timeago = new TimeAgo(TIMEZONE);

        $groups = '0';
        if (($user = new User())->isLoggedIn()) {
            $groups = implode(',', array_keys($user->getAllGroupIds()));
        }

        $topResources = DB::getInstance()->query(
            <<<SQL
            SELECT * FROM nl2_resources
            WHERE
                category_id
            IN (
                SELECT category_id FROM nl2_resources_categories_permissions
                WHERE
                    group_id
                IN (
                    {$groups}
                )
                AND `view` = 1
            ) ORDER BY rating DESC LIMIT 5
            SQL
        );
        $topResourcesArr = [];

        foreach ($topResources->results() as $resource) {
            $author = new User($resource->creator_id);

            if (!$author->exists()) {
                continue;
            }

            $topResourcesArr[$resource->id] = [
                'name' => Output::getClean($resource->name),
                'short_description' => Output::getClean($resource->short_description),
                'link' => URL::build('/resources/resource/' . $resource->id . '-' . URL::urlSafe($resource->name)),
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
                $topResourcesArr[$resource->id]['icon'] = rtrim(URL::getSelfURL(), '/') . (defined('CONFIG_PATH') ? CONFIG_PATH . '/' : '/') . 'uploads/resources_icons/default.png';
            }
        }

        $this->_engine->addVariables([
            'TOP_RESOURCES_TITLE' => $this->_resources_language->get('resources', 'top_resources'),
            'TOP_RESOURCES' => $topResourcesArr,
            'NO_TOP_RESOURCES' => $this->_resources_language->get('resources', 'no_top_resources'),
        ]);

        $this->_content = $this->_engine->fetch('widgets/resources/top_resources.tpl');
    }
}
