<?php
/**
 * Example usage of the CreateWikidataEntity job with job chains
 * 
 * This example demonstrates how to use the new createEntityWithRelated method
 * to create a main entity and its related entities using job chains.
 * 
 * IMPORTANT: This approach replaces the old method of creating entities where the formatter
 * would try to create related entities directly. Now, each entity is created by its own
 * job in a chain, which is more reliable and avoids race conditions.
 */

// Example 1: Creating an event with victims and perpetrators
$eventData = [
    'title' => 'Example Historical Event',
    'description' => 'This is a test event for demonstration purposes',
    'date' => '1945-05-08',
    'location' => 'Berlin, Germany',
    
    // Victims to be created as separate entities
    'victims' => [
        [
            'name' => 'John Doe',
            'birthDate' => '1920-01-15',
            'deathDate' => '1945-05-01',
            'description' => 'Victim of historical event'
        ],
        [
            'name' => 'Jane Smith',
            'birthDate' => '1922-03-20',
            'description' => 'Another victim of historical event'
        ]
    ],
    
    // Perpetrators to be created as separate entities
    'perpetrators' => [
        [
            'name' => 'Example Perpetrator',
            'birthDate' => '1900-11-10',
            'deathDate' => '1946-10-16',
            'description' => 'Perpetrator of historical event'
        ]
    ],
    
    // Sources to be created as separate entities
    'sources' => [
        [
            'title' => 'Historical Source Book',
            'author' => 'Historian Name',
            'publicationDate' => '1990',
            'pages' => '145-150',
            'publisher' => 'Academic Press'
        ]
    ]
];

// Create the event and all related entities using job chains
// The main entity will be created first, then the related entities will be created
// in separate jobs that automatically link back to the main entity
App\Jobs\CreateWikidataEntity::createEntityWithRelated(
    $eventData,                            // Entity data
    App\Jobs\CreateWikidataEntity::ENTITY_TYPE_EVENT, // Entity type
    'https://example.com/webhook/callback', // Optional callback URL
    12345                                   // Optional request ID for database tracking
);

// Example 2: Creating a person with sources
$personData = [
    'title' => 'Example Historical Figure',
    'firstName' => 'Example',
    'lastName' => 'Figure',
    'birthDate' => '1900-01-01',
    'deathDate' => '1980-12-31',
    'description' => 'An example historical figure',
    
    // Sources to be created as separate entities
    'sources' => [
        [
            'title' => 'Biography of Example Figure',
            'author' => 'Biographer Name',
            'publicationDate' => '2000',
            'publisher' => 'Biography Press'
        ]
    ]
];

// Create the person and their sources using job chains
App\Jobs\CreateWikidataEntity::createEntityWithRelated(
    $personData,
    App\Jobs\CreateWikidataEntity::ENTITY_TYPE_PERSON,
    null, // No callback URL
    null  // No request ID
);

// NOTE: This is a fundamental change to how entity creation works.
// Previously, related entities were created directly by the formatter.
// Now, they are created by separate jobs in a chain, which is more reliable
// because it ensures parent entities exist before child entities are created.
// This avoids race conditions and missing parent IDs.