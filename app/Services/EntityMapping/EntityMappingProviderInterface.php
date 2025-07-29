<?php

namespace App\Services\EntityMapping;

/**
 * Interface for entity mapping providers
 * 
 * This interface defines methods for mapping between entity classes, property names,
 * and entity values to their corresponding IDs in a knowledge base system like 
 * Wikidata or FactGrid.
 */
interface EntityMappingProviderInterface
{
    /**
     * Get the entity type ID for a given entity class
     * 
     * @param string $entityClass The fully qualified class name
     * @return string The entity type ID (e.g., 'Q5' for human in Wikidata)
     */
    public function getEntityTypeId(string $entityClass): string;
    
    /**
     * Get property ID for a given property name
     * 
     * @param string $propertyName The property name constant
     * @return string The property ID (e.g., 'P31' for instance-of in Wikidata)
     */
    public function getPropertyId(string $propertyName): string;
    
    /**
     * Get entity ID for a given entity value
     * 
     * @param string $entityValue The entity value constant
     * @return string The entity ID (e.g., 'Q2026714' for Victim of Nazism in Wikidata)
     */
    public function getEntityId(string $entityValue): string;
}