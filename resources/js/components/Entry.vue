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
                    <div v-if="currentStep === 1" class="entry-form-wrapper">
                        <div class="step-container">
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
                                            @change="updateTypeInfo"
                                        />
                                        <label for="type-Q6983405"
                                            >Ereignis</label
                                        >
                                    </div>
                                    <div>
                                        <input
                                            v-model="formsType"
                                            name="type"
                                            type="radio"
                                            value="Q5003624"
                                            id="type-Q5003624"
                                            @change="updateTypeInfo"
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
                                            @change="updateTypeInfo"
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
                                            @change="updateTypeInfo"
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
                                            @change="updateTypeInfo"
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
                                            @change="updateTypeInfo"
                                        />
                                        <label for="type-Q40357"
                                            >Gefängnis</label
                                        >
                                    </div>
                                </fieldset>
                                <div class="button-container">
                                    <button
                                        class="button"
                                        type="button"
                                        @click="openTitleOverlay"
                                        :disabled="!formsType"
                                    >
                                        Weiter
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Info-Box (Desktop) -->
                        <div class="info-box desktop-info" v-if="!isMobileView">
                            <div class="info-box-header">
                                <h3>
                                    {{
                                        typeInfoContent
                                            ? getTypeInfoTitle()
                                            : "Eintragstypen"
                                    }}
                                </h3>
                            </div>
                            <div class="info-box-content">
                                <div
                                    v-if="typeInfoContent"
                                    v-html="typeInfoContent"
                                ></div>
                                <div v-else>
                                    <p>
                                        Bitte wählen Sie einen Eintragstyp aus,
                                        um Informationen darüber zu erhalten.
                                    </p>
                                    <p>
                                        Zu den verfügbaren Eintragstypen
                                        gehören:
                                    </p>
                                    <ul>
                                        <li>
                                            <strong>Ereignis</strong> -
                                            Historische Geschehnisse im Zeitraum
                                            1933-1945
                                        </li>
                                        <li>
                                            <strong>Erinnerungsort</strong> -
                                            Physische Orte, die an die Opfer des
                                            Nationalsozialismus erinnern
                                        </li>
                                        <li>
                                            <strong>Staatspolizeistelle</strong>
                                            - Regionale Dienstellen der Geheimen
                                            Staatspolizei
                                        </li>
                                        <li>
                                            <strong
                                                >Staatspolizeiaußenstelle</strong
                                            >
                                            - Lokale Zweigstellen einer
                                            Staatspolizeistelle
                                        </li>
                                        <li>
                                            <strong
                                                >Arbeitserziehungslager</strong
                                            >
                                            - Haftsystem der Gestapo während des
                                            Nationalsozialismus
                                        </li>
                                        <li>
                                            <strong>Gefängnis</strong> -
                                            Haftanstalten, die während der
                                            NS-Zeit für politische Gefangene
                                            genutzt wurden
                                        </li>
                                    </ul>
                                    <p>
                                        Wählen Sie einen Typ, um detailliertere
                                        Informationen zu erhalten.
                                    </p>
                                </div>
                            </div>
                        </div>
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
                        v-model:victimCountQualifier="victimCountQualifier"
                        v-model:survivorCountQualifier="survivorCountQualifier"
                        v-model:deathCountQualifier="deathCountQualifier"
                        v-model:arrestCountQualifier="arrestCountQualifier"
                        v-model:causeSearchResults="causeSearchResults"
                        v-model:selectedCauses="selectedCauses"
                        v-model:mapZoom="mapZoom"
                        v-model:mapCenter="mapCenter"
                        v-model:selectedLocation="selectedLocation"
                        v-model:locationSearch="locationSearch"
                        v-model:error="error"
                        v-model:imageUrl="imageUrl"
                        v-model:imageFilename="imageFilename"
                        v-model:selectedSources="selectedSources"
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

    <!-- Mobile Info-Overlay -->
    <div
        class="mobile-info-overlay"
        v-if="showMobileInfoOverlay && isMobileView"
        @click="closeMobileInfoOverlay"
    >
        <div class="overlay-content" @click.stop>
            <div class="overlay-header">
                <h3>{{ getTypeInfoTitle() }}</h3>
                <button class="close-overlay" @click="closeMobileInfoOverlay">
                    &times;
                </button>
            </div>
            <div class="overlay-body">
                <div v-html="typeInfoContent"></div>
            </div>
        </div>
    </div>

    <!-- Title Search Overlay -->
    <TitleSearchOverlay
        v-if="showTitleOverlay"
        :initial-title="formsTitle"
        :entry-type="formsType"
        :title="'Neuen ' + getTypeInfoTitle() + ' anlegen'"
        @update:title="updateTitle"
        @close="cancelTitleOverlay"
        @create="createWithTitle"
    />
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from "vue";
import { LMap, LTileLayer, LMarker } from "@vue-leaflet/vue-leaflet";
import "leaflet/dist/leaflet.css";
import * as L from "leaflet";
import axios from "axios";
import { debounce } from "lodash";
import { useRouter } from "vue-router";
import EventsForm from "./entry/EventsForm.vue";
import TitleSearchOverlay from "./entry/fields/TitleSearchOverlay.vue";
import glossaryItems from "../data/glossaryData";

