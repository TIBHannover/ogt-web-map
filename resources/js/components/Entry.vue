<template>
    <v-container id="container" class="mb-110">
        <v-row
            :style="{
                'padding-left':
                    freeClientWidth < 75 && !isMenuDisplayed
                        ? 75 - freeClientWidth + 'px'
                        : 0,
            }"
        >
            <v-col>
                <h1
                    class="font-family-special-elite font-weight-bold hyphens-auto py-4 text-h4 text-md-h3"
                    lang="de"
                >
                    Eintrag anlegen
                </h1>
            </v-col>
        </v-row>

        <v-row>
            <v-col>
                <div class="forms-container">
                    <!-- Schritt 1: Eintragstyp wählen -->
                    <div v-if="currentStep === 1" class="step-container">
                        <h2 class="step-title">
                            Schritt 1: Wählen Sie den Eintragstyp
                        </h2>
                        <form class="forms">
                            <fieldset>
                                <legend for="type">Eintrag-Typ</legend>
                                <div>
                                    <input
                                        v-model="formsType"
                                        name="type"
                                        type="radio"
                                        value="Q6983405"
                                        id="type-Q6983405"
                                    />
                                    <label for="type-Q6983405">Ereignis</label>
                                </div>
                                <div>
                                    <input
                                        v-model="formsType"
                                        name="type"
                                        type="radio"
                                        value="Q5003624"
                                        id="type-Q5003624"
                                    />
                                    <label for="type-Q5003624"
                                        >Erinnerungsort</label
                                    >
                                </div>
                                <div>
                                    <input
                                        v-model="formsType"
                                        name="type"
                                        type="radio"
                                        value="Q108047567"
                                        id="type-Q108047567"
                                    />
                                    <label for="type-Q108047567"
                                        >Staatspolizeistelle</label
                                    >
                                </div>
                                <div>
                                    <input
                                        v-model="formsType"
                                        name="type"
                                        type="radio"
                                        value="Q108047541"
                                        id="type-Q108047541"
                                    />
                                    <label for="type-Q108047541"
                                        >Staatspolizeiaußenstelle</label
                                    >
                                </div>
                                <div>
                                    <input
                                        v-model="formsType"
                                        name="type"
                                        type="radio"
                                        value="Q277565"
                                        id="type-Q277565"
                                    />
                                    <label for="type-Q277565"
                                        >Arbeitserziehungslager</label
                                    >
                                </div>
                                <div>
                                    <input
                                        v-model="formsType"
                                        name="type"
                                        type="radio"
                                        value="Q40357"
                                        id="type-Q40357"
                                    />
                                    <label for="type-Q40357">Gefängnis</label>
                                </div>
                            </fieldset>
                            <div class="button-container">
                                <button
                                    class="button"
                                    type="button"
                                    @click="goToStep(2)"
                                    :disabled="!formsType"
                                >
                                    Weiter
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Schritt 2: Ereignis-Formular -->
                    <EventsForm
                        v-if="currentStep === 2 && formsType === 'Q6983405'"
                        v-model:formsTitle="formsTitle"
                        v-model:formsDescription="formsDescription"
                        v-model:wikidataResults="wikidataResults"
                        v-model:peopleSearchResults="peopleSearchResults"
                        v-model:selectedVictims="selectedVictims"
                        v-model:showNewVictimForm="showNewVictimForm"
                        v-model:newVictim="newVictim"
                        v-model:perpetratorsSearchResults="
                            perpetratorsSearchResults
                        "
                        v-model:selectedPerpetrators="selectedPerpetrators"
                        v-model:showNewPerpetratorForm="showNewPerpetratorForm"
                        v-model:newPerpetrator="newPerpetrator"
                        v-model:eventTimeType="eventTimeType"
                        v-model:eventDate="eventDate"
                        v-model:eventStartDate="eventStartDate"
                        v-model:eventEndDate="eventEndDate"
                        v-model:victimCount="victimCount"
                        v-model:survivorCount="survivorCount"
                        v-model:deathCount="deathCount"
                        v-model:arrestCount="arrestCount"
                        v-model:cause="cause"
                        v-model:causeSearchResults="causeSearchResults"
                        v-model:selectedCauses="selectedCauses"
                        v-model:mapZoom="mapZoom"
                        v-model:mapCenter="mapCenter"
                        v-model:selectedLocation="selectedLocation"
                        v-model:locationSearch="locationSearch"
                        v-model:error="error"
                        v-model:imageUrl="imageUrl"
                        v-model:imageFilename="imageFilename"
                        @searchWikidataEntities="searchWikidataEntities"
                        @selectExistingEntity="selectExistingEntity"
                        @searchPeople="searchPeople"
                        @selectVictim="selectVictim"
                        @startNewVictimForm="startNewVictimForm"
                        @cancelNewVictimForm="cancelNewVictimForm"
                        @addNewVictim="addNewVictim"
                        @removeVictim="removeVictim"
                        @startAddingAnotherVictim="startAddingAnotherVictim"
                        @searchPerpetrators="searchPerpetrators"
                        @selectPerpetrator="selectPerpetrator"
                        @startNewPerpetratorForm="startNewPerpetratorForm"
                        @cancelNewPerpetratorForm="cancelNewPerpetratorForm"
                        @addNewPerpetrator="addNewPerpetrator"
                        @removePerpetrator="removePerpetrator"
                        @startAddingAnotherPerpetrator="
                            startAddingAnotherPerpetrator
                        "
                        @searchCause="searchCause"
                        @selectCause="selectCause"
                        @removeCause="removeCause"
                        @searchLocation="searchLocation"
                        @handleMapClick="handleMapClick"
                        @goToStep="goToStep"
                        @submit="resetForm"
                    />

                    <!-- Schritt 2: Erinnerungsort-Formular -->
                    <div
                        v-if="currentStep === 2 && formsType === 'Q5003624'"
                        class="step-container"
                    >
                        <h2 class="step-title">
                            Schritt 2: Erinnerungsort-Details eingeben
                        </h2>
                        <form class="forms">
                            <label for="title">Titel *</label>
                            <input
                                v-model="formsTitle"
                                name="title"
                                type="text"
                                maxlength="250"
                                placeholder="z.B. Gedenkstätte Bergen-Belsen"
                                @input="searchWikidataEntities"
                            />

                            <!-- Anzeige der Suchergebnisse -->
                            <div
                                v-if="wikidataResults.length > 0"
                                class="search-results"
                            >
                                <p class="search-info">
                                    Möglicherweise existiert dieser Eintrag
                                    bereits:
                                </p>
                                <div
                                    v-for="result in wikidataResults"
                                    :key="result.id"
                                    class="search-result-item"
                                    @click="selectExistingEntity(result)"
                                >
                                    <div class="result-title">
                                        {{ result.label }}
                                    </div>
                                    <div
                                        class="result-description"
                                        v-if="result.description"
                                    >
                                        {{ result.description }}
                                    </div>
                                    <div class="result-id">{{ result.id }}</div>
                                </div>
                            </div>

                            <label for="description">Kurzbeschreibung *</label>
                            <textarea
                                v-model="formsDescription"
                                name="description"
                                maxlength="750"
                                placeholder="z.B. Gedenkstätte für die Opfer des Konzentrationslagers Bergen-Belsen."
                            ></textarea>

                            <label for="location">Standort auswählen: *</label>
                            <div class="map-container">
                                <l-map
                                    ref="mapRef"
                                    :zoom="mapZoom"
                                    :center="mapCenter"
                                    @click="handleMapClick"
                                    style="
                                        height: 400px;
                                        width: 600px;
                                        z-index: 0;
                                        position: relative;
                                    "
                                >
                                    <l-tile-layer
                                        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                                        layer-type="base"
                                        name="OpenStreetMap"
                                    ></l-tile-layer>
                                    <l-marker
                                        v-if="selectedLocation"
                                        :lat-lng="selectedLocation"
                                        :icon="markerIcon"
                                        :z-index-offset="1000"
                                    ></l-marker>
                                </l-map>
                                <div
                                    class="selected-coordinates"
                                    v-if="selectedLocation"
                                >
                                    <strong>Ausgewählte Koordinaten:</strong>
                                    {{ selectedLocation.lat.toFixed(6) }},
                                    {{ selectedLocation.lng.toFixed(6) }}
                                </div>
                                <div class="map-search">
                                    <input
                                        v-model="locationSearch"
                                        type="text"
                                        placeholder="Adresse oder Ort suchen..."
                                        @keydown.enter.prevent="searchLocation"
                                    />
                                    <button
                                        type="button"
                                        @click="searchLocation"
                                    >
                                        Suchen
                                    </button>
                                </div>
                            </div>

                            <div class="button-container">
                                <button
                                    class="button bg-secondary"
                                    type="button"
                                    @click="goToStep(1)"
                                >
                                    Zurück
                                </button>
                                <button
                                    class="button"
                                    type="button"
                                    @click="resetForm"
                                >
                                    Eintrag anlegen
                                </button>
                            </div>
                            <div v-if="error" class="bg-error">{{ error }}</div>
                        </form>
                    </div>

                    <!-- Fügen Sie hier weitere Formulare für andere Typen hinzu -->
                    <!-- Für andere Typen können ähnliche Formulare mit spezifischen Anforderungen erstellt werden -->
                    <div
                        v-if="
                            currentStep === 2 &&
                            !['Q6983405', 'Q5003624'].includes(formsType)
                        "
                        class="step-container"
                    >
                        <h2 class="step-title">Schritt 2: Details eingeben</h2>
                        <p>
                            Formular für diesen Eintragstyp wird bald verfügbar
                            sein.
                        </p>
                        <div class="button-container">
                            <button
                                class="button bg-secondary"
                                type="button"
                                @click="goToStep(1)"
                            >
                                Zurück
                            </button>
                        </div>
                    </div>
                </div>
                <br /><br /><br /><br />
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from "vue";
import { LMap, LTileLayer, LMarker } from "@vue-leaflet/vue-leaflet";
import "leaflet/dist/leaflet.css";
import * as L from "leaflet";
import axios from "axios";
import { debounce } from "lodash";

