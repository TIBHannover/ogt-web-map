<template>
    <div class="persons-section container-section">
        <div class="section-header">
            <label :for="fieldId" class="section-label">{{ label }}:</label>
            <button
                v-if="showInfoButton"
                type="button"
                class="info-icon"
                @click="onInfoButtonClick"
            >
                <v-icon>mdi-information-variant</v-icon>
            </button>
        </div>

        <div class="add-person-container">
            <v-btn
                color="primary"
                class="mt-4"
                @click="dialogOpen = true"
                prepend-icon="mdi-account-plus"
            >
                {{ label }} hinzufügen
            </v-btn>
        </div>

        <div v-if="selectedPersons.length > 0" class="selected-persons">
            <h4>Ausgewählte {{ label }}:</h4>
            <div
                v-for="person in selectedPersons"
                :key="person.id || person.tempId"
                class="selected-person"
            >
                <span class="person-name">
                    <a
                        v-if="person.id"
                        :href="`https://www.wikidata.org/wiki/${person.id}`"
                        target="_blank"
                    >
                        {{
                            person.label ||
                            `${person.firstName} ${person.lastName}`
                        }}
                    </a>
                    <span v-else>
                        {{ `${person.firstName} ${person.lastName}` }}
                    </span>
                </span>
                <span class="person-description" v-if="person.description">
                    - {{ person.description }}
                </span>
                <button
                    type="button"
                    class="remove-person"
                    @click="onRemovePerson(person.id || person.tempId)"
                >
                    ✕
                </button>
            </div>
        </div>

        <v-dialog v-model="dialogOpen" max-width="600px">
            <v-card>
                <v-card-title>{{ label }} hinzufügen</v-card-title>
                <v-card-text class="overlay-content">
                    <!-- Person suchen -->
                    <div v-if="!showNewPersonForm" class="persons-search">
                        <div class="search-input-container">
                            <v-text-field
                                :placeholder="`Namen der ${label} suchen...`"
                                v-model="searchQuery"
                                append-inner-icon="mdi-magnify"
                                @click:append-inner="
                                    onSearchPeople(searchQuery)
                                "
                                @keydown.enter="onSearchPeople(searchQuery)"
                                clearable
                                :loading="isSearchLoading"
                            ></v-text-field>
                            <v-btn
                                color="primary"
                                class="ml-3 search-button"
                                @click="onSearchPeople(searchQuery)"
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
                                    peopleSearchResults.length === 0
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
                                peopleSearchResults.length === 0
                            "
                            class="search-no-results"
                        >
                            Keine Ergebnisse gefunden
                        </div>
                    </div>

                    <!-- Suchergebnisse für Personen -->
                    <v-list
                        v-if="
                            peopleSearchResults.length > 0 && !showNewPersonForm
                        "
                        class="search-results mt-3"
                        density="compact"
                    >
                        <v-list-item
                            v-for="person in peopleSearchResults"
                            :key="person.id"
                            @click="onSelectPerson(person)"
                            class="search-result-item"
                        >
                            <v-list-item-title>{{
                                person.label
                            }}</v-list-item-title>
                            <v-list-item-subtitle v-if="person.description">
                                {{ person.description }}
                            </v-list-item-subtitle>
                            <v-list-item-subtitle
                                v-if="person.birth || person.death"
                                class="result-metadata"
                            >
                                <span v-if="person.birth"
                                    >* {{ person.birth }}</span
                                >
                                <span v-if="person.death"
                                    >† {{ person.death }}</span
                                >
                            </v-list-item-subtitle>
                            <v-list-item-subtitle class="result-id">{{
                                person.id
                            }}</v-list-item-subtitle>
                        </v-list-item>
                    </v-list>

                    <!-- Button für neue Person -->
                    <div
                        v-if="!showNewPersonForm && searchPerformed"
                        class="mt-4"
                    >
                        <v-divider class="mb-3"></v-divider>
                        <div class="d-flex justify-center">
                            <v-btn
                                color="secondary"
                                @click="onStartNewPersonForm"
                                prepend-icon="mdi-account-plus"
                            >
                                Person nicht gefunden? Neue Person anlegen
                            </v-btn>
                        </div>
                    </div>

                    <!-- Formular für neue Person -->
                    <div v-if="showNewPersonForm" class="new-person-form">
                        <h4 class="text-h6 mb-4">Neue Person anlegen</h4>
                        <div class="form-group">
                            <v-text-field
                                v-model="newPersonLocal.firstName"
                                label="Vorname *"
                                :rules="[rules.required]"
                                required
                            ></v-text-field>
                        </div>
                        <div class="form-group">
                            <v-text-field
                                v-model="newPersonLocal.lastName"
                                label="Nachname *"
                                :rules="[rules.required]"
                                required
                            ></v-text-field>
                        </div>
                        <div class="form-group">
                            <v-text-field
                                v-model="newPersonLocal.birthDate"
                                label="Geburtsdatum"
                                type="date"
                            ></v-text-field>
                        </div>
                        <div class="form-group">
                            <v-text-field
                                v-model="newPersonLocal.deathDate"
                                label="Sterbedatum"
                                type="date"
                            ></v-text-field>
                        </div>
                        <div class="form-group">
                            <v-textarea
                                v-model="newPersonLocal.description"
                                label="Kurze Beschreibung"
                                rows="3"
                            ></v-textarea>
                        </div>
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey" @click="closeDialog"> Abbrechen </v-btn>
                    <v-btn
                        v-if="showNewPersonForm"
                        color="primary"
                        @click="onAddNewPerson"
                        :disabled="!canAddNewPerson"
                    >
                        Person hinzufügen
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { computed, ref } from "vue";

