<template>
    <div class="search-overlay">
        <div class="overlay-content">
            <!-- Overlay Header -->
            <div class="overlay-header">
                <h3>{{ props.title || "Person suchen oder anlegen" }}</h3>
                <button
                    type="button"
                    class="close-overlay"
                    @click="handleClose"
                >
                    <v-icon>mdi-close</v-icon>
                </button>
            </div>

            <!-- Overlay Body -->
            <div class="overlay-body-wrapper">
                <div class="overlay-body">
                    <!-- Search Input -->
                    <div class="search-input-container">
                        <v-text-field
                            v-model="searchQuery"
                            placeholder="Namen der Person suchen..."
                            append-inner-icon="mdi-magnify"
                            @click:append-inner="performSearch"
                            @keydown.enter="performSearch"
                            clearable
                            :loading="isLoading"
                            class="person-overlay-search-field"
                            autofocus
                        ></v-text-field>
                        <v-btn
                            color="primary"
                            class="ml-3 search-button"
                            @click="performSearch"
                            :disabled="!searchQuery.trim()"
                            :loading="isLoading"
                        >
                            Suchen
                        </v-btn>
                    </div>

                    <!-- Info Box -->
                    <div
                        class="search-info-box"
                        v-if="
                            searchQuery.trim() &&
                            searchPerformed &&
                            searchResults.length === 0
                        "
                    >
                        <div class="info-icon-wrapper">
                            <v-icon>mdi-information-outline</v-icon>
                        </div>
                        <p>
                            Keine existierende Person mit diesem Namen gefunden.
                            Sie können diese Person neu anlegen.
                        </p>
                    </div>

                    <!-- Search Results -->
                    <div v-if="searchResults.length > 0" class="search-results">
                        <h4>Suchergebnisse:</h4>
                        <div
                            v-for="result in searchResults"
                            :key="result.id"
                            class="search-result-item"
                            @click="selectExistingPerson(result)"
                        >
                            <div class="result-title">
                                {{ result.label }}
                                <v-chip
                                    v-if="result.isOrganization"
                                    size="small"
                                    color="primary"
                                    class="ml-2"
                                >
                                    Organisation
                                </v-chip>
                            </div>
                            <div
                                class="result-description"
                                v-if="result.description"
                            >
                                {{ result.description }}
                            </div>
                            <div
                                class="result-metadata"
                                v-if="result.birth || result.death"
                            >
                                <span v-if="result.birth"
                                    >* {{ result.birth }}</span
                                >
                                <span v-if="result.birth && result.death">
                                    |
                                </span>
                                <span v-if="result.death"
                                    >† {{ result.death }}</span
                                >
                            </div>
                            <div class="result-actions">
                                <div class="result-id">{{ result.id }}</div>
                                <button
                                    v-if="result.id.startsWith('Q')"
                                    class="open-wikidata-button"
                                    @click.stop="openWikidata(result.id)"
                                >
                                    <v-icon size="small"
                                        >mdi-open-in-new</v-icon
                                    >
                                    Auf Wikidata öffnen
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Loading Indicator -->
                    <div v-if="isLoading" class="loading">
                        <v-progress-circular indeterminate color="primary" />
                        <span>Suche läuft...</span>
                    </div>

                    <!-- Checkbox - erscheint nur nach durchgeführter Suche -->
                    <div
                        v-if="searchPerformed && !isLoading"
                        class="confirmation-checkbox"
                    >
                        <input
                            type="checkbox"
                            id="confirm-new-entry"
                            v-model="noExistingEntry"
                        />
                        <label for="confirm-new-entry"
                            >Es gibt keine vorhandene Person mit diesem
                            Namen</label
                        >
                    </div>

                    <div class="button-group">
                        <button
                            class="button bg-secondary"
                            @click="$emit('close')"
                        >
                            Abbrechen
                        </button>
                        <button
                            class="button create-button"
                            :disabled="
                                !searchQuery.trim() ||
                                isLoading ||
                                !searchPerformed ||
                                !noExistingEntry
                            "
                            @click="createPerson"
                        >
                            <v-icon size="small" class="me-1">mdi-plus</v-icon>
                            {{ getCreateButtonText() }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, inject } from "vue";
