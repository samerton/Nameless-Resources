<?php
/**
 * Resources helper class for Resources module
 *
 * @author Samerton
 * @license MIT
 */

namespace Resources\Classes;

use DB;
use QueryOptions;
use Settings;
use URL;

class Resources {

    /**
     * Build a URL to download a resource
     *
     * @param Resource $resource Resource to download
     * @return string
     */
    public static function buildDownloadUrl(Resource $resource): string {
        return URL::build(
            RESOURCES_ROOT .
            RESOURCES_RESOURCE .
            "/{$resource->getId()}" .
            RESOURCES_DOWNLOAD
        );
    }

    /**
     * Build a URL to purchase a resource
     *
     * @param Resource $resource Resource to purchase
     * @return string
     */
    public static function buildPurchaseUrl(Resource $resource): string {
        return URL::build(
            RESOURCES_ROOT .
            RESOURCES_RESOURCE .
            "/{$resource->getId()}" .
            RESOURCES_PURCHASE
        );
    }

    /**
     * Build a URL to view a resource
     *
     * @param Resource $resource Resource to view
     * @return string
     */
    public static function buildViewUrl(Resource $resource): string {
        $slug = URL::urlSafe($resource->getName());

        return URL::build(
            RESOURCES_ROOT .
            RESOURCES_RESOURCE .
            "/{$resource->getId()}-$slug"
        );
    }

    /**
     * Gets currently active currency
     *
     * @return string ISO-4217 currency code (e.g. GBP, USD)
     */
    public static function currency(): string {
        return Settings::get('currency', 'GBP', 'resources');
    }

    /**
     * Gets max filesize setting
     *
     * @return string Max filesize (kilobytes)
     */
    public static function filesize(): string {
        return Settings::get('filesize', '2048', 'resources');
    }

    /**
     * Gets pre-purchase info
     *
     * @return string Pre-purchase info
     */
    public static function prePurchaseInfo(): string {
        return str_replace(
            '{{siteName}}',
            SITE_NAME,
            Settings::get('pre_purchase_info', RESOURCES_PRE_PURCHASE_DEFAULT, 'resources')
        );
    }

    /**
     * List resources with optional filters
     *
     * @param ?QueryOptions $options
     * @return Resource[] Array of resources
     */
    public static function list(QueryOptions $options = null): array {
        $limit = '';
        $order = '';
        $params = [];
        $where = '';

        if ($options->limit) {
            $offset = 0;
            if ($options->page) {
                $offset = $options->page > 1 ? $options->limit * $options->page : 0;
            }
            $limit = "LIMIT $offset, $options->limit";
        }

        if ($options->order) {
            $orderBy = $options->orderDirection ?? '';
            $order = "ORDER BY $options->order $orderBy";
        }

        if ($options->where) {
            [$where, $params] = DB::makeWhere($options->where);
        }

        $resources = DB::getInstance()->query(
            <<<SQL
            SELECT *
            FROM nl2_resources
            $where
            $order
            $limit
            SQL,
            $params
        );

        return [];
    }

}
