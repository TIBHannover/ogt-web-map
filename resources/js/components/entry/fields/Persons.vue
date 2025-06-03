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
                class="mt-2 button bg-secondary"
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
                <div class="person-info">
                    <span class="person-name">
                        <a
                            v-if="person.id && person.id.startsWith('Q')"
                            :href="`https://www.wikidata.org/wiki/${person.id}`"
                            target="_blank"
                        >
                            {{
                                person.label ||
                                (person.isOrganization
                                    ? person.lastName
                                    : `${person.firstName} ${person.lastName}`)
                            }}
                        </a>
                        <span v-else>
                            {{
                                person.isOrganization
                                    ? person.lastName
                                    : `${person.firstName} ${person.lastName}`
                            }}
                        </span>
                    </span>
                    <span class="person-description" v-if="person.description">
                        - {{ person.description }}
                    </span>
                </div>
                <div class="person-actions">
                    <a
                        v-if="person.id && person.id.startsWith('Q')"
                        :href="`https://www.wikidata.org/wiki/${person.id}`"
                        target="_blank"
                        class="source-link"
                    >
                        <v-icon size="x-small mr-1">mdi-open-in-new</v-icon>
                        Wikidata
                    </a>
                    <button
                        type="button"
                        class="edit-person"
                        @click="onEditPerson(person)"
                        title="Person bearbeiten"
                        v-if="!person.id"
                    >
                        <v-icon size="small">mdi-pencil</v-icon>
                    </button>

                    <button
                        type="button"
                        class="remove-person"
                        @click="onRemovePerson(person.id || person.tempId)"
                        title="Person entfernen"
                    >
                        ✕
                    </button>
                </div>
            </div>
        </div>

        <v-dialog v-model="dialogOpen" max-width="600px">
            <v-card>
                <v-card-title>{{
                    editMode ? `${label} bearbeiten` : `${label} hinzufügen`
                }}</v-card-title>
                <v-card-text class="overlay-content">
                    <!-- Person suchen -->
                    <div v-if="!showNewPersonForm" class="persons-search">
                        <div class="search-input-container">
                            <v-text-field
                                :placeholder="`Namen der ${label} oder einer Organisation suchen...`"
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

                        <wikidata-suggestions
                            v-if="
                                !isSearchLoading &&
                                !searchQuery.trim() &&
                                label === 'Täter*In *'
                            "
                            :q-ids="['Q106625639']"
                            endpoint="people"
                            @select="onSelectPerson"
                        />
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
                                Person/Organisation nicht gefunden? Neu anlegen
                            </v-btn>
                        </div>
                    </div>

                    <!-- Formular für neue Person -->
                    <div v-if="showNewPersonForm" class="new-person-form">
                        <h4 class="text-h6 mb-4">
                            {{
                                editMode
                                    ? "Eintrag bearbeiten"
                                    : "Neuen Eintrag anlegen"
                            }}
                        </h4>
                        <div class="form-group">
                            <v-checkbox
                                v-model="newPersonLocal.isOrganization"
                                label="Dies ist eine Organisation/Institution"
                                hide-details
                                class="mb-3"
                            ></v-checkbox>
                        </div>
                        <div class="form-group">
                            <v-text-field
                                v-model="newPersonLocal.firstName"
                                v-if="!newPersonLocal.isOrganization"
                                :label="
                                    'Vorname' +
                                    (newPersonLocal.isOrganization
                                        ? ' (optional)'
                                        : ' *')
                                "
                                :rules="[
                                    newPersonLocal.isOrganization
                                        ? ''
                                        : rules.required,
                                ]"
                                :required="!newPersonLocal.isOrganization"
                            ></v-text-field>
                        </div>
                        <div class="form-group">
                            <v-text-field
                                v-model="newPersonLocal.lastName"
                                :label="
                                    newPersonLocal.isOrganization
                                        ? 'Name der Organisation *'
                                        : 'Nachname *'
                                "
                                :rules="[rules.required]"
                                required
                            ></v-text-field>
                        </div>
                        <div class="form-group">
                            <v-textarea
                                v-model="newPersonLocal.description"
                                label="Kurze Beschreibung*"
                                placeholder="Beispiel: deutscher Widerstandskämpfer... / Opfer des Nationalsozialismus"
                                rows="3"
                                :rules="[rules.required]"
                                required
                            ></v-textarea>
                        </div>
                        <!-- Geschlecht / Gender (P21) -->
                        <div class="form-group">
                            <gender-selector
                                v-model="newPersonLocal.gender"
                                label="Geschlecht"
                                v-if="!newPersonLocal.isOrganization"
                            ></gender-selector>
                        </div>
                        <div
                            class="form-group"
                            v-if="!newPersonLocal.isOrganization"
                        >
                            <v-text-field
                                v-model="newPersonLocal.birthDate"
                                label="Geburtsdatum"
                                type="date"
                            ></v-text-field>
                        </div>
                        <div
                            class="form-group"
                            v-if="!newPersonLocal.isOrganization"
                        >
                            <v-text-field
                                v-model="newPersonLocal.deathDate"
                                label="Sterbedatum"
                                type="date"
                            ></v-text-field>
                        </div>

                        <!-- Neue Felder mit Wikidata-Auswahl -->
                        <div
                            class="form-group"
                            v-if="!newPersonLocal.isOrganization"
                        >
                            <!-- Nationalität / Citizenship (P27) -->
                            <wikidata-selector
                                v-model="newPersonLocal.nationality"
                                label="Staatsangehörigkeit"
                                dialog-title="Staatsangehörigkeit auswählen"
                                search-label="Land suchen..."
                                :search-results="
                                    wikidataSearchResults.nationality || []
                                "
                                :is-loading="
                                    isWikidataSearchLoading.nationality
                                "
                                :search-performed="
                                    wikidataSearchPerformed.nationality
                                "
                                endpoint="places"
                                @search="
                                    searchWikidataItems('nationality', $event)
                                "
                            ></wikidata-selector>

                            <!-- Geburtsort / Place of birth (P19) -->
                            <wikidata-selector
                                v-model="newPersonLocal.place_of_birth"
                                label="Geburtsort"
                                dialog-title="Geburtsort auswählen"
                                search-label="Ort suchen..."
                                :search-results="
                                    wikidataSearchResults.place_of_birth || []
                                "
                                :is-loading="
                                    isWikidataSearchLoading.place_of_birth
                                "
                                :search-performed="
                                    wikidataSearchPerformed.place_of_birth
                                "
                                endpoint="places"
                                @search="
                                    searchWikidataItems(
                                        'place_of_birth',
                                        $event,
                                    )
                                "
                            ></wikidata-selector>

                            <!-- Sterbeort / Place of death (P20) -->
                            <wikidata-selector
                                v-model="newPersonLocal.place_of_death"
                                label="Sterbeort"
                                dialog-title="Sterbeort auswählen"
                                search-label="Ort suchen..."
                                :search-results="
                                    wikidataSearchResults.place_of_death || []
                                "
                                :is-loading="
                                    isWikidataSearchLoading.place_of_death
                                "
                                :search-performed="
                                    wikidataSearchPerformed.place_of_death
                                "
                                endpoint="places"
                                @search="
                                    searchWikidataItems(
                                        'place_of_death',
                                        $event,
                                    )
                                "
                            ></wikidata-selector>

                            <!-- Haftort / Place of detention (P2632) -->
                            <wikidata-selector
                                v-model="newPersonLocal.place_of_detention"
                                label="Haftort"
                                dialog-title="Haftort auswählen"
                                search-label="Ort suchen..."
                                :search-results="
                                    wikidataSearchResults.place_of_detention ||
                                    []
                                "
                                :is-loading="
                                    isWikidataSearchLoading.place_of_detention
                                "
                                :search-performed="
                                    wikidataSearchPerformed.place_of_detention
                                "
                                endpoint="places"
                                @search="
                                    searchWikidataItems(
                                        'place_of_detention',
                                        $event,
                                    )
                                "
                            ></wikidata-selector>
                        </div>
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey" @click="closeDialog"> Abbrechen </v-btn>
                    <v-btn
                        v-if="showNewPersonForm"
                        color="primary"
                        @click="editMode ? onUpdatePerson() : onAddNewPerson()"
                        :disabled="!canAddNewPerson"
                    >
                        {{ editMode ? "Speichern" : "Person hinzufügen" }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { computed, ref } from "vue";
import WikidataSelector from "./common/WikidataSelector.vue";
import GenderSelector from "./GenderSelector.vue";
import WikidataSuggestions from "./common/WikidataSuggestions.vue";

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
            isOrganization: false,
            gender: null,
            nationality: null,
            place_of_birth: null,
            place_of_death: null,
            place_of_detention: null,
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
    "updatePerson",
    "removePerson",
    "infoButtonClick",
    "searchWikidata",
]);

