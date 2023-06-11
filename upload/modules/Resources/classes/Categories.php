<?php
/**
 * Categories helper class for Resources module
 *
 * @author Samerton
 * @license MIT
 */

namespace Resources\Classes;

use DB;
use User;
use URL;

class Categories {

    /**
     * Build a URL to view a category
     *
     * @param Category $category Category to view
     * @return string
     */
    public static function buildViewUrl(Category $category): string {
        $slug = URL::urlSafe($category->getName());

        return URL::build(
            RESOURCES_ROOT .
            RESOURCES_CATEGORY .
            "/{$category->getId()}-$slug"
        );
    }

    /**
     * Check if the user can view a category
     *
     * @param User $user
     * @param Category $category
     * @return bool
     */
    public static function canView(User $user, Category $category): bool {
        $groups = $user->getAllGroupIds();
        $in = implode(',', array_map(static fn ($group) => '?', $groups));

        $check = DB::getInstance()->query(
            <<<SQL
            SELECT `view`
            FROM nl2_resources_categories_permissions
            WHERE `view` = 1 AND `category_id` = ? AND `group_id` IN ($in)
            SQL,
            [$category->getId(), ...$groups]
        );

        return !!$check->count();
    }

    /**
     * Get category by ID
     *
     * @param int $id ID of category to retrieve
     * @return ?Category
     */
    public static function findById(int $id): ?Category {
        $result = DB::getInstance()->query(
            <<<SQL
            SELECT * FROM nl2_resources_categories WHERE id = ?
            SQL,
            [$id]
        );

        if ($result->count()) {
            return (new Category())->fromDB($result->first());
        }

        return null;
    }

    /**
     * List categories
     *
     * @param ?User $user Current user, or null if permissions should not be checked
     * @return Category[] Array of categories
     */
    public static function list(User $user = null): array {
        if ($user) {
            $groups = $user->getAllGroupIds();
            $in = implode(',', array_map(static fn ($group) => '?', $groups));

            $results = DB::getInstance()->query(
                <<<SQL
                SELECT
                    rc.*,
                    (SELECT COUNT(*) FROM nl2_resources r WHERE r.`category_id` = rc.`id`) resource_count
                FROM nl2_resources_categories rc
                WHERE id IN
                      (
                      SELECT rcp.`category_id`
                      FROM nl2_resources_categories_permissions rcp
                      WHERE rcp.`view` = 1
                        AND rcp.`group_id` IN ($in)
                      )
                ORDER BY rc.`display_order`
                SQL,
                $groups
            )->results();

        } else {
            $results = DB::getInstance()->query(
                <<<SQL
                SELECT
                    rc.*,
                    (SELECT COUNT(*) FROM nl2_resources r WHERE r.`category_id` = rc.`id`) resource_count
                FROM nl2_resources_categories rc
                ORDER BY rc.`display_order`
                SQL
            )->results();
        }

        $categories = [];

        if (count($results)) {
            foreach ($results as $result) {
                $category = (new Category())->fromDB($result);
                $categories[$category->getId()] = $category;
            }
        }

        return $categories;
    }

}
