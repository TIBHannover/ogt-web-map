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
                            @keydown.enter.prevent="(e) => performSearch()"
                            variant="outlined"
                            clearable
                            :loading="isLoading"
                            ref="searchInputRef"
                        ></v-text-field>
                        <v-btn
                            color="primary"
                            class="ml-2"
                            @click="() => performSearch()"
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

                    <!-- Error message -->
                    <div
                        v-if="searchError"
                        class="error-message-container pa-4 mb-4"
                    >
                        <v-alert
                            type="warning"
                            variant="tonal"
                            density="compact"
                            class="mb-0"
                        >
                            {{ searchError }}
                        </v-alert>
                    </div>

                    <!-- Suggestions -->
                    <wikidata-suggestions
                        v-if="
                            props.suggestions &&
                            props.suggestions.length > 0 &&
                            !searchQuery.trim() &&
                            (!searchPerformed ||
                                (searchResults && searchResults.length === 0))
                        "
                        :q-ids="props.suggestions"
                        :endpoint="props.endpoint"
                        @select="selectItem"
                    ></wikidata-suggestions>

                    <!-- No results message -->
                    <div
                        v-else-if="
                            searchPerformed &&
                            searchQuery.trim() &&
                            searchResults &&
                            searchResults.length === 0 &&
                            !isLoading
                        "
                        class="pa-4 text-center"
                    >
                        Keine Ergebnisse gefunden
                    </div>

                    <!-- Search results -->
                    <v-list
                        v-else-if="
                            searchQuery.trim() &&
                            searchResults &&
                            searchResults.length > 0
                        "
                        density="compact"
                        class="search-results"
                    >
                        <v-list-item
                            v-for="(item, index) in searchResults || []"
                            :key="item && item.id ? item.id : 'error-' + index"
                            @click="item && !item.isError && selectItem(item)"
                            class="search-result-item"
                            :class="{ 'error-item': item && item.isError }"
                            :disabled="!item || (item && item.isError)"
                        >
                            <v-list-item-title>{{
                                item && item.label ? item.label : ""
                            }}</v-list-item-title>
                            <v-list-item-subtitle
                                v-if="item && item.description"
                                :class="{
                                    'error-message': item && item.isError,
                                }"
                                >{{ item.description }}</v-list-item-subtitle
                            >

                            <template v-slot:append>
                                <v-chip
                                    v-if="item && !item.isError && item.id"
                                    size="small"
                                    variant="text"
                                    color="grey"
                                >
                                    {{ item.id }}
                                </v-chip>
                                <v-icon
                                    v-else-if="item && item.isError"
                                    color="error"
                                    icon="mdi-alert-circle"
                                ></v-icon>
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
import WikidataSuggestions from "./WikidataSuggestions.vue";

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
        default: "people",
        validator: (value) =>
            [
                "people",
                "places",
                "cause",
                "books",
                "archives",
                "occupations",
                "nationalities",
            ].includes(value),
    },
    suggestions: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["update:modelValue", "search", "search-error"]);

// Local state
const dialogOpen = ref(false);
const searchQuery = ref("");
const entityLabels = ref({});
const loadingEntity = ref(false);
const searchError = ref(null);
const searchInputRef = ref(null);
let searchDebounceTimer = null;

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
                description: response.data.description || "",
            };
            return;
        }

        // If that fails, try the Wikidata API directly
        try {
            const wikidataResponse = await axios.get(
                `https://www.wikidata.org/w/api.php?action=wbgetentities&ids=${entityId}&format=json&languages=de|en&props=labels|descriptions&origin=*`,
            );

            if (
                wikidataResponse.data &&
                wikidataResponse.data.entities &&
                wikidataResponse.data.entities[entityId]
            ) {
                const entity = wikidataResponse.data.entities[entityId];
                let label = "";
                let description = "";

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
                    description: description,
                };
            }
        } catch (wikidataError) {
            console.error("Error accessing Wikidata API:", wikidataError);
            // Create a fallback entry with just the ID
            entityLabels.value[entityId] = {
                label: entityId,
                description: "Konnte Daten nicht laden",
            };
        }
    } catch (error) {
        console.error("Error fetching entity label:", error);
        // Create a fallback entry with just the ID
        entityLabels.value[entityId] = {
            label: entityId,
            description: "Fehler beim Laden",
        };
    } finally {
        loadingEntity.value = false;
    }
};

