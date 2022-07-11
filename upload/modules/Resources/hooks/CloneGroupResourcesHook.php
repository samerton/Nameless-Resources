<?php
/*
 *  Made by Partydragen + Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr13
 *
 *  Clone group event listener handler class
 */

class CloneGroupResourcesHook {

    public static function execute(array $params = []): void {

        // Clone group permissions for resource categories
        $new_group_id = $params['group_id'];
        $permissions = DB::getInstance()->query('SELECT * FROM nl2_resources_categories_permissions WHERE group_id = ?', [$params['cloned_group_id']]);
        if ($permissions->count()) {
            $permissions = $permissions->results();

            $inserts = [];
            foreach ($permissions as $permission) {
                $inserts[] = '(' .$new_group_id . ',' . $permission->category_id . ',' . $permission->view . ',' . $permission->post . ',' . $permission->move_resource . ',' . $permission->edit_resource . ',' . $permission->delete_resource . ',' . $permission->edit_review . ',' . $permission->delete_review . ',' . $permission->download . ',' . $permission->premium . ')';
            }

            $query = 'INSERT INTO nl2_resources_categories_permissions (group_id, category_id, `view`, post, move_resource, edit_resource, delete_resource, edit_review, delete_review, download, premium) VALUES ';
            $query .= implode(',', $inserts);

            DB::getInstance()->query($query);
        }
    }
}
