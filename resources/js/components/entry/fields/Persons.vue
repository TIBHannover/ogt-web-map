<template>
    <div class="persons-section container-section">
        <label :for="fieldId" class="section-label">{{ label }}</label>
        <div v-if="!showNewPersonForm" class="persons-search">
            <input
                type="text"
                :placeholder="`Namen der ${label} suchen...`"
                @input="(event) => onSearchPeople(event.target.value)"
                @focus="(event) => onSearchPeople(event.target.value)"
            />

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
        <div
            v-if="peopleSearchResults.length > 0 && !showNewPersonForm"
            class="search-results"
        >
            <div
                v-for="person in peopleSearchResults"
                :key="person.id"
                class="search-result-item"
                @click="onSelectPerson(person)"
            >
                <div class="result-title">{{ person.label }}</div>
                <div class="result-description" v-if="person.description">
                    {{ person.description }}
                </div>
                <div
                    class="result-metadata"
                    v-if="person.birth || person.death"
                >
                    <span v-if="person.birth">* {{ person.birth }}</span>
                    <span v-if="person.death">† {{ person.death }}</span>
                </div>
                <div class="result-id">{{ person.id }}</div>
            </div>
        </div>

        <!-- Formular für neue Person -->
        <div v-if="showNewPersonForm" class="new-person-form">
            <h4>Neue Person anlegen</h4>
            <div class="form-group">
                <label :for="`${fieldId}-firstName`">Vorname *</label>
                <input
                    v-model="newPersonLocal.firstName"
                    type="text"
                    :id="`${fieldId}-firstName`"
                    required
                />
            </div>
            <div class="form-group">
                <label :for="`${fieldId}-lastName`">Nachname *</label>
                <input
                    v-model="newPersonLocal.lastName"
                    type="text"
                    :id="`${fieldId}-lastName`"
                    required
                />
            </div>
            <div class="form-group">
                <label :for="`${fieldId}-birthDate`">Geburtsdatum</label>
                <input
                    v-model="newPersonLocal.birthDate"
                    type="date"
                    :id="`${fieldId}-birthDate`"
                />
            </div>
            <div class="form-group">
                <label :for="`${fieldId}-deathDate`">Sterbedatum</label>
                <input
                    v-model="newPersonLocal.deathDate"
                    type="date"
                    :id="`${fieldId}-deathDate`"
                />
            </div>
            <div class="form-group">
                <label :for="`${fieldId}-description`">Kurze Beschreibung</label>
                <textarea
                    v-model="newPersonLocal.description"
                    :id="`${fieldId}-description`"
                    rows="3"
                ></textarea>
            </div>
            <div class="button-group">
                <button
                    type="button"
                    class="button bg-secondary"
                    @click="onCancelNewPersonForm"
                >
                    Abbrechen
                </button>
                <button
                    type="button"
                    class="button"
                    @click="onAddNewPerson"
                    :disabled="!canAddNewPerson"
                >
                    Person hinzufügen
                </button>
            </div>
        </div>

        <!-- Ausgewählte Personen -->
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

        <!-- Button für weitere Person -->
        <div v-if="!showNewPersonForm" class="add-another-person">
            <div class="new-person-option">
                <button
                    type="button"
                    class="button bg-secondary"
                    @click="onStartNewPersonForm"
                >
                    Person nicht gefunden? Neue Person anlegen
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";

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
]);

// Local state
const newPersonLocal = computed(() => props.newPerson);

// Computed für die Prüfung, ob eine neue Person hinzugefügt werden kann
const canAddNewPerson = computed(() => {
    return newPersonLocal.value.firstName && newPersonLocal.value.lastName;
});

// Event handlers
const onSearchPeople = (query) => {
    emit("searchPeople", query);
};

const onSelectPerson = (person) => {
    emit("selectPerson", person);
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
};

const onRemovePerson = (id) => {
    emit("removePerson", id);
};
</script>

<style scoped>
.persons-section {
    width: 100%;
    margin-bottom: 1.5rem;
}

.persons-search {
    margin-bottom: 0.5rem;
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

.form-group {
    margin-bottom: 1rem;
    width: 100%;
}

.form-group label {
    display: block;
    margin-bottom: 0.25rem;
}

.new-person-form {
    background-color: #f5f5f5;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}

.new-person-form h4 {
    margin-top: 0;
    margin-bottom: 1rem;
}

.button-group {
    display: flex;
    justify-content: space-between;
}

.selected-persons {
    margin-top: 1rem;
    padding: 0.5rem;
    background-color: #f5f5f5;
    border-radius: 4px;
}

.selected-person {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    background-color: white;
    border-radius: 4px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.person-name {
    font-weight: 500;
    margin-right: 0.5rem;
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
}

.result-metadata {
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 0.25rem;
}

.result-metadata span {
    margin-right: 0.75rem;
}

.new-person-option,
.add-another-person {
    padding: 0.75rem;
    border-top: 1px solid #eee;
    text-align: center;
}

/* Diese Stile gelten für Suchresultate, wurden aus dem originalen CSS übernommen */
.search-results {
    background-color: white;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    max-height: 300px;
    overflow-y: auto;
    margin-bottom: 1rem;
    z-index: 10;
    position: relative;
    border: 1px solid #eee;
}

.search-info {
    padding: 0.75rem;
    color: #666;
    font-style: italic;
    text-align: center;
    border-bottom: 1px solid #eee;
}

.search-result-item {
    padding: 0.75rem;
    cursor: pointer;
    border-bottom: 1px solid #eee;
    transition: background-color 0.2s;
}

.search-result-item:hover {
    background-color: #f5f5f5;
}

.search-result-item:last-child {
    border-bottom: none;
}

.result-title {
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.result-description {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.result-id {
    font-size: 0.8rem;
    color: #888;
}
</style>