// Import the EventsForm component
import EventsForm from "./entry/EventsForm.vue";

// Props
const props = defineProps({
    isMenuDisplayed: Boolean,
});

// Reactive references
const freeClientWidth = ref(document.documentElement.clientWidth);
const error = ref(null);
const formsType = ref("Q6983405");
const formsTitle = ref(null);
const formsDescription = ref(null);
const isSubmitting = ref(false);
const wikidataResults = ref([]);
const peopleSearchResults = ref([]); // For victims search
const selectedVictims = ref([]); // Store selected victims
const currentStep = ref(1); // Aktueller Schritt des Formulars
const showNewVictimForm = ref(false);
const newVictim = ref({
    firstName: "",
    lastName: "",
    birthDate: null,
    deathDate: null,
    description: "",
});
const lastSearchQuery = ref("");

// Täter (Perpetrator) Zustandsvariablen
const perpetratorsSearchResults = ref([]);
const selectedPerpetrators = ref([]);
const showNewPerpetratorForm = ref(false);
const newPerpetrator = ref({
    firstName: "",
    lastName: "",
    birthDate: null,
    deathDate: null,
    description: "",
});
const lastPerpetratorSearchQuery = ref("");

// Zeitpunkt/Zeitraum Zustandsvariablen
const eventTimeType = ref("point"); // 'point' oder 'range'
const eventDate = ref(null);
const eventStartDate = ref(null);
const eventEndDate = ref(null);

