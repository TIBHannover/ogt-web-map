<template>
    <div v-if="qIds.length > 0" class="factgrid-suggestions">
        <div class="suggestions-header">
            <h4>Schnellzugriff:</h4>
        </div>
        <v-list density="compact" class="suggestions-list">
            <v-list-item
                v-for="entity in entities"
                :key="entity.id"
                @click="onSelect(entity)"
                class="suggestion-item"
            >
                <div>
                    <v-list-item-title>{{ entity.label }}</v-list-item-title>
                    <v-list-item-subtitle v-if="entity.description">
                        {{ entity.description }}
                    </v-list-item-subtitle>
                    <v-list-item-subtitle class="result-id">
                    </v-list-item-subtitle>
                </div>
                <div>
                    <a
                        :href="`https://database.factgrid.de/entity/${entity.id}`"
                        target="_blank"
                        class="source-link"
                    >
                        <v-icon size="x-small mr-1">mdi-open-in-new</v-icon>
                        Factgrid:
                        <span class="grey-darken-1">{{ entity.id }}</span>
                    </a>
                </div>
            </v-list-item>
        </v-list>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    qIds: {
        type: Array,
        default: () => [],
    },
    endpoint: {
        type: String,
        default: "entities",
    },
});

const emit = defineEmits(["select"]);

// Hardcoded suggestions data for specific Q-IDs
const qIdData = {
    // TäterIn
    Q1784661: {
        id: "Q1784661",
        label: "Täter des NS-Regimes",
        description: "Person, die am NS-Regime beteiligt war",
    },
    // Ursache der Verfolgung
    Q1784663: {
        id: "Q1784663",
        label: "Opfer des NS-Regimes",
        description: "Person, die vom NS-Regime verfolgt wurde",
    },
    // Staatspolizeistellen
    Q1060031: {
        id: "Q1060031",
        label: "Staatspolizeileitstelle Hannover",
        description:
            "Regionale Dienststelle der Gestapo in Hannover, verantwortlich für politische Verfolgung in mittleren und südlichen Teilen Niedersachsens.",
    },
    // Staatsangehörigkeit / Nationality
    Q140530: {
        id: "Q140530",
        label: "Deutschland",
        description: "Staat in Mitteleuropa",
    },
    // Ursache der Verfolgung
    Q1787386: {
        id: "Q1787386",
        label: "politische Verfolgung",
        description:
            "Verfolgung aufgrund politischer Überzeugungen oder Aktivitäten",
    },
    Q484817: {
        id: "Q484817",
        label: "Antisemitismus",
        description:
            "Judenfeindschaft im Kontext moderner Ideologien, insbesondere Nationalismus, Rassismus und Islamismus",
    },
};

// Filter entities based on provided Q-IDs
const entities = computed(() => {
    return props.qIds.filter((id) => qIdData[id]).map((id) => qIdData[id]);
});

const onSelect = (entity) => {
    emit("select", entity);
};
</script>

<style scoped>
.factgrid-suggestions {
    margin-top: 1rem;
    margin-bottom: 1.5rem;
}

.suggestions-header h4 {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 0.5rem;
}

.suggestions-list {
    background-color: white;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #eee;
}

.suggestion-item {
    cursor: pointer;
    transition: background-color 0.2s;
}

.suggestion-item:hover {
    background-color: #f5f5f5;
}

.result-id {
    font-size: 0.8rem;
    color: #888;
}
</style>
