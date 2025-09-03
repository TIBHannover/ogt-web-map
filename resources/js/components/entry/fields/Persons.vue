<template>
    <!-- Component name should be multi-word: PersonsField -->
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

        <div class="persons-container">
            <!-- Add Person button -->
            <v-btn
                color="primary"
                class="mt-2 mb-4 button bg-secondary"
                @click="
                    (e) => {
                        forceResetAndOpen(false, e);
                    }
                "
                prepend-icon="mdi-account-plus"
                :data-action="'add-person'"
                id="add-person-button"
            >
                {{ label.includes("*") ? label.replace("*", "") : label }}
                Person hinzufügen
            </v-btn>

            <br />

            <!-- Add Organization button -->
            <v-btn
                color="primary"
                class="mt-2 button bg-secondary"
                @click="
                    (e) => {
                        forceResetAndOpen(true, e);
                    }
                "
                prepend-icon="mdi-domain-plus"
                :data-action="'add-organization'"
                id="add-organization-button"
            >
                {{ label.includes("*") ? label.replace("*", "") : label }}
                Organisation hinzufügen
            </v-btn>
        </div>

        <ValidationError
            :show="showValidationErrors && !isValid"
            :message="`Bitte füge mindestens einen Eintrag für ${label} hinzu.`"
            showIcon
        />

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
                        v-if="
                            !person.id ||
                            (person.id && !person.id.startsWith('Q'))
                        "
                        type="button"
                        class="edit-person"
                        @click="
                            onEditPerson(person, $event);
                            console.log(
                                'Edit button clicked for person:',
                                person,
                            );
                        "
                        title="Person bearbeiten"
                        data-action="edit-person"
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
                    editMode
                        ? `${label} bearbeiten`
                        : organizationMode
                          ? `${label} Organisation hinzufügen`
                          : `${label} Person hinzufügen`
                }}</v-card-title>
                <v-card-text class="overlay-content">
                    <!-- Search UI Container - only shown when not adding a new person -->
                    <div v-if="!showNewPersonForm">
                        <!-- Person suchen -->
                        <div class="persons-search">
                            <div class="search-input-container">
                                <v-text-field
                                    :placeholder="
                                        organizationMode
                                            ? `Namen der Organisation suchen...`
                                            : `Namen der Person suchen...`
                                    "
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
                                    label === 'Täter*In *' &&
                                    !organizationMode
                                "
                                :q-ids="['Q106625639']"
                                endpoint="people"
                                @select="onSelectPerson"
                            />
                            <wikidata-suggestions
                                v-if="
                                    !isSearchLoading &&
                                    !searchQuery.trim() &&
                                    label === 'Täter*In *' &&
                                    organizationMode
                                "
                                :q-ids="['Q380622']"
                                endpoint="people"
                                @select="onSelectPerson"
                            />
                        </div>

                        <!-- Suchergebnisse für Personen -->
                        <v-list
                            v-if="filteredSearchResults.length > 0"
                            class="search-results mt-3"
                            density="compact"
                        >
                            <v-list-item
                                v-for="person in filteredSearchResults"
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
                                <v-chip
                                    v-if="person.isOrganization"
                                    size="small"
                                    color="primary"
                                    class="ml-2 mb-1"
                                >
                                    Organisation
                                </v-chip>
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
                        <div v-if="searchPerformed" class="mt-4">
                            <v-divider class="mb-3"></v-divider>
                            <div class="d-flex justify-center">
                                <v-btn
                                    color="secondary"
                                    @click="onStartNewPersonForm"
                                    prepend-icon="mdi-account-plus"
                                >
                                    {{
                                        isAuthorForm
                                            ? "Autor*In nicht gefunden? Neu anlegen"
                                            : organizationMode
                                              ? "Organisation nicht gefunden? Neu anlegen"
                                              : "Person nicht gefunden? Neu anlegen"
                                    }}
                                </v-btn>
                            </div>
                        </div>
                    </div>

                    <!-- Formular für neue Person -->
                    <div v-if="showNewPersonForm" class="new-person-form">
                        <h4 class="text-h6 mb-4">
                            {{
                                editMode
                                    ? "Eintrag bearbeiten"
                                    : isAuthorForm
                                      ? "Neue/n Autor*In anlegen"
                                      : organizationMode
                                        ? "Neue Organisation anlegen"
                                        : "Neue Person anlegen"
                            }}
                        </h4>
                        <!--
                        <div class="form-group" v-if="!hideIsOrganisation">
                            <v-checkbox
                                v-model="newPersonLocal.isOrganization"
                                label="Dies ist eine Organisation/Institution"
                                hide-details
                                class="mb-3"
                            ></v-checkbox>
                        </div>
                        -->
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
                            <text-area
                                v-model="newPersonLocal.description"
                                :label="
                                    isAuthorForm
                                        ? 'Kurze Beschreibung des Autors*'
                                        : 'Kurze Beschreibung*'
                                "
                                :placeholder="
                                    isAuthorForm
                                        ? 'Beispiel: deutscher Schriftsteller...'
                                        : 'Beispiel: deutscher Widerstandskämpfer... / Opfer des Nationalsozialismus'
                                "
                                rows="3"
                                :rules="[rules.required]"
                                :required="true"
                            ></text-area>
                        </div>

                        <!-- Geburtsname / Birth name (P1477) -->
                        <div
                            class="form-group"
                            v-if="!newPersonLocal.isOrganization"
                        >
                            <text-field
                                v-model="newPersonLocal.birthName"
                                label="Geburtsname"
                                placeholder="Geburtsname, falls abweichend vom aktuellen Namen"
                            >
                            </text-field>
                        </div>

                        <!-- Arbeitgeber / Employer (P108) - only for perpetrators -->
                        <div
                            class="form-group"
                            v-if="
                                !newPersonLocal.isOrganization &&
                                (newPersonLocal.role === 'perpetrator' ||
                                    props.label.toLowerCase().includes('täter'))
                            "
                        >
                            <wikidata-selector
                                v-model="newPersonLocal.employer"
                                label="Arbeitgeber"
                                dialog-title="Arbeitgeber auswählen"
                                search-label="Organisation suchen..."
                                :search-results="
                                    wikidataSearchResults.employer || []
                                "
                                :is-loading="isWikidataSearchLoading.employer"
                                :search-performed="
                                    wikidataSearchPerformed.employer
                                "
                                endpoint="people"
                                @search="
                                    searchWikidataItems('employer', $event)
                                "
                            ></wikidata-selector>
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
                                v-if="!hidePlaceOfDeath"
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
                                v-if="!hidePlaceOfDetention"
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

                            <!-- Image field -->
                            <div class="form-group">
                                <image-selector
                                    :image-url="newPersonLocal.imageUrl"
                                    :image-filename="
                                        newPersonLocal.imageFilename
                                    "
                                    @update:image-url="
                                        newPersonLocal.imageUrl = $event
                                    "
                                    @update:image-filename="
                                        newPersonLocal.imageFilename = $event
                                    "
                                >
                                </image-selector>
                            </div>
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
                        :id="
                            editMode
                                ? 'update-person-button'
                                : 'save-new-person-button'
                        "
                    >
                        {{
                            editMode
                                ? "Speichern"
                                : organizationMode
                                  ? "Organisation hinzufügen"
                                  : "Person hinzufügen"
                        }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, computed, watch, toRefs, nextTick, onMounted } from "vue";