// Opferzahlen und Ursache
const victimCount = ref(0);
const survivorCount = ref(0);
const deathCount = ref(0);
const arrestCount = ref(0);
const cause = ref("");
const causeSearchResults = ref([]);
const selectedCauses = ref([]);
const lastCauseSearchQuery = ref("");

// Karten-spezifische Daten
const mapRef = ref(null);
const mapZoom = ref(8); // Angepasster Zoom für Niedersachsen
const mapCenter = ref([52.8, 9.0]); // Zentrum von Niedersachsen
const selectedLocation = ref(null); // Ausgewählte Koordinaten
const locationSearch = ref(""); // Suchtext für Standorte

// Bild-spezifische Daten
const imageUrl = ref(null);
const imageFilename = ref(null);

// Benutzerdefiniertes Marker-Icon erstellen
const createCustomIcon = (type) => {
    return L.icon({
        iconUrl: `/images/leaflet/markerIcons/coloredFilledWhite/${type}.svg`,
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowUrl: "/images/leaflet/marker-shadow.png",
        shadowSize: [41, 41],
    });
};

// Icon für den Marker basierend auf dem Typ
const markerIcon = computed(() => {
    if (formsType.value === "Q6983405") return createCustomIcon("events");
    if (formsType.value === "Q5003624") return createCustomIcon("memorials");
    if (formsType.value === "Q108047567")
        return createCustomIcon("statePoliceOffices");
    if (formsType.value === "Q108047541")
        return createCustomIcon("fieldOffices");
    if (formsType.value === "Q277565")
        return createCustomIcon("laborEducationCamps");
    if (formsType.value === "Q40357") return createCustomIcon("prisons");
    return createCustomIcon("events"); // Standard-Icon
});

