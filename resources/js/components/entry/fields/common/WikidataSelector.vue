<template>
    <div class="wikidata-selector">
        <!-- Input field with dropdown trigger -->
        <v-text-field
            v-model="displayValue"
            :label="label"
            readonly
            :disabled="disabled"
            append-inner-icon="mdi-magnify"
            @click="openDialog"
            @click:append-inner="openDialog"
            clearable
            @click:clear="clearSelection"
            variant="outlined"
        >
            <template v-slot:append>
                <v-chip
                    v-if="selectedItem"
                    size="small"
                    class="wikidata-id-chip"
                    variant="outlined"
                >
                    {{ selectedItem.id }}
                </v-chip>
            </template>
        </v-text-field>

        <!-- Dialog for selecting values -->
        <v-dialog v-model="dialogOpen" max-width="600px">
            <v-card>
                <v-card-title>{{ dialogTitle }}</v-card-title>
                <v-card-text>
                    <!-- Search field -->
                    <div class="search-container mb-4">
                        <v-text-field
                            v-model="searchQuery"
                            :label="searchLabel"
                            append-inner-icon="mdi-magnify"
                            @click:append-inner="performSearch"
                            @keydown.enter="performSearch"
                            variant="outlined"
                            clearable
                            :loading="isLoading"
                            autofocus
                        ></v-text-field>
                        <v-btn
                            color="primary"
                            class="ml-2"
                            @click="performSearch"
                            :disabled="!searchQuery.trim() || isLoading"
                            :loading="isLoading"
                        >
                            Suchen
                        </v-btn>
                    </div>

                    <!-- Loading indicator -->
                    <div
                        v-if="isLoading"
                        class="d-flex justify-center align-center pa-4"
                    >
                        <v-progress-circular
                            indeterminate
                            color="primary"
                        ></v-progress-circular>
                        <span class="ml-2">Suche läuft...</span>
                    </div>

                    <!-- No results message -->
                    <div
                        v-else-if="
                            searchPerformed && searchResults.length === 0
                        "
                        class="pa-4 text-center"
                    >
                        Keine Ergebnisse gefunden
                    </div>

                    <!-- Search results -->
                    <v-list
                        v-else-if="searchResults.length > 0"
                        density="compact"
                        class="search-results"
                    >
                        <v-list-item
                            v-for="item in searchResults"
                            :key="item.id"
                            @click="selectItem(item)"
                            class="search-result-item"
                        >
                            <v-list-item-title>{{
                                item.label
                            }}</v-list-item-title>
                            <v-list-item-subtitle v-if="item.description">{{
                                item.description
                            }}</v-list-item-subtitle>

                            <template v-slot:append>
                                <v-chip
                                    size="small"
                                    variant="text"
                                    color="grey"
                                >
                                    {{ item.id }}
                                </v-chip>
                            </template>
                        </v-list-item>
                    </v-list>
                </v-card-text>

                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey" @click="dialogOpen = false"
                        >Abbrechen</v-btn
                    >
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from "vue";
import axios from "axios";

