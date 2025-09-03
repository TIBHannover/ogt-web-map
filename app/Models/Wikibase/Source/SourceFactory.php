<?php

namespace App\Models\Wikibase\Source;

class SourceFactory
{
    /**
     * Create a source model from array data
     *
     * @param array $sourceData The source data
     * @return BaseSource|null A source model or null if type is unsupported
     */
    public static function createFromArray(array $sourceData): ?BaseSource
    {
        if (empty($sourceData["type"])) {
            return null;
        }

        switch ($sourceData["type"]) {
            case "book":
                return BookSource::fromArray($sourceData);

            case "url":
                return UrlSource::fromArray($sourceData);

            case "archive":
                return ArchiveSource::fromArray($sourceData);

            default:
                return null;
        }
    }

    /**
     * Create source models from array of source data
     *
     * @param array $sourcesData Array of source data
     * @return array Array of source models
     */
    public static function createMultipleFromArray(array $sourcesData): array
    {
        $sources = [];

        foreach ($sourcesData as $sourceData) {
            $source = self::createFromArray($sourceData);
            if ($source !== null) {
                $sources[] = $source;
            }
        }

        return $sources;
    }
}