// Methods
const setFreeClientWidth = () => {
    const container = document.getElementById("container");
    freeClientWidth.value =
        (document.documentElement.clientWidth - container.clientWidth) / 2;
};

// Navigation zwischen den Schritten
const goToStep = (step) => {
    if (step === 2 && !formsType.value) {
        error.value = "Bitte wählen Sie einen Eintragstyp aus";
        return;
    }

    error.value = null; // Fehler zurücksetzen beim Schrittwechsel
    currentStep.value = step;
};

// Karte zuru00fccksetzen auf Niedersachsen-Ansicht
const resetMapView = () => {
    if (mapRef.value && mapRef.value.leafletObject) {
        mapRef.value.leafletObject.setView(mapCenter.value, mapZoom.value, {
            reset: true,
            animate: false,
        });
    }
    selectedLocation.value = null;
};

// Reinitialisiert die Karte und sorgt fu00fcr korrekte Darstellung
const reinitializeMap = () => {
    // CSS-Fixes anwenden
    applyLeafletCSS();

    // Map neu initialisieren
    if (mapRef.value && mapRef.value.leafletObject) {
        setTimeout(() => {
            mapRef.value.leafletObject.invalidateSize(true);
            resetMapView();

            // Erzwinge ein Update der Tile-Layer
            mapRef.value.leafletObject.eachLayer(function (layer) {
                if (layer instanceof L.TileLayer) {
                    layer.redraw();
                }
            });
        }, 100);
    }
};

// OpenStreetMap-Integration
const handleMapClick = (event) => {
    console.log("Map clicked!", event.latlng);
    // Die angeklickten Koordinaten speichern
    selectedLocation.value = event.latlng;

    // Sicherstellen, dass die Karte aktualisiert wird, falls der Marker nicht angezeigt wird
    if (mapRef.value && mapRef.value.leafletObject) {
        // Kurzes Timeout, um die Vue-Aktualisierung abzuwarten
        setTimeout(() => {
            mapRef.value.leafletObject.invalidateSize(true);
        }, 50);
    }
};

// Standortsuche mit Nominatim (OpenStreetMap Geocoding-Service)
const searchLocation = async () => {
    if (!locationSearch.value) return;

    try {
        const response = await axios.get(
            `https://nominatim.openstreetmap.org/search`,
            {
                params: {
                    q: locationSearch.value,
                    format: "json",
                    limit: 1,
                },
                headers: {
                    "Accept-Language": "de",
                },
            },
        );

        if (response.data && response.data.length > 0) {
            const result = response.data[0];
            const lat = parseFloat(result.lat);
            const lng = parseFloat(result.lon);

            // Karte auf das gefundene Ergebnis zentrieren
            mapCenter.value = [lat, lng];
            mapZoom.value = 15; // Näher heranzoomen

            // Marker setzen
            selectedLocation.value = { lat, lng };
        } else {
            alert("Keine Ergebnisse gefunden");
        }
    } catch (err) {
        console.error("Fehler bei der Standortsuche:", err);
        alert("Fehler bei der Suche");
    }
};