// Local state
const dialogOpen = ref(false);
const searchQuery = ref("");
const editMode = ref(false);
const editingPersonId = ref(null);
const newPersonLocal = computed(() => props.newPerson);

// Wikidata search state
const wikidataSearchResults = ref({
    nationality: [],
    place_of_birth: [],
    place_of_death: [],
    place_of_detention: [],
});

const isWikidataSearchLoading = ref({
    nationality: false,
    place_of_birth: false,
    place_of_death: false,
    place_of_detention: false,
});

const wikidataSearchPerformed = ref({
    nationality: false,
    place_of_birth: false,
    place_of_death: false,
    place_of_detention: false,
});

// Predefined gender options with Wikidata IDs
const genderOptions = [
    { id: "Q6581097", label: "männlich" },
    { id: "Q6581072", label: "weiblich" },
    { id: "Q1097630", label: "Intersexualität" },
    { id: "Q1052281", label: "Transfrau" },
    { id: "Q2449503", label: "Transmann" },
    { id: "Q48270", label: "nichtbinär" },
];

// Validierungsregeln
const rules = {
    required: (v) => !!v || "Dieses Feld ist erforderlich",
};

// Computed für die Prüfung, ob eine neue Person hinzugefügt werden kann
const canAddNewPerson = computed(() => {
    // Bei Organisationen brauchen wir nur einen Nachnamen/Organisationsnamen
    if (newPersonLocal.value.isOrganization) {
        return !!newPersonLocal.value.lastName;
    }
    // Bei Personen brauchen wir Vor- und Nachnamen
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
    editMode.value = false; // Stelle sicher, dass wir nicht im Bearbeitungsmodus sind
    emit("startNewPersonForm");
};

const onCancelNewPersonForm = () => {
    editMode.value = false;
    editingPersonId.value = null;
    emit("cancelNewPersonForm");
};

const onAddNewPerson = () => {
    emit("update:newPerson", newPersonLocal.value);
    emit("addNewPerson");
    dialogOpen.value = false;
};

const onEditPerson = (person) => {
    // Aktiviere den Bearbeitungsmodus
    editMode.value = true;
    editingPersonId.value = person.id || person.tempId;

    // Fu00fclle das Formular mit den Daten der Person
    const personData = {
        firstName: person.firstName || "",
        lastName: person.lastName || "",
        birthDate: person.birthDate || null,
        deathDate: person.deathDate || null,
        description: person.description || "",
        isOrganization: person.isOrganization || false,
        gender: person.gender || null,
        nationality: person.nationality || null,
        place_of_birth: person.place_of_birth || null,
        place_of_death: person.place_of_death || null,
        place_of_detention: person.place_of_detention || null,
        // Bewahre die ID und andere wichtige Eigenschaften
        id: person.id,
        tempId: person.tempId,
        label: person.label,
    };

    // Aktualisiere die newPerson-Daten
    emit("update:newPerson", personData);

    // Starte das Formular für die Person
    emit("startNewPersonForm");

    // u00d6ffne den Dialog
    dialogOpen.value = true;
};

const onUpdatePerson = () => {
    if (!canAddNewPerson.value) return;

    // Fu00fcge die ID des bearbeiteten Eintrags hinzu
    const updatedPerson = {
        ...newPersonLocal.value,
        id: editingPersonId.value,
    };

    // Aktualisiere die Person
    emit("updatePerson", updatedPerson);

    // Schlieu00dfe den Dialog und setze den Bearbeitungsmodus zuru00fcck
    dialogOpen.value = false;
    editMode.value = false;
    editingPersonId.value = null;
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
    editMode.value = false;
    editingPersonId.value = null;
    if (props.showNewPersonForm) {
        onCancelNewPersonForm();
    }
};

// Wikidata search functions
const searchWikidataItems = async (field, query, endpoint = null) => {
    if (!query || query.trim() === "") return;

    isWikidataSearchLoading.value[field] = true;
    wikidataSearchPerformed.value[field] = true;

    try {
        // Bestimme den API-Endpunkt basierend auf dem Feldtyp
        let apiEndpoint = "people";

        if (endpoint) {
            // Wenn ein expliziter Endpunkt übergeben wurde, verwende diesen
            apiEndpoint = endpoint;
        } else {
            // Sonst bestimme den Endpunkt basierend auf dem Feldtyp
            switch (field) {
                case "nationality":
                    apiEndpoint = "places"; // Staatsangehörigkeit ist ein Land, daher places-Endpunkt
                    break;
                case "place_of_birth":
                case "place_of_death":
                case "place_of_detention":
                    apiEndpoint = "places"; // Verwende den neuen places-Endpunkt für Orte
                    break;
                default:
                    apiEndpoint = "people";
            }
        }

        // Verwende den entsprechenden API-Endpunkt
        const response = await fetch(
            `/api/wikidata/search/${apiEndpoint}?query=${encodeURIComponent(query)}`,
        );

        if (!response.ok) {
            throw new Error(`Server responded with status: ${response.status}`);
        }

        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            throw new Error(
                `Expected JSON response but got ${contentType || "unknown content type"}`,
            );
        }

        const data = await response.json();
        handleWikidataResults(field, data);
    } catch (error) {
        console.error(`Error searching Wikidata for ${field}:`, error);
        wikidataSearchResults.value[field] = [];
    } finally {
        isWikidataSearchLoading.value[field] = false;
    }
};

const handleWikidataResults = (field, results) => {
    wikidataSearchResults.value[field] = results || [];
    isWikidataSearchLoading.value[field] = false;
};
</script>

<style scoped>
.persons-section {
    width: 100%;
    margin-bottom: 1.5rem;
}

.field-section-title {
    font-size: 1rem;
    font-weight: 500;
    color: #333;
    margin-top: 1.5rem;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e0e0e0;
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

.source-link {
    color: #005691;
    text-decoration: none;
    font-size: 0.8rem;
    display: inline-flex;
    align-items: center;
    &:hover {
        text-decoration: underline;
    }
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

.person-info {
    display: flex;
    flex-grow: 1;
    align-items: center;
    position: relative;
    overflow: hidden;
}

.person-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.edit-person {
    background: none;
    border: none;
    color: #1976d2;
    cursor: pointer;
    font-size: 1rem;
    padding: 0.25rem 0.5rem;
    border-radius: 50%;
    transition: background-color 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
}

.edit-person:hover {
    background-color: rgba(25, 118, 210, 0.1);
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
</style>
