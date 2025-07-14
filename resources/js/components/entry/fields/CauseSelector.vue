<template>
    <div class="cause-section container-section">
        <div class="section-header">
            <label class="section-label">Ursache der Verfolgung:</label>
            <button
                type="button"
                class="info-icon"
                @click="$emit('infoButtonClick', 'cause')"
            >
                <v-icon>mdi-information-variant</v-icon>
            </button>
        </div>

        <div class="add-cause-container">
            <v-btn
                color="primary"
                class="mt-4 button bg-secondary"
                @click="dialogOpen = true"
                prepend-icon="mdi-tag-plus"
            >
                Ursache hinzufügen
            </v-btn>
        </div>

        <!-- Anzeige der ausgewählten Ursachen -->
        <div v-if="selectedCauses.length > 0" class="selected-causes">
            <h4>Ausgewählte Ursachen:</h4>
            <div
                v-for="cause in selectedCauses"
                :key="cause.id"
                class="cause-display"
            >
                <span class="cause-name">
                    <a
                        :href="`https://www.wikidata.org/wiki/${cause.id}`"
                        target="_blank"
                    >
                        {{ cause.label }}
                    </a>
                </span>
                <span class="cause-description" v-if="cause.description">
                    - {{ cause.description }}
                </span>
                <button
                    type="button"
                    class="remove-cause"
                    @click="$emit('removeCause', cause.id)"
                >
                    ✕
                </button>
            </div>
        </div>

        <!-- Dialog für die Ursachensuche -->
        <v-dialog v-model="dialogOpen" max-width="600px">
            <v-card>
                <v-card-title>Ursache der Verfolgung hinzufügen</v-card-title>
                <v-card-text class="overlay-content">
                    <!-- Ursache suchen -->
                    <div class="causes-search">
                        <div class="search-input-container">
                            <v-text-field
                                placeholder="Nach Ursache der Verfolgung suchen..."
                                v-model="searchQuery"
                                append-inner-icon="mdi-magnify"
                                @click:append-inner="onSearchCause(searchQuery)"
                                @keydown.enter="onSearchCause(searchQuery)"
                                clearable
                                :loading="isSearchLoading"
                                @focus="$emit('fieldFocus', 'cause')"
                            ></v-text-field>
                            <v-btn
                                color="primary"
                                class="ml-3 search-button"
                                @click="onSearchCause(searchQuery)"
                                :disabled="!searchQuery.trim()"
                                :loading="isSearchLoading"
                            >
                                Suchen
                            </v-btn>
                        </div>

                        <!-- Ladeindikator -->
                        <div
                            v-if="
                                isSearchLoading &&
                                !(
                                    searchPerformed &&
                                    causeSearchResults.length === 0
                                )
                            "
                            class="search-loading"
                        >
                            <v-progress-circular
                                size="24"
                                indeterminate
                                color="primary"
                            ></v-progress-circular>
                            <span>Suche läuft...</span>
                        </div>

                        <!-- Keine Ergebnisse Meldung -->
                        <div
                            v-else-if="
                                searchPerformed &&
                                !isSearchLoading &&
                                causeSearchResults.length === 0
                            "
                            class="search-no-results"
                        >
                            Keine Ergebnisse gefunden
                        </div>

                        <!-- Vorschläge anzeigen -->
                        <wikidata-suggestions
                            v-if="!isSearchLoading && !searchQuery.trim()"
                            :q-ids="['Q1899269', 'Q22649']"
                            endpoint="causes"
                            @select="onSelectCause"
                        />
                    </div>

                    <!-- Suchergebnisse für Ursachen -->
                    <v-list
                        v-if="causeSearchResults.length > 0"
                        class="search-results mt-3"
                        density="compact"
                    >
                        <v-list-item
                            v-for="cause in causeSearchResults"
                            :key="cause.id"
                            @click="onSelectCause(cause)"
                            class="search-result-item"
                        >
                            <v-list-item-title>{{
                                cause.label
                            }}</v-list-item-title>
                            <v-list-item-subtitle v-if="cause.description">
                                {{ cause.description }}
                            </v-list-item-subtitle>
                            <v-list-item-subtitle class="result-id">{{
                                cause.id
                            }}</v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey" @click="closeDialog">Schließen</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref } from "vue";
