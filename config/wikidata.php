<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Wikidata API URL
    |--------------------------------------------------------------------------
    |
    | The URL of the Wikidata API endpoint.
    |
    */
    'api_url' => env('WIKIDATA_API_URL', 'https://www.wikidata.org/w/api.php'),

    /*
    |--------------------------------------------------------------------------
    | Wikidata SPARQL URL
    |--------------------------------------------------------------------------
    |
    | The URL of the Wikidata SPARQL endpoint.
    |
    */
    'url' => env('WIKIDATA_URL', 'https://query.wikidata.org/sparql'),

    /*
    |--------------------------------------------------------------------------
    | Wikidata Bot Username
    |--------------------------------------------------------------------------
    |
    | The username of the Wikidata bot account to use for API requests.
    |
    */
    'bot_username' => env('WIKIDATA_BOT_USERNAME', ''),

    /*
    |--------------------------------------------------------------------------
    | Wikidata Bot Password
    |--------------------------------------------------------------------------
    |
    | The password of the Wikidata bot account to use for API requests.
    |
    */
    'bot_password' => env('WIKIDATA_BOT_PASSWORD', ''),

    /*
    |--------------------------------------------------------------------------
    | Wikidata Simulation Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, Wikidata API requests will be simulated and no actual
    | changes will be made to Wikidata. This is useful for testing and
    | development.
    |
    */
    'simulation_mode' => env('WIKIDATA_SIMULATION_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Edit Summary
    |--------------------------------------------------------------------------
    |
    | The summary to use for edits made to Wikidata. This appears in the edit
    | history on Wikidata.
    |
    */
    'edit_summary' => env('WIKIDATA_EDIT_SUMMARY', 'Adding data via OGT API'),
    
    /*
    |--------------------------------------------------------------------------
    | Use Queue System
    |--------------------------------------------------------------------------
    |
    | When enabled, entity creation will be processed through Laravel's queue
    | system to handle rate limiting gracefully.
    |
    */
    'use_queue' => env('WIKIDATA_USE_QUEUE', true),
    
    /*
    |--------------------------------------------------------------------------
    | Max Retries for Rate Limited Operations
    |--------------------------------------------------------------------------
    |
    | The maximum number of times to retry operations that are rate limited.
    |
    */
    'max_retries' => env('WIKIDATA_MAX_RETRIES', 3),
    
    /*
    |--------------------------------------------------------------------------
    | Initial Retry Delay (in seconds)
    |--------------------------------------------------------------------------
    |
    | The initial delay in seconds before retrying a rate-limited operation.
    | This value will be multiplied by 2^retry_count for exponential backoff.
    |
    */
    'initial_retry_delay' => env('WIKIDATA_INITIAL_RETRY_DELAY', 5),
    
    /*
    |--------------------------------------------------------------------------
    | Callback URL
    |--------------------------------------------------------------------------
    |
    | Global callback URL to notify when entities are created. This is more
    | reliable than per-request callbacks because it can be configured in
    | environment variables and will be used for all entity creations.
    |
    */
   'callback_url' => env('WIKIDATA_CALLBACK_URL'),
   
   /*
    |--------------------------------------------------------------------------
    | Cached Persons File
    |--------------------------------------------------------------------------
    |
    | The path to the file where the cached persons data is stored.
    |
    */
   'cachedPersonsFile' => env('WIKIDATA_CACHED_PERSONS_FILE', 'wikidata/persons.json'),
];