import WikidataSelector from "./common/WikidataSelector.vue";
import GenderSelector from "./GenderSelector.vue";
import WikidataSuggestions from "./common/WikidataSuggestions.vue";
import ValidationError from "../../helpers/ValidationError.vue";
import TextField from "./common/TextField.vue";
import TextArea from "./common/TextArea.vue";
import ImageSelector from "./ImageSelector.vue";
import { useValidation } from "../../../composables/useValidation";

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
            birthName: "", // Added for P1477 Geburtsname
            imageUrl: null, // Image URL for display
            imageFilename: null, // Image filename for Wikidata
            employer: null, // Added for P108 Arbeitgeber
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
    hidePlaceOfDeath: {
        type: Boolean,
        default: false,
    },
    hidePlaceOfDetention: {
        type: Boolean,
        default: false,
    },
    hideIsOrganisation: {
        type: Boolean,
        default: false,
    },
    isAuthorForm: {
        type: Boolean,
        default: false,
    },
});

// Composables

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
    "validation",
]);

// Local state
const dialogOpen = ref(false);
const searchQuery = ref("");
const editMode = ref(false);
const editingPersonId = ref(null);
const organizationMode = ref(false);
// Initialize with a deep clone of the newPerson object
const newPersonLocal = ref(JSON.parse(JSON.stringify(props.newPerson)));

// Debug log function
const logDebug = (message, data = null) => {
    console.log(`[Persons] ${message}`, data || "");
};

// Filter search results based on organization mode
const filteredSearchResults = computed(() => {
    if (!props.peopleSearchResults || props.peopleSearchResults.length === 0) {
        return [];
    }

    // Filter results based on organization mode
    if (organizationMode.value) {
        // If we're in organization mode, prioritize organizations
        return [...props.peopleSearchResults].sort((a, b) => {
            if (a.isOrganization && !b.isOrganization) return -1;
            if (!a.isOrganization && b.isOrganization) return 1;
            return 0;
        });
    } else {
        // If we're in person mode, prioritize persons
        return [...props.peopleSearchResults].sort((a, b) => {
            if (!a.isOrganization && b.isOrganization) return -1;
            if (a.isOrganization && !b.isOrganization) return 1;
            return 0;
        });
    }
});

