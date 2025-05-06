<template>
    <div class="step-container">
        <h2 class="step-title">Schritt 2: Ereignis-Details eingeben</h2>
        <form class="forms">
            <label for="title">Titel *</label>
            <input
                v-model="title"
                name="title"
                type="text"
                maxlength="250"
                placeholder="z.B. Novemberpogrom 1938 in Osnabrück"
                @input="onTitleInput"
            />

            <!-- Anzeige der Suchergebnisse -->
            <div v-if="wikidataResultsValue.length > 0" class="search-results">
                <p class="search-info">
                    Möglicherweise existiert dieser Eintrag bereits:
                </p>
                <div
                    v-for="result in wikidataResultsValue"
                    :key="result.id"
                    class="search-result-item"
                    @click="onSelectExistingEntity(result)"
                >
                    <div class="result-title">{{ result.label }}</div>
                    <div class="result-description" v-if="result.description">
                        {{ result.description }}
                    </div>
                    <div class="result-id">{{ result.id }}</div>
                </div>
            </div>

            <label for="description">Kurzbeschreibung *</label>
            <textarea
                v-model="description"
                name="description"
                maxlength="750"
                placeholder="z.B. vom NS-Regime gelenkte Gewaltmaßnahmen gegen Juden in Osnabrück."
            ></textarea>

            <!-- Opfer-Eingabefeld -->
            <div class="victims-section">
                <label for="victims">Opfer</label>
                <div v-if="!showNewVictimFormValue" class="victims-search">
                    <input
                        type="text"
                        placeholder="Namen der Opfer suchen..."
                        @input="(event) => onSearchPeople(event.target.value)"
                    />
                </div>

                <!-- Suchergebnisse für Opfer -->
                <div
                    v-if="
                        peopleSearchResultsValue.length > 0 &&
                        !showNewVictimFormValue
                    "
                    class="search-results"
                >
                    <div
                        v-for="person in peopleSearchResultsValue"
                        :key="person.id"
                        class="search-result-item"
                        @click="onSelectVictim(person)"
                    >
                        <div class="result-title">{{ person.label }}</div>
                        <div
                            class="result-description"
                            v-if="person.description"
                        >
                            {{ person.description }}
                        </div>
                        <div
                            class="result-metadata"
                            v-if="person.birth || person.death"
                        >
                            <span v-if="person.birth"
                                >* {{ person.birth }}</span
                            >
                            <span v-if="person.death"
                                >† {{ person.death }}</span
                            >
                        </div>
                        <div class="result-id">{{ person.id }}</div>
                    </div>

                    <!-- Button für neue Person -->
                    <div class="new-person-option">
                        <button
                            type="button"
                            class="button bg-secondary"
                            @click="onStartNewVictimForm"
                        >
                            Person nicht gefunden? Neue Person anlegen
                        </button>
                    </div>
                </div>

                <!-- Formular für neue Person -->
                <div v-if="showNewVictimFormValue" class="new-victim-form">
                    <h4>Neue Person anlegen</h4>
                    <div class="form-group">
                        <label for="firstName">Vorname *</label>
                        <input
                            v-model="newVictimLocal.firstName"
                            type="text"
                            id="firstName"
                            required
                        />
                    </div>
                    <div class="form-group">
                        <label for="lastName">Nachname *</label>
                        <input
                            v-model="newVictimLocal.lastName"
                            type="text"
                            id="lastName"
                            required
                        />
                    </div>
                    <div class="form-group">
                        <label for="birthDate">Geburtsdatum</label>
                        <input
                            v-model="newVictimLocal.birthDate"
                            type="date"
                            id="birthDate"
                        />
                    </div>
                    <div class="form-group">
                        <label for="deathDate">Sterbedatum</label>
                        <input
                            v-model="newVictimLocal.deathDate"
                            type="date"
                            id="deathDate"
                        />
                    </div>
                    <div class="form-group">
                        <label for="description">Kurze Beschreibung</label>
                        <textarea
                            v-model="newVictimLocal.description"
                            id="description"
                            rows="3"
                        ></textarea>
                    </div>
                    <div class="button-group">
                        <button
                            type="button"
                            class="button bg-secondary"
                            @click="onCancelNewVictimForm"
                        >
                            Abbrechen
                        </button>
                        <button
                            type="button"
                            class="button"
                            @click="onAddNewVictim"
                            :disabled="!canAddNewVictim"
                        >
                            Person hinzufügen
                        </button>
                    </div>
                </div>

                <!-- Ausgewählte Opfer -->
                <div
                    v-if="selectedVictimsValue.length > 0"
                    class="selected-victims"
                >
                    <h4>Ausgewählte Opfer:</h4>
                    <div
                        v-for="victim in selectedVictimsValue"
                        :key="victim.id || victim.tempId"
                        class="selected-victim"
                    >
                        <span class="victim-name">
                            <a
                                :href="`https://www.wikidata.org/wiki/${victim.id}`"
                                target="_blank"
                            >
                                {{
                                    victim.label ||
                                    `${victim.firstName} ${victim.lastName}`
                                }}
                            </a>
                        </span>
                        <span
                            class="victim-description"
                            v-if="victim.description"
                        >
                            - {{ victim.description }}
                        </span>
                        <button
                            type="button"
                            class="remove-victim"
                            @click="onRemoveVictim(victim.id || victim.tempId)"
                        >
                            ✕
                        </button>
                    </div>
                </div>

                <!-- Button für weiteres Opfer -->
                <div
                    v-if="
                        !showNewVictimFormValue &&
                        (!peopleSearchResultsValue.length ||
                            selectedVictimsValue.length > 0)
                    "
                    class="add-another-victim"
                >
                    <button
                        type="button"
                        class="button bg-secondary"
                        @click="onStartAddingAnotherVictim"
                    >
                        Weiteres Opfer hinzufügen
                    </button>
                </div>
            </div>

            <label for="location">Standort auswählen *</label>
            <div class="map-search">
                <input
                    v-model="locationSearchValue"
                    type="text"
                    placeholder="Adresse oder Ort suchen..."
                    @keydown.enter.prevent="onSearchLocation"
                />
                <button type="button" @click="onSearchLocation">Suchen</button>
            </div>
            <div class="map-container">
                <l-map
                    ref="mapRef"
                    :zoom="mapZoomValue"
                    :center="mapCenterValue"
                    @click="onHandleMapClick"
                    @ready="onMapReady"
                    style="height: 100%; width: 100%"
                >
                    <l-tile-layer
                        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                        layer-type="base"
                        name="OpenStreetMap"
                    ></l-tile-layer>
                    <l-marker
                        v-if="selectedLocationValue"
                        :lat-lng="selectedLocationValue"
                        :icon="markerIcon"
                        :z-index-offset="1000"
                    ></l-marker>
                </l-map>
                <div class="selected-coordinates" v-if="selectedLocationValue">
                    <strong>Ausgewählte Koordinaten:</strong>
                    {{ selectedLocationValue.lat.toFixed(6) }},
                    {{ selectedLocationValue.lng.toFixed(6) }}
                </div>
            </div>

            <div class="button-container">
                <button
                    class="button bg-secondary"
                    type="button"
                    @click="onGoToStep(1)"
                >
                    Zurück
                </button>
                <button class="button" type="button" @click="onSubmit">
                    Eintrag anlegen
                </button>
            </div>
            <div v-if="errorValue" class="bg-error">{{ errorValue }}</div>
        </form>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from "vue";