import axios from "axios";

const props = defineProps({
    title: {
        type: String,
        default: "Person suchen oder anlegen",
    },
    entryType: {
        type: String,
        default: "Q5", // Default to Person (Q5)
    },
    context: {
        type: String,
        default: "create",
    },
    initialName: {
        type: String,
        default: "",
    },
});

const emit = defineEmits(["close", "select-person", "create-new-person"]);

// State
const searchQuery = ref(props.initialName || "");
const searchResults = ref([]);
const isLoading = ref(false);
const searchPerformed = ref(false);
const noExistingEntry = ref(false);
const isMobileView = ref(false);

// Handle screen size changes
const checkScreenSize = () => {
    isMobileView.value = window.innerWidth < 768;
};

// Watch for checkbox changes
watch(noExistingEntry, (newValue) => {});

onMounted(() => {
    checkScreenSize();
    window.addEventListener("resize", checkScreenSize);

    // If initial name is provided, perform search automatically
    if (props.initialName) {
        searchQuery.value = props.initialName;
        performSearch();
    }

    // Focus the search input field when component is mounted
    setTimeout(() => {
        const input = document.querySelector(
            ".person-overlay-search-field .v-field__input",
        );
        if (input) {
            input.focus();
        }
    }, 300);
});

// Clean up event listener
onUnmounted(() => {
    window.removeEventListener("resize", checkScreenSize);
});

// Watch for changes to initialName
watch(
    () => props.initialName,
    (newVal) => {
        if (newVal) {
            searchQuery.value = newVal;
            performSearch();
        }
    },
);

// Perform search
const performSearch = async () => {
    if (!searchQuery.value.trim()) {
        return;
    }

    isLoading.value = true;
    searchPerformed.value = true;
    searchResults.value = [];

    try {
        // Try the search endpoint
        const response = await axios.get("/api/wikidata/search/people", {
            params: { query: searchQuery.value },
        });

        // Process results
        if (response.data && Array.isArray(response.data)) {
            searchResults.value = response.data;
        } else if (response.data && response.data.results) {
            searchResults.value = response.data.results;
        } else {
            // Create a fallback local entry
            searchResults.value = [];
        }
    } catch (error) {
        console.error("Error performing search:", error);

        // Create a fallback result that allows user to continue
        searchResults.value = [
            {
                id: "local1",
                label: searchQuery.value,
                description:
                    "Neuer Eintrag (Keine bestehenden Einträge gefunden)",
                isOrganization: false,
            },
        ];
    } finally {
        isLoading.value = false;
    }
};

// Reset the search results when the query changes
watch(searchQuery, (newVal, oldVal) => {
    if (newVal !== oldVal && searchResults.value.length > 0) {
        searchResults.value = [];
        searchPerformed.value = false;
    }
});

// Close overlay
const handleClose = () => {
    searchResults.value = [];
    searchPerformed.value = false;
    noExistingEntry.value = false;
    emit("close");
};

// Get button text based on the context
const getCreateButtonText = () => {
    if (props.context === "update") {
        return "Person anlegen";
    }
    return "Person erstellen";
};

// Select an existing person
const selectExistingPerson = (person) => {
    console.log("Selected person:", person);
    emit("select-person", person);
    handleClose();
};

// Open Wikidata page in new window
const openWikidata = (id) => {
    window.open(`https://www.wikidata.org/wiki/${id}`, "_blank");
};

// Create a new person
const createPerson = () => {
    if (!searchQuery.value.trim() || !noExistingEntry.value) {
        showToast(
            "Bitte bestätigen Sie, dass es keine vorhandene Person gibt",
            "warning",
        );
        return;
    }

    // Parse the name to potentially split into first and last name
    const nameParts = searchQuery.value.trim().split(" ");
    let personData = {};

    if (nameParts.length > 1) {
        const lastName = nameParts.pop();
        const firstName = nameParts.join(" ");
        personData = {
            firstName,
            lastName,
        };
    } else {
        personData = {
            firstName: "",
            lastName: searchQuery.value.trim(),
        };
    }

    // Emit event with the name to use for the new person
    emit("create-new-person", searchQuery.value, personData);
    handleClose();
};