// Hard reset all dialog state and form data
const resetDialogState = () => {
    // Reset all state variables
    searchQuery.value = "";
    editMode.value = false;
    editingPersonId.value = null;
    organizationMode.value = false;
    dialogOpen.value = false;

    // Create a fresh copy of the default person data
    const defaultPersonData = JSON.parse(JSON.stringify(props.newPerson));

    // Completely replace the object - don't mutate it
    newPersonLocal.value = defaultPersonData;

    // Notify parent components
    emit("update:newPerson", defaultPersonData);

    // If we were in new person form mode, cancel it
    if (props.showNewPersonForm) {
        emit("cancelNewPersonForm");
    }

    // Clear any search results
    emit("searchPeople", "");
};

// Force a reset and then open dialog for adding new person
const forceResetAndOpen = (isOrg, event) => {
    // Prevent event propagation
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }

    // First close and reset everything
    resetDialogState();

    // Then use setTimeout to ensure complete reset before opening again
    setTimeout(() => {
        // Make sure the parent component is not in new person form mode
        if (props.showNewPersonForm) {
            emit("cancelNewPersonForm");
        }

        // Reset the newPersonLocal value to default
        newPersonLocal.value = JSON.parse(JSON.stringify(props.newPerson));

        // Open the dialog in the correct mode (will show search form)
        openPersonDialog(isOrg);
    }, 50);
};

// Function to open the dialog with the correct mode
const openPersonDialog = (isOrg, event) => {
    // Check if we have an event and prevent it from bubbling up
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }

    // Reset edit-specific state
    editMode.value = false;
    editingPersonId.value = null;
    searchQuery.value = "";

    // Make a deep clone to avoid reference issues
    const basePersonData = JSON.parse(JSON.stringify(props.newPerson));

    // Set the organization flag in the new person object - completely replace the object
    newPersonLocal.value = {
        ...basePersonData,
        isOrganization: isOrg,
        // Set role based on the label if available
        role: props.label.toLowerCase().includes("täter")
            ? "perpetrator"
            : props.label.toLowerCase().includes("opfer")
              ? "victim"
              : "",
    };

    // Force update to parent component before opening dialog
    emit("update:newPerson", newPersonLocal.value);

    // Now open the dialog and set the organization mode
    dialogOpen.value = true;
    organizationMode.value = isOrg;

    // Make sure we're NOT in new person form mode - we want to show the search first
    // This is critical for showing the search form instead of the edit form
    if (props.showNewPersonForm) {
        emit("cancelNewPersonForm");
    }

    // Clear search results to start fresh
    emit("searchPeople", "");
};

// Computed property to check if selection is valid
const isValid = computed(() => {
    return props.selectedPersons && props.selectedPersons.length > 0;
});

// Validation function
const validatePersons = () => {
    return props.selectedPersons && props.selectedPersons.length > 0;
};

// Use the validation composable
const {
    isValid: isValidResult,
    showValidationErrors,
    validate: validateForm,
    showErrors,
} = useValidation(validatePersons, {
    emit,
    watchCallback: () => [props.selectedPersons],
});

// Wikidata search state
const wikidataSearchResults = ref({
    nationality: [],
    place_of_birth: [],
    place_of_death: [],
    place_of_detention: [],
    employer: [], // Added for P108 Arbeitgeber
});

const isWikidataSearchLoading = ref({
    nationality: false,
    place_of_birth: false,
    place_of_death: false,
    place_of_detention: false,
    employer: false, // Added for P108 Arbeitgeber
});