import * as L from "leaflet";
import "leaflet/dist/leaflet.css";
import { LMap, LTileLayer, LMarker } from "@vue-leaflet/vue-leaflet";

// Props von der übergeordneten Komponente
const props = defineProps({
    formsTitle: {
        type: String,
        default: null,
    },
    formsDescription: {
        type: String,
        default: null,
    },
    wikidataResults: {
        type: Array,
        default: () => [],
    },
    peopleSearchResults: {
        type: Array,
        default: () => [],
    },
    selectedVictims: {
        type: Array,
        default: () => [],
    },
    showNewVictimForm: {
        type: Boolean,
        default: false,
    },
    newVictim: {
        type: Object,
        default: () => ({
            firstName: "",
            lastName: "",
            birthDate: null,
            deathDate: null,
            description: "",
        }),
    },
    mapZoom: {
        type: Number,
        default: 8,
    },
    mapCenter: {
        type: Array,
        default: () => [52.8, 9.0],
    },
    selectedLocation: {
        type: Object,
        default: null,
    },
    locationSearch: {
        type: String,
        default: "",
    },
    error: {
        type: String,
        default: null,
    },
});

// Emit-Ereignisse, die an die Elternkomponente gesendet werden
const emit = defineEmits([
    "update:formsTitle",
    "update:formsDescription",
    "update:wikidataResults",
    "update:peopleSearchResults",
    "update:selectedVictims",
    "update:showNewVictimForm",
    "update:newVictim",
    "update:mapZoom",
    "update:mapCenter",
    "update:selectedLocation",
    "update:locationSearch",
    "update:error",
    "searchWikidataEntities",
    "selectExistingEntity",
    "searchPeople",
    "selectVictim",
    "startNewVictimForm",
    "cancelNewVictimForm",
    "addNewVictim",
    "removeVictim",
    "startAddingAnotherVictim",
    "searchLocation",
    "handleMapClick",
    "goToStep",
    "submit",
]);