const props = defineProps({
    label: {
        type: String,
        required: true,
    },
    fieldId: {
        type: String,
        required: true,
    },
    peopleSearchResults: {
        type: Array,
        default: () => [],
    },
    selectedPersons: {
        type: Array,
        default: () => [],
    },
    showNewPersonForm: {
        type: Boolean,
        default: false,
    },
    newPerson: {
        type: Object,
        default: () => ({
            firstName: "",
            lastName: "",
            birthDate: null,
            deathDate: null,
            description: "",
        }),
    },
    isSearchLoading: {
        type: Boolean,
        default: false,
    },
    searchPerformed: {
        type: Boolean,
        default: false,
    },
    showInfoButton: {
        type: Boolean,
        default: false,
    },
});

// Emits für Ereignisse
const emit = defineEmits([
    "searchPeople",
    "selectPerson",
    "startNewPersonForm",
    "cancelNewPersonForm",
    "update:newPerson",
    "addNewPerson",
    "removePerson",
    "infoButtonClick",
]);

// Local state
const dialogOpen = ref(false);
const searchQuery = ref("");
const newPersonLocal = computed(() => props.newPerson);

// Validierungsregeln
const rules = {
    required: (v) => !!v || "Dieses Feld ist erforderlich",
};

// Computed für die Prüfung, ob eine neue Person hinzugefügt werden kann
const canAddNewPerson = computed(() => {
    return newPersonLocal.value.firstName && newPersonLocal.value.lastName;
});

// Event handlers
const onSearchPeople = (query) => {
    if (!query || query.trim() === "") return;
    emit("searchPeople", query);
};

const onSelectPerson = (person) => {
    emit("selectPerson", person);
    dialogOpen.value = false;
};

const onStartNewPersonForm = () => {
    emit("startNewPersonForm");
};

const onCancelNewPersonForm = () => {
    emit("cancelNewPersonForm");
};

const onAddNewPerson = () => {
    emit("update:newPerson", newPersonLocal.value);
    emit("addNewPerson");
    dialogOpen.value = false;
};

const onRemovePerson = (id) => {
    emit("removePerson", id);
};

const onInfoButtonClick = () => {
    emit("infoButtonClick", props.fieldId);
};

const closeDialog = () => {
    dialogOpen.value = false;
    searchQuery.value = "";
    if (props.showNewPersonForm) {
        onCancelNewPersonForm();
    }
};
</script>

<style scoped>
.persons-section {
    width: 100%;
    margin-bottom: 1.5rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

label.section-label {
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

.persons-search {
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

.new-person-form {
    background-color: #f5f5f5;
    padding: 1.5rem;
    border-radius: 4px;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.selected-persons {
    margin-top: 1.5rem;
    padding: 1rem;
    background-color: #f5f5f5;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.selected-persons h4 {
    margin-top: 0;
    margin-bottom: 1rem;
    font-size: 1.1rem;
    color: #333;
}

.selected-person {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    margin-bottom: 0.75rem;
    background-color: white;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.2s;
}

.selected-person:hover {
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
}

.person-name {
    font-weight: 500;
    margin-right: 0.5rem;
}

.person-name a {
    color: #1976d2;
    text-decoration: none;
}

.person-name a:hover {
    text-decoration: underline;
}

.person-description {
    color: #666;
    font-size: 0.9rem;
    flex-grow: 1;
}

.remove-person {
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

.remove-person:hover {
    background-color: rgba(211, 47, 47, 0.1);
}

.result-metadata {
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 0.25rem;
}

.result-metadata span {
    margin-right: 0.75rem;
}

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

@media (min-width: 1200px) {
    .info-icon {
        display: none !important;
    }
}
</style>
