<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Export Target
    |--------------------------------------------------------------------------
    |
    | This value determines which knowledge base system to use for entity exports.
    | Supported values: 'wikidata', 'factgrid'
    |
    */
    'target' => env('EXPORT_TARGET', 'wikidata'),
];