// Lokale Zustandsverwaltung durch Computed Properties
const title = computed({
    get: () => props.formsTitle,
    set: (value) => emit("update:formsTitle", value),
});

const description = computed({
    get: () => props.formsDescription,
    set: (value) => emit("update:formsDescription", value),
});

// Zugriff auf übergebene Werte durch Computed Properties (nur lesen)
const wikidataResultsValue = computed(() => props.wikidataResults);
const peopleSearchResultsValue = computed(() => props.peopleSearchResults);
const selectedVictimsValue = computed(() => props.selectedVictims);
const showNewVictimFormValue = computed(() => props.showNewVictimForm);
const mapZoomValue = computed(() => props.mapZoom);
const mapCenterValue = computed(() => props.mapCenter);
const selectedLocationValue = computed(() => props.selectedLocation);
const locationSearchValue = computed({
    get: () => props.locationSearch,
    set: (value) => emit("update:locationSearch", value),
});
const errorValue = computed(() => props.error);

// Lokale Kopie des newVictim Objekts
const newVictimLocal = ref({
    firstName: "",
    lastName: "",
    birthDate: null,
    deathDate: null,
    description: "",
});

// Beobachte Änderungen im Props und aktualisiere die lokale Kopie
watch(
    () => props.newVictim,
    (newValue) => {
        newVictimLocal.value = { ...newValue };
    },
    { immediate: true, deep: true },
);

// Benutzerdefiniertes Marker-Icon erstellen
const createCustomIcon = (type) => {
    return L.icon({
        iconUrl: `/images/leaflet/markerIcons/coloredFilledWhite/events.svg`,
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowUrl: "/images/leaflet/marker-shadow.png",
        shadowSize: [41, 41],
    });
};

// Icon für den Marker (verwende Q6983405 für Ereignisse)
const markerIcon = createCustomIcon("Q6983405");

// Map reference und Initialisierung
const mapRef = ref(null);
const leafletMap = ref(null); // Referenz zur Leaflet-Map-Instance

// Wird aufgerufen, wenn die Leaflet-Karte bereit ist
const onMapReady = (map) => {
    leafletMap.value = map;

    setTimeout(() => {
        if (leafletMap.value) {
            leafletMap.value.invalidateSize();
            if (selectedLocationValue.value) {
                leafletMap.value.setView(
                    [
                        selectedLocationValue.value.lat,
                        selectedLocationValue.value.lng,
                    ],
                    mapZoomValue.value,
                );
            } else {
                leafletMap.value.setView(
                    mapCenterValue.value,
                    mapZoomValue.value,
                );
            }
        }
    }, 200);
};

// Computed für die Prüfung, ob ein neues Opfer hinzugefügt werden kann
const canAddNewVictim = computed(() => {
    return newVictimLocal.value.firstName && newVictimLocal.value.lastName;
});

// Ereignishandler, die Emits an die Elternkomponente senden
const onTitleInput = () => emit("searchWikidataEntities");
const onSelectExistingEntity = (entity) => emit("selectExistingEntity", entity);
const onSearchPeople = (query) => emit("searchPeople", query);
const onSelectVictim = (person) => emit("selectVictim", person);
const onStartNewVictimForm = () => emit("startNewVictimForm");
const onCancelNewVictimForm = () => emit("cancelNewVictimForm");

const onAddNewVictim = () => {
    emit("update:newVictim", newVictimLocal.value);
    emit("addNewVictim");
};

const onRemoveVictim = (id) => emit("removeVictim", id);
const onStartAddingAnotherVictim = () => emit("startAddingAnotherVictim");
const onSearchLocation = () => emit("searchLocation");
const onHandleMapClick = (e) => {
    // Lokaler Map-Click-Handler, der die Emit-Funktion aufruft
    emit("handleMapClick", e);

    // Zusätzlich, falls wir lokale Map-Aktualisierungen benötigen
    if (leafletMap.value) {
        setTimeout(() => {
            leafletMap.value.invalidateSize();
        }, 50);
    }
};
const onGoToStep = (step) => emit("goToStep", step);
const onSubmit = () => emit("submit");
</script>

<style scoped>
.font-family-special-elite {
    font-family: "Special Elite" !important;
}

.forms-container {
    max-width: 800px;
}

