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
    public function canPostResourceInAnyCategory($group_id = null, $secondary_groups = null){
        if($group_id == null){
            return false;
        } else {
            if($secondary_groups)
                $secondary_groups = json_decode($secondary_groups, true);
        }
        // Can the user post a resource in any category?
        $access = $this->_db->get("resources_categories_permissions", array("group_id", "=", $group_id))->results();

        if(count($access)) {
            foreach($access as $item){
                if($item->post == 1)
                    return true;
            }
        }

        if(is_array($secondary_groups) && count($secondary_groups)){
            foreach($secondary_groups as $group_id){
                $access = $this->_db->get("resources_categories_permissions", array("group_id", "=", $group_id))->results();

                if(count($access)) {
                    foreach($access as $item){
                        if($item->post == 1)
                            return true;
                    }
                }
            }
        }

        return false;
    }

    // Can the user edit resources in this category?
    // Params: $cat_id - category ID (int), $group_id - group ID of user (int), $secondary_groups - array of group IDs user is in (array of ints)
    public function canEditResources($cat_id, $group_id = null, $secondary_groups = null){
        if($group_id == null){
            return false;
        } else {
            if($secondary_groups)
                $secondary_groups = json_decode($secondary_groups, true);
        }
        // Does the category exist?
        $exists = $this->_db->get("resources_categories", array("id", "=", $cat_id))->results();
        if(count($exists)){
            // Can the user edit resources in this category?
            $access = $this->_db->get("resources_categories_permissions", array("category_id", "=", $cat_id))->results();

            foreach($access as $item){
                if($item->group_id == $group_id || (is_array($secondary_groups) && count($secondary_groups) && in_array($item->group_id, $secondary_groups))){
                    if($item->edit_resource == 1){
                        return true;
                    }
                }
            }
        }

        return false;
    }

    // Can the user move resources in this category?
    // Params: $cat_id - category ID (int), $group_id - group ID of user (int), $secondary_groups - array of group IDs user is in (array of ints)
    public function canMoveResources($cat_id, $group_id = null, $secondary_groups = null){
        if($group_id == null){
            return false;
        } else {
            if($secondary_groups)
                $secondary_groups = json_decode($secondary_groups, true);
        }
        // Does the category exist?
        $exists = $this->_db->get("resources_categories", array("id", "=", $cat_id))->results();
        if(count($exists)){
            // Can the user move resources in this category?
            $access = $this->_db->get("resources_categories_permissions", array("category_id", "=", $cat_id))->results();

            foreach($access as $item){
                if($item->group_id == $group_id || (is_array($secondary_groups) && count($secondary_groups) && in_array($item->group_id, $secondary_groups))){
                    if($item->move_resource == 1){
                        return true;
                    }
                }
            }
        }

        return false;
    }

    // Can the user delete resources in this category?
    // Params: $cat_id - category ID (int), $group_id - group ID of user (int), $secondary_groups - array of group IDs user is in (array of ints)
    public function canDeleteResources($cat_id, $group_id = null, $secondary_groups = null){
        if($group_id == null){
            return false;
        } else {
            if($secondary_groups)
                $secondary_groups = json_decode($secondary_groups, true);
        }
        // Does the category exist?
        $exists = $this->_db->get("resources_categories", array("id", "=", $cat_id))->results();
        if(count($exists)){
            // Can the user delete resources in this category?
            $access = $this->_db->get("resources_categories_permissions", array("category_id", "=", $cat_id))->results();

            foreach($access as $item){
                if($item->group_id == $group_id || (is_array($secondary_groups) && count($secondary_groups) && in_array($item->group_id, $secondary_groups))){
                    if($item->delete_resource == 1){
                        return true;
                    }
                }
            }
        }

        return false;
    }

    // Can the user edit reviews in this category?
    // Params: $cat_id - category ID (int), $group_id - group ID of user (int), $secondary_groups - array of group IDs user is in (array of ints)
    public function canEditReviews($cat_id, $group_id = null, $secondary_groups = null){
        if($group_id == null){
            return false;
        } else {
            if($secondary_groups)
                $secondary_groups = json_decode($secondary_groups, true);
        }
        // Does the category exist?
        $exists = $this->_db->get("resources_categories", array("id", "=", $cat_id))->results();
        if(count($exists)){
            // Can the user move resources in this category?
            $access = $this->_db->get("resources_categories_permissions", array("category_id", "=", $cat_id))->results();

            foreach($access as $item){
                if($item->group_id == $group_id || (is_array($secondary_groups) && count($secondary_groups) && in_array($item->group_id, $secondary_groups))){
                    if($item->edit_review == 1){
                        return true;
                    }
                }
            }
        }

        return false;
    }

    // Can the user delete reviews in this category?
    // Params: $cat_id - category ID (int), $group_id - group ID of user (int), $secondary_groups - array of group IDs user is in (array of ints)
    public function canDeleteReviews($cat_id, $group_id = null, $secondary_groups = null){
        if($group_id == null){
            return false;
        } else {
            if($secondary_groups)
                $secondary_groups = json_decode($secondary_groups, true);
        }
        // Does the category exist?
        $exists = $this->_db->get("resources_categories", array("id", "=", $cat_id))->results();
        if(count($exists)){
            // Can the user delete reviews in this category?
            $access = $this->_db->get("resources_categories_permissions", array("category_id", "=", $cat_id))->results();

            foreach($access as $item){
                if($item->group_id == $group_id || (is_array($secondary_groups) && count($secondary_groups) && in_array($item->group_id, $secondary_groups))){
                    if($item->delete_review == 1){
                        return true;
                    }
                }
            }
        }

        return false;
    }

    // Can the user view this category?
    // Params: $cat_id - category ID (int), $group_id - group ID of user (int), $secondary_groups - array of group IDs user is in (array of ints)
    public function canViewCategory($cat_id, $group_id = null, $secondary_groups = null){
        if($group_id == null){
            $group_id = 0;
        } else {
            if($secondary_groups)
                $secondary_groups = json_decode($secondary_groups, true);
        }
        // Does the category exist?
        $exists = $this->_db->get("resources_categories", array("id", "=", $cat_id))->results();
        if(count($exists)){
            // Can the user delete reviews in this category?
            $access = $this->_db->get("resources_categories_permissions", array("category_id", "=", $cat_id))->results();

            foreach($access as $item){
                if($item->group_id == $group_id || (is_array($secondary_groups) && count($secondary_groups) && in_array($item->group_id, $secondary_groups))){
                    if($item->view == 1){
                        return true;
                    }
                }
            }
        }

        return false;
    }
}