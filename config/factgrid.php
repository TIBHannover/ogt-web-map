<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FactGrid API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for connecting to the FactGrid API.
    |
    */
    "api_url" => env(
        "FACTGRID_API_URL",
        "https://database.factgrid.de/api.php",
    ),

    /*
    |--------------------------------------------------------------------------
    | FactGrid SPARQL URL
    |--------------------------------------------------------------------------
    |
    | The URL of the FactGrid SPARQL endpoint.
    |
    */
    "url" => env("FACTGRID_URL", "https://database.factgrid.de/sparql"),

    /*
    |--------------------------------------------------------------------------
    | FactGrid Bot Username
    |--------------------------------------------------------------------------
    |
    | The username of the FactGrid bot account to use for API requests.
    |
    */
    "bot_username" => env("FACTGRID_BOT_USERNAME", ""),

    /*
    |--------------------------------------------------------------------------
    | FactGrid Bot Password
    |--------------------------------------------------------------------------
    |
    | The password of the FactGrid bot account to use for API requests.
    |
    */
    "bot_password" => env("FACTGRID_BOT_PASSWORD", ""),

    /*
    |--------------------------------------------------------------------------
    | FactGrid Simulation Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, FactGrid API requests will be simulated and no actual
    | changes will be made to FactGrid. This is useful for testing and
    | development.
    |
    */
    "simulation_mode" => env("FACTGRID_SIMULATION_MODE", false),

    /*
    |--------------------------------------------------------------------------
    | Edit Summary
    |--------------------------------------------------------------------------
    |
    | The summary to use for edits made to FactGrid. This appears in the edit
    | history on FactGrid.
    |
    */
    "edit_summary" => env("FACTGRID_EDIT_SUMMARY", "Adding data via OGT API"),

    /*
    |--------------------------------------------------------------------------
    | Use Queue System
    |--------------------------------------------------------------------------
    |
    | When enabled, entity creation will be processed through Laravel's queue
    | system to handle rate limiting gracefully.
    |
    */
    "use_queue" => env("FACTGRID_USE_QUEUE", true),

    /*
    |--------------------------------------------------------------------------
    | Max Retries for Rate Limited Operations
    |--------------------------------------------------------------------------
    |
    | The maximum number of times to retry operations that are rate limited.
    |
    */
    "max_retries" => env("FACTGRID_MAX_RETRIES", 3),

    /*
    |--------------------------------------------------------------------------
    | Initial Retry Delay (in seconds)
    |--------------------------------------------------------------------------
    |
    | The initial delay in seconds before retrying a rate-limited operation.
    | This value will be multiplied by 2^retry_count for exponential backoff.
    |
    */
    "initial_retry_delay" => env("FACTGRID_INITIAL_RETRY_DELAY", 5),

    /*
    |--------------------------------------------------------------------------
    | Callback URL
    |--------------------------------------------------------------------------
    |
    | Additional options for debugging and logging FactGrid operations.
    |
    */
    "callback_url" => env("FACTGRID_CALLBACK_URL"),

    /*
    |--------------------------------------------------------------------------
    | Cached Persons File
    |--------------------------------------------------------------------------
    |
    | The path to the file where the cached persons data is stored.
    |
    */
    "cachedPersonsFile" => env(
        "FACTGRID_CACHED_PERSONS_FILE",
        "factgrid/persons.json",
    ),

    /*
    |--------------------------------------------------------------------------
    | Cached Places File
    |--------------------------------------------------------------------------
    |
    | The path to the file where the cached places data is stored.
    |
    */
    "cachedPlacesFile" => env(
        "FACTGRID_CACHED_PLACES_FILE",
        "factgrid/places.json",
    ),
];
