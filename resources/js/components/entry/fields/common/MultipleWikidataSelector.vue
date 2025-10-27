<template>
    <div class="multiple-wikidata-selector">
        <!-- Header with label and add button -->
        <div class="selector-header">
            <h3 class="selector-title">{{ label }}</h3>
            <v-btn
                color="primary"
                size="small"
                class="add-button"
                @click="openDialog"
                prepend-icon="mdi-plus"
                variant="outlined"
            >
                Hinzufügen
            </v-btn>
        </div>

        <!-- Selected items display -->
        <div v-if="selectedItems.length > 0" class="selected-chips-container">
            <v-chip
                v-for="item in selectedItems"
                :key="item.id"
                size="small"
                closable
                class="mb-1 mr-1"
                @click:close="removeItem(item)"
            >
                {{ item.label }}
                <span class="wikidata-id-chip">{{ item.id }}</span>
            </v-chip>
        </div>

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
                            searchPerformed &&
                            filteredSearchResults.length === 0
                        "
                        class="pa-4 text-center"
                    >
                        Keine Ergebnisse gefunden
                    </div>

                    <!-- Search results -->
                    <v-list
                        v-else-if="filteredSearchResults.length > 0"
                        density="compact"
                        class="search-results"
                    >
                        <v-list-item
                            v-for="item in filteredSearchResults"
                            :key="item.id"
                            @click="selectItemAndClose(item)"
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

                <v-divider></v-divider>

                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey" @click="dialogOpen = false"
                        >Abbrechen</v-btn
                    >
                    <v-btn color="primary" @click="confirmSelection"
                        >Bestätigen</v-btn
                    >
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from "vue";
import axios from "axios";

// Create a silent axios instance that won't log 422 errors to console
const silentAxios = axios.create();

// Add response interceptor to silence specific errors
silentAxios.interceptors.response.use(
    (response) => response,
    (error) => {
        // Convert to a silent error that won't pollute the console
        return Promise.reject({
            ...error,
            isHandled: true,
        });
    },
);

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
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
        default: "Wikidata-Einträge auswählen",
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
        default: "places",
        validator: (value) =>
            [
                "people",
                "places",
                "cause",
                "books",
                "archives",
                "nationalities",
            ].includes(value),
    },
    maxSelections: {
        type: Number,
        default: 0, // 0 means unlimited
    },
});

const emit = defineEmits(["update:modelValue", "search"]);

// Local state
const dialogOpen = ref(false);
const searchQuery = ref("");
const entityLabels = ref({});
const loadingEntities = ref(false);
const tempSelectedItems = ref([]); // For storing selections temporarily during dialog session

// Filter search results to hide already selected items
const filteredSearchResults = computed(() => {
    return props.searchResults;
});

// Function to fetch entity labels from Wikidata for multiple IDs
const fetchEntityLabels = async (entityIds) => {
    if (!entityIds || entityIds.length === 0) return;

    // Create a Set to track failed entity IDs if it doesn't exist
    if (!window.failedEntityIds) {
        window.failedEntityIds = new Set();
    }

    // Filter out IDs that are either already fetched or known to fail
    const missingIds = entityIds.filter(
        (id) => !entityLabels.value[id] && !window.failedEntityIds.has(id),
    );

    if (missingIds.length === 0) return;

    loadingEntities.value = true;

    try {
        // For each missing ID, set a default value first to prevent multiple fetches
        missingIds.forEach((id) => {
            entityLabels.value[id] = {
                id: id,
                label: id, // Use ID as default label
                description: "",
            };
        });

        // Batch requests to API
        const chunks = [];
        for (let i = 0; i < missingIds.length; i += 50) {
            chunks.push(missingIds.slice(i, i + 50));
        }

        for (const chunk of chunks) {
            const ids = chunk.join("|");

            try {
                // Try the local API first, silently handle errors
                try {
                    const response = await silentAxios.get(
                        `/api/wikidata/entities?ids=${ids}`,
                    );
                    if (response.data && response.data.entities) {
                        for (const [id, entity] of Object.entries(
                            response.data.entities,
                        )) {
                            entityLabels.value[id] = {
                                id: id,
                                label: entity.label || id,
                                description: entity.description || "",
                            };
                        }
                    }
                } catch (error) {
                    // Mark these IDs as failed to prevent repeated requests
                    if (error.response && error.response.status === 422) {
                        // Split the IDs string back into an array
                        ids.split("|").forEach((id) => {
                            window.failedEntityIds.add(id.trim());
                        });
                    }
                }
            } catch (error) {}
        }
    } catch (error) {
    } finally {
        loadingEntities.value = false;
    }
};

