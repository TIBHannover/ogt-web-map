<?php

namespace App\Services\Wikidata;

use Illuminate\Support\Facades\Log;

class WikidataOptimizer
{
    /**
     *
     * @param array $data
     * @param string $operation
     * @return array
     */
    public function optimize(array $data, string $operation): array
    {
        $optimizedData = match ($operation) {
            "create" => $this->optimizeForCreate($data),
            "update" => $this->optimizeForUpdate($data),
            default => $data,
        };

        if (empty($optimizedData) || !is_array($optimizedData)) {
            Log::error(
                "WikidataOptimizer: Optimization resulted in invalid data",
                [
                    "operation" => $operation,
                    "result" => $optimizedData,
                ]
            );
        }

        if ($operation === "create") {
            if (
                !isset($optimizedData["labels"]) ||
                empty($optimizedData["labels"])
            ) {
                Log::error(
                    'WikidataOptimizer: Missing required "labels" field in optimized data'
                );
            }
            if (
                !isset($optimizedData["claims"]) ||
                empty($optimizedData["claims"]) ||
                !isset($optimizedData["claims"]["P31"])
            ) {
                Log::error(
                    "WikidataOptimizer: Missing required P31 claim in optimized data"
                );
            }
        }

        return $optimizedData;
    }

    /**
     *
     * @param array $data
     * @return array
     */
    private function optimizeForCreate(array $data): array
    {
        if (isset($data["id"])) {
            Log::info("WikidataOptimizer: Removing ID for create operation");
            unset($data["id"]);
        }

        $this->ensureRequiredFields($data);

        return $data;
    }

    /**
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
     *
     * @param array $data
     * @return array
     */
    private function optimizeForUpdate(array $data): array
    {
        if (!isset($data["id"])) {
            Log::warning("WikidataOptimizer: Missing ID for update operation");
        }

        return $data;
    }

    /**
     *
     * @param array &$data
     * @return void
     */
    private function ensureRequiredFields(array &$data): void
    {
        if (!isset($data["labels"]) || empty($data["labels"])) {
            Log::warning('WikidataOptimizer: Missing required field "labels"');
        }

        if (
            !isset($data["claims"]) ||
            empty($data["claims"]) ||
            !isset($data["claims"]["P31"])
        ) {
            Log::warning(
                "WikidataOptimizer: Missing required P31 statement (instance of)"
            );
        }
    }
}