const wikidataSearchPerformed = ref({
    nationality: false,
    place_of_birth: false,
    place_of_death: false,
    place_of_detention: false,
    employer: false, // Added for P108 Arbeitgeber
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
    searchQuery.value = query; // Store the search query locally
    emit("searchPeople", query);
};

const onSelectPerson = (person) => {
    emit("selectPerson", person);
    dialogOpen.value = false;
};

const onStartNewPersonForm = () => {
    // Reset edit mode and any editing state
    editMode.value = false;
    editingPersonId.value = null;

    // Reset newPersonLocal to default before setting values
    const basePersonData = JSON.parse(JSON.stringify(props.newPerson));

    // Preserve organization mode setting
    basePersonData.isOrganization = organizationMode.value;

    // Set role based on label
    const role = props.label.toLowerCase().includes("täter")
        ? "perpetrator"
        : props.label.toLowerCase().includes("opfer")
          ? "victim"
          : "";

    // Set both role and relationship_type to ensure consistency
    basePersonData.role = role;
    basePersonData.relationship_type = role;

    // Log the role assignment for debugging
    console.log(`Setting role and relationship_type for new person: ${role}`);

    // Use the search query to prefill name fields
    if (searchQuery.value) {
        const parts = searchQuery.value.trim().split(" ");
        if (parts.length > 1) {
            basePersonData.firstName = parts[0];
            basePersonData.lastName = parts.slice(1).join(" ");
        } else {
            basePersonData.lastName = parts[0];
        }
    }

    // Set the newPersonLocal with our prepared data
    newPersonLocal.value = basePersonData;

    // Update parent data
    emit("update:newPerson", basePersonData);

    // Emit the event to show the form
    emit("startNewPersonForm");
};

const onCancelNewPersonForm = () => {
    editMode.value = false;
    editingPersonId.value = null;
    emit("cancelNewPersonForm");
};

const onAddNewPerson = () => {
    // Store a local copy of the current data before any changes
    const dataToAdd = JSON.parse(JSON.stringify(newPersonLocal.value));

    // Ensure both role and relationship_type are set consistently
    if (dataToAdd.role && !dataToAdd.relationship_type) {
        dataToAdd.relationship_type = dataToAdd.role;
    } else if (dataToAdd.relationship_type && !dataToAdd.role) {
        dataToAdd.role = dataToAdd.relationship_type;
    }

    // For debugging - log the role assignment
    console.log(
        `Adding new person with role: ${dataToAdd.role}, relationship_type: ${dataToAdd.relationship_type}`,
    );

    // Update parent with data to add (do this before resetting)
    emit("update:newPerson", dataToAdd);

    // Signal to add the new person
    emit("addNewPerson");

    // Reset dialog state completely
    resetDialogState();
};

const onEditPerson = (person, event) => {
    // Prevent event propagation if event exists
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }

    // Clear any existing search results
    searchQuery.value = "";

    // First, reset form state completely
    const cleanStartingState = JSON.parse(JSON.stringify(props.newPerson));
    newPersonLocal.value = cleanStartingState;

    // Now prepare the edit data before setting edit mode
    const personData = {
        firstName: person.firstName || "",
        lastName: person.lastName || "",
        birthDate: person.birthDate || null,
        deathDate: person.deathDate || null,
        description: person.description || "",
        birthName: person.birthName || "",
        isOrganization: person.isOrganization || false,
        gender: person.gender || null,
        nationality: person.nationality || null,
        place_of_birth: person.place_of_birth || null,
        place_of_death: person.place_of_death || null,
        place_of_detention: person.place_of_detention || null,
        imageUrl: person.imageUrl || null,
        imageFilename: person.imageFilename || person.image || null,
        employer: person.employer || null,
        role: person.role || null,
        // Preserve important properties
        id: person.id,
        tempId: person.tempId,
        label: person.label,
    };

    // Force dialog to open first
    dialogOpen.value = true;

    // Use nextTick to ensure the dialog is mounted before updating form data
    nextTick(() => {
        // Wait briefly for dialog rendering
        setTimeout(() => {
            // Completely replace the newPersonLocal object
            newPersonLocal.value = personData;

            // Now set the edit mode flags AFTER data is prepared
            editMode.value = true;
            editingPersonId.value = person.id || person.tempId;
            organizationMode.value = personData.isOrganization || false;

            // Update parent component data
            emit("update:newPerson", personData);

            // Trigger form state in parent - this shows the edit form instead of search
            emit("startNewPersonForm");
        }, 50);
    });
};

const onUpdatePerson = () => {
    if (!canAddNewPerson.value) {
        console.warn("Cannot update person: validation failed");
        return;
    }

    // Füge die ID des bearbeiteten Eintrags hinzu
    const updatedPerson = {
        ...newPersonLocal.value,
        id: editingPersonId.value,
    };

    // Aktualisiere die Person
    emit("updatePerson", updatedPerson);

    // Schließe den Dialog und setze den Bearbeitungsmodus zurück
    dialogOpen.value = false;
    editMode.value = false;
    editingPersonId.value = null;
    organizationMode.value = false;

    // Formular zurücksetzen
    emit("cancelNewPersonForm");
};

const onRemovePerson = (id) => {
    emit("removePerson", id);
};

// Add initialization on component mount
onMounted(() => {
    // Ensure we start with a clean state
    resetDialogState();
});

const onInfoButtonClick = () => {
    emit("infoButtonClick", props.fieldId);
};

const closeDialog = () => {
    // Use the centralized reset function
    resetDialogState();

    // Ensure we're not in new person form mode when dialog closes
    if (props.showNewPersonForm) {
        emit("cancelNewPersonForm");
    }

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

// Export the validation status for parent components
defineExpose({
    isValid,
    validate: validateForm,
    showValidationErrors: showErrors,
});
</script>

<style scoped></style>
