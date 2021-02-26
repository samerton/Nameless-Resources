<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr3
 *
 *  License: MIT
 *
 *  Resources class
 */

class Resources {
    private $_db;

    // Constructor, connect to database
    public function __construct(){
        $this->_db = DB::getInstance();
    }

    // Can the user post a resource in any category?
    public function canPostResourceInAnyCategory($group_ids){
        if (is_array($group_ids)) {
            $group_ids = implode(',', $group_ids);
        }

        return $this->_db->query('SELECT `post` FROM nl2_resources_categories_permissions WHERE `post` = 1 AND group_id IN (' . $group_ids . ')', array())->count() ? true : false;
    }

    // Can the user post a resource in a given category?
    public function canPostResourceInCategory($group_ids, $cat_id){
        if (is_array($group_ids)) {
            $group_ids = implode(',', $group_ids);
        }

        return $this->_db->query('SELECT `post` FROM nl2_resources_categories_permissions WHERE category_id = ? AND `post` = 1 AND group_id IN (' . $group_ids . ')', array($cat_id))->count() ? true : false;
    }

    // Can the user download a resource from a given category?
    public function canDownloadResourceFromCategory($group_ids, $cat_id){
        if (is_array($group_ids)) {
            $group_ids = implode(',', $group_ids);
        }

        return $this->_db->query('SELECT `view`, `download` FROM nl2_resources_categories_permissions WHERE category_id = ? AND `download` = 1 AND `view` = 1 AND group_id IN (' . $group_ids . ')', array($cat_id))->count() ? true : false;
    }

    // Get which resource types a user can post in a category
    public function getAvailableResourceTypes($group_ids, $cat_id){
        if (is_array($group_ids)) {
            $group_ids = implode(',', $group_ids);
        }

        $results = $this->_db->query('SELECT `post`, `premium` FROM nl2_resources_categories_permissions WHERE category_id = ? AND group_id IN (' . $group_ids . ')', array($cat_id))->results();
        $return = new stdClass();
        $return->post = false;
        $return->premium = false;

        if (count($results)) {
            foreach ($results as $result) {
                if ($result->post == 1) {
                    $return->post = true;
                }
                if ($result->premium == 1) {
                    $return->premium = true;
                }
            }
        }

        return $return;
    }

    // Can the user edit resources in this category?
    // Params: $cat_id - category ID (int), $group_ids - array of group IDs user is in (array of ints)
    public function canEditResources($cat_id, $group_ids){
        if (is_array($group_ids)) {
            $group_ids = implode(',', $group_ids);
        }

        return $this->_db->query('SELECT `view`, `edit_resource` FROM nl2_resources_categories_permissions WHERE category_id = ? AND `view` = 1 AND `edit_resource` = 1 AND group_id IN (' . $group_ids . ')', array($cat_id))->count() ? true : false;
    }

    // Can the user move resources in this category?
    // Params: $cat_id - category ID (int), $group_ids - array of group IDs user is in (array of ints)
    public function canMoveResources($cat_id, $group_ids){
        if (is_array($group_ids)) {
            $group_ids = implode(',', $group_ids);
        }

        return $this->_db->query('SELECT `view`, `move_resource` FROM nl2_resources_categories_permissions WHERE category_id = ? AND `view` = 1 AND `move_resource` = 1 AND group_id IN (' . $group_ids . ')', array($cat_id))->count() ? true : false;
    }

    // Can the user delete resources in this category?
    // Params: $cat_id - category ID (int), $group_ids - array of group IDs user is in (array of ints)
    public function canDeleteResources($cat_id, $group_ids){
        if (is_array($group_ids)) {
            $group_ids = implode(',', $group_ids);
        }

        return $this->_db->query('SELECT `view`, `delete_resource` FROM nl2_resources_categories_permissions WHERE category_id = ? AND `view` = 1 AND `delete_resource` = 1 AND group_id IN (' . $group_ids . ')', array($cat_id))->count() ? true : false;
    }

    // Can the user edit reviews in this category?
    // Params: $cat_id - category ID (int), $group_ids - array of group IDs user is in (array of ints)
    public function canEditReviews($cat_id, $group_ids){
        if (is_array($group_ids)) {
            $group_ids = implode(',', $group_ids);
        }

        return $this->_db->query('SELECT `view`, `edit_review` FROM nl2_resources_categories_permissions WHERE category_id = ? AND `view` = 1 AND `edit_review` = 1 AND group_id IN (' . $group_ids . ')', array($cat_id))->count() ? true : false;
    }

    // Can the user delete reviews in this category?
    // Params: $cat_id - category ID (int), $group_ids - array of group IDs user is in (array of ints)
    public function canDeleteReviews($cat_id, $group_ids){
        if (is_array($group_ids)) {
            $group_ids = implode(',', $group_ids);
        }

        return $this->_db->query('SELECT `view`, `delete_review` FROM nl2_resources_categories_permissions WHERE category_id = ? AND `view` = 1 AND `delete_review` = 1 AND group_id IN (' . $group_ids . ')', array($cat_id))->count() ? true : false;
    }

    // Can the user view this category?
    // Params: $cat_id - category ID (int), $group_ids - group IDs of user (array of IDs)
    public function canViewCategory($cat_id, $group_ids){
        if (is_array($group_ids)) {
            $group_ids = implode(',', $group_ids);
        }

        // Can the user view this category?
        return $this->_db->query('SELECT `view` FROM nl2_resources_categories_permissions WHERE `view` = 1 AND category_id = ? AND group_id IN (' . $group_ids . ')', array($cat_id))->count() ? true : false;
    }

    public function getCategories($group_ids) {
        if (is_array($group_ids)) {
            $group_ids = implode(',', $group_ids);
        }

        return $this->_db->query('SELECT * FROM nl2_resources_categories WHERE id IN (SELECT category_id FROM nl2_resources_categories_permissions WHERE `view` = 1 AND group_id IN (' . $group_ids . '))', array())->results();
    }

    public function getResourcesList($group_ids, $order_by, $category_id = null) {
        if (is_array($group_ids)) {
            $group_ids = implode(',', $group_ids);
        }

        $where = '';
        $params = array();

        if ($category_id) {
            $where = 'AND category_id = ? ';
            $params[] = $category_id;
        }

        return $this->_db->query('SELECT * FROM nl2_resources WHERE category_id IN (SELECT category_id FROM nl2_resources_categories_permissions WHERE `view` = 1 ' . $where . 'AND group_id IN (' . $group_ids . ')) ORDER BY '.$order_by.' DESC', $params)->results();
    }

    public function getAuthorLatestResources($author_id, $group_ids) {
        if (is_array($group_ids)) {
            $group_ids = implode(',', $group_ids);
        }

        return $this->_db->query('SELECT * FROM nl2_resources WHERE creator_id = ? AND category_id IN (SELECT category_id FROM nl2_resources_categories_permissions WHERE `view` = 1 AND group_id IN (' . $group_ids . ')) ORDER BY `updated` DESC', array($author_id))->results();
    }

    public function hasPermission($category, $required_permission, $groups) {
        $permissions = $this->_db->get('forums_permissions', array('category_id', '=', $category))->results();
        foreach ($permissions as $permission) {
            if (in_array($permission->group_id, $groups)) {
                if ($permission->$required_permission == 1)
                    return true;
            }
        }
        return false;
    }
}