// Debounce-Funktion für die Wikidata-Suche
const searchWikidataEntities = debounce(async () => {
    if (!formsTitle.value || formsTitle.value.length < 3) {
        wikidataResults.value = [];
        return;
    }

    try {
        const response = await axios.get("/api/wikidata/entities", {
            params: {
                title: formsTitle.value,
                limit: 5,
            },
        });

        if (response.data.success && response.data.results) {
            wikidataResults.value = response.data.results;
        } else {
            wikidataResults.value = [];
        }
    } catch (err) {
        console.error("Fehler bei der Wikidata-Suche:", err);
        wikidataResults.value = [];
    }
}, 300);

// CSS Reset fu00fcr Leaflet-Tiles
const applyLeafletCSS = () => {
    // Lu00f6sung fu00fcr Probleme mit Tile-Abstu00e4nden
    const styleId = "leaflet-tile-fix";

    // Entferne bereits existierende Styles mit dieser ID
    const existingStyle = document.getElementById(styleId);
    if (existingStyle) {
        existingStyle.remove();
    }

    const style = document.createElement("style");
    style.id = styleId;
    style.textContent = `
        /* Grundlegende Map-Fixes */
        .leaflet-container {
            height: 400px !important;
            width: 600px !important;
            z-index: 0 !important;
        }

        /* Container-Fixes */
        .leaflet-tile-container {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            height: 100% !important;
            transform-origin: 0 0 !important;
        }

        /* Tile-Fixes */
        .leaflet-tile {
            position: absolute !important;
            box-shadow: none !important;
            border: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Korrektur fu00fcr Tile-Bilder */
        .leaflet-tile-container img {
            display: block !important;
            width: 256px !important;
            height: 256px !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            box-shadow: none !important;
        }

        /* Marker-Fixes */
        .leaflet-marker-pane {
            position: absolute;
            z-index: 100 !important;
        }

        .leaflet-marker-icon {
            position: absolute;
            z-index: 1000 !important;
        }
    `;
    document.head.appendChild(style);
};

// Bei Auswahl eines existierenden Eintrags
const selectExistingEntity = (entity) => {
    // Öffne den Wikidata-Eintrag in einem neuen Fenster/Tab
    const wikidataUrl = `https://www.wikidata.org/wiki/${entity.id}`;
    window.open(wikidataUrl, "_blank");

    // Leere die Suchergebnisse nach dem Öffnen
    wikidataResults.value = [];
};

// Opfer-Suche und Formular Funktionen
const searchPeople = debounce(async (query) => {
    if (!query || query.length < 2) {
        peopleSearchResults.value = [];
        return;
    }

    lastSearchQuery.value = query;

    try {
        const response = await axios.get("/api/wikidata/search/people", {
            params: { query },
        });
        peopleSearchResults.value = response.data;
    } catch (error) {
        console.error("Fehler bei der Personensuche:", error);
        peopleSearchResults.value = [];
    }
}, 300);

const startNewVictimForm = () => {
    showNewVictimForm.value = true;
    // Vorname/Nachname aus der Suche vorausfüllen, wenn möglich
    if (lastSearchQuery.value) {
        const parts = lastSearchQuery.value.trim().split(" ");
        if (parts.length > 1) {
            newVictim.value.firstName = parts[0];
            newVictim.value.lastName = parts.slice(1).join(" ");
        } else {
            newVictim.value.lastName = parts[0];
        }
    }
};

const cancelNewVictimForm = () => {
    showNewVictimForm.value = false;
    resetNewVictimForm();
};

const resetNewVictimForm = () => {
    newVictim.value = {
        firstName: "",
        lastName: "",
        birthDate: null,
        deathDate: null,
        description: "",
    };
    lastSearchQuery.value = "";
};

