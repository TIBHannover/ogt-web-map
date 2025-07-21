# Wikidata Entity Models

This directory contains models for Wikidata entities used by the OGT API. These models provide a structured approach to creating and managing Wikidata entities.

## Structure

```
app/Models/Wikidata/
├── BaseEntity.php                 # Abstract base class with common functionality
├── Person.php                     # Person entity model
│   ├── Victim.php                 # Extends Person with victim-specific claims
│   └── Perpetrator.php            # Extends Person with perpetrator-specific claims
├── Event.php                      # Event entity model
├── Organization.php               # Organization entity model
├── Structure.php                  # Architectural structure entity model
├── Book.php                       # Book entity model
├── EntityFactory.php              # Factory to create entity models
└── Source/                        # Source models
    ├── BaseSource.php             # Abstract base class for sources
    ├── BookSource.php             # Book source model
    ├── UrlSource.php              # URL source model
    ├── ArchiveSource.php          # Archive source model
    └── SourceFactory.php          # Factory to create source models
```

## Usage

### Creating Entities

To create an entity, use the `EntityFactory`:

```php
// Create an event
$event = EntityFactory::createFromArray($data, WikidataFormatter::ENTITY_TYPE_EVENT);

// Create a person with a specific role
$victim = EntityFactory::createPersonWithRole($data, WikidataFormatter::ROLE_VICTIM);
```

Or create them directly:

```php
// Create a book
$book = Book::fromArray($bookData);

// Create an organization
$organization = Organization::fromArray($organizationData);
```

### Working with Sources

To create source models:

```php
// Create multiple sources from data
$sources = SourceFactory::createMultipleFromArray($sourcesData);

// Create a single source
$urlSource = UrlSource::fromArray($urlData);
```

### Getting Formatted Data

All entity models have a `getFormattedData()` method that returns the data structure expected by the Wikidata API:

```php
$formattedData = $entity->getFormattedData();
```

## Integration with WikidataFormatter

The `WikidataFormatterModels` class is a bridge between these models and the original `WikidataFormatter` class. It gradually migrates functionality to use the new models while maintaining compatibility with the rest of the system.

```php
$formatter = new WikidataFormatterModels();
$result = $formatter->formatEntityData($data, WikidataFormatter::ENTITY_TYPE_PERSON);
```

## Extending

To add a new entity type:

1. Create a new class that extends `BaseEntity`
2. Implement the specific properties and methods for that entity type
3. Add a `fromArray()` static method to create the entity from array data
4. Update `EntityFactory` to handle the new entity type

## Notes

- All models follow a consistent interface for creation and data access
- Entity-specific logic is encapsulated in the respective model classes
- The factories provide a convenient way to create models from array data
- The models are immutable and use fluent interfaces for property setting