// Computed properties
const selectedItem = computed(() => {
    if (!props.modelValue) return null;

    // If modelValue is already a Q-number string
    if (
        typeof props.modelValue === "string" &&
        props.modelValue.startsWith("Q")
    ) {
        // Find in search results
        const foundItem =
            props.searchResults && Array.isArray(props.searchResults)
                ? props.searchResults.find(
                      (item) => item && item.id && item.id === props.modelValue,
                  )
                : null;
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
                description: entityLabels.value[props.modelValue].description,
            };
        }

        // If all else fails, just show the ID as a fallback
        return { id: props.modelValue, label: props.modelValue };
    }

    // Handle legacy format - if modelValue is an object with id property
    if (typeof props.modelValue === "object" && props.modelValue?.id) {
        // Convert to string format for backward compatibility
        return props.modelValue;
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
    searchError.value = null; // Clear any previous errors

    // Focus the search field after the dialog is fully rendered
    setTimeout(() => {
        if (searchInputRef.value && searchInputRef.value.$el) {
            const input = searchInputRef.value.$el.querySelector("input");
            if (input) input.focus();
        }
    }, 100);

    // Suggestions will be shown via the WikidataSuggestions component
    // No need to trigger a search here as the suggested items are shown in the template
};

const performSearch = () => {
    // Clear any existing debounce timer
    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
    }

    if (!searchQuery.value || !searchQuery.value.trim()) {
        return;
    }

    // Debounce the search to prevent multiple rapid calls
    searchDebounceTimer = setTimeout(() => {
        searchError.value = null; // Clear previous errors

        try {
            emit("search", searchQuery.value.trim(), props.endpoint);
        } catch (error) {
            console.error("Error in search operation:", error);

            // Set error message
            searchError.value =
                "Fehler bei der Suche. Bitte versuchen Sie es erneut.";

            // Add fallback error item to search results if there's an error during search emission
            const errorItem = {
                id: "error",
                label: "Fehler bei der Suche",
                description:
                    "Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.",
                isError: true,
            };

            if (
                !Array.isArray(props.searchResults) ||
                props.searchResults.length === 0
            ) {
                // Only show error if no results are available
                emit("search-error", errorItem);
            }
        }
    }, 300); // 300ms debounce
};

const selectItem = (item) => {
    // Guard against null or invalid items
    if (!item || !item.id) return;

    // Store only the Q-number as a string
    // This simplifies data structure and matches EventsForm format
    emit("update:modelValue", item.id);
    dialogOpen.value = false;
};

const clearSelection = () => {
    emit("update:modelValue", null);
};

// Watch for changes to modelValue and fetch the label if needed
watch(
    () => props.modelValue,
    (newValue) => {
        if (
            typeof newValue === "string" &&
            newValue.startsWith("Q") &&
            !entityLabels.value[newValue]
        ) {
            fetchEntityLabel(newValue);
        }
    },
    { immediate: true },
);

// Watch for isLoading changes to clear error when loading starts
watch(
    () => props.isLoading,
    (newValue) => {
        if (newValue === true) {
            searchError.value = null;
        }
    },
);

// Watch for search results to clear error when results arrive
watch(
    () => props.searchResults,
    (newValue) => {
        if (newValue && Array.isArray(newValue) && newValue.length > 0) {
            searchError.value = null;
        }
    },
);

// Watch for dialog opening/closing
watch(
    () => dialogOpen.value,
    (newValue) => {
        if (newValue === false) {
            // Clear search state when dialog closes
            if (searchDebounceTimer) {
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = null;
            }
            searchError.value = null;
            searchQuery.value = "";
        } else {
            // When dialog opens, focus on search field after a short delay
            setTimeout(() => {
                if (searchInputRef.value && searchInputRef.value.$el) {
                    const input =
                        searchInputRef.value.$el.querySelector("input");
                    if (input) input.focus();
                }
            }, 300);
        }
    },
);

// When component mounts, fetch any missing entity labels
onMounted(() => {
    if (
        typeof props.modelValue === "string" &&
        props.modelValue.startsWith("Q") &&
        !entityLabels.value[props.modelValue]
    ) {
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
}

.search-result-item.error-item {
    cursor: not-allowed;
    background-color: rgba(244, 67, 54, 0.05);
}

.error-message {
    color: #f44336;
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

.error-message-container {
    border-left: 3px solid #fb8c00;
    background-color: #fff3e0;
    margin: 8px 0;
    border-radius: 4px;
}

.v-overlay__content {
    border-radius: 8px;
    overflow: hidden;
}

/* Ensure the search input is properly visible */
.search-container .v-field__input {
    min-height: 44px !important;
}
</style>