const addNewVictim = () => {
    const tempId = "new_" + Date.now();
    selectedVictims.value.push({
        tempId: tempId,
        firstName: newVictim.value.firstName,
        lastName: newVictim.value.lastName,
        birthDate: newVictim.value.birthDate,
        deathDate: newVictim.value.deathDate,
        description: newVictim.value.description,
    });

    showNewVictimForm.value = false;
    resetNewVictimForm();
};

const startAddingAnotherVictim = () => {
    showNewVictimForm.value = false;
    resetNewVictimForm();
    peopleSearchResults.value = [];
};

// Computed property für die Validierung des neuen Opfer-Formulars
const canAddNewVictim = computed(() => {
    return newVictim.value.firstName.trim() && newVictim.value.lastName.trim();
});

const selectVictim = (person) => {
    // Check if person is not already selected
    if (!selectedVictims.value.some((v) => v.id === person.id)) {
        selectedVictims.value.push({
            id: person.id,
            label: person.label,
            description: person.description,
            birth: person.birth,
            death: person.death,
        });
    }
    peopleSearchResults.value = []; // Clear search results
    lastSearchQuery.value = "";
};

const removeVictim = (victimId) => {
    selectedVictims.value = selectedVictims.value.filter(
        (v) => (v.id || v.tempId) !== victimId,
    );
};

// Tu00e4ter Funktionen
const searchPerpetrators = debounce(async (query) => {
    if (!query || query.length < 2) {
        perpetratorsSearchResults.value = [];
        return;
    }

    lastPerpetratorSearchQuery.value = query;

    try {
        const response = await axios.get("/api/wikidata/search/people", {
            params: { query, type: "perpetrators" },
        });
        perpetratorsSearchResults.value = response.data;
    } catch (error) {
        console.error("Fehler bei der Tu00e4tersuche:", error);
        perpetratorsSearchResults.value = [];
    }
}, 300);

const startNewPerpetratorForm = () => {
    showNewPerpetratorForm.value = true;
    // Vorname/Nachname aus der Suche vorausfu00fcllen, wenn möglich
    if (lastPerpetratorSearchQuery.value) {
        const parts = lastPerpetratorSearchQuery.value.trim().split(" ");
        if (parts.length > 1) {
            newPerpetrator.value.firstName = parts[0];
            newPerpetrator.value.lastName = parts.slice(1).join(" ");
        } else {
            newPerpetrator.value.lastName = parts[0];
        }
    }
};

const cancelNewPerpetratorForm = () => {
    showNewPerpetratorForm.value = false;
    resetNewPerpetratorForm();
};

const resetNewPerpetratorForm = () => {
    newPerpetrator.value = {
        firstName: "",
        lastName: "",
        birthDate: null,
        deathDate: null,
        description: "",
    };
    lastPerpetratorSearchQuery.value = "";
};

const addNewPerpetrator = () => {
    const tempId = "new_" + Date.now();
    selectedPerpetrators.value.push({
        tempId: tempId,
        firstName: newPerpetrator.value.firstName,
        lastName: newPerpetrator.value.lastName,
        birthDate: newPerpetrator.value.birthDate,
        deathDate: newPerpetrator.value.deathDate,
        description: newPerpetrator.value.description,
    });

    showNewPerpetratorForm.value = false;
    resetNewPerpetratorForm();
};

const startAddingAnotherPerpetrator = () => {
    showNewPerpetratorForm.value = false;
    resetNewPerpetratorForm();
    perpetratorsSearchResults.value = [];
};

// Computed property fu00fcr die Validierung des neuen Tu00e4ter-Formulars
const canAddNewPerpetrator = computed(() => {
    return (
        newPerpetrator.value.firstName.trim() &&
        newPerpetrator.value.lastName.trim()
    );
});

const selectPerpetrator = (person) => {
    // Check if person is not already selected
    if (!selectedPerpetrators.value.some((v) => v.id === person.id)) {
        selectedPerpetrators.value.push({
            id: person.id,
            label: person.label,
            description: person.description,
            birth: person.birth,
            death: person.death,
        });
    }
    perpetratorsSearchResults.value = []; // Clear search results
    lastPerpetratorSearchQuery.value = "";
};