// Get the global toast function if available
const ogtGlobals = inject("ogtGlobals", null);

// Helper function to show toast notifications
const showToast = (message, type = "info") => {
    if (ogtGlobals?.showToast) {
        ogtGlobals.showToast(message, type);
    } else {
        console.log(`[${type.toUpperCase()}] ${message}`);
    }
};
</script>

<style scoped>
.search-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow-y: auto;
    padding: 20px;
}

.search-input-container {
    display: flex;
    margin-bottom: 20px;
}

.person-overlay-search-field {
    flex-grow: 1;
}

:deep(.person-overlay-search-field .v-field__append-inner) {
    cursor: pointer;
}

:deep(.disabled-icon .v-field__append-inner) {
    opacity: 0.5;
    cursor: default;
}

:deep(.person-overlay-search-field .v-field__input) {
    padding: 8px 16px;
}

:deep(.person-overlay-search-field .v-icon) {
    color: #1976d2;
}

:deep(.person-overlay-search-field .v-field--focused) {
    border-color: #1976d2 !important;
}

:deep(.person-overlay-search-field .v-field) {
    border-radius: 4px;
    border: 1px solid #ccc;
    background-color: white;
}

.overlay-content {
    background-color: white;
    border-radius: 8px;
    width: 100%;
    max-width: 800px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    position: relative;
}

.overlay-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background-color: #f5f5f5;
    border-bottom: 1px solid #e0e0e0;
}

.overlay-header h3 {
    margin: 0;
    font-size: 1.2rem;
    color: #333;
}

.overlay-body-wrapper {
    overflow-y: auto;
    flex-grow: 1;
    max-height: calc(90vh - 60px);
}

.overlay-body {
    padding: 20px;
    display: flex;
    flex-direction: column;
    min-height: 200px;
}

.entry-banner {
    background-color: #f5f5f5;
    padding: 15px 20px;
    margin-bottom: 20px;
    border-radius: 4px;
    border-left: 4px solid #1976d2;
}

.entry-banner h2 {
    margin: 0;
    font-size: 1.1rem;
    color: #333;
}

.input-group {
    margin-bottom: 20px;
}

.input-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #555;
}

.search-input-container {
    display: flex;
    align-items: center;
}

.search-input-container input {
    flex-grow: 1;
    padding: 10px 15px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 1rem;
    transition: border-color 0.2s;
    outline: none;
    height: 48px;
}

.search-input-container input:focus {
    border-color: #1976d2;
    box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.2);
}

.search-info-box {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 20px;
    padding: 12px;
    background-color: #fff9c4;
    border-radius: 4px;
    border-left: 4px solid #fbc02d;
}

.confirmation-checkbox {
    display: flex;
    align-items: center;
    margin: 1.5rem 0;
    padding: 1rem;
    background-color: #fff9c4;
    border-radius: 4px;
    border-left: 4px solid #fbc02d;
}

.confirmation-checkbox input[type="checkbox"] {
    margin-right: 10px;
    width: 20px;
    height: 20px;
}

.confirmation-checkbox label {
    font-size: 0.95rem;
    color: #5d4037;
}

.search-info-box .info-icon-wrapper {
    color: #f57f17;
    display: flex;
    align-items: center;
    justify-content: center;
}

.search-info-box p {
    margin: 0;
    color: #5d4037;
}

.search-info {
    margin-top: 10px;
    font-size: 0.9rem;
    color: #666;
}

.search-button {
    height: 48px;
    min-width: 100px;
    background-color: #1976d2;
    color: white;
    border: none;
}

.search-button:hover {
    background-color: #1565c0;
}

.search-button:disabled {
    background-color: #e0e0e0;
    color: #9e9e9e;
}

.loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30px;
    gap: 15px;
    color: #666;
}

.search-results {
    margin-top: 10px;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
    max-height: 300px;
    overflow-y: auto;
    background-color: #fafafa;
}

