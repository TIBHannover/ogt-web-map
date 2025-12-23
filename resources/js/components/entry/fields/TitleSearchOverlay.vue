<template>
    <div class="search-overlay">
        <div class="overlay-content">
            <div class="overlay-header">
                <h3>{{ title }}</h3>
                <button class="close-overlay" @click="handleClose">
                    &times;
                </button>
            </div>
            <div class="overlay-body-wrapper">
                <div class="overlay-body">
                    <div class="entry-banner">
                        <h2>Welchen Eintrag möchtest du anlegen?</h2>
                    </div>
                    <div class="input-group">
                        <label for="entry-title">Titel des Eintrags</label>
                        <div class="search-input-container">
                            <v-text-field
                                id="entry-title"
                                v-model="localTitle"
                                @update:model-value="onInput"
                                @keydown.enter="
                                    (e) => {
                                        e.preventDefault();
                                        performSearch(e);
                                    }
                                "
                                placeholder="Titel eingeben..."
                                maxlength="250"
                                autocomplete="off"
                                append-inner-icon="mdi-magnify"
                                @click:append-inner="
                                    () => {
                                        if (
                                            localTitle &&
                                            localTitle.length >= 3
                                        ) {
                                            performSearch();
                                        }
                                    }
                                "
                                :class="{
                                    'disabled-icon':
                                        !localTitle || localTitle.length < 3,
                                }"
                                ref="titleInput"
                                variant="outlined"
                                density="comfortable"
                                hide-details
                                single-line
                                color="primary"
                                bg-color="white"
                                class="title-overlay-search-field"
                                clearable
                            ></v-text-field>
                        </div>
                    </div>
                    <div class="search-info-box">
                        <div class="info-icon-wrapper">
                            <v-icon>mdi-information-outline</v-icon>
                        </div>
                        <p>
                            Bitte prüfe, ob dieser Eintrag bereits existiert und
                            bestätige dies mit der Checkbox unter der Suche.
                        </p>
                    </div>

                    <!-- Suchergebnisse anzeigen -->
                    <div
                        v-if="searchResults && searchResults.length > 0"
                        class="search-results"
                    >
                        <h4>Suchergebnisse:</h4>
                        <div
                            v-for="result in searchResults"
                            :key="result.id"
                            class="search-result-item"
                        >
                            <div class="result-title">{{ result.label }}</div>
                            <div
                                class="result-description"
                                v-if="result.description"
                            >
                                {{ result.description }}
                            </div>
                            <div class="result-actions">
                                <div class="result-id">{{ result.id }}</div>
                                <button
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

                    <!-- Loading Indikator -->
                    <div v-if="isLoading" class="loading">
                        <v-progress-circular
                            indeterminate
                            color="primary"
                        ></v-progress-circular>
                        <span>Suche läuft...</span>
                    </div>

                    <!-- Keine Ergebnisse -->
                    <div
                        v-if="
                            searchPerformed &&
                            searchResults.length === 0 &&
                            !isLoading
                        "
                        class="no-results"
                    >
                        Keine Einträge gefunden.
                    </div>

                    <!-- Checkbox - erscheint nur nach durchgeführter Suche und im Kontext 'create' -->
                    <div
                        v-if="
                            searchPerformed &&
                            !isLoading &&
                            props.context !== 'update'
                        "
                        class="confirmation-checkbox"
                    >
                        <input
                            type="checkbox"
                            id="no-existing-entry"
                            v-model="noExistingEntry"
                        />
                        <label for="no-existing-entry"
                            >Es gibt keinen vorhandenen Eintrag mit diesem
                            Titel</label
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
                                !localTitle ||
                                localTitle.length < 3 ||
                                isLoading ||
                                !searchPerformed ||
                                (props.context !== 'update' && !noExistingEntry)
                            "
                            @click="createEntry"
                        >
                            <v-icon size="small" class="me-1">mdi-plus</v-icon>
                            {{ getCreateButtonText() }}
                        </button>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="info-box" v-if="!isMobileView">
                    <div class="info-box-header">
                        <h3>Hinweise zur Titeleingabe</h3>
                    </div>
                    <div class="info-box-content">
                        <p>
                            <strong>Wichtig:</strong> Bevor du einen neuen
                            Eintrag erstellst, suche bitte zuerst, ob dieser
                            bereits existiert.
                        </p>
                        <p>Folge diesen Schritten:</p>
                        <ol>
                            <li>
                                Gebe den gewünschten Titel in das Suchfeld ein
                            </li>
                            <li>Klicke auf "Suchen"</li>
                            <li>
                                Prüfe die angezeigten Suchergebnisse sorgfältig
                            </li>
                            <li>
                                Falls kein passender Eintrag existiert,
                                aktivieren Sie die Checkbox
                            </li>
                            <li>
                                Klicken Sie auf "{{ getTypeInfoTitle() }}
                                erstellen"
                            </li>
                        </ol>
                        <p>
                            Falls du einen ähnlichen Eintrag findest, kannst du
                            diesen auf Wikidata öffnen, um mehr Informationen zu
                            erhalten.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from "vue";
