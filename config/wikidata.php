<?php

return [
    "cachedPersonsFile" => "persons.json",
    "url" => "https://query.wikidata.org/sparql",

    // Wikidata Bot configuration
    "bot_username" => env("WIKIDATA_BOT_USERNAME", ""),
    "bot_password" => env("WIKIDATA_BOT_PASSWORD", ""),
    "api_url" => env("WIKIDATA_API_URL", "https://test.wikidata.org/w/api.php"),
];
