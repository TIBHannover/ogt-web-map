<?php

namespace App\Services\Wikibase;

use Illuminate\Support\Facades\Log;
use App\Services\EntityMapping\EntityMappingProviderInterface;

class WikibaseOptimizer
{
    /**
     * @var EntityMappingProviderInterface
     */
    protected $mappingProvider;

    /**
     * Constructor
     *
     * @param EntityMappingProviderInterface $mappingProvider
     */
    public function __construct(EntityMappingProviderInterface $mappingProvider)
    {
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * Optimize entity data for a specific operation
     *
     * @param array $data
     * @param string $operation
     * @return array
     */
    public function optimize(array $data, string $operation): array
    {
        // Get the instance of property ID from the mapping provider
        $instanceOfProperty = $this->mappingProvider->getPropertyId(
            "INSTANCE_OF",
        );

        // Log the input data before optimization
        Log::debug("WikibaseOptimizer: Input data before optimization", [
            "operation" => $operation,
            "claims_count" => isset($data["claims"])
                ? count($data["claims"])
                : 0,
            "claim_keys" => isset($data["claims"])
                ? array_keys($data["claims"])
                : [],
            "has_instance_of" => isset($data["claims"][$instanceOfProperty])
                ? "yes"
                : "no",
        ]);

        $optimizedData = match ($operation) {
            "create" => $this->optimizeForCreate($data),
            "update" => $this->optimizeForUpdate($data),
            default => $data,
        };

        // Process language settings for all operations
        $optimizedData = $this->processLanguageSettings($optimizedData);

        if (empty($optimizedData) || !is_array($optimizedData)) {
            Log::error(
                "WikibaseOptimizer: Optimization resulted in invalid data",
                [
                    "operation" => $operation,
                    "result" => $optimizedData,
                ],
            );
        }

        if ($operation === "create") {
            if (
                !isset($optimizedData["labels"]) ||
                empty($optimizedData["labels"])
            ) {
                Log::error(
                    'WikibaseOptimizer: Missing required "labels" field in optimized data',
                );
            }
            if (
                !isset($optimizedData["claims"]) ||
                empty($optimizedData["claims"]) ||
                !isset($optimizedData["claims"][$instanceOfProperty])
            ) {
                Log::error(
                    "WikibaseOptimizer: Missing required instance-of claim in optimized data",
                );
            }
        }

        // Log the output data after optimization
        Log::debug("WikibaseOptimizer: Output data after optimization", [
            "operation" => $operation,
            "claims_count" => isset($optimizedData["claims"])
                ? count($optimizedData["claims"])
                : 0,
            "claim_keys" => isset($optimizedData["claims"])
                ? array_keys($optimizedData["claims"])
                : [],
            "has_instance_of" => isset(
                $optimizedData["claims"][$instanceOfProperty],
            )
                ? "yes"
                : "no",
        ]);

        return $optimizedData;
    }

    /**
     * Optimize data for create operation
     *
     * @param array $data
     * @return array
     */
    private function optimizeForCreate(array $data): array
    {
        if (isset($data["id"])) {
            Log::info("WikibaseOptimizer: Removing ID for create operation");
            unset($data["id"]);
        }

        // Remove 'type' field which causes API errors
        if (isset($data["type"])) {
            Log::info(
                "WikibaseOptimizer: Removing 'type' field for API compatibility",
            );
            unset($data["type"]);
        }

        // Remove 'title' and 'description' fields which should only exist in labels/descriptions
        if (isset($data["title"])) {
            Log::info(
                "WikibaseOptimizer: Removing 'title' field for API compatibility",
            );
            unset($data["title"]);
        }

        if (isset($data["description"])) {
            Log::info(
                "WikibaseOptimizer: Removing 'description' field for API compatibility",
            );
            unset($data["description"]);
        }

        $this->ensureRequiredFields($data);

        return $data;
    }

    /**
     * Check if a string looks like a URL
     *
     * @param string $str
     * @return bool
     */
    private function looksLikeUrl(string $str): bool
    {
        return preg_match("/^[a-z0-9]+(\.[a-z0-9]+)+/i", $str) &&
            !preg_match("/\s/", $str);
    }

    /**
     * Optimize data for update operation
     *
     * @param array $data
     * @return array
     */
    private function optimizeForUpdate(array $data): array
    {
        // For update operations, we should NOT include the ID in the data
        // The ID is sent separately in the API request parameters
        if (isset($data["id"])) {
            Log::info(
                "WikibaseOptimizer: Removing ID from data payload for update operation, it will be sent as a separate parameter",
            );
            unset($data["id"]);
        }

        // Remove 'type' field which causes API errors
        if (isset($data["type"])) {
            Log::info(
                "WikibaseOptimizer: Removing 'type' field for API compatibility",
            );
            unset($data["type"]);
        }

        return $data;
    }

    /**
     * Ensure required fields are present in the data
     *
     * @param array &$data
     * @return void
     */
    private function ensureRequiredFields(array &$data): void
    {
        if (!isset($data["labels"]) || empty($data["labels"])) {
            Log::warning('WikibaseOptimizer: Missing required field "labels"');
        }

        $instanceOfProperty = $this->mappingProvider->getPropertyId(
            "INSTANCE_OF",
        );

        if (
            !isset($data["claims"]) ||
            empty($data["claims"]) ||
            !isset($data["claims"][$instanceOfProperty])
        ) {
            Log::warning(
                "WikibaseOptimizer: Missing required instance-of statement",
            );
        }
    }

    /**
     * Process language settings to ensure only German labels are used
     *
     * @param array $data
     * @return array
     */
    private function processLanguageSettings(array $data): array
    {
        // Only process if we have labels
        if (!isset($data["labels"]) || empty($data["labels"])) {
            return $data;
        }

        // If we have a German label, keep only that one
        if (isset($data["labels"]["de"])) {
            $germanLabel = $data["labels"]["de"];

            // Remove all non-German labels
            foreach (array_keys($data["labels"]) as $langCode) {
                if ($langCode !== "de") {
                    unset($data["labels"][$langCode]);
                }
            }

            Log::info(
                "WikibaseOptimizer: Using only German label for Wikidata",
                [
                    "label" => $germanLabel["value"] ?? "(empty)",
                ],
            );
        }

        // For descriptions, also keep only German
        if (isset($data["descriptions"]) && !empty($data["descriptions"])) {
            if (isset($data["descriptions"]["de"])) {
                $germanDescription = $data["descriptions"]["de"];

                // Remove all non-German descriptions
                foreach (array_keys($data["descriptions"]) as $langCode) {
                    if ($langCode !== "de") {
                        unset($data["descriptions"][$langCode]);
                    }
                }

                Log::info(
                    "WikibaseOptimizer: Using only German description for Wikidata",
                    [
                        "description" =>
                            $germanDescription["value"] ?? "(empty)",
                    ],
                );
            }
        }

        return $data;
    }
}