import axios from "axios";
import { debounce } from "lodash";

const props = defineProps({
    initialTitle: {
        type: String,
        default: "",
    },
    entryType: {
        type: String,
        required: true,
    },
    title: {
        type: String,
        default: "Neuen Eintrag erstellen",
    },
    context: {
        type: String,
        default: "create", // 'create' or 'update'
        validator: (value) => ["create", "update"].includes(value),
    },
});

const emit = defineEmits(["update:title", "close", "create"]);

const localTitle = ref(props.initialTitle || "");
const searchResults = ref([]);
const isLoading = ref(false);
const searchPerformed = ref(false);
const noExistingEntry = ref(false);
const isMobileView = ref(false);
const lastEmittedTitle = ref(props.initialTitle || "");

// Watch for changes to initialTitle
watch(
    () => props.initialTitle,
    (newValue) => {
        localTitle.value = newValue || "";
        // Reset search state when initial title changes
        searchResults.value = [];
        searchPerformed.value = false;
        noExistingEntry.value = false;
    },
    { immediate: true },
);

// Check screen size for responsive design
const checkScreenSize = () => {
    isMobileView.value = window.innerWidth < 1200;
};

// Add and remove event listener for screen resize
onMounted(() => {
    checkScreenSize();
    window.addEventListener("resize", checkScreenSize);

    // Reset search state on mount
    searchResults.value = [];
    searchPerformed.value = false;
    noExistingEntry.value = false;

    // Focus and select the text field
    setTimeout(() => {
        const input = document.querySelector(
            ".title-overlay-search-field .v-field__input",
        );
        if (input) {
            input.focus();
            input.select();
        }
    }, 300);
});

onUnmounted(() => {
    window.removeEventListener("resize", checkScreenSize);
});

// Search function
const performSearch = async (event) => {
    // If an event is provided, prevent default behavior
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    if (!localTitle.value || localTitle.value.length < 3) {
        return;
    }

    // Force any pending debounced title updates to execute immediately
    // This ensures the title is updated before the search completes
    if (emitTitleUpdate && emitTitleUpdate.flush) {
        emitTitleUpdate.flush();
    }

    // Focus back on the input after search
    setTimeout(() => {
        const input = document.querySelector(
            ".title-overlay-search-field .v-field__input",
        );
        if (input) {
            input.focus();
        }
    }, 50);

    // Don't clear results yet, just set loading state
    isLoading.value = true;
    noExistingEntry.value = false; // Reset checkbox when performing a new search

    try {
        // Force FactGrid endpoint
        const baseEndpoint = "/api/factgrid/entities";

        console.log("Using API endpoint in TitleSearchOverlay:", baseEndpoint);

        const response = await axios.get(baseEndpoint, {
            params: {
                title: localTitle.value,
                limit: 5,
            },
        });
        // Set search results after response received
        if (response.data.success && response.data.results) {
            searchResults.value = response.data.results;
        } else {
            searchResults.value = [];
        }

        // Always make sure search performed is set to true AFTER results are updated
        searchPerformed.value = true;
    } catch (err) {
        console.error("Fehler bei der FactGrid-Suche:", err);
        console.log(
            "Error details:",
            err.response ? err.response.data : "No response data",
        );
        searchResults.value = [];
        searchPerformed.value = true;
    } finally {
        isLoading.value = false;

        // Ensure the results are displayed after a small delay
        setTimeout(() => {
            if (!searchPerformed.value) {
                console.log("Ensuring search results are displayed");
                searchPerformed.value = true;
            }
        }, 50);
    }
};

// Update title when input changes

// Use a debounced function to emit title updates
const emitTitleUpdate = debounce((value) => {
    // Only emit if the value has changed
    if (lastEmittedTitle.value !== value) {
        lastEmittedTitle.value = value;
        // Only emit title updates in create mode (not update mode)
        // This prevents the overlay from closing in update mode
        if (props.context !== "update") {
            emit("update:title", value);
        }
    }
}, 500); // 500ms debounce

