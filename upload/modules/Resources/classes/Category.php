<?php
/**
 * Category class
 *
 * @author Samerton
 * @license MIT
 */

namespace Resources\Classes;

class Category {

    /** @var string Default resource icon */
    private string $defaultResourceIcon;

    /** @var string Category description */
    private string $description;

    /** @var ?int Category ID */
    private ?int $id;

    /** @var string Category name */
    private string $name;

    /** @var int Category order */
    private int $order;

    /** @var int|null Number of resources in category */
    private ?int $resourceCount;

    /**
     * Convert category database row to Category instance
     *
     * @param mixed $row Row from resources_categories DB table
     * @return self|null
     */
    public function fromDB($row): ?self {
        if (
            isset(
                $row->description,
                $row->id,
                $row->name,
                $row->display_order
            )
        ) {
            $this->description = $row->description;
            $this->id = $row->id;
            $this->name = $row->name;
            $this->order = $row->display_order;

            $this->defaultResourceIcon = $row->default_resource_icon ?? RESOURCES_ICON_DEFAULT;

            if (isset($row->resource_count)) {
                $this->resourceCount = $row->resource_count;
            }

            return $this;
        }

        return null;
    }

    /**
     * Get category description
     *
     * @return string
     */
    public function getDescription(): string {
        return $this->description;
    }

    /**
     * Get category ID
     *
     * @return ?int
     */
    public function getId(): ?int {
        return $this->id;
    }

    /**
     * Get category name
     *
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Get category order
     *
     * @return int
     */
    public function getOrder(): int {
        return $this->order;
    }

    /**
     * Get category resource count
     *
     * @return ?int
     */
    public function getResourceCount(): ?int {
        return $this->resourceCount;
    }

    /**
     * Get default resource icon for this category
     *
     * @return string
     */
    public function getDefaultResourceIcon(): string {
        return $this->defaultResourceIcon;
    }
}
