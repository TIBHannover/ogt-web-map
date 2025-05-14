<?php

return [
    'bot_username' => env('WIKIDATA_BOT_USERNAME'),
    'bot_password' => env('WIKIDATA_BOT_PASSWORD'),
    'api_url' => env('WIKIDATA_API_URL', 'https://test.wikidata.org/w/api.php'),
    'url' => env('WIKIDATA_URL', 'https://query.wikidata.org/sparql'),
    'debug' => env('WIKIDATA_DEBUG', false),
    'simulation_mode' => env('WIKIDATA_SIMULATION_MODE', false),
    
    // OAuth config (wenn verwendet)
    'oauth_consumer_key' => env('WIKIDATA_OAUTH_CONSUMER_KEY'),
    'oauth_consumer_secret' => env('WIKIDATA_OAUTH_CONSUMER_SECRET'),
    'oauth_callback_url' => env('WIKIDATA_OAUTH_CALLBACK_URL'),
];