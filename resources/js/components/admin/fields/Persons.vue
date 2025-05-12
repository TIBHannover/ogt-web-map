<template>
    <v-card variant="outlined" class="mb-3">
        <v-list lines="false">
            <v-list-item
                v-for="(person, index) in persons"
                :key="'person-' + index"
            >
                <!-- Format für Personen ohne ID (manuelle Eingabe) -->
                <template v-if="!person.id">
                    <v-list-item-title>
                        {{ person.firstName }} {{ person.lastName }}
                    </v-list-item-title>
                    <v-list-item-subtitle>
                        <div v-if="person.birthDate">
                            <strong>Geburtsdatum:</strong> {{ formatDateDay(person.birthDate) }}
                        </div>
                        <div v-if="person.deathDate">
                            <strong>Todesdatum:</strong> {{ formatDateDay(person.deathDate) }}
                        </div>
                        <div v-if="person.birthPlace">
                            <strong>Geburtsort:</strong> {{ person.birthPlace }}
                        </div>
                        <div v-if="person.rank">
                            <strong>Rang/Position:</strong> {{ person.rank }}
                        </div>
                    </v-list-item-subtitle>
                </template>
                
                <!-- Format für Personen mit ID (aus Wikidata) -->
                <template v-else-if="person.id || person.wikidataId">
                    <v-list-item-title>
                        {{ person.label || person.name || "Unbekannter Name" }}
                    </v-list-item-title>
                    <v-list-item-subtitle>
                        <div v-if="person.birth || person.birthDate">
                            <strong>Geburtsdatum:</strong> {{ formatDateDay(person.birth || person.birthDate) }}
                        </div>
                        <div v-if="person.death || person.deathDate">
                            <strong>Todesdatum:</strong> {{ formatDateDay(person.death || person.deathDate) }}
                        </div>
                        <div v-if="person.description">
                            <strong>Beschreibung:</strong> {{ person.description }}
                        </div>
                        <div v-if="person.birthPlace">
                            <strong>Geburtsort:</strong> {{ person.birthPlace }}
                        </div>
                        <div v-if="person.rank">
                            <strong>Rang/Position:</strong> {{ person.rank }}
                        </div>
                        <div v-if="person.id || person.wikidataId">
                            <strong>Wikidata ID:</strong>
                            <a
                                :href="getWikidataUrl(person.id || person.wikidataId)"
                                target="_blank"
                            >
                                {{ person.id || person.wikidataId }}
                            </a>
                        </div>
                    </v-list-item-subtitle>
                </template>
                
                <!-- Format für einfache Strings oder unbekannte Formate -->
                <template v-else>
                    {{ person }}
                </template>
            </v-list-item>
        </v-list>
    </v-card>
</template>

<script setup>
import { defineProps } from 'vue';

// Eigenschaften definieren
const props = defineProps({
    persons: {
        type: Array,
        required: true
    }
});

// Hilfsfunktionen
const formatDateDay = (dateString) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    return new Intl.DateTimeFormat("de-DE", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric"
    }).format(date);
};

const getWikidataUrl = (id) => {
    return `https://www.wikidata.org/wiki/${id}`;
};
</script>