const props = defineProps({
    modelValue: {
        type: [String, Object],
        default: null,
    },
    label: {
        type: String,
        required: true,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    dialogTitle: {
        type: String,
        default: "Wikidata-Eintrag auswählen",
    },
    searchLabel: {
        type: String,
        default: "Suchen...",
    },
    searchResults: {
        type: Array,
        default: () => [],
    },
    isLoading: {
        type: Boolean,
        default: false,
    },
    searchPerformed: {
        type: Boolean,
        default: false,
    },
    endpoint: {
        type: String,
        default: 'people',
        validator: (value) => ['people', 'places', 'cause', 'books', 'archives'].includes(value)
    },
});

const emit = defineEmits(["update:modelValue", "search"]);

// Local state
const dialogOpen = ref(false);
const searchQuery = ref("");
const entityLabels = ref({});
const loadingEntity = ref(false);

// Function to fetch entity label from Wikidata
const fetchEntityLabel = async (entityId) => {
    if (!entityId || entityLabels.value[entityId]) return;
    
    loadingEntity.value = true;
    
    try {
        // First try the local API
        const response = await axios.get(`/api/wikidata/entity/${entityId}`);
        if (response.data && response.data.label) {
            entityLabels.value[entityId] = {
                label: response.data.label,
                description: response.data.description || ''
            };
            return;
        }
        
        // If that fails, try the Wikidata API directly
        const wikidataResponse = await axios.get(
            `https://www.wikidata.org/w/api.php?action=wbgetentities&ids=${entityId}&format=json&languages=de|en&props=labels|descriptions&origin=*`
        );
        
        if (wikidataResponse.data && 
            wikidataResponse.data.entities && 
            wikidataResponse.data.entities[entityId]) {
            
            const entity = wikidataResponse.data.entities[entityId];
            let label = '';
            let description = '';
            
            // Try to get German label first, fall back to English
            if (entity.labels && entity.labels.de) {
                label = entity.labels.de.value;
            } else if (entity.labels && entity.labels.en) {
                label = entity.labels.en.value;
            } else {
                label = entityId; // Fallback to ID if no label found
            }
            
            // Try to get German description first, fall back to English
            if (entity.descriptions && entity.descriptions.de) {
                description = entity.descriptions.de.value;
            } else if (entity.descriptions && entity.descriptions.en) {
                description = entity.descriptions.en.value;
            }
            
            entityLabels.value[entityId] = {
                label: label,
                description: description
            };
        }
    } catch (error) {
        console.error('Error fetching entity label:', error);
    } finally {
        loadingEntity.value = false;
    }
};

// Computed properties
const selectedItem = computed(() => {
    if (!props.modelValue) return null;

    // If modelValue is an object with id property
    if (typeof props.modelValue === "object" && props.modelValue?.id) {
        return props.modelValue;
    }

    // If modelValue is a string (Wikidata ID)
    if (
        typeof props.modelValue === "string" &&
        props.modelValue.startsWith("Q")
    ) {
        // Find in search results
        const foundItem = props.searchResults.find(
            (item) => item.id === props.modelValue,
        );
        if (foundItem) return foundItem;

        // If not found in results, fetch the entity from the API
        if (!loadingEntity.value) {
            fetchEntityLabel(props.modelValue);
        }

        // If loading, return a temporary object with loading indicator
        if (loadingEntity.value) {
            return { id: props.modelValue, label: "Lädt..." };
        }

        // If we've fetched the label, use that
        if (entityLabels.value[props.modelValue]) {
            return { 
                id: props.modelValue, 
                label: entityLabels.value[props.modelValue].label,
                description: entityLabels.value[props.modelValue].description
            };
        }

        // If all else fails, just show the ID as a fallback
        return { id: props.modelValue, label: props.modelValue };
    }

    return null;
});

const displayValue = computed(() => {
    if (!selectedItem.value) return "";
    return selectedItem.value.label || selectedItem.value.id;
});

// Methods
const openDialog = () => {
    if (props.disabled) return;
    dialogOpen.value = true;
    searchQuery.value = "";
};

const performSearch = () => {
    if (!searchQuery.value.trim()) return;
    emit("search", searchQuery.value, props.endpoint);
};

const selectItem = (item) => {
    emit("update:modelValue", item.id);
    dialogOpen.value = false;
};

const clearSelection = () => {
    emit("update:modelValue", null);
};

// Watch for changes to modelValue and fetch the label if needed
watch(() => props.modelValue, (newValue) => {
    if (typeof newValue === 'string' && newValue.startsWith('Q') && !entityLabels.value[newValue]) {
        fetchEntityLabel(newValue);
    }
}, { immediate: true });

// When component mounts, fetch any missing entity labels
onMounted(() => {
    if (typeof props.modelValue === 'string' && 
        props.modelValue.startsWith('Q') && 
        !entityLabels.value[props.modelValue]) {
        fetchEntityLabel(props.modelValue);
    }
});
</script>

<style scoped>
.search-container {
    display: flex;
    align-items: center;
}

.search-result-item {
    cursor: pointer;
    transition: background-color 0.2s;
}

.search-result-item:hover {
    background-color: #f5f5f5;
}

.search-results {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #eee;
    border-radius: 4px;
}

.wikidata-id-chip {
    font-size: 0.7rem;
    color: #666;
}
</style>
