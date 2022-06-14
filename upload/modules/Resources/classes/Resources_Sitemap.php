<?php
/*
 *  Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr13
 *
 *  License: MIT
 *
 *  Resource module Sitemap method
 */

use SitemapPHP\Sitemap;

class Resources_Sitemap {
    public static function generateSitemap(Sitemap $sitemap = null){
        if(!$sitemap)
            return;

        // Core pages
        $sitemap->addItem(URL::build('/resources'), 0.9);

        $db = DB::getInstance();

        $resources = $db->query('SELECT id, `name`, updated, category_id, creator_id FROM nl2_resources WHERE category_id IN (SELECT category_id FROM nl2_resources_categories_permissions WHERE group_id = 0 AND `view` = 1)')->results();
        $authors = [];

        foreach ($resources as $resource) {
            $sitemap->addItem(URL::build('/resources/resource/' . $resource->id . '-' . urlencode($resource->name)), 0.5, 'weekly', date('Y-m-d', $resource->updated));
            if(!in_array($resource->creator_id, $authors))
                $authors[] = $resource->creator_id;
        }

        $resources = null;

        foreach($authors as $author){
            $author = $db->query('SELECT id, username FROM nl2_users WHERE id = ?', [$author])->results();

            if(count($author)){
                $author = $author[0];

                $sitemap->addItem(URL::build('/resources/author/' . $author->id . '-' . urlencode($author->username)), 0.5, 'monthly');
            }
        }

        $authors = null;

        $categories = $db->query('SELECT id, `name` FROM nl2_resources_categories WHERE id IN (SELECT category_id FROM nl2_resources_categories_permissions WHERE group_id = 0 AND `view` = 1)')->results();

        foreach($categories as $category){
            $sitemap->addItem(URL::build('/resources/category/' . $category->id . '-' . urlencode($category->name)), 0.5, 'daily');
        }
    }
}
