<?php

return [
    /**
     * Debug mode for Wikibase operations
     * When true, no actual API calls are made to Wikibase services
     */
    'debug_mode' => env('WIKIBASE_DEBUG_MODE', true),
    
    /**
     * Target system for entity export
     * Valid values: 'wikidata', 'factgrid'
     */
    'export_target' => env('EXPORT_TARGET', 'wikidata'),
];