// Props
const props = defineProps({
    isMenuDisplayed: Boolean,
});

// Reactive references
const router = useRouter();
const freeClientWidth = ref(document.documentElement.clientWidth);
const error = ref(null);
const formsType = ref("Q6983405");
const formsTitle = ref(null);
const formsDescription = ref(null);
const isSubmitting = ref(false);
const wikidataResults = ref([]);
const peopleSearchResults = ref([]);
const selectedVictims = ref([]);
const currentStep = ref(1);
const showNewVictimForm = ref(false);
const newVictim = ref({
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
    isOrganization: false,
    gender: null,
    nationality: null,
    place_of_birth: null,
    place_of_death: null,
    place_of_detention: null,
});
const lastPerpetratorSearchQuery = ref("");

const eventTimeType = ref("point"); // 'point' oder 'range'
const eventDate = ref(null);
const eventStartDate = ref(null);
const eventEndDate = ref(null);

const victimCount = ref(0);
const survivorCount = ref(0);
const deathCount = ref(0);
const arrestCount = ref(0);
const victimCountQualifier = ref("");
const survivorCountQualifier = ref("");
const deathCountQualifier = ref("");
const arrestCountQualifier = ref("");
const cause = ref("");
const causeSearchResults = ref([]);
const selectedCauses = ref([]);
const lastCauseSearchQuery = ref("");

const mapRef = ref(null);
const mapZoom = ref(8);
const mapCenter = ref([52.8, 9.0]); // Zentrum von Niedersachsen
const selectedLocation = ref(null);
const locationSearch = ref("");

const imageUrl = ref(null);
const imageFilename = ref(null);
const selectedSources = ref([]);

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

const setFreeClientWidth = () => {
    const container = document.getElementById("container");
    freeClientWidth.value =
        (document.documentElement.clientWidth - container.clientWidth) / 2;
};

const goToStep = (step) => {
    if (step === 2 && !formsType.value) {
        error.value = "Bitte wählen Sie einen Eintragstyp aus";
        return;
    }

    error.value = null;
    currentStep.value = step;
};

const isMobileView = ref(false);
const checkScreenSize = () => {
    isMobileView.value = window.innerWidth < 1200;
};

const typeInfoContent = ref("");
const showMobileInfoOverlay = ref(false);

const updateTypeInfo = () => {
    const typeToGlossaryMapping = {
        Q6983405: "Ereignis",
        Q5003624: "Erinnerungsort",
        Q108047567: "Staatspolizeistelle",
        Q108047541: "Außendienststelle", // Im Glossar als "Außendienststelle"
        Q277565: "Arbeitserziehungslager",
        Q40357: "Gefängnis",
    };

    // Get the corresponding glossary item key
    const glossaryKey = typeToGlossaryMapping[formsType.value];

    // Get the glossary item if it exists
    const glossaryItem = glossaryKey ? glossaryItems[glossaryKey] : null;

    if (glossaryItem) {
        let content = "";

        glossaryItem.descriptions.forEach((desc) => {
            if (typeof desc === "string") {
                content += `<p>${desc}</p>`;
            } else if (Array.isArray(desc)) {
                content += "<ul>";
                desc.forEach((item) => {
                    content += `<li>${item}</li>`;
                });
                content += "</ul>";
            }
        });

        typeInfoContent.value = content;
    } else {
        typeInfoContent.value = "";
    }

    if (isMobileView.value && typeInfoContent.value) {
        showMobileInfoOverlay.value = true;
    }
};

// Close mobile info overlay
const closeMobileInfoOverlay = () => {
    showMobileInfoOverlay.value = false;
};

const getTypeInfoTitle = () => {
    const typeTitles = {
        Q6983405: "Ereignis",
        Q5003624: "Erinnerungsort",
        Q108047567: "Staatspolizeistelle",
        Q108047541: "Staatspolizeiaußenstelle",
        Q277565: "Arbeitserziehungslager",
        Q40357: "Gefängnis",
    };

    return typeTitles[formsType.value] || "Eintragstyp";
};

const showTitleOverlay = ref(false);

const openTitleOverlay = () => {
    showTitleOverlay.value = true;
};

const cancelTitleOverlay = () => {
    showTitleOverlay.value = false;
};

const updateTitle = (newTitle) => {
    formsTitle.value = newTitle;
};

const createWithTitle = (title) => {
    formsTitle.value = title;
    showTitleOverlay.value = false;
    currentStep.value = 2;
};

const resetMapView = () => {
    if (mapRef.value && mapRef.value.leafletObject) {
        mapRef.value.leafletObject.setView(mapCenter.value, mapZoom.value, {
            reset: true,
            animate: false,
        });
    }
    selectedLocation.value = null;
};

const reinitializeMap = () => {
    applyLeafletCSS();

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

const handleMapClick = (event) => {
    console.log("Map clicked!", event.latlng);
    selectedLocation.value = event.latlng;

    if (mapRef.value && mapRef.value.leafletObject) {
        setTimeout(() => {
            mapRef.value.leafletObject.invalidateSize(true);
        }, 50);
    }
};

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

            mapCenter.value = [lat, lng];
            mapZoom.value = 15; // Näher heranzoomen

            selectedLocation.value = { lat, lng };
        } else {
            alert("Keine Ergebnisse gefunden");
        }
    } catch (err) {
        console.error("Fehler bei der Standortsuche:", err);
        alert("Fehler bei der Suche");
    }
};

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

const applyLeafletCSS = () => {
    const styleId = "leaflet-tile-fix";

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

        /* Korrektur für Tile-Bilder */
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

const selectExistingEntity = (entity) => {
    const wikidataUrl = `https://www.wikidata.org/wiki/${entity.id}`;
    window.open(wikidataUrl, "_blank");

    wikidataResults.value = [];
};

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
        isOrganization: false,
        gender: null,
        nationality: null,
        place_of_birth: null,
        place_of_death: null,
        place_of_detention: null,
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
        isOrganization: newVictim.value.isOrganization || false,
        // Wikidata fields
        gender: newVictim.value.gender || null,
        nationality: newVictim.value.nationality || null,
        place_of_birth: newVictim.value.place_of_birth || null,
        place_of_death: newVictim.value.place_of_death || null,
        place_of_detention: newVictim.value.place_of_detention || null,
    });

    showNewVictimForm.value = false;
    resetNewVictimForm();
};

const startAddingAnotherVictim = () => {
    showNewVictimForm.value = false;
    resetNewVictimForm();
    peopleSearchResults.value = [];
};

const canAddNewVictim = computed(() => {
    return newVictim.value.firstName.trim() && newVictim.value.lastName.trim();
});

const selectVictim = (person) => {
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
        console.error("Fehler bei der Tätersuche:", error);
        perpetratorsSearchResults.value = [];
    }
}, 300);

const startNewPerpetratorForm = () => {
    showNewPerpetratorForm.value = true;
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
        isOrganization: false,
        gender: null,
        nationality: null,
        place_of_birth: null,
        place_of_death: null,
        place_of_detention: null,
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
        isOrganization: newPerpetrator.value.isOrganization || false,
        // Wikidata fields
        gender: newPerpetrator.value.gender || null,
        nationality: newPerpetrator.value.nationality || null,
        place_of_birth: newPerpetrator.value.place_of_birth || null,
        place_of_death: newPerpetrator.value.place_of_death || null,
        place_of_detention: newPerpetrator.value.place_of_detention || null,
    });

    showNewPerpetratorForm.value = false;
    resetNewPerpetratorForm();
};

const startAddingAnotherPerpetrator = () => {
    showNewPerpetratorForm.value = false;
    resetNewPerpetratorForm();
    perpetratorsSearchResults.value = [];
};

const canAddNewPerpetrator = computed(() => {
    return (
        newPerpetrator.value.firstName.trim() &&
        newPerpetrator.value.lastName.trim()
    );
});

const selectPerpetrator = (person) => {
    if (!selectedPerpetrators.value.some((v) => v.id === person.id)) {
        selectedPerpetrators.value.push({
            id: person.id,
            label: person.label,
            description: person.description,
            birth: person.birth,
            death: person.death,
        });
    }
    perpetratorsSearchResults.value = [];
    lastPerpetratorSearchQuery.value = "";
};

const removePerpetrator = (perpetratorId) => {
    selectedPerpetrators.value = selectedPerpetrators.value.filter(
        (v) => (v.id || v.tempId) !== perpetratorId,
    );
};