import WikidataSuggestions from "./common/WikidataSuggestions.vue";

const props = defineProps({
    cause: {
        type: String,
        default: "",
    },
    causeSearchResults: {
        type: Array,
        default: () => [],
    },
    selectedCauses: {
        type: Array,
        default: () => [],
    },
    isSearchLoading: {
        type: Boolean,
        default: false,
    },
    searchPerformed: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits([
    "searchCause",
    "selectCause",
    "removeCause",
    "fieldFocus",
    "infoButtonClick",
]);

// Local state
const dialogOpen = ref(false);
const searchQuery = ref("");

// Event handlers
const onSearchCause = (query) => {
    if (!query || query.trim() === "") return;
    emit("searchCause", query);
};

const onSelectCause = (cause) => {
    emit("selectCause", cause);
    dialogOpen.value = false;
    searchQuery.value = "";
};

const closeDialog = () => {
    dialogOpen.value = false;
    searchQuery.value = "";
};
</script>

<!-- Global form styles -->
<style>
@import '../styles/form-styles.css';
</style>

<style scoped>
.container-section {
    width: 100%;
    margin-bottom: 1.5rem;
    background-color: #f5f5f5;
    padding: 1rem;
    border-radius: 4px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.section-label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.info-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background-color: #1976d2;
    color: white;
    border: none;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.info-icon:hover {
    background-color: #1565c0;
}

.causes-search {
    margin-bottom: 0.5rem;
    width: 100%;
}

.search-input-container {
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.search-button {
    height: 56px;
    white-space: nowrap;
}

.search-loading {
    display: flex;
    align-items: center;
    margin-top: 0.5rem;
    color: #666;
}

.search-loading span {
    margin-left: 0.5rem;
}

.search-no-results {
    margin-top: 0.5rem;
    padding: 0.5rem;
    background-color: #f8f8f8;
    border-radius: 4px;
    color: #666;
    font-style: italic;
}

.overlay-content {
    max-height: 80vh;
    overflow-y: auto;
}

/* Wikidata search results */
.search-results {
    background-color: white;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    max-height: 300px;
    overflow-y: auto;
    margin-bottom: 1.5rem;
    z-index: 20;
    position: relative;
    border: 1px solid #eee;
}

.search-result-item {
    cursor: pointer;
    transition: background-color 0.2s;
}

.search-result-item:hover {
    background-color: #f5f5f5;
}

.result-id {
    font-size: 0.8rem;
    color: #888;
}

.selected-causes {
    margin-top: 1.5rem;
    padding: 1rem;
    background-color: #f5f5f5;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.selected-causes h4 {
    margin-top: 0;
    margin-bottom: 1rem;
    font-size: 1.1rem;
    color: #333;
}

.cause-display {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    margin-bottom: 0.75rem;
    background-color: white;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.2s;
    position: relative;
}

.cause-display:hover {
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
}

.cause-name {
    font-weight: 500;
    margin-right: 0.5rem;
}

.cause-name a {
    color: #1976d2;
    text-decoration: none;
}

.cause-name a:hover {
    text-decoration: underline;
}

.cause-description {
    color: #666;
    font-size: 0.9rem;
    flex-grow: 1;
}

.remove-cause {
    background: none;
    border: none;
    color: #d32f2f;
    cursor: pointer;
    font-size: 1rem;
    font-weight: bold;
    padding: 0.25rem 0.5rem;
    border-radius: 50%;
    transition: background-color 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
}

.remove-cause:hover {
    background-color: rgba(211, 47, 47, 0.1);
}

.button {
    padding: 12px 24px;
    font-size: 16px;
    background-color: #1976d2;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.2s;
    height: auto;
    text-transform: none;
}

.button:hover {
    background-color: #1565c0;
}
</style>