// Get item objects with complete information from IDs
const selectedItems = computed(() => {
    const modelValue = Array.isArray(props.modelValue) ? props.modelValue : [];

    // If model value contains objects, return them directly
    if (modelValue.length > 0 && typeof modelValue[0] === "object") {
        return modelValue;
    }

    // If model value contains IDs, convert to objects
    return modelValue
        .map((id) => {
            // If ID is already an object with id property
            if (typeof id === "object" && id?.id) {
                return id;
            }

            // If it's a string (Wikidata ID)
            if (typeof id === "string" && id.startsWith("Q")) {
                // Find in search results
                const foundItem = props.searchResults.find(
                    (item) => item.id === id,
                );
                if (foundItem) return foundItem;

                // If we've fetched the label, use that
                if (entityLabels.value[id]) {
                    return {
                        id: id,
                        label: entityLabels.value[id].label,
                        description: entityLabels.value[id].description,
                    };
                }

                // If all else fails, just show the ID as a fallback
                return { id: id, label: id };
            }

            return null;
        })
        .filter(Boolean); // Remove any null values
});

// Methods
const openDialog = () => {
    if (props.disabled) return;

    // Initialize temp selections with current selections
    tempSelectedItems.value = [...selectedItems.value];

    dialogOpen.value = true;
    searchQuery.value = "";
};

// New method to select an item and close the dialog immediately
const selectItemAndClose = (item) => {
    // Check if already selected
    if (!isItemSelected(item)) {
        // Add item if under max limit
        if (
            props.maxSelections === 0 ||
            tempSelectedItems.value.length < props.maxSelections
        ) {
            tempSelectedItems.value.push(item);
            // Update with just IDs to ensure compatibility with backend
            updateModelValue();
        }
    }
    dialogOpen.value = false;
};

const performSearch = () => {
    if (!searchQuery.value.trim()) return;
    emit("search", searchQuery.value, props.endpoint);
};

const isItemSelected = (item) => {
    return tempSelectedItems.value.some((selected) => selected.id === item.id);
};

const selectItem = (item) => {
    // Toggle selection
    if (isItemSelected(item)) {
        // Remove item if already selected
        tempSelectedItems.value = tempSelectedItems.value.filter(
            (selected) => selected.id !== item.id,
        );
    } else {
        // Add item if not selected and under max limit (if specified)
        if (
            props.maxSelections === 0 ||
            tempSelectedItems.value.length < props.maxSelections
        ) {
            tempSelectedItems.value.push(item);
        }
    }
};

const removeItem = (item) => {
    tempSelectedItems.value = tempSelectedItems.value.filter(
        (selected) => selected.id !== item.id,
    );

    // If dialog is closed, also update the model value
    if (!dialogOpen.value) {
        updateModelValue();
    }
};

const confirmSelection = () => {
    updateModelValue();
    dialogOpen.value = false;
};

const updateModelValue = () => {
    // Extract just the IDs for the model value to ensure compatibility with backend
    const ids = tempSelectedItems.value.map((item) => item.id);
    emit("update:modelValue", ids);
};

// Watch for changes to modelValue and fetch the labels if needed
watch(
    () => props.modelValue,
    (newValue) => {
        if (Array.isArray(newValue)) {
            const ids = newValue
                .filter(
                    (item) => typeof item === "string" && item.startsWith("Q"),
                )
                .filter((id) => !entityLabels.value[id]);

            if (ids.length > 0) {
                fetchEntityLabels(ids);
            }
        }
    },
    { immediate: true, deep: true },
);

// When component mounts, fetch any missing entity labels
onMounted(() => {
    if (Array.isArray(props.modelValue)) {
        const ids = props.modelValue
            .filter((item) => typeof item === "string" && item.startsWith("Q"))
            .filter((id) => !entityLabels.value[id]);

        if (ids.length > 0) {
            fetchEntityLabels(ids);
        }
    }
});
</script>

<style scoped>
.multiple-wikidata-selector {
    margin-bottom: 16px;
}

.selector-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.selector-title {
    font-size: 1rem;
    font-weight: 500;
    margin: 0;
}

.add-button {
    white-space: nowrap;
}

.selected-chips-container {
    margin-top: 8px;
    display: flex;
    flex-wrap: wrap;
}

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

.search-result-selected {
    background-color: rgba(33, 150, 243, 0.08);
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
    margin-left: 4px;
}

.add-more-chip {
    cursor: pointer;
}

.selected-summary {
    max-height: 150px;
    overflow-y: auto;
}

.selected-items-list {
    max-height: 120px;
    overflow-y: auto;
}

.selected-item-row {
    padding: 4px 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.selected-item-row:last-child {
    border-bottom: none;
}
</style>
