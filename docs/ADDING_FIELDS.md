# Adding Fields to the OGT API System

This guide provides a comprehensive overview of all places where code needs to be modified when adding a new field to the OGT API system. This is especially useful when adding new person attributes or entity properties that should be sent to Wikidata.

## Table of Contents

1. [Database Layer](#1-database-layer)
2. [Frontend Form Components](#2-frontend-form-components)
3. [Wikibase Formatter (Backend)](#3-wikibase-formatter-backend)
4. [Admin Interface](#4-admin-interface)
5. [Testing the Integration](#5-testing-the-integration)
6. [Complete Data Workflow](#6-complete-data-workflow)

## 1. Database Layer

### A. Create a Migration File

```bash
# Example: Adding a 'hobby' field
php artisan make:migration add_hobby_field_to_wikidata_entry_requests --table=wikidata_entry_requests
```

**Example Migration File Structure:**

```php
// database/migrations/YYYY_MM_DD_timestamp_add_hobby_field_to_wikidata_entry_requests.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // For SQLite
            $columns = DB::select("PRAGMA table_info(wikidata_entry_requests)");
            $existingColumns = array_map(fn($col) => $col->name, $columns);

            if (!in_array('hobby', $existingColumns)) {
                DB::statement("ALTER TABLE wikidata_entry_requests ADD COLUMN hobby TEXT NULL");
            }
        } else {
            // For MySQL and others
            Schema::table('wikidata_entry_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('wikidata_entry_requests', 'hobby')) {
                    $table->text('hobby')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            Schema::table('wikidata_entry_requests', function (Blueprint $table) {
                $table->dropColumn('hobby');
            });
        }
        // SQLite doesn't support dropping columns without recreating the table
    }
};
```

### B. Update the Model

Add the field to the `$fillable` array in `app/Models/WikibaseEntryRequest.php`:

```php
protected $fillable = [
    // Existing fields...
    "hobby", // New field
];

// If the field is an array/JSON (for multiselect fields):
protected $casts = [
    // Existing casts...
    "hobby" => "array", // For multiselect fields
];
```

## 2. Frontend Form Components

### A. Update the Person Form Component

Modify `resources/js/components/entry/fields/Persons.vue`:

```vue
<!-- Template section: Add the new field input -->
<wikidata-selector 
    v-model="newPersonLocal.hobby"
    label="Hobby"
    dialog-title="Hobby auswählen"
    search-label="Hobby suchen..."
    :search-results="wikidataSearchResults.hobby || []"
    :is-loading="isWikidataSearchLoading.hobby"
    :search-performed="wikidataSearchPerformed.hobby"
    endpoint="hobbies" <!-- Define an appropriate endpoint -->
    @search="searchWikidataItems('hobby', $event)"
></wikidata-selector>

<!-- For multiselect fields, use MultipleWikidataSelector -->
<multiple-wikidata-selector
    v-model="newPersonLocal.hobbies"
    label="Hobbies"
    dialog-title="Hobbies auswählen"
    search-label="Hobby suchen..."
    :search-results="wikidataSearchResults.hobbies || []"
    :is-loading="isWikidataSearchLoading.hobbies"
    :search-performed="wikidataSearchPerformed.hobbies"
    endpoint="hobbies"
    @search="searchWikidataItems('hobbies', $event)"
></multiple-wikidata-selector>
```

```javascript
// Script section: Update data structures
// 1. Add to default person object
const props = defineProps({
    // ...
    defaultPerson: {
        default: () => ({
            // Existing fields...
            hobby: null,           // Single-select
            hobbies: [],           // Multi-select
        }),
    },
    // ...
});

// 2. Add to search results tracking
const wikidataSearchResults = ref({
    // Existing fields...
    hobby: [],
    hobbies: [],
});

const isWikidataSearchLoading = ref({
    // Existing fields...
    hobby: false,
    hobbies: false,
});

const wikidataSearchPerformed = ref({
    // Existing fields...
    hobby: false,
    hobbies: false,
});

// 3. Update edit function to include the new field
const onEditPerson = (person) => {
    // ...
    newPersonLocal.value = {
        // Existing fields...
        hobby: person.hobby || null,
        hobbies: person.hobbies || [],
    };
    // ...
};

// 4. Update search function field mapping
const searchWikidataItems = (field, query) => {
    // ...
    switch (field) {
        // Existing cases...
        case "hobby":
        case "hobbies":
            apiEndpoint = "hobbies"; // Define an appropriate API endpoint
            break;
    }
    // ...
    
    // Update display names
    const fieldDisplayNames = {
        // Existing fields...
        hobby: "Hobby",
        hobbies: "Hobbies",
    };
    // ...
};
```

### B. Create or Update API Endpoints

If the field requires a new data source endpoint (like "hobbies"), update or create a controller:

```php
// app/Http/Controllers/WikidataPropertyController.php

// Add a new method for the endpoint
public function searchHobbies(Request $request)
{
    $query = $request->input('query');
    
    // Use an appropriate Wikidata SPARQL query for hobbies
    $sparqlQuery = <<<SPARQL
    SELECT ?item ?itemLabel ?itemDescription WHERE {
      ?item wdt:P31 wd:Q159538.  # Instance of hobby
      SERVICE wikibase:label { bd:serviceParam wikibase:language "de,en". }
      FILTER(CONTAINS(LCASE(?itemLabel), LCASE("$query")))
    }
    LIMIT 25
    SPARQL;
    
    $results = $this->searchWikidata($sparqlQuery);
    return response()->json($results);
}
```

Then update routes in `routes/api.php`:

```php
// Add the new endpoint
Route::get('/wikidata/hobbies', 'WikidataPropertyController@searchHobbies');
```

## 3. Wikibase Formatter (Backend)

### A. Update Person Model

In `app/Models/Wikibase/Person.php`:

```php
// 1. Add protected property
protected $hobby;

// 2. Add setter method for single value
public function setHobby(string $hobbyId): void
{
    $this->hobby = $hobbyId;

    if (empty($hobbyId) || substr($hobbyId, 0, 1) !== "Q") {
        return;
    }

    $hobbyProperty = $this->mappingProvider->getPropertyId("HOBBY"); // Map to appropriate Wikidata property

    $this->data["claims"][$hobbyProperty] = [
        [
            "mainsnak" => [
                "snaktype" => "value",
                "property" => $hobbyProperty,
                "datavalue" => [
                    "value" => [
                        "entity-type" => "item",
                        "id" => $hobbyId,
                        "numeric-id" => intval(substr($hobbyId, 1)),
                    ],
                    "type" => "wikibase-entityid",
                ],
            ],
            "type" => "statement",
            "rank" => "normal",
        ],
    ];
}

// 3. For multiselect fields, use this pattern instead:
public function setHobbies($hobbyIds): void
{
    $hobbyProperty = $this->mappingProvider->getPropertyId("HOBBIES");

    // Initialize the array if it doesn't exist
    if (!isset($this->data["claims"][$hobbyProperty])) {
        $this->data["claims"][$hobbyProperty] = [];
    }

    // Handle both single ID and array of IDs
    $hobbyIdArray = is_array($hobbyIds) ? $hobbyIds : [$hobbyIds];

    foreach ($hobbyIdArray as $hobbyId) {
        if (empty($hobbyId) || substr($hobbyId, 0, 1) !== "Q") {
            continue;
        }

        // Add the hobby claim
        $this->data["claims"][$hobbyProperty][] = [
            "mainsnak" => [
                "snaktype" => "value",
                "property" => $hobbyProperty,
                "datavalue" => [
                    "value" => [
                        "entity-type" => "item",
                        "id" => $hobbyId,
                        "numeric-id" => intval(substr($hobbyId, 1)),
                    ],
                    "type" => "wikibase-entityid",
                ],
            ],
            "type" => "statement",
            "rank" => "normal",
        ];
    }
}

// 4. Update fromArray method to handle the new field
public static function fromArray(array $data): self
{
    // ...existing code...
    
    // Single-select field
    if (isset($data["hobby"])) {
        $person->setHobby($data["hobby"]);
    }
    
    // Multi-select field
    if (isset($data["hobbies"])) {
        $person->setHobbies($data["hobbies"]);
    }
    
    // ...existing code...
    
    return $person;
}
```

### B. Update WikibaseFormatterModels

In `app/Services/Wikibase/WikibaseFormatterModels.php`:

```php
// Make sure the field is included in preparePersonData
protected function preparePersonData(array $entity, BaseEntity $parentEntity, string $role): array
{
    // ...existing code...
    
    // Return formatted person data
    return [
        // ...existing fields...
        "hobby" => $entity["hobby"] ?? null,
        "hobbies" => $entity["hobbies"] ?? [],
        // ...
    ];
}
```

### C. Update EntityMappingProvider

In `app/Services/EntityMapping/EntityMappingProvider.php`:

```php
// Add the new property to the mapping
protected function initProperties(): void
{
    // ...existing properties...
    
    // Add the new Wikidata property ID mapping
    $this->properties["HOBBY"] = "P2283";  // "uses" property as example
    $this->properties["HOBBIES"] = "P2283"; // Same property for multi-value
}
```

## 4. Admin Interface

### A. Update the person-entry.blade.php Partial

Ensure `resources/views/admin/partials/person-entry.blade.php` properly handles the new field, especially if it can be an array:

```php
<!-- Update the wikidataFields array -->
@php
$wikidataFields = ['gender', 'place_of_birth', 'place_of_death', 'place_of_detention', 'nationality', 'employer', 'hobby', 'hobbies'];
@endphp

<!-- Make sure array handling is in place for multiselect fields -->
@if(is_array($person[$field]) && !isset($person[$field]['id']))
    @if(count($person[$field]) > 0)
        <div class="d-inline-block">
            @foreach($person[$field] as $index => $item)
                <!-- Render each item -->
                @if($index < count($person[$field]) - 1), @endif
            @endforeach
        </div>
    @else
        <span class="text-muted">-</span>
    @endif
@endif
```

### B. Update WikidataPropertyController Translation

In `app/Http/Controllers/WikidataPropertyController.php`:

```php
// Add the field translation for admin display
public static function translatePropertyLabel($key)
{
    $translations = [
        // ...existing translations...
        'hobby' => 'Hobby',
        'hobbies' => 'Hobbies',
    ];
    
    return $translations[$key] ?? $key;
}
```

## 5. Testing the Integration

To verify that the new field is properly integrated:

1. **Database Migration**: Run the migration to add the field
   ```bash
   php artisan migrate
   ```

2. **Form Input**: Test that you can input the field in the entry form

3. **Database Storage**: Check that the field is saved in the database
   ```sql
   SELECT hobby FROM wikidata_entry_requests WHERE id = ?;
   ```

4. **Admin View**: Verify the field displays correctly in the admin interface

5. **Wikidata Submission**: Confirm that the field is included in the data sent to Wikidata
   ```bash
   php artisan test:request-formatter <id>
   ```

## 6. Complete Data Workflow

When adding a new field, the data flows through the system in this order:

1. **User Input**: User enters data in the frontend form (`Persons.vue`)
2. **Form Validation**: Data is validated before submission
3. **API Submission**: Form data is sent to the API endpoint
4. **Database Storage**: Data is saved in the `wikidata_entry_requests` table
5. **Admin Review**: Admin reviews and potentially edits the data
6. **Admin Approval**: Admin approves the entry for submission
7. **Job Processing**: A background job processes the approved entry
8. **WikibaseFormatter**: The formatter prepares the data for Wikidata
9. **Person Model**: The model creates the proper Wikibase claims
10. **Wikidata API**: Data is submitted to the Wikidata API
11. **Status Update**: Entry status is updated to reflect the submission result

## Common Pitfalls and Solutions

### Duplicate Method Declarations
- Be careful when adding new methods to existing classes
- PHP will throw a fatal error if methods are declared twice
- Always check for existing method declarations when adding new ones

### Array Handling in Blade Templates
- Always check if a field is an array before outputting it with `{{ $field }}`
- Use `is_array()` and proper looping to display array values
- Consider using `json_encode()` as a fallback for complex data types

### Field Type Consistency
- Be consistent with field types (string vs array)
- Update the model's `$casts` property for JSON/array fields
- Make sure the UI components match the expected data type

## Conclusion

Adding a new field to the OGT API system requires changes at multiple levels of the application, from the database to the frontend interface. Following this guide systematically ensures that the new field is properly integrated throughout the entire data flow.

For any questions or assistance, please contact the development team.