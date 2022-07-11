<?php
/*
 *  Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr13
 *
 *  Delete user event listener for Resources module
 */

class DeleteUserResourcesHook {
    public static function execute(array $params = []): void {
        if (isset($params['user_id']) && $params['user_id'] > 1) {
            $db = DB::getInstance();

            // Delete the user's resources
            $db->delete('resources', ['creator_id', $params['user_id']]);

            // Delete the user's resource comments
            $db->delete('resources_comments', ['author_id', $params['user_id']]);

            // Resource payments
            $db->delete('resources_payments', ['user_id', $params['user_id']]);

            // Resource premium details
            $db->delete('resources_users_premium_details', ['user_id', $params['user_id']]);
        }
    }
}