const searchCause = debounce(async (query) => {
    if (!query || query.length < 2) {
        causeSearchResults.value = [];
        return;
    }

    lastCauseSearchQuery.value = query;

    try {
        const response = await axios.get("/api/wikidata/search/cause", {
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
        const missingFields = [];

        if (!(formsTitle.value || "").trim()) {
            missingFields.push("Titel");
        }

        if (!(formsDescription.value || "").trim()) {
            missingFields.push("Kurzbeschreibung");
        }

        if (
            ["Q6983405", "Q5003624"].includes(formsType.value) &&
            !selectedLocation.value
        ) {
            missingFields.push("Standort");
        }

        // Prüfe, ob mindestens ein Täter vorhanden ist
        if (
            formsType.value === "Q6983405" &&
            (!selectedPerpetrators.value ||
                selectedPerpetrators.value.length === 0)
        ) {
            missingFields.push("Täter*In");
        }

        // Prüfe, ob mindestens eine Quelle vorhanden ist
        if (
            formsType.value === "Q6983405" &&
            (!selectedSources.value || selectedSources.value.length === 0)
        ) {
            missingFields.push("Quelle");
        }

        if (missingFields.length) {
            error.value = `Fehlende Pflichtfelder: ${missingFields.join(", ")}`;
            isSubmitting.value = false;
            return;
        }

        let coordinates = null;
        if (selectedLocation.value) {
            coordinates = `${selectedLocation.value.lat}, ${selectedLocation.value.lng}`;
        }

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

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
                victimCountQualifier: victimCountQualifier.value,
                survivorCountQualifier: survivorCountQualifier.value,
                deathCountQualifier: deathCountQualifier.value,
                arrestCountQualifier: arrestCountQualifier.value,
                causes: selectedCauses.value,
                imageUrl: imageUrl.value,
                imageFilename: imageFilename.value,
                sources: selectedSources.value,
            },
            {
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                },
            },
        );

        if (response.data.success) {
            // Debug logging
            console.log("Submitted qualifier values:", {
                victimCountQualifier: victimCountQualifier.value,
                survivorCountQualifier: survivorCountQualifier.value,
                deathCountQualifier: deathCountQualifier.value,
                arrestCountQualifier: arrestCountQualifier.value,
            });
            
            // Redirect to thank-you page with request ID
            router.push({
                name: "thank-you",
                params: { requestId: response.data.requestId },
            });
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

const clearForm = () => {
    formsTitle.value = null;
    formsDescription.value = null;
    selectedLocation.value = null;
    selectedVictims.value = [];
    newVictim.value = {
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
    };
    showNewVictimForm.value = false;

    selectedPerpetrators.value = [];
    newPerpetrator.value = {
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
    };
    showNewPerpetratorForm.value = false;

    eventTimeType.value = "point";
    eventDate.value = null;
    eventStartDate.value = null;
    eventEndDate.value = null;

    victimCount.value = 0;
    survivorCount.value = 0;
    deathCount.value = 0;
    arrestCount.value = 0;
    victimCountQualifier.value = "";
    survivorCountQualifier.value = "";
    deathCountQualifier.value = "";
    arrestCountQualifier.value = "";
    causeSearchResults.value = [];
    selectedCauses.value = [];
    lastCauseSearchQuery.value = "";

    mapZoom.value = 8;
    mapCenter.value = [52.8, 9.0];
    locationSearch.value = "";

    imageUrl.value = null;
    imageFilename.value = null;
    selectedSources.value = [];
};

watch(currentStep, (newStep) => {
    if (newStep === 2) {
        setTimeout(() => {
            reinitializeMap();
        }, 300);
    }
});

// Lifecycle hooks
onMounted(() => {
    window.addEventListener("resize", setFreeClientWidth);

    checkScreenSize();
    window.addEventListener("resize", checkScreenSize);

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

    setTimeout(() => {
        reinitializeMap();
    }, 300);
});

onUnmounted(() => {
    window.removeEventListener("resize", setFreeClientWidth);
    window.removeEventListener("resize", checkScreenSize);
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
    overflow: visible; /* Geändert von hidden zu visible, damit Suchfeld sichtbar bleibt */
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

/* Info box and mobile overlay styles */
.info-box {
    width: 30%;
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    border: 1px solid #e0e0e0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    max-height: 600px;
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

.info-box-content ul {
    padding-left: 1.5rem;
    margin-bottom: 1rem;
}

.info-box-content li {
    margin-bottom: 0.5rem;
}

/* Mobile Info Overlay */
.mobile-info-overlay {
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

.overlay-content {
    width: 90%;
    max-width: 500px;
    max-height: 80vh;
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

.overlay-body {
    padding: 1rem;
    overflow-y: auto;
    max-height: 60vh;
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

@media (max-width: 1200px) {
    .desktop-info {
        display: none; /* Desktop-Infobox auf mobilen Geräten ausblenden */
    }
}

/* Responsiver Aufbau für Entry Form */
.entry-form-wrapper {
    display: flex;
    gap: 2rem;
}

@media (max-width: 1200px) {
    .entry-form-wrapper {
        flex-direction: column;
    }

    .step-container {
        width: 100%;
    }
}
</style>