const onInput = (value) => {
    // Store locally without emitting
    localTitle.value = value;

    // Debounce the update event
    emitTitleUpdate(value);

    // Don't immediately clear search results when typing
    // Just reset the searchPerformed flag to indicate user is typing
    if (value !== props.initialTitle) {
        searchPerformed.value = false;
        // Keep existing results until new ones arrive
    }
};

// Open entity entry in new tab
const openWikidata = (entityId) => {
    // Force FactGrid URL
    const baseUrl = "https://database.factgrid.de/entity/";
    window.open(`${baseUrl}${entityId}`, "_blank");
};

// Save and close function for update mode
const saveAndClose = () => {
    // Cancel any pending debounced updates
    if (emitTitleUpdate && emitTitleUpdate.cancel) {
        emitTitleUpdate.cancel();
    }

    // Only if value has changed
    if (localTitle.value !== lastEmittedTitle.value) {
        console.log("Saving final title value:", localTitle.value);
        lastEmittedTitle.value = localTitle.value;
        emit("update:title", localTitle.value);
    }

    // Close the dialog with a small delay to allow update to propagate
    setTimeout(() => {
        handleClose();
    }, 100);
};

// Clean up state when dialog is closed

// Clean up state when dialog is closed
const handleClose = (emitClose = true) => {
    // Cancel any pending debounced updates
    if (emitTitleUpdate && emitTitleUpdate.cancel) {
        emitTitleUpdate.cancel();
    }

    // For 'update' mode, we only emit on explicit save
    // For 'create' mode, we emit on close to ensure we have the latest value
    if (
        props.context !== "update" &&
        localTitle.value !== lastEmittedTitle.value
    ) {
        lastEmittedTitle.value = localTitle.value;
        emit("update:title", localTitle.value);
    }

    searchResults.value = [];
    searchPerformed.value = false;
    noExistingEntry.value = false;

    if (emitClose) {
        emit("close");
    }
};

// Get the type info title based on entry type
const getTypeInfoTitle = () => {
    const typeTitles = {
        Q5: "Person",
        Q6983405: "Ereignis",
        Q5003624: "Erinnerungsort",
        Q108047567: "Staatspolizeistelle",
        Q108047541: "Staatspolizeiaußenstelle",
        Q277565: "Arbeitserziehungslager",
        Q40357: "Gefängnis",
    };

    const result = typeTitles[props.entryType] || "Eintragstyp";
    return result;
};

// Get the create button text based on entry type and context
// Get the button text based on context
const getCreateButtonText = () => {
    if (props.context === "update") {
        return "Titel anpassen";
    }
    const buttonText = `${getTypeInfoTitle()} erstellen`;
    return buttonText;
};

// Create the entry and proceed to the next step
// Create or update entry
const createEntry = () => {
    if (!localTitle.value || localTitle.value.length < 3) {
        return;
    }

    // In update context, don't require the noExistingEntry checkbox
    if (props.context !== "update" && !noExistingEntry.value) {
        console.log("Not in update context and checkbox not checked");
        return;
    }

    // First close the overlay to prevent any interference with state
    handleClose(false);

    // Make sure the title gets emitted properly after overlay is closed
    setTimeout(() => {
        emit("update:title", localTitle.value);

        // Also emit the create event which is what BaseInfos.vue listens for
        emit("create", localTitle.value);
    }, 0);
};

// Update local title when prop changes
watch(
    () => props.initialTitle,
    (newVal) => {
        localTitle.value = newVal || "";
    },
);
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
}

.search-input-container {
    width: 100%;
    position: relative;
}

.title-overlay-search-field {
    width: 100%;
}

:deep(.title-overlay-search-field .v-field__append-inner) {
    cursor: pointer;
}

:deep(.disabled-icon .v-field__append-inner) {
    opacity: 0.5;
    cursor: default;
}

:deep(.title-overlay-search-field .v-field__input) {
    padding: 8px 16px;
    min-height: 48px;
}

:deep(.title-overlay-search-field .v-icon) {
    color: #1976d2;
}

:deep(.title-overlay-search-field .v-field--focused) {
    border-color: #1976d2 !important;
}

:deep(.title-overlay-search-field .v-field) {
    border-radius: 4px;
    border: 1px solid #ccc;
    background-color: white;
}

.overlay-content {
    width: 90%;
    max-width: 1000px; /* Increased to accommodate the info box */
    max-height: 90vh;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.overlay-header {
    padding: 1rem;
    background-color: #1976d2;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.overlay-header h3 {
    margin: 0;
    font-size: 1.2rem;
}

.overlay-body-wrapper {
    display: flex;
    flex-direction: row;
    max-height: calc(90vh - 3.5rem);
}

.overlay-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
    max-width: 65%;
}

