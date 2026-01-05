<template>
    <div class="factgrid-selector">
        <!-- Field with selected value or empty field -->
        <v-text-field
            :modelValue="displayValue"
            :label="label"
            readonly
            :disabled="disabled"
            variant="outlined"
            @click="openDialog"
            v-bind="$attrs"
            clearable
            clear-icon="mdi-close-circle"
            @click:clear.stop="clearSelection"
        >
            <template v-slot:append-inner>
                <v-icon
                    v-if="selectedItem"
                    :icon="'mdi-magnify'"
                    @click.stop="openDialog"
                />
                <v-icon v-else :icon="'mdi-magnify'" @click.stop="openDialog" />
            </template>
        </v-text-field>

        <!-- If selected, show chip with ID and link -->
        <div v-if="selectedItem" class="mt-1 selected-item-info">
            <v-chip
                size="small"
                label
                link
                :href="`https://database.factgrid.de/entity/${selectedItem.id}`"
                target="_blank"
            >
                <v-icon start size="x-small">mdi-open-in-new</v-icon>
                Factgrid:
                <span class="wikidata-id-chip">{{ selectedItem.id }}</span>
            </v-chip>
        </div>

        <!-- Dialog for search and selection -->
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
                            @keydown.down="focusFirstResult"
                            variant="outlined"
                            ref="searchInputRef"
                            clearable
                            :loading="isLoading"
                            autofocus
                        ></v-text-field>
                    </div>

                    <!-- Suggestions from props -->
                    <FactgridSuggestions
                        :q-ids="suggestions"
                        :endpoint="endpoint"
                        @select="selectItem"
                    />

                    <!-- Error message if search failed -->
                    <div
                        v-if="searchError"
                        class="error-message-container mb-4"
                    >
                        <v-alert
                            type="error"
                            variant="tonal"
                            density="compact"
                            class="mb-0"
                        >
                            {{ searchError }}
                        </v-alert>
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
                        ref="resultsList"
                    >
                        <v-list-item
                            v-for="(item, index) in searchResults"
                            :key="item.id || index"
                            @click="selectItem(item)"
                            :class="[
                                'search-result-item',
                                { 'error-item': item.isError },
                            ]"
                            :tabindex="index + 1"
                            @keydown.enter="selectItem(item)"
                        >
                            <v-list-item-title>{{
                                item.label
                            }}</v-list-item-title>
                            <v-list-item-subtitle v-if="item.description">{{
                                item.description
                            }}</v-list-item-subtitle>
                            <template v-slot:append>
                                <v-chip
                                    v-if="item.id && !item.isError"
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

                <v-divider></v-divider>

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
import FactgridSuggestions from "./FactgridSuggestions.vue";

const props = defineProps({
    modelValue: {
        type: [String, Object, Array],
        default: null,
    },
    field: {
        type: String,
        default: "",
    },
    asArray: {
        type: Boolean,
        default: false,
    },
    label: {
        type: String,
        default: "",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    dialogTitle: {
        type: String,
        default: "Factgrid-Eintrag auswählen",
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
                "commemorates",
                "organizations",
                "quantity",
                "services",
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
const resultsList = ref(null);
let searchDebounceTimer = null;

// Function to fetch entity label from Factgrid
const fetchEntityLabel = async (entityId) => {
    if (!entityId || entityLabels.value[entityId]) return;

    loadingEntity.value = true;

    try {
        // First try the local API
        const response = await axios.get(`/api/factgrid/entity/${entityId}`);
        if (response.data && response.data.label) {
            entityLabels.value[entityId] = {
                label: response.data.label,
                description: response.data.description || "",
            };
            return;
        }

        // If that fails, create a fallback entry
        entityLabels.value[entityId] = {
            label: entityId,
            description: "Konnte Daten nicht laden",
        };
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
        if (!loadingEntity.value && !entityLabels.value[props.modelValue]) {
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

    // Suggestions will be shown via the FactgridSuggestions component
};

const focusFirstResult = () => {
    if (resultsList.value && props.searchResults.length > 0) {
        const firstItem = resultsList.value.$el.querySelector(
            ".search-result-item",
        );
        if (firstItem) firstItem.focus();
    }
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

    // If asArray prop is true, emit as array, otherwise as string
    if (props.asArray) {
        emit("update:modelValue", [item.id]);
    } else {
        // Store only the Q-number as a string
        emit("update:modelValue", item.id);
    }
    dialogOpen.value = false;
};

const clearSelection = () => {
    if (props.asArray) {
        emit("update:modelValue", []);
    } else {
        emit("update:modelValue", null);
    }
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
        if (!newValue) {
            // Dialog was closed, reset the search
            searchQuery.value = "";
        }
    },
);

// When component mounts, initialize if needed
onMounted(() => {
    if (
        props.modelValue &&
        typeof props.modelValue === "string" &&
        props.modelValue.startsWith("Q") &&
        !entityLabels.value[props.modelValue]
    ) {
        fetchEntityLabel(props.modelValue);
    }
});
</script>

<style scoped>
.factgrid-selector {
    position: relative;
    margin-bottom: 16px;
}

.search-container {
    display: flex;
}

.search-result-item {
    cursor: pointer;
    transition: background-color 0.2s;
}

.search-result-item.error-item {
    background-color: rgba(244, 67, 54, 0.05);
    cursor: not-allowed;
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
    font-size: 0.75rem;
    color: #757575;
}

.error-message-container {
    background-color: rgba(244, 67, 54, 0.05);
    border-radius: 4px;
    border: 1px solid rgba(244, 67, 54, 0.2);
    margin-bottom: 1rem;
}

.v-overlay__content {
    display: flex;
    flex-direction: column;
    max-width: 600px;
}

.search-container .v-field__input {
    padding-top: 0;
    padding-bottom: 0;
}
</style>