const removePerpetrator = (perpetratorId) => {
    selectedPerpetrators.value = selectedPerpetrators.value.filter(
        (v) => (v.id || v.tempId) !== perpetratorId,
    );
};

// Ursachen-Funktionen
const searchCause = debounce(async (query) => {
    if (!query || query.length < 2) {
        causeSearchResults.value = [];
        return;
    }

    lastCauseSearchQuery.value = query;

    try {
        // Suche nach Wikidata-Einträgen
        const response = await axios.get("/api/wikidata/search/concepts", {
            params: {
                query,
            },
        });
        causeSearchResults.value = response.data;
    } catch (error) {
        console.error("Fehler bei der Ursachensuche:", error);
        causeSearchResults.value = [];
    }
}, 300);

const selectCause = (cause) => {
    // Prüfe, ob die Ursache bereits in der Liste ist, um Duplikate zu vermeiden
    if (!selectedCauses.value.some((item) => item.id === cause.id)) {
        selectedCauses.value.push({
            id: cause.id,
            label: cause.label,
            description: cause.description,
        });
    }
    causeSearchResults.value = [];
    lastCauseSearchQuery.value = "";
};

const removeCause = (causeId) => {
    selectedCauses.value = selectedCauses.value.filter(
        (cause) => cause.id !== causeId,
    );
};

const resetForm = async () => {
    error.value = null;
    isSubmitting.value = true;

    try {
        // Validierung abhängig vom Eintragstyp
        const missingFields = [];

        if (!(formsTitle.value || "").trim()) {
            missingFields.push("Titel");
        }

        if (!(formsDescription.value || "").trim()) {
            missingFields.push("Kurzbeschreibung");
        }

        // Für Ereignisse und Erinnerungsorte sind Koordinaten erforderlich
        if (
            ["Q6983405", "Q5003624"].includes(formsType.value) &&
            !selectedLocation.value
        ) {
            missingFields.push("Standort");
        }

        if (missingFields.length) {
            error.value = `Fehlende Pflichtfelder: ${missingFields.join(", ")}`;
            isSubmitting.value = false;
            return;
        }

        // Koordinaten vorbereiten für die API-Anfrage
        let coordinates = null;
        if (selectedLocation.value) {
            coordinates = `${selectedLocation.value.lat}, ${selectedLocation.value.lng}`;
        }

        // CSRF-Token für Laravel aus dem Meta-Tag holen
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        // Daten an Laravel-Backend senden
        const response = await axios.post(
            "/api/wikidata/entities",
            {
                title: formsTitle.value.trim(),
                description: formsDescription.value.trim(),
                type: formsType.value,
                coordinates: coordinates,
                victims: selectedVictims.value,
                perpetrators: selectedPerpetrators.value,
                eventTimeType: eventTimeType.value,
                eventDate: eventDate.value,
                eventStartDate: eventStartDate.value,
                eventEndDate: eventEndDate.value,
                victimCount: victimCount.value,
                survivorCount: survivorCount.value,
                deathCount: deathCount.value,
                arrestCount: arrestCount.value,
                cause: cause.value,
                causes: selectedCauses.value,
                imageUrl: imageUrl.value,
                imageFilename: imageFilename.value,
            },
            {
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                },
            },
        );

        if (response.data.success) {
            alert(`Eintrag erfolgreich erstellt: ${response.data.requestId}`);
            clearForm();
            currentStep.value = 1;
        } else {
            error.value =
                response.data.message ||
                "Fehler bei der Erstellung des Eintrags";
        }
    } catch (err) {
        console.error("Fehler bei der API-Anfrage:", err);
        error.value = `Fehler: ${err.response?.data?.message || err.message}`;
    } finally {
        isSubmitting.value = false;
    }
};