.entry-banner {
    background-color: #f5f5f5;
    padding: 1rem;
    margin-bottom: 1.5rem;
    border-radius: 4px;
    border-left: 4px solid #1976d2;
}

.entry-banner h2 {
    margin: 0;
    font-size: 1.3rem;
    color: #333;
}

.input-group {
    margin-bottom: 1.5rem;
}

.input-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.search-input-container {
    display: flex;
    gap: 0.5rem;
}

.search-input-container input {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 1rem;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}

.search-input-container input:focus {
    outline: none;
    border-color: #1976d2;
    box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.25);
}

.search-info-box {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 1rem;
    padding: 0.75rem;
    background-color: #fff9c4; /* Light yellow background */
    border-radius: 4px;
    border-left: 4px solid #fbc02d; /* Darker yellow border */
}

.search-info-box .info-icon-wrapper {
    color: #f57f17; /* Amber color for icon */
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.search-info-box p {
    margin: 0;
    color: #5d4037;
    font-size: 0.95rem;
}

.search-info {
    color: #666;
    margin-bottom: 1rem;
    font-size: 0.95rem;
}

.search-button {
    background-color: #1976d2;
    color: white;
    white-space: nowrap;
    padding: 0 1.25rem;
}

.search-button:hover {
    background-color: #1565c0;
}

.search-button:disabled {
    background-color: #bbdefb;
    cursor: not-allowed;
}

.loading {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 2rem 0;
    gap: 1rem;
    color: #666;
}

.search-results {
    margin-top: 1rem;
    margin-bottom: 1.5rem;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
    overflow-y: scroll;
    max-height: 400px;
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
    background-color: transparent;
    border: none;
    color: #1976d2;
    font-size: 0.85rem;
    padding: 0.25rem 0.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    transition: color 0.2s;
}

.open-wikidata-button:hover {
    color: #0d47a1;
    text-decoration: underline;
}

.no-results {
    text-align: center;
    padding: 2rem;
    color: #666;
    font-style: italic;
    background-color: #f9f9f9;
    border-radius: 4px;
    margin-bottom: 1.5rem;
}

.confirmation-checkbox {
    display: flex;
    align-items: center;
    margin: 1.5rem 0;
    padding: 1rem;
    background-color: #f5f5f5;
    border-radius: 4px;
    border-left: 4px solid #1976d2;
}

.confirmation-checkbox input[type="checkbox"] {
    margin-right: 0.75rem;
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.confirmation-checkbox label {
    font-weight: 500;
    color: #333;
    cursor: pointer;
}

.button-group {
    display: flex;
    justify-content: space-between;
    margin-top: 1.5rem;
    gap: 1rem;
}

.button {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.bg-secondary {
    background-color: #757575;
    color: white;
}

.bg-secondary:hover {
    background-color: #616161;
}

.create-button {
    background-color: #1976d2;
    color: white;
}

.create-button:hover {
    background-color: #1565c0;
}

.create-button:disabled {
    background-color: #bdbdbd;
    cursor: not-allowed;
}

.save-button {
    background-color: #4caf50;
    color: white;
}

.save-button:hover {
    background-color: #388e3c;
}

.close-overlay {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    margin: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.3s ease;
}

.close-overlay:hover {
    background-color: rgba(255, 255, 255, 0.2);
}

/* Info Box Styles */
.info-box {
    width: 35%;
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-left: 1px solid #e0e0e0;
    overflow-y: auto;
}

.info-box-header {
    border-bottom: 1px solid #e9ecef;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
}

.info-box-header h3 {
    font-size: 1.2rem;
    color: #1976d2;
    margin: 0;
}

.info-box-content {
    font-size: 0.95rem;
    line-height: 1.5;
    color: #495057;
}

.info-box-content p {
    margin-bottom: 1rem;
}

.info-box-content ol {
    padding-left: 1.5rem;
    margin-bottom: 1rem;
}

.info-box-content li {
    margin-bottom: 0.5rem;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .overlay-content {
        max-width: 600px;
    }

    .overlay-body-wrapper {
        flex-direction: column;
    }

    .overlay-body {
        max-width: 100%;
    }

    .info-box {
        display: none; /* Hide info box on mobile */
        width: 100%;
        border-left: none;
        border-top: 1px solid #e0e0e0;
    }
}
</style>
