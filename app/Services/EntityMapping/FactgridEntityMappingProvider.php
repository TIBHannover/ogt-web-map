<?php

namespace App\Services\EntityMapping;

/**
 * FactGrid-specific implementation of the entity mapping provider
 */
class FactgridEntityMappingProvider implements EntityMappingProviderInterface
{
    /**
     * Maps entity classes to FactGrid Q IDs
     *
     * @var array<string, string>
     */
    private $entityTypeMap = [
        // These are placeholder values - replace with actual FactGrid Q IDs
        "App\Models\Wikibase\Person" => "Q5", // human
        "App\Models\Wikibase\Event" => "Q6983405", // event
        "App\Models\Wikibase\Organization" => "Q43229", // organization
        "App\Models\Wikibase\Book" => "Q7725634", // literary work
        "App\Models\Wikibase\Structure" => "Q811979", // architectural structure
        "App\Models\Wikibase\Memorial" => "Q5003624", // memorial
        "App\Models\Wikibase\GestapoPlace" => "Q106996250", // Ort des Gestapoterrors
    ];

    /**
     * Maps property names to FactGrid P IDs
     *
     * @var array<string, string>
     */
    private $propertyMap = [
        // Common properties
        "INSTANCE_OF" => "P2",
        "COORDINATE_LOCATION" => "P48",
        "IMAGE" => "P189",
        "GENDER" => "P154",
        "BIRTH_DATE" => "P77",
        "DEATH_DATE" => "P38",
        "PLACE_OF_BIRTH" => "P82",
        "PLACE_OF_DEATH" => "P168",
        "PLACE_OF_DETENTION" => "P216",
        "PLACE_OF_RESIDENCE" => "P83",
        "OCCUPATION" => "P165",
        "SUBJECT_HAS_ROLE" => "P277",
        "SIGNIFICANT_EVENT" => "P137",
        "COUNTRY_OF_CITIZENSHIP" => "P616",
        "BIRTH_NAME" => "P34",
        "EMPLOYER" => "P315",
        "TITLE" => "P11",
        "AUTHOR" => "P21",
        "AUTHOR_STRING" => "P21",
        "PUBLICATION_DATE" => "P222",
        "INCEPTION" => "P49",
        "DISSOLVED" => "P50",
        "HEADQUARTERS_LOCATION" => "P83",
        "OFFICIAL_NAME" => "P34",
        "ARCHITECT" => "P552",
        "HEIGHT" => "P401",
        "POINT_IN_TIME" => "P106",
        "START_TIME" => "P49",
        "END_TIME" => "P50",
        "LOCATION" => "P47",
        "HAS_CAUSE" => "P464",
        "HAS_PARTICIPANT" => "P133",
        "PERPETRATOR" => "P277",
        "VICTIM" => "P277",
        "VICTIM_COUNT" => "P1164",
        "SURVIVOR_COUNT" => "P1164",
        "DEATH_COUNT" => "P1164",
        "ARREST_COUNT" => "P1164",
        "SOURCING_CIRCUMSTANCES" => "P155",
        "DESCRIBED_BY_SOURCE" => "P146",
        "ARCHIVE" => "P185",
        "INVENTORY_NUMBER" => "P10",
        "PAGE" => "P54",
        "STATED_IN_URL" => "P146",
        "LANGUAGE" => "P18",
        "RETRIEVAL_DATE" => "P432",
        "COMMEMORATES" => "P550",
        "OPERATOR" => "P534",
        "OPENING_DATE" => "P49",
        "ADDRESS" => "P153",
        "WEBSITE" => "P156",
        "QUALIFIER_OF" => "P155",
        "HAS_USE" => "P280",
        "FOUNDING_DATE" => "P49",
        "DISSOLUTION_DATE" => "P50",
        "PRISONER_COUNT" => "P1164",
        "PARENT_ORGANIZATION" => "P319",
        "CHILD_ORGANIZATION" => "P419",
        "PREDECESSOR" => "P7",
        "SUCCESSOR" => "P7",
        "EMPLOYEES_COUNT" => "P1164",
        "EMPLOYEES" => "P315",
        "DIRECTOR" => "P14",
        "FAMILY_NAME" => "P247",
        "GIVEN_NAME" => "P248",
        "PART_OF" => "P8",
        "HAS_PARTS" => "P9",
        "START_POINT" => "P95",
        "DESTINATION_POINT" => "P47",
        "SIGNIFICANT_PLACE" => "P47"
    ];

    /**
     * Maps entity values to FactGrid Q IDs
     *
     * @var array<string, string>
     */
    private $entityMap = [
        // Role values (using same Q-numbers as Wikidata for now)
        "VICTIM" => "Q2026714", // Victim of Nazism
        "PERPETRATOR" => "Q111080573", // Perpetrator of Nazism

        // Gender values (using same Q-numbers as Wikidata for now)
        "MALE" => "Q6581097", // male
        "FEMALE" => "Q6581072", // female
        "NON_BINARY" => "Q48270", // non-binary

        // Event types (using same Q-numbers as Wikidata for now)
        "NAZI_CRIME" => "Q6983405", // Nazi crime event
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
