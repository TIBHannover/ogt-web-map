<template>
    <div v-if="qIds.length > 0" class="wikidata-suggestions">
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
                        {{ entity.id }}
                    </v-list-item-subtitle>
                </div>
                <div>
                    <a
                        :href="`https://www.wikidata.org/wiki/${entity.id}`"
                        target="_blank"
                        class="source-link"
                    >
                        <v-icon size="x-small mr-1">mdi-open-in-new</v-icon>
                        Wikidata
                    </a>
                </div>
            </v-list-item>
        </v-list>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";

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
    // Tu00e4terIn
    Q106625639: {
        id: "Q106625639",
        label: "Staatspolizeileitstelle Hannover",
        description: "Dienststelle der Geheimen Staatspolizei in Hannover",
    },
    // Ursache der Verfolgung
    Q1899269: {
        id: "Q1899269",
        label: "politische Verfolgung",
        description:
            "Verfolgung aufgrund politischer Überzeugungen oder Aktivitäten",
    },
    Q22649: {
        id: "Q22649",
        label: "Antisemitismus",
        description:
            "Judenfeindschaft im Kontext moderner Ideologien, insbesondere Nationalismus, Rassismus und Islamismus",
    },
    // You can add more predefined entities as needed
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
.wikidata-suggestions {
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