// Funktion zum Leeren des Formulars nach erfolgreicher Eingabe
const clearForm = () => {
    formsTitle.value = null;
    formsDescription.value = null;
    selectedLocation.value = null;
    selectedVictims.value = [];
    // newVictim zuru00fccksetzen
    newVictim.value = {
        firstName: "",
        lastName: "",
        birthDate: null,
        deathDate: null,
        description: "",
    };
    showNewVictimForm.value = false;

    // Neue Felder zuru00fccksetzen
    selectedPerpetrators.value = [];
    newPerpetrator.value = {
        firstName: "",
        lastName: "",
        birthDate: null,
        deathDate: null,
        description: "",
    };
    showNewPerpetratorForm.value = false;

    // Zeitpunkt/Zeitraum zuru00fccksetzen
    eventTimeType.value = "point";
    eventDate.value = null;
    eventStartDate.value = null;
    eventEndDate.value = null;

    // Opferzahlen und Ursache zuru00fccksetzen
    victimCount.value = 0;
    survivorCount.value = 0;
    deathCount.value = 0;
    arrestCount.value = 0;
    cause.value = "";
    causeSearchResults.value = [];
    selectedCauses.value = [];
    lastCauseSearchQuery.value = "";

    // Karte zuru00fccksetzen
    mapZoom.value = 8;
    mapCenter.value = [52.8, 9.0];
    locationSearch.value = "";

    // Bild zurücksetzen
    imageUrl.value = null;
    imageFilename.value = null;
};

// Watcher für currentStep - invalidiert die Kartengröu00dfe, wenn wir zu Schritt 2 gehen
watch(currentStep, (newStep) => {
    if (newStep === 2) {
        // Warte kurz, bis das DOM aktualisiert wurde, dann Kartengröu00dfe aktualisieren
        setTimeout(() => {
            // Verwende unsere neue Reinitialisierungsfunktion
            reinitializeMap();
        }, 300);
    }
});

// Lifecycle hooks
onMounted(() => {
    window.addEventListener("resize", setFreeClientWidth);

    // Apply the CSS fixes for Leaflet tiles with fixed size
    applyLeafletCSS();

    // Fix für Leaflet-Icons in Vue
    // Dies ist notwendig, da Leaflet Standard-Icon-Pfade erwartet
    // Siehe https://github.com/vue-leaflet/vue-leaflet/issues/158
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl:
            "https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon-2x.png",
        iconUrl: "https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon.png",
        shadowUrl:
            "https://unpkg.com/leaflet@1.7.1/dist/images/marker-shadow.png",
    });

    // Timing-Fix: Warte auf den nächsten Tick und initialisiere die Karte vollständig
    setTimeout(() => {
        // Verwende unsere neue Reinitialisierungsfunktion
        reinitializeMap();
    }, 300);
});

onUnmounted(() => {
    window.removeEventListener("resize", setFreeClientWidth);
});
</script>

<style scoped>
.font-family-special-elite {
    font-family: "Special Elite" !important;
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
    padding: 0.75rem 1rem;
    width: 100%;
    background-color: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}

.forms input:focus,
.forms textarea:focus {
    outline: none;
    border-color: #1976d2;
    box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.25);
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
    box-shadow: none;
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
    padding: 12px 24px;
    background-color: #1976d2;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.2s;
}

.button:hover {
    background-color: #1565c0;
}

.button:disabled {
    background-color: #bbdefb;
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
    margin-bottom: 1rem;
    border: 1px solid #ccc;
    border-radius: 4px;
    overflow: visible; /* Geu00e4ndert von hidden zu visible, damit Suchfeld sichtbar bleibt */
    position: relative;
}

.leaflet-container {
    width: 100% !important;
    height: 400px !important;
    background-color: #f8f8f8;
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
    padding: 0.75rem 1rem;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}

.map-search input:focus {
    outline: none;
    border-color: #1976d2;
    box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.25);
}

.map-search button {
    padding: 0.5rem 1rem;
    background-color: #1976d2;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    min-width: 100px;
    transition: background-color 0.2s;
}

.map-search button:hover {
    background-color: #1565c0;
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
</style>
