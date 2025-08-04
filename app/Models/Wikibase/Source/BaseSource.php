<?php

namespace App\Models\Wikibase\Source;

abstract class BaseSource
{
    protected $title;
    protected $description;
    protected $type;

    public function __construct(string $title, string $description = "")
    {
        $this->title = $title;
        $this->description = $description;
    }

    /**
     * Generate formatted reference data
     */
    abstract public function toReference(): array;

    /**
     * Generate source claim for entity
     */
    abstract public function toClaim(): array;

    /**
     * Get the title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get the description
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get the source type
     */
    public function getType(): string
    {
        return $this->type;
    }
    
    /**
     * Get the entity ID if this source is an entity
     * 
     * @return string|null The entity ID if this source is an entity, null otherwise
     */
    public function getEntityId(): ?string
    {
        // Base implementation returns null - override in subclasses for entities
        return null;
    }

    /**
     * Ensures that a URL has a valid scheme (http:// or https://).
     *
     * @param string $url The URL to check
     * @return string The URL with scheme
     */
    protected function ensureUrlHasScheme(string $url): string
    {
        // Trim URL
        $url = trim($url);

        // Check if URL already has a scheme
        if (preg_match("#^https?://#i", $url)) {
            return $url;
        }

        // If no scheme is present, add https://
        return "https://" . $url;
    }

    /**
     * Get formatted reference data with optional parent entity ID
     *
     * @param string|null $parentEntityId Optional parent entity ID for linking references
     * @return array Formatted reference data
     */
    public function getFormattedReference(?string $parentEntityId = null): array
    {
        // Get base reference data
        $reference = $this->toReference();

        // If parent entity ID is provided and it's a valid Wikidata ID,
        // add a reference to it
        if (!empty($parentEntityId) && substr($parentEntityId, 0, 1) === "Q") {
            if (!isset($reference["snaks"]["P248"])) {
                $reference["snaks"]["P248"] = [];
            }

            $reference["snaks"]["P248"][] = [
                "snaktype" => "value",
                "property" => "P248", // Stated in
                "datavalue" => [
                    "value" => [
                        "entity-type" => "item",
                        "id" => $parentEntityId,
                        "numeric-id" => intval(substr($parentEntityId, 1)),
                    ],
                    "type" => "wikibase-entityid",
                ],
            ];
        }

        return $reference;
    }

    /**
     * Formats a date into the format expected by Wikidata.
     *
     * @param string $date The date to format
     * @return array|null A formatted date for Wikidata or null in case of error
     */
    protected function formatDate(string $date): ?array
    {
        try {
            $dateTime = new \DateTime($date);
            $year = $dateTime->format("Y");
            $month = $dateTime->format("m");
            $day = $dateTime->format("d");

            return [
                "time" => "+{$year}-{$month}-{$day}T00:00:00Z",
                "timezone" => 0,
                "before" => 0,
                "after" => 0,
                "precision" => 11, // Day precision
                "calendarmodel" => "http://www.wikidata.org/entity/Q1985727", // Gregorian calendar
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}