.step-container {
    background-color: rgba(255, 255, 255, 0.9);
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.step-title {
    margin-bottom: 20px;
    font-size: 1.5rem;
    color: #333;
}

.forms {
    --max-width: 100%;

    display: flex;
    flex-direction: column;
    align-items: flex-start;
    width: 100%;
}

.forms > *:not(:last-child) {
    margin-bottom: 1rem;
}

.forms input,
.forms textarea {
    padding: 0.5rem 0.75rem;
    width: 100%;
    background-color: white;
    border: 1px solid #ddd;
}

.forms textarea {
    min-height: 8.5rem;
    resize: none;
}

.forms fieldset {
    display: block;
    width: 100%;
    border-color: transparent;
}

.forms fieldset div {
    margin-top: 0.75rem;
}

.forms fieldset div {
    display: flex;
    flex-direction: row;
    align-items: center;
}

.forms fieldset div input {
    margin-right: 0.75rem;
    width: 1rem;
    height: 1rem;
}

.forms fieldset div label {
    flex: 1 0 0;
}

.button-container {
    display: flex;
    justify-content: space-between;
    width: 100%;
    margin-top: 1.5rem;
}

.button {
    padding: 10px 20px;
    background-color: #1976d2;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
}

.button:hover {
    background-color: #1565c0;
}

.button:disabled {
    background-color: #bdbdbd;
    cursor: not-allowed;
}

.button.bg-secondary {
    background-color: #757575;
}

.button.bg-secondary:hover {
    background-color: #616161;
}

.bg-error {
    color: #d32f2f;
    margin-top: 1rem;
    font-weight: bold;
}

/* Leaflet map specific fixes */
.leaflet-pane {
    z-index: 1;
}

.leaflet-tile-pane {
    z-index: 0;
}

.leaflet-tile {
    margin: 0 !important;
    padding: 0 !important;
}

.leaflet-tile-container img {
    width: 256px !important;
    height: 256px !important;
    margin: 0 !important;
    padding: 0 !important;
    left: 0 !important;
    top: 0 !important;
    border: none !important;
}

.leaflet-control {
    z-index: 10;
}

.leaflet-top,
.leaflet-bottom {
    z-index: 10;
}

.map-container {
    width: 600px;
    height: 400px;
    margin-bottom: 1rem;
    border: 1px solid #ccc;
    border-radius: 4px;
    overflow: visible; /* Geändert von hidden zu visible, damit Suchfeld sichtbar bleibt */
    position: relative;
    max-width: 100%;
}

.map-container .leaflet-container {
    width: 100% !important;
    height: 400px !important;
    background-color: #f8f8f8;
    overflow: hidden;
}

.map-container::after {
    content: "";
    display: block;
    clear: both;
}

.selected-coordinates {
    margin-top: 0.5rem;
    background-color: #f5f5f5;
    padding: 8px;
    border-radius: 4px;
    font-size: 0.9rem;
}

.map-search {
    margin-top: 0.5rem;
    display: flex;
    width: 100%;
    position: relative;
    z-index: 10;
}

.map-search input {
    flex-grow: 1;
    margin-right: 0.5rem;
    padding: 0.5rem;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.map-search button {
    padding: 0.5rem 1rem;
    background-color: #1976d2;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

/* Wikidata search results */
.search-results {
    width: 100%;
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #ccc;
    border-radius: 4px;
    margin-top: -0.5rem;
    background-color: #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.search-info {
    padding: 8px 12px;
    font-size: 0.9rem;
    color: #666;
    border-bottom: 1px solid #eee;
    margin: 0;
}

.search-result-item {
    padding: 10px 12px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    transition: background-color 0.2s;
}

.search-result-item:hover {
    background-color: #f5f5f5;
}

.search-result-item:last-child {
    border-bottom: none;
}

.result-title {
    font-weight: bold;
    margin-bottom: 3px;
}

.result-description {
    font-size: 0.9rem;
    color: #555;
    margin-bottom: 3px;
}

.result-id {
    font-size: 0.8rem;
    color: #888;
}

/* Opfer/Victims Styling */
.victims-section {
    width: 100%;
    margin-bottom: 1.5rem;
}

.victims-search {
    margin-bottom: 0.5rem;
}

.form-group {
    margin-bottom: 1rem;
    width: 100%;
}

.form-group label {
    display: block;
    margin-bottom: 0.25rem;
    font-weight: 500;
}

.new-victim-form {
    background-color: #f5f5f5;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}

.new-victim-form h4 {
    margin-top: 0;
    margin-bottom: 1rem;
}

.button-group {
    display: flex;
    justify-content: space-between;
}

.selected-victims {
    margin-top: 1rem;
    padding: 0.5rem;
    background-color: #f5f5f5;
    border-radius: 4px;
}

.selected-victim {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    background-color: white;
    border-radius: 4px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.victim-name {
    font-weight: 500;
    margin-right: 0.5rem;
}

.victim-description {
    color: #666;
    font-size: 0.9rem;
    flex-grow: 1;
}

.remove-victim {
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
.add-another-victim {
    padding: 0.75rem;
    border-top: 1px solid #eee;
    text-align: center;
}
</style>
