<?php
/**
 * Resource class
 *
 * @author Samerton
 * @license MIT
 */

namespace Resources\Classes;

use DateTime;
use Exception;
use Resources\Classes\Categories;
use Resources\Classes\Types\ResourceType;
use User;

class Resource {

    /** @var Category Resource category */
    private Category $category;

    /** @var DateTime Resource created date/time */
    private DateTime $created;

    /** @var User Resource creator */
    private User $creator;

    /** @var string Resource description */
    private string $description;

    /** @var ?int Resource download count */
    private ?int $downloadCount;

    /** @var ?string Path to resource icon */
    private ?string $icon;

    /** @var ?int Resource ID */
    private ?int $id;

    /** @var ?string Resource latest version */
    private ?string $latestVersion;

    /** @var string Resource name */
    private string $name;

    /** @var ?float Resource price */
    private ?float $price;

    /** @var ?int Resource rating */
    private ?int $rating;

    /** @var ?int Resource sale percentage */
    private ?int $sale;

    /** @var string Resource short description */
    private string $shortDescription;

    /** @var string Type of resource */
    private string $type;

    /** @var DateTime Resource last updated date/time */
    private DateTime $updated;

    /** @var ?int Resource view count */
    private ?int $viewCount;

    public function __construct() {

    }

    /**
     * Convert resource database row to Resource instance
     *
     * @param mixed $row Row from resources DB table
     * @return self|null
     *
     * @throws Exception If invalid created/updated provided
     */
    public function fromDB($row): ?self {
        if (
            isset(
                $row->id,
                $row->category_id,
                $row->creator_id,
                $row->name,
                $row->short_description,
                $row->description,
                $row->created,
                $row->updated,
                $row->type
            )
        ) {
            $this->id = $row->id;
            $this->category = Categories::findById($row->category_id);
            $this->creator = new User($row->creator_id);
            $this->name = $row->name;
            $this->shortDescription = $row->short_description;
            $this->description = $row->description;
            $this->created = DateTime::createFromFormat('U', $row->created);
            $this->updated = DateTime::createFromFormat('U', $row->updated);

            $this->icon = $row->icon ?? $this->category->getDefaultResourceIcon();

            switch ($row->type) {
                case ResourceType::PREMIUM:
                    $this->type = ResourceType::PREMIUM;
                    break;

                case ResourceType::SUBSCRIPTION:
                    $this->type = ResourceType::SUBSCRIPTION;
                    break;

                default:
                    $this->type = ResourceType::FREE;
                    break;
            }

            if ($row->downloads) {
                $this->downloadCount = $row->downloads;
            }

            if ($row->latestVersion) {
                $this->latestVersion = $row->latestVersion;
            }

            if ($row->price_dec) {
                $this->price = $row->price_dec;
            } else if ($row->price) {
                $this->price = floatval($row->price);
            }

            if ($row->rating) {
                $this->rating = $row->rating;
            }

            if ($row->sale) {
                $this->sale = $row->sale;
            }

            if ($row->views) {
                $this->viewCount = $row->views;
            }

            return $this;
        }

        return null;
    }

    /**
     * Get resource category
     *
     * @return Category
     */
    public function getCategory(): Category {
        return $this->category;
    }

    /**
     * Get resource created date/time
     *
     * @return DateTime
     */
    public function getCreated(): DateTime {
        return $this->created;
    }

    /**
     * Get resource creator
     *
     * @return User
     */
    public function getCreator(): User {
        return $this->creator;
    }

    /**
     * Get resource description
     *
     * @return string
     */
    public function getDescription(): string {
        return $this->description;
    }

    /**
     * Get resource download count
     *
     * @return ?int
     */
    public function getDownloadCount(): ?int {
        return $this->downloadCount;
    }

    /**
     * Get resource icon
     *
     * @return ?string
     */
    public function getIcon(): ?string {
        return $this->icon;
    }

    /**
     * Get resource ID
     *
     * @return ?int
     */
    public function getId(): ?int {
        return $this->id;
    }

    /**
     * Get latest version of resource
     *
     * @return ?string
     */
    public function getLatestVersion(): ?string {
        return $this->latestVersion;
    }

    /**
     * Get resource name
     *
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Get resource price
     *
     * @return ?float
     */
    public function getPrice(): ?float {
        if ($this->type === ResourceType::PREMIUM) {
            // TODO: customise precision
            return round($this->price, 2);
        }

        return null;
    }

    /**
     * Get resource rating
     *
     * @return ?int
     */
    public function getRating(): ?int {
        return $this->rating;
    }

    /**
     * Get resource sale percentage
     *
     * @return ?int
     */
    public function getSalePct(): ?int {
        return $this->sale;
    }

    /**
     * Get resource sale price
     *
     * @return ?float
     */
    public function getSalePrice(): ?float {
        if ($this->type === ResourceType::PREMIUM) {
            $price = $this->price;

            if ($this->sale) {
                // TODO: customise precision
                $price = $this->price * ($this->sale / 100);
            }

            return round($price, 2);
        }

        return null;
    }

    /**
     * Get resource short description
     *
     * @return string
     */
    public function getShortDescription(): string {
        return $this->shortDescription;
    }

    /**
     * Get resource type
     *
     * @return string
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * Get resource last updated date/time
     *
     * @return DateTime
     */
    public function getUpdated(): DateTime {
        return $this->updated;
    }

    /**
     * Get resource view count
     *
     * @return ?int
     */
    public function getViewCount(): ?int {
        return $this->viewCount;
    }

}
