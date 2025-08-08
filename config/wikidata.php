<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Wikidata API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for connecting to the Wikidata API.
    |
    */

    // API URL for Wikidata
    "api_url" => env("WIKIDATA_API_URL", "https://www.wikidata.org/w/api.php"),

    // SPARQL query endpoint
    "sparql_url" => env(
        "WIKIDATA_SPARQL_URL",
        "https://query.wikidata.org/sparql",
    ),

    // Sandbox API URL
    "sandbox_api_url" => env(
        "WIKIDATA_SANDBOX_API_URL",
        "https://test.wikidata.org/w/api.php",
    ),

    // Custom Wikibase URL (for using alternative Wikibase instances like FactGrid)
    "custom_wikibase_url" => env("CUSTOM_WIKIBASE_URL"),

    // Bot username
    "bot_username" => env("WIKIDATA_BOT_USERNAME", "GTOBot@GTOBot"),

    // Bot password
    "bot_password" => env("WIKIDATA_BOT_PASSWORD"),

    // Sandbox mode - use test.wikidata.org instead of www.wikidata.org
    "sandbox_mode" => env("WIKIDATA_SANDBOX_MODE", false),

    // Simulation mode - don't make actual API calls, just return fake entity IDs
    "simulation_mode" => env("WIKIDATA_SIMULATION_MODE", false),

    // Debug mode - enable logging and return more details
    "debug_mode" => env("WIKIDATA_DEBUG_MODE", false),

    // Maximum retries for rate-limited operations
    "max_retries" => env("WIKIDATA_MAX_RETRIES", 3),

    // Initial delay in seconds before retry
    "initial_retry_delay" => env("WIKIDATA_INITIAL_RETRY_DELAY", 5),

    // Edit summary to use for Wikidata edits
    "edit_summary" => env("WIKIDATA_EDIT_SUMMARY", "Adding data via OGT API"),

    // Request model class
    "request_model" => \App\Models\WikibaseEntryRequest::class,

    /*
    |--------------------------------------------------------------------------
    | Debugging and Logging Options
    |--------------------------------------------------------------------------
    |
    | Additional options for debugging and logging Wikidata operations.
    |
    */

    // Enable extra debugging output
    "debug_logging" => env("WIKIDATA_DEBUG_LOGGING", false),

    // Log full request and response details (can be very verbose)
    "log_full_requests" => env("WIKIDATA_LOG_FULL_REQUESTS", false),

    // Log entity creation data
    "log_entity_creation" => env("WIKIDATA_LOG_ENTITY_CREATION", false),

    // Log API URLs on initialization
    "log_api_urls" => env("WIKIDATA_LOG_API_URLS", false),

    // Verify entity IDs after creation
    "verify_entity_ids" => env("WIKIDATA_VERIFY_ENTITY_IDS", false),
];