.search-results h4 {
    margin: 0;
    padding: 10px 15px;
    background-color: #f5f5f5;
    border-bottom: 1px solid #e0e0e0;
    font-size: 0.95rem;
    color: #333;
    font-weight: 500;
}

.search-results {
    margin-top: 1rem;
    margin-bottom: 1.5rem;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
    overflow-y: auto;
    max-height: 400px !important;
}

.search-results h4 {
    margin: 0;
    padding: 0.75rem 1rem;
    background-color: #f5f5f5;
    border-bottom: 1px solid #e0e0e0;
    font-size: 1rem;
    color: #333;
}

.search-result-item {
    padding: 1rem;
    border-bottom: 1px solid #e0e0e0;
    background-color: white;
    transition: background-color 0.2s;
    cursor: pointer;
}

.search-result-item:last-child {
    border-bottom: none;
}

.search-result-item:hover {
    background-color: #f8f8f8;
}

.result-title {
    font-weight: bold;
    margin-bottom: 0.5rem;
    color: #333;
}

.result-description {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 0.75rem;
}

.result-metadata {
    font-size: 0.85rem;
    color: #777;
    margin-bottom: 0.5rem;
}

.result-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 6px;
}

.result-id {
    font-size: 0.8rem;
    color: #888;
}

.open-wikidata-button {
    background-color: #e3f2fd;
    color: #1976d2;
    border: none;
    border-radius: 4px;
    padding: 0.3rem 0.75rem;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.open-wikidata-button:hover {
    background-color: #bbdefb;
}

.no-results {
    margin-top: 20px;
    padding: 20px;
    text-align: center;
    background-color: #f9f9f9;
    border-radius: 4px;
}

.confirmation-checkbox {
    display: flex;
    align-items: flex-start;
    margin-top: 20px;
    background-color: #fff9c4;
    padding: 15px;
    border-radius: 4px;
    text-align: left;
}

.confirmation-checkbox input[type="checkbox"] {
    margin-right: 10px;
    margin-top: 3px;
    width: 18px;
    height: 18px;
}

.confirmation-checkbox label {
    font-size: 0.95rem;
    color: #5d4037;
}

.button-group {
    margin-top: 20px;
    display: flex;
    justify-content: space-between;
    gap: 10px;
}

.button {
    border: none;
    border-radius: 4px;
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
    outline: none;
}

.bg-secondary {
    background-color: #f5f5f5;
    color: #333;
}

.bg-secondary:hover {
    background-color: #e0e0e0;
}

.create-button {
    background-color: #1976d2;
    color: white;
}

.create-button:hover {
    background-color: #1565c0;
}

.create-button:disabled {
    background-color: #bbdefb;
    color: white;
    cursor: not-allowed;
}

.button-group {
    display: flex;
    justify-content: space-between;
    margin-top: 1.5rem;
}

.close-overlay {
    background: none;
    border: none;
    cursor: pointer;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.2s;
    color: #757575;
    font-size: 1.2rem;
    padding: 0;
    margin: 0;
}

.close-overlay:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.create-new-section {
    margin-top: 20px;
    padding: 16px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background-color: #f5f5f5;
}

@media (max-width: 1200px) {
    .overlay-content {
        max-width: 90%;
    }

    .overlay-body-wrapper {
        max-height: 70vh;
    }

    .overlay-body {
        padding: 15px;
    }

    .info-box {
        position: static;
        width: 100%;
        max-width: none;
        margin-bottom: 20px;
        max-height: none;
    }
}

@media (max-width: 768px) {
    .search-overlay {
        padding: 0;
    }

    .overlay-content {
        max-width: 100%;
        height: 100%;
        max-height: 100vh;
        border-radius: 0;
    }

    .overlay-body-wrapper {
        max-height: calc(100vh - 60px);
    }

    .search-input-container {
        flex-direction: column;
    }

    .search-button {
        width: 100%;
        margin-top: 10px;
        margin-left: 0 !important;
    }

    .button-group {
        flex-direction: column;
    }
}
</style>
