<?php

namespace App\Services\EntityMapping;

/**
 * Wikidata-specific implementation of the entity mapping provider
 */
class WikidataEntityMappingProvider implements EntityMappingProviderInterface
{
    /**
     * Maps entity classes to Wikidata Q IDs
     *
     * @var array<string, string>
     */
    private $entityTypeMap = [
        "App\Models\Wikibase\Person" => "Q5", // human
        "App\Models\Wikibase\Event" => "Q6983405", // event
        "App\Models\Wikibase\Organization" => "Q43229", // organization
        "App\Models\Wikibase\Book" => "Q7725634", // literary work
        "App\Models\Wikibase\Structure" => "Q811979", // architectural structure
        "App\Models\Wikibase\Memorial" => "Q5003624", // memorial
        "App\Models\Wikibase\GestapoPlace" => "Q106996250", // Ort des Gestapoterrors
    ];

    /**
     * Maps property names to Wikidata P IDs
     *
     * @var array<string, string>
     */
    private $propertyMap = [
        // Common properties
        "INSTANCE_OF" => "P31",
        "COORDINATE_LOCATION" => "P625",
        "IMAGE" => "P18",

        // Person properties
        "GENDER" => "P21",
        "BIRTH_DATE" => "P569",
        "DEATH_DATE" => "P570",
        "PLACE_OF_BIRTH" => "P19",
        "PLACE_OF_DEATH" => "P20",
        "PLACE_OF_DETENTION" => "P2632",
        "PLACE_OF_RESIDENCE" => "P551",
        "OCCUPATION" => "P106",
        "SUBJECT_HAS_ROLE" => "P2868",
        "SIGNIFICANT_EVENT" => "P793",
        "COUNTRY_OF_CITIZENSHIP" => "P27",
        "BIRTH_NAME" => "P1477",
        "EMPLOYER" => "P108",

        // Book properties
        "TITLE" => "P1476",
        "AUTHOR" => "P50",
        "AUTHOR_STRING" => "P2093",
        "PUBLICATION_DATE" => "P577",

        // Organization properties
        "INCEPTION" => "P571",
        "DISSOLVED" => "P576",
        "HEADQUARTERS_LOCATION" => "P159",
        "OFFICIAL_NAME" => "P1448",

        // Structure properties
        "ARCHITECT" => "P84",
        "HEIGHT" => "P2048",

        // Event properties
        "POINT_IN_TIME" => "P585",
        "START_TIME" => "P580",
        "END_TIME" => "P582",
        "LOCATION" => "P276",
        "HAS_CAUSE" => "P828",
        "HAS_PARTICIPANT" => "P710",
        "PERPETRATOR" => "P8031",
        "VICTIM" => "P8032",
        "VICTIM_COUNT" => "P1590",
        "SURVIVOR_COUNT" => "P1561",
        "DEATH_COUNT" => "P1120",
        "ARREST_COUNT" => "P5582",
        "SOURCING_CIRCUMSTANCES" => "P1480",

        // Source properties
        "DESCRIBED_BY_SOURCE" => "P1343",
        "ARCHIVE" => "P485",
        "INVENTORY_NUMBER" => "P217",
        "PAGE" => "P304",
        "STATED_IN_URL" => "P973",
        "LANGUAGE" => "P407",
        "RETRIEVAL_DATE" => "P813",

        // Memorial properties
        "COMMEMORATES" => "P547",
        "OPERATOR" => "P137",
        "OPENING_DATE" => "P1619",
        "ADDRESS" => "P6375",
        "WEBSITE" => "P856",
        "QUALIFIER_OF" => "P805",
        "HAS_USE" => "P366",

        // GestapoPlace properties
        "FOUNDING_DATE" => "P571",
        "DISSOLUTION_DATE" => "P576",
        "PRISONER_COUNT" => "P5630",
        "PARENT_ORGANIZATION" => "P749",
        "CHILD_ORGANIZATION" => "P355",
        "PREDECESSOR" => "P1365",
        "SUCCESSOR" => "P1366",
        "SOURCING_CIRCUMSTANCES" => "P1480",
        "DIRECTOR" => "P1037",
        "EMPLOYEES_COUNT" => "P1128",
        "EMPLOYEES" => "P1830",
    ];

    /**
     * Maps entity values to Wikidata Q IDs
     *
     * @var array<string, string>
     */
    private $entityMap = [
        // Role values
        "VICTIM" => "Q2026714", // Victim of Nazism
        "PERPETRATOR" => "Q111080573", // Perpetrator of Nazism

        // Gender values
        "MALE" => "Q6581097", // male
        "FEMALE" => "Q6581072", // female
        "NON_BINARY" => "Q48270", // non-binary

        // Event types
        "NAZI_CRIME" => "Q6983405", // Nazi crime event

        // Add other entity values as needed
    ];

    /**
     * {@inheritdoc}
     */
    public function getEntityTypeId(string $entityClass): string
    {
        return $this->entityTypeMap[$entityClass] ?? "";
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyId(string $propertyName): string
    {
        return $this->propertyMap[$propertyName] ?? "";
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityId(string $entityValue): string
    {
        return $this->entityMap[$entityValue] ?? "";
    }
}
