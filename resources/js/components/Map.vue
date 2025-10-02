<template>
    <div class="wrapper">
        <transition name="fade">
            <div class="map-loading-overlay" v-if="isLoading">
                <div class="loading-container">
                    <v-progress-circular
                        indeterminate
                        size="70"
                        width="7"
                        color="primary"
                    ></v-progress-circular>
                    <div class="loading-text mt-4">Karte wird geladen...</div>
                </div>
            </div>
        </transition>

        <map-options-sidebar
            v-if="map"
            v-model:groupedPlacesShownOnMap="groupedPlacesShownOnMap"
            :groupedPlaces="groupedPlaces"
            :map="map"
            :mapMarkerIconsPath="mapMarkerIconsPath"
            ref="optionsSidebar"
        ></map-options-sidebar>

        <map-timeline
            :groupedPlaces="groupedPlaces"
            :map="map"
            @update-time-line="handleTimelineUpdate"
            @timeline-filter-enabled-change="handleTimelineFilterChange"
            @closeMapOptions="closeMapOptions"
        ></map-timeline>

        <place-info-sidebar
            v-model:groupedPlacesShownOnMap="groupedPlacesShownOnMap"
            :selectedPlace="selectedPlace"
            :showPlaceInfoSidebar="showPlaceInfoSidebar"
            @hidePlaceInfoSidebar="toggleShowPlaceInfoSidebar(false)"
            @showPerson="showPerson"
            @switchLocation="switchLocation"
            @undoZoomIntoPlace="restoreCachedMapView"
            @zoomIntoPlace="setMapView(selectedPlace.latLng, 18, true)"
        ></place-info-sidebar>

        <div id="leafletMapId"></div>
    </div>
</template>

<script>
import {
    ref,
    onMounted,
    onUnmounted,
    nextTick,
    inject,
    toRaw,
    watch,
} from "vue";
import { useRouter, useRoute } from "vue-router";
import axios from "axios";
import Leaflet from "leaflet/dist/leaflet";
import "leaflet/dist/leaflet.css";
import L from "leaflet/dist/leaflet";
import MapOptionsSidebar from "./map/MapOptionsSidebar";
import PlaceInfoSidebar from "./map/PlaceInfoSidebar";
import MapTimeline from "./map/MapTimeline";
import NavigationHelpers from "./helpers/NavigationHelpers";
import leafletConfig from "../config/leafletConfig";
import { VSelectionControlGroup } from "vuetify/lib/components/index.mjs";
const showPlaceInfoSidebar = ref(false);
const sourceCircumstances = ref({
    Q5727902: "ca.",
    Q47035128: ">",
    Q52834024: "<",
});
import mockPlacesData from "../data/mock-places.json";
import mockPersonsData from "../data/mock-persons.json";

// Constants
const WIKIDATA_ID_CLASSES = {
    deportation: ["Q379693", "Q5883983", "Q61927259"],
};

export default {
    name: "Map",
    components: {
        MapOptionsSidebar,
        PlaceInfoSidebar,
        MapTimeline,
    },
    setup() {
        const isLoading = ref(true);
        const router = useRouter();
        const route = useRoute();
        const configLeaflet = leafletConfig;
        const ogtGlobals = inject("ogtGlobals");
        const map = ref(null);
        const rawMap = ref(null);
        const rawGroupedPlaces = ref({});
        const markers = ref([]);
        const {
            isTimelineOpen,
            isMapOptionsOpen,
            isPlaceInfoOpen,
            closeMapOptions,
            openPlaceInfo,
            closePlaceInfo,
        } = NavigationHelpers();
        const minDate = ref({
            year: -Infinity,
            month: -Infinity,
        });
        const maxDate = ref({
            year: Infinity,
            month: Infinity,
        });
        const groupNames = ref(null);
        const cachedMapView = ref({
            latLng: {
                lat: 0,
                lng: 0,
            },
            zoomLevel: 0,
        });
        var currentMinDate = ref({
            year: -Infinity,
            month: -Infinity,
        });
        var currentMaxDate = ref({
            year: Infinity,
            month: Infinity,
        });
        const derivedPlacesData = ref({});
        const isTimelineFilterEnabled = ref(false);
        const getDerivedPlacesData = () => derivedPlacesData.value;
        const groupedPlaces = ref({
            events: {
                color: "#8c1f21",
                displayOrder: 6,
                iconName: "events.svg",
                layerGroup: null,
                layerName: "Ereignisse",
                linkedMainGroupItem: null,
                places: {},
                placesByCoordinates: [],
            },
            persecutionOfSocialistFront: {
                color: "#8c1f21",
                displayOrder: 7,
                iconName: "events.svg",
                layerGroup: null,
                layerName: "Verfolgung der Sozialistischen Front",
                linkedMainGroupItem: { "events": "Q133303308" },
                places: {},
                placesByCoordinates: [],
            },
            extPolicePrisons: {
                color: "#3d3b3d",
                displayOrder: 4,
                iconName: "extPolicePrisons.svg",
                layerGroup: null,
                layerName: "Erweiterte Polizeigefängnisse",
                linkedMainGroupItem: null,
                places: {},
                placesByCoordinates: [],
            },
            fieldOffices: {
                color: "#456879",
                displayOrder: 2,
                iconName: "fieldOffices.svg",
                layerGroup: null,
                layerName: "Außendienststellen",
                linkedMainGroupItem: null,
                places: {},
                placesByCoordinates: [],
            },
            laborEducationCamps: {
                color: "#3d3b3d",
                displayOrder: 5,
                iconName: "laborEducationCamps.svg",
                layerGroup: null,
                layerName: "Arbeitserziehungslager",
                linkedMainGroupItem: null,
                places: {},
                placesByCoordinates: [],
            },
            memorials: {
                color: "#f3a923",
                displayOrder: 8,
                iconName: "memorials.svg",
                layerGroup: null,
                layerName: "Erinnerungsorte",
                linkedMainGroupItem: null,
                places: {},
                placesByCoordinates: [],
            },
            prisons: {
                color: "#3d3b3d",
                displayOrder: 3,
                iconName: "prisons.svg",
                layerGroup: null,
                layerName: "Gefängnisse",
                linkedMainGroupItem: null,
                places: {},
                placesByCoordinates: [],
            },
            statePoliceHeadquarters: {
                color: "#456879",
                displayOrder: 0,
                iconName: "statePoliceHeadquarters.svg",
                layerGroup: null,
                layerName: "Staatspolizeileitstellen",
                linkedMainGroupItem: null,
                places: {},
                placesByCoordinates: [],
            },
            statePoliceOffices: {
                color: "#456879",
                displayOrder: 1,
                iconName: "statePoliceOffices.svg",
                layerGroup: null,
                layerName: "Staatspolizeistellen",
                linkedMainGroupItem: null,
                places: {},
                placesByCoordinates: [],
            },
        });

        const groupedPlacesShownOnMap = ref([
            "events",
            //"persecutionOfSocialistFront", // initially hidden on map
            "extPolicePrisons",
            "fieldOffices",
            "laborEducationCamps",
            "memorials",
            "prisons",
            "statePoliceHeadquarters",
            "statePoliceOffices",
        ]);

        watch(
            groupedPlacesShownOnMap,
            (newValue) => {
                handleLayerGroupsUpdate(newValue);
            },
        );

        var currentGroupedPlaces = ref([]);
        const layers = ref(null);
        const locationMarkers = ref([]);
        const markersDate = ref(new Map());
        const mapMarkerIconsPath = ref(
            "/images/leaflet/markerIcons/coloredFilledWhite/",
        );
        const persons = ref({
            perpetrators: {},
            victims: {},
        });
        const selectedPlace = ref({
            addresses: {
                additional: [
                    {
                        endDate: {
                            locale: "",
                            value: null,
                        },
                        isUncertain: false,
                        label: "",
                        latLng: {
                            lat: 0,
                            lng: 0,
                        },
                        startDate: {
                            locale: "",
                            value: null,
                        },
                    },
                ],
                selected: {
                    endDate: {
                        locale: "",
                        value: null,
                    },
                    isUncertain: false,
                    label: "",
                    latLng: {
                        lat: 0,
                        lng: 0,
                    },
                    startDate: {
                        locale: "",
                        value: null,
                    },
                },
            },
            childOrganizations: [
                {
                    endDate: {
                        locale: "",
                        value: null,
                    },
                    hasLocationMarker: false,
                    id: "",
                    label: "",
                    startDate: {
                        locale: "",
                        value: null,
                    },
                },
            ],
            citizenships: [],
            commemoratedBy: [
                {
                    hasLocationMarker: false,
                    id: "",
                    name: "",
                },
            ],
            commemorates: [
                {
                    hasLocationMarker: false,
                    id: "",
                    label: "",
                },
            ],
            dateOfBirth: {
                locale: "",
                value: null,
            },
            dateOfDeath: {
                locale: "",
                value: null,
            },
            description: "",
            destinationPoints: [],
            detentions: [
                {
                    endDate: {
                        locale: "",
                        value: null,
                    },
                    hasLocationMarker: false,
                    id: "",
                    name: "",
                    startDate: {
                        locale: "",
                        value: null,
                    },
                },
            ],
            directors: [
                {
                    endDate: {
                        locale: "",
                        value: null,
                    },
                    hasPersonData: false,
                    id: "",
                    maxStartDate: {
                        locale: "",
                        value: null,
                    },
                    minEndDate: {
                        locale: "",
                        value: null,
                    },
                    name: "",
                    startDate: {
                        locale: "",
                        value: null,
                    },
                },
            ],
            dissolvedDate: {
                locale: "",
                value: null,
            },
            employeeCounts: [
                {
                    pointInTime: {
                        locale: "",
                        value: null,
                    },
                    sourcingCircumstance: "",
                    value: 0,
                },
            ],
            employees: [
                {
                    endDate: {
                        locale: "",
                        value: null,
                    },
                    id: "",
                    maxStartDate: {
                        locale: "",
                        value: null,
                    },
                    minEndDate: {
                        locale: "",
                        value: null,
                    },
                    name: "",
                    startDate: {
                        locale: "",
                        value: null,
                    },
                },
            ],
            employers: [
                {
                    endDate: {
                        locale: "",
                        value: null,
                    },
                    hasLocationMarker: false,
                    id: "",
                    maxStartDate: {
                        locale: "",
                        value: null,
                    },
                    minEndDate: {
                        locale: "",
                        value: null,
                    },
                    name: "",
                    startDate: {
                        locale: "",
                        value: null,
                    },
                },
            ],
            endDate: {
                locale: "",
                value: null,
            },
            familyName: "",
            gender: "",
            givenName: "",
            groupName: "",
            hasParts: [
                {
                    id: "",
                    label: "",
                },
            ],
            hasUses: [],
            id: "",
            inceptionDate: {
                locale: "",
                value: null,
            },
            isDeportationEvent: false,
            label: "",
            // Leaflet LatLng geographical point object
            latLng: {
                lat: 0,
                lng: 0,
            },
            layerName: "",
            locations: [],
            mainImageUrl: "",
            mainImageLegend: "",
            numberOfArrests: [],
            numberOfCasualties: [],
            numberOfDeaths: [],
            numberOfSurvivors: [],
            openingDate: {
                locale: "",
                value: null,
            },
            operators: [],
            parentOrganizations: [
                {
                    endDate: {
                        locale: "",
                        value: null,
                    },
                    hasLocationMarker: false,
                    id: "",
                    label: "",
                    startDate: {
                        locale: "",
                        value: null,
                    },
                },
            ],
            partOfs: [
                {
                    id: "",
                    label: "",
                },
            ],
            perpetrators: [
                {
                    hasLocationMarker: false,
                    hasPersonData: false,
                    id: "",
                    label: "",
                },
            ],
            placeOfBirth: "",
            placeOfDeath: "",
            pointInTime: {
                locale: "",
                value: null,
            },
            predecessors: [
                {
                    hasLocationMarker: false,
                    id: "",
                    label: "",
                    pointInTime: {
                        locale: "",
                        value: null,
                    },
                },
            ],
            prisoners: [
                {
                    id: "",
                    name: "",
                },
            ],
            prisonerCounts: [
                {
                    sourcingCircumstance: "",
                    value: 0,
                },
            ],
            significantEvents: [
                {
                    hasLocationMarker: false,
                    id: "",
                    label: "",
                },
            ],
            significantPlaces: [],
            sources: [
                {
                    label: "",
                    pages: "",
                    inventoryNumber: "",
                },
            ],
            urlSources: [
                {
                    label: "",
                    url: "",
                },
            ],
            startDate: {
                locale: "",
                value: null,
            },
            startPoints: [],
            successors: [
                {
                    hasLocationMarker: false,
                    id: "",
                    label: "",
                    pointInTime: {
                        locale: "",
                        value: null,
                    },
                },
            ],
            targets: [],
            victims: [
                {
                    hasPersonData: false,
                    id: "",
                    name: "",
                },
            ],
            website: "",
        });

        const optionsSidebar = ref(null);

        const handleTimelineFilterChange = (enabled) => {
            isTimelineFilterEnabled.value = enabled;
            debouncedApplyFilters(null, null, null);
        };

        const forceConsistentUIState = () => {
            closeMapOptions();
            closePlaceInfo();
            showPlaceInfoSidebar.value = false;

            const searchParams = new URLSearchParams(window.location.search);
            if (searchParams.get("id") || searchParams.get("pid")) {
                closeMapOptions();
            }

            return true;
        };

        watch(isPlaceInfoOpen, (newValue) => {
            if (showPlaceInfoSidebar.value !== newValue) {
                showPlaceInfoSidebar.value = newValue;
            }
        });

        onMounted(async () => {
            try {
                isLoading.value = true;
                forceConsistentUIState();
                await nextTick();

                patchLeafletForNullChecks();

                const leafletMap = document.getElementById("leafletMapId");
                if (leafletMap) {
                    leafletMap.style.width =
                        document.documentElement.clientWidth + "px";
                    await setupLeafletMap();
                    leafletMap.style.width = "100%";

                    try {
                        await nextTick();
                        const [placesPromise, personsPromise] =
                            await Promise.all([
                                getGroupedPlaces(),
                                getPersons(),
                            ]);

                        await ensureDataProcessed();

                        setTimeout(() => {
                            isLoading.value = false;
                        }, 500);
                    } catch (error) {
                        console.error("Error updating map:", error);
                        isLoading.value = false;
                    }
                }
                map.value.on("moveend", () => {
                    debouncedApplyFilters(null, null, null);
                });
            } catch (error) {
                console.error("Error during component mount:", error);
                isLoading.value = false;
            }
        });

        onUnmounted(() => {
            if (map.value) {
                map.value.remove();
            }
        });

        const setupLeafletMap = async () => {
            try {
                await nextTick();
                const leafletMap = document.getElementById("leafletMapId");
                if (!leafletMap) {
                    console.error("Leaflet map container not found");
                    return;
                }

                let baseLayers = {};

                // Erstelle die Base Layers
                for (const [providerName, providerData] of Object.entries(
                    leafletConfig.tileLayerProviders,
                )) {
                    baseLayers[providerName] = L.tileLayer(
                        providerData.urlTemplate,
                        providerData.options,
                    );
                }

                // Initialisiere die Karte
                map.value = new L.Map("leafletMapId", {
                    center: leafletConfig.center,
                    zoom: leafletConfig.zoom,
                    layers: [baseLayers[leafletConfig.initialLayerName]],
                });
                const rawMap = toRaw(map.value);

                await nextTick();

                // Erstelle Layer Control
                layers.value = L.control.layers(baseLayers);
                layers.value.addTo(rawMap);

                // Setze Position der Zoom-Kontrollen
                rawMap.zoomControl.setPosition("bottomright");

                // Füge Maßstab hinzu
                L.control
                    .scale({
                        imperial: false,
                    })
                    .addTo(rawMap);

                setCachedMapView(rawMap);
            } catch (error) {
                console.error("Error setting up Leaflet map:", error);
            }
        };
        /**
         * Request grouped places of Gestapo terror.
         */
        const getGroupedPlaces = async () => {
            try {
                let groupedPlaces = {};

                // Check if this is a refresh or initial load
                const isRefresh = window.initialPlacesLoaded;

                try {
                    // Versuche zuerst die Anfrage an den Cache
                    const response = await axios.get(
                        "/api/wikidata/places/cache",
                    );
                    groupedPlaces = response.data;
                } catch (error) {
                    // Bei Fehler zu Mock-Daten greifen
                    console.warn(
                        "Fehler beim Laden der Wikidata Places-Cache-Daten, verwende Mock-Daten:",
                        error,
                    );
                    groupedPlaces = mockPlacesData;
                }

                await nextTick();

                // If this is a refresh, clear existing markers first
                if (isRefresh) {
                    clearAllPlaceMarkers();
                }

                // Pass the data to visualize places
                await visualizePlaces(groupedPlaces);
                setCurrentValues();
                derivePlaceData(groupedPlaces);
                if (
                    Object.keys(persons.value.victims).length > 0 ||
                    Object.keys(persons.value.perpetrators).length > 0
                ) {
                    deriveDataFromVictims();
                    deriveLocationEmployees();
                }
                updateInfoSidebarData();
                await checkUrlForLocation();
                await checkUrlForPerson();
                currentGroupedPlaces.value = Object.keys(groupedPlaces);

                // Update places cache after a delay
                if (!window.initialPlacesLoaded) {
                    setTimeout(() => {
                        updatePlacesCache();
                    }, 10000);
                    window.initialPlacesLoaded = true;
                }
            } catch (error) {
                console.error("Error fetching grouped places:", error);
            }
        };

        /**
         * Update the places cache by fetching fresh data from the API
         */
        const updatePlacesCache = () => {
            if (Object.keys(groupedPlaces.value).length === 0) {
                console.log(
                    "Kein Places-Cache-Update durchgeführt, da keine Ortsdaten vorliegen",
                );
                return;
            }

            setTimeout(() => {
                const worker = createPlacesWorker();

                worker.onmessage = (e) => {
                    const message = e.data;

                    if (message.type === "error") {
                        console.warn("Places Worker error:", message.error);
                    } else if (message.type === "success") {
                        // Clear existing markers before adding new ones
                        clearAllPlaceMarkers();

                        // Update groupedPlaces with new data
                        visualizePlaces(message.data);
                        derivePlaceData(message.data);
                        if (
                            Object.keys(persons.value.victims).length > 0 ||
                            Object.keys(persons.value.perpetrators).length > 0
                        ) {
                            deriveDataFromVictims();
                            deriveLocationEmployees();
                        }
                        updateInfoSidebarData();
                    }

                    worker.terminate();
                };

                worker.postMessage("start");
            }, 10000);
        };

        /**
         * Clear all place markers from the map
         */
        const clearAllPlaceMarkers = () => {
            // Remove all existing markers from layer groups
            Object.keys(groupedPlaces.value).forEach((groupName) => {
                const layerGroup = groupedPlaces.value[groupName].layerGroup;
                if (layerGroup) {
                    layerGroup.clearLayers();
                }

                // Clear the places by coordinates array
                groupedPlaces.value[groupName].placesByCoordinates = [];

                // Reset markers map
                markersDate.value = new Map();
            });
        };

        /**
         * Clears all place markers from the map to prevent duplicates when refreshing data
         */

        const createPlacesWorker = () => {
            const workerCode = `
                function processData(data) {
                    // Hier könntest du optional Daten optimieren oder filtern, falls nötig
                    return data;
                }

                self.onmessage = async function(e) {
                    try {
                        // Lade die Daten
                        const url = self.location.origin + '/api/wikidata/places';

                        try {
                            const response = await fetch(url);

                            if (!response.ok) {
                                throw new Error('Network response was not ok: ' + response.status);
                            }

                            const rawData = await response.json();

                            // Verarbeite die Daten - optional
                            const processedData = processData(rawData);

                            // Sende die verarbeiteten Daten zurück
                            self.postMessage({
                                type: 'success',
                                data: processedData
                            });
                        } catch (fetchError) {
                            // Wenn der API-Aufruf fehlschlägt, geben wir den Fehler weiter
                            self.postMessage({
                                type: 'error',
                                error: 'API request failed: ' + fetchError.message
                            });
                        }
                    } catch (error) {
                        self.postMessage({
                            type: 'error',
                            error: error.message
                        });
                    }
                };
            `;

            const blob = new Blob([workerCode], {
                type: "application/javascript",
            });
            return new Worker(URL.createObjectURL(blob));
        };
        /**
         * Request persons data from cached file and Wikidata for perpetrators and victims.
         */
        const getPersons = async () => {
            try {
                // Check if we need to handle refreshed data
                const isRefresh = window.initialPersonsLoaded;

                try {
                    // Versuche zuerst die API-Anfrage
                    const response = await axios.get(
                        "/api/wikidata/persons/cache",
                    );
                    persons.value = response.data;
                } catch (error) {
                    // Bei Fehler zu Mock-Daten greifen
                    console.warn(
                        "Fehler beim Laden der Wikidata Persons-Daten, verwende Mock-Daten:",
                        error,
                    );
                    persons.value = mockPersonsData;
                }

                // Process the data
                processPersonsData();

                if (Object.keys(derivedPlacesData.value).length > 0) {
                    await checkUrlForLocation();
                    await checkUrlForPerson();
                }

                // Only schedule a cache update if this is the initial load
                if (!window.initialPersonsLoaded) {
                    setTimeout(() => {
                        updatePersonsCache();
                    }, 10000);
                    window.initialPersonsLoaded = true;
                }
            } catch (error) {
                console.error("Error fetching persons:", error);
            }
        };

        const updatePersonsCache = () => {
            if (
                Object.keys(persons.value.victims).length === 0 &&
                Object.keys(persons.value.perpetrators).length === 0
            ) {
                console.log(
                    "Kein Cache-Update durchgeführt, da keine Personendaten vorliegen",
                );
                return;
            }
            setTimeout(() => {
                const worker = createAdvancedWorker();

                worker.onmessage = (e) => {
                    const message = e.data;

                    if (message.type === "error") {
                        console.warn("Worker error:", message.error);
                    } else if (message.type === "success") {
                        // Update persons data
                        persons.value = message.data;

                        // Re-process the data
                        processPersonsData();
                        updateInfoSidebarData();
                    }

                    worker.terminate();
                };

                worker.postMessage("start");
            }, 10000);
        };

        const createAdvancedWorker = () => {
            const workerCode = `
                // Hilfsfunktionen für die Datenverarbeitung im Worker
                function processData(data) {
                    // Hier könntest du optional Daten optimieren oder filtern, falls nötig
                    return data;
                }

                self.onmessage = async function(e) {
                    try {
                        // Lade die Daten
                        const url = self.location.origin + '/api/wikidata/persons';

                        try {
                            const response = await fetch(url);

                            if (!response.ok) {
                                throw new Error('Network response was not ok: ' + response.status);
                            }

                            const rawData = await response.json();

                            // Verarbeite die Daten - optional
                            const processedData = processData(rawData);

                            // Sende die verarbeiteten Daten zurück
                            self.postMessage({
                                type: 'success',
                                data: processedData
                            });
                        } catch (fetchError) {
                            // Wenn der API-Aufruf fehlschlägt, geben wir den Fehler weiter
                            // Die Komponente wird dann weiterhin die Mock-Daten verwenden
                            self.postMessage({
                                type: 'error',
                                error: 'API request failed: ' + fetchError.message
                            });
                        }
                    } catch (error) {
                        self.postMessage({
                            type: 'error',
                            error: error.message
                        });
                    }
                };
            `;

            const blob = new Blob([workerCode], {
                type: "application/javascript",
            });
            return new Worker(URL.createObjectURL(blob));
        };

        /**
         * Process retrieved persons data to derive data for locations and to show/update person data in map info sidebar.
         */
        const processPersonsData = () => {
            deriveLocationEmployees();
            deriveDataFromVictims();
            updateInfoSidebarData();
        };
        /**
         * Update locations/persons data displayed in opened info sidebar of the map.
         */
        const updateInfoSidebarData = () => {
            if (!showPlaceInfoSidebar.value) {
                return;
            }

            const personGroups = Object.keys(persons);
            const locationGroups = Object.keys(groupedPlaces);

            if (personGroups.includes(selectedPlace.value.groupName)) {
                showPerson({
                    id: selectedPlace.value.id,
                    group: selectedPlace.value.groupName,
                });
            } else if (locationGroups.includes(selectedPlace.value.groupName)) {
                let location =
                    groupedPlaces[selectedPlace.value.groupName]["places"][
                        selectedPlace.value.id
                    ];
                setSelectedPlace(
                    location,
                    selectedPlace.value.latLng,
                    selectedPlace.value.groupName,
                    getDerivedPlacesData(),
                );
            }
        };

        const setCurrentValues = (newMinDate, newMaxDate, newGroups) => {
            if (newMinDate) {
                currentMinDate.value = newMinDate;
            }
            if (newMaxDate) {
                currentMaxDate.value = newMaxDate;
            }
            if (newGroups) {
                currentGroupedPlaces.value = newGroups;
            }
        };

        const applyFilters = (minDate, maxDate, groupNames) => {
            if (minDate == null) {
                minDate = currentMinDate.value;
            }
            if (maxDate == null) {
                maxDate = currentMaxDate.value;
            }
            if (groupNames == null) {
                groupNames = currentGroupedPlaces.value;
            }

            const rawMap = toRaw(map.value);

            Array.from(markersDate.value.entries()).forEach((entry) => {
                const marker = entry[0];
                const data = entry[1];
                const groupName = data.groupName;

                const dateFilterApplies =
                    !isTimelineFilterEnabled.value ||
                    dateFilter(data, minDate, maxDate);

                const groupFilterApplies =
                    !groupNames || groupNames.includes(groupName);

                if (dateFilterApplies && groupFilterApplies) {
                    marker.addTo(rawMap);
                } else {
                    marker.remove();
                }
            });
            setCurrentValues(minDate, maxDate, groupNames);
        };

        const dateFilter = (data, minDate, maxDate) => {
            if (!data.startDate || !data.endDate) {
                return false;
            }

            const startDate = new Date(data.startDate);
            const endDate = new Date(data.endDate);

            if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
                return false;
            }

            return (
                (endDate.getFullYear() > minDate.year ||
                    (endDate.getFullYear() === minDate.year &&
                        endDate.getMonth() >= minDate.month)) &&
                (startDate.getFullYear() < maxDate.year ||
                    (startDate.getFullYear() === maxDate.year &&
                        startDate.getMonth() <= maxDate.month))
            );
        };

        const visualizePlaces = async (groupedPlaces) => {
            try {
                await nextTick();
                for (const [group, places] of Object.entries(groupedPlaces)) {
                    let placeMarkers = createPlaceMarkers(group, places);
                    createPlacesLayerGroups(group, placeMarkers);
                }

                handleLayerGroupsUpdate(groupedPlacesShownOnMap.value);
            } catch (error) {
                console.error("Error in visualizePlaces:", error);
            }
        };
        const createPlaceMarkers = (placeGroupName, places) => {
            if (!places) {
                console.warn("No places provided for", placeGroupName);
                return [];
            }

            const placeMarkers = [];
            const groupConfig = groupedPlaces.value[placeGroupName];
            const iconUrl =
                ogtGlobals.proxyPath +
                mapMarkerIconsPath.value +
                groupedPlaces.value[placeGroupName].iconName;

            const groupIcon = L.icon({
                className: "",
                iconUrl:
                    ogtGlobals.proxyPath +
                    mapMarkerIconsPath.value +
                    groupConfig.iconName,
                iconRetinaUrl:
                    ogtGlobals.proxyPath +
                    mapMarkerIconsPath.value +
                    groupConfig.iconName,
                shadowUrl:
                    ogtGlobals.proxyPath + "/images/leaflet/marker-shadow.png",
                iconSize: [48, 53],
                iconAnchor: [24, 52],
                popupAnchor: [1, -34],
                tooltipAnchor: [16, -28],
                shadowSize: [76, 52],
            });

            for (const [placeId, place] of Object.entries(places)) {
                if (!place || !place.coordinates) {
                    console.warn(`Invalid place data for ${placeId}`);
                    continue;
                }

                const countedPlaceCoordinates = Object.keys(
                    place.coordinates || {},
                ).length;
                let coordinatesIndex = 0;
                locationMarkers.value[placeId] = [];

                for (const [statementId, placeCoordinate] of Object.entries(
                    place.coordinates,
                )) {
                    if (!placeCoordinate?.value) {
                        console.warn(`Invalid coordinate data for ${placeId}`);
                        continue;
                    }
                    const marker = L.marker(placeCoordinate.value, {
                        icon: groupIcon,
                        riseOnHover: true,
                        title: place.label,
                    });

                    const popup = L.popup({
                        className: "font-weight-bold",
                    });

                    marker.bindPopup(popup);

                    marker.on("click", (event) => {
                        popup.setContent(place.label);
                        setSelectedPlace(
                            place,
                            event.latlng,
                            placeGroupName,
                            getDerivedPlacesData(),
                        );
                        toggleShowPlaceInfoSidebar(true);
                    });

                    placeMarkers.push(marker);

                    let placeLabelWithIndex = place.label;
                    if (countedPlaceCoordinates > 1) {
                        placeLabelWithIndex += ` (${++coordinatesIndex})`;
                    }

                    locationMarkers.value[placeId][
                        Object.values(placeCoordinate.value).join(",")
                    ] = marker;

                    const getDateProp = (key) => {
                        return (
                            place[key]?.value ||
                            Object.values(place[key] ?? {})[0]?.value
                        );
                    };

                    const getStartDate = () => {
                        if (placeGroupName === "memorials") {
                            return (
                                getDateProp("openingDate") ??
                                getDateProp("inceptionDates") ??
                                getDateProp("startTime") ??
                                new Date("1945-05-08").toDateString()
                            );
                        } else if (["events", "persecutionOfSocialistFront"].includes(placeGroupName)) {
                            return (
                                getDateProp("pointInTime") ??
                                getDateProp("startTime") ??
                                new Date("1933-01-30").toDateString()
                            );
                        } else {
                            return (
                                getDateProp("inceptionDates") ??
                                getDateProp("startTime") ??
                                getDateProp("openingDate") ??
                                null
                            );
                        }
                    };

                    const getEndDate = () => {
                        if (placeGroupName === "memorials") {
                            return (
                                getDateProp("endTime") ??
                                new Date().toDateString()
                            );
                        } else if (["events", "persecutionOfSocialistFront"].includes(placeGroupName)) {
                            return (
                                getDateProp("pointInTime") ??
                                getDateProp("endTime") ??
                                new Date("1945-05-08").toDateString()
                            );
                        } else {
                            return (
                                getDateProp("dissolvedDates") ??
                                getDateProp("endTime") ??
                                new Date("1945-05-08").toDateString()
                            );
                        }
                    };

                    markersDate.value.set(marker, {
                        startDate: getStartDate(),
                        endDate: getEndDate(),
                        groupName: placeGroupName,
                    });

                    groupedPlaces.value[
                        placeGroupName
                    ].placesByCoordinates.push({
                        placeLabelWithIndex: placeLabelWithIndex,
                        latLng: placeCoordinate.value,
                        marker: marker,
                    });
                }
            }

            return placeMarkers;
        };
        /**
         * Set selected place info.
         *
         * @param object place Wikidata place data
         * @param object latLng Leaflet LatLng geographical point object
         * @param string placeGroupName Name of place group
         */
        const setSelectedPlace = async (
            place,
            latLng,
            placeGroupName,
            placeData,
        ) => {
            try {
                await nextTick();
                await new Promise((resolve) => setTimeout(resolve, 100));

                selectedPlace.value.description = place.description;
                selectedPlace.value.label = place.label;
                selectedPlace.value.groupName = placeGroupName;
                selectedPlace.value.layerName =
                    groupedPlaces.value[placeGroupName].layerName;
                selectedPlace.value.id = place.id;
                selectedPlace.value.latLng = latLng;

                window.history.replaceState(
                    null,
                    null,
                    encodeURI(
                        `${document.location.pathname}?id=${place.id}&group=${placeGroupName}&lat=${latLng.lat}&lng=${latLng.lng}`,
                    ),
                );

                selectedPlace.value.mainImageUrl = "";
                selectedPlace.value.mainImageLegend = "";

                const imageKeys = place.images ? Object.keys(place.images) : [];
                const imagesCounted = imageKeys.length;

                const coordinatesKeys = place.coordinates
                    ? Object.keys(place.coordinates)
                    : [];
                const coordinatesCounted = coordinatesKeys.length;

                if (imagesCounted == 1 && coordinatesCounted == 1) {
                    selectedPlace.value.mainImageUrl =
                        place.images[imageKeys[0]].value;
                    selectedPlace.value.mainImageLegend = place.images[
                        imageKeys[0]
                    ].mediaLegend
                        ? place.images[imageKeys[0]].mediaLegend.value
                        : "";
                } else if (imagesCounted > 0) {
                    imageKeys.some((imageKey) => {
                        let image = place.images[imageKey];

                        if (
                            image.coordinate &&
                            image.coordinate.value.lat == latLng.lat &&
                            image.coordinate.value.lng == latLng.lng
                        ) {
                            selectedPlace.value.mainImageUrl = image.value;
                            selectedPlace.value.mainImageLegend =
                                image.mediaLegend
                                    ? image.mediaLegend.value
                                    : "";
                            return true;
                        }
                    });
                }
                if (
                    selectedPlace.value.mainImageUrl &&
                    selectedPlace.value.mainImageUrl.startsWith("http:")
                ) {
                    selectedPlace.value.mainImageUrl =
                        selectedPlace.value.mainImageUrl.replace(
                            "http:",
                            "https:",
                        );
                }

                selectedPlace.value.inceptionDate = {
                    locale: "",
                    value: null,
                };

                if (place.inceptionDates) {
                    for (const [statementId, inceptionDate] of Object.entries(
                        place.inceptionDates,
                    )) {
                        selectedPlace.value.inceptionDate = getDate(
                            inceptionDate.value,
                            inceptionDate.datePrecision,
                        );
                        break;
                    }
                }

                selectedPlace.value.dissolvedDate = {
                    locale: "",
                    value: null,
                };

                if (place.dissolvedDates) {
                    for (const [statementId, dissolvedDate] of Object.entries(
                        place.dissolvedDates,
                    )) {
                        selectedPlace.value.dissolvedDate = getDate(
                            dissolvedDate.value,
                            dissolvedDate.datePrecision,
                        );
                        break;
                    }
                }

                selectedPlace.value.addresses = {
                    additional: [],
                    selected: null,
                };

                // add location coordinates and related additional information to location address data
                for (const [statementId, coordinate] of Object.entries(
                    place.coordinates,
                )) {
                    let label = coordinate.streetAddress
                        ? coordinate.streetAddress.value
                        : "";

                    let startDate = coordinate.startTime
                        ? getDate(
                              coordinate.startTime.value,
                              coordinate.startTime.datePrecision,
                          )
                        : null;

                    let endDate = coordinate.endTime
                        ? getDate(
                              coordinate.endTime.value,
                              coordinate.endTime.datePrecision,
                          )
                        : null;

                    // check if the coordinate is specified as uncertain (= inaccurate) in Wikidata
                    // uncertainty - https://www.wikidata.org/wiki/Q13649246
                    let isUncertain = false;
                    if (
                        coordinate.sourcingCircumstance &&
                        coordinate.sourcingCircumstance.id == "Q13649246"
                    ) {
                        isUncertain = true;
                    }

                    let address = {
                        endDate: endDate,
                        isUncertain: isUncertain,
                        label: label,
                        latLng: coordinate.value,
                        startDate: startDate,
                    };

                    if (
                        coordinate.value.lat == latLng.lat ||
                        coordinate.value.lng == latLng.lng
                    ) {
                        selectedPlace.value.addresses.selected = address;
                    } else {
                        selectedPlace.value.addresses.additional.push(address);
                    }
                }

                // merge street addresses and related additional information with location address data
                if (place.streetAddresses) {
                    let selectedAddress =
                        selectedPlace.value.addresses.selected;
                    let additionalAddresses =
                        selectedPlace.value.addresses.additional;

                    let streetAddressKeys = Object.keys(place.streetAddresses);

                    if (
                        streetAddressKeys.length == 1 &&
                        additionalAddresses.length == 0
                    ) {
                        let address =
                            place.streetAddresses[streetAddressKeys[0]];

                        if (selectedAddress.label == "") {
                            selectedAddress.label = address.value;
                        }

                        if (address.startTime) {
                            selectedAddress.startDate = getDate(
                                address.startTime.value,
                                address.startTime.datePrecision,
                            );
                        }

                        if (address.endTime) {
                            selectedAddress.endDate = getDate(
                                address.endTime.value,
                                address.endTime.datePrecision,
                            );
                        }
                    } else {
                        streetAddressKeys.forEach((addressKey) => {
                            let streetAddress =
                                place.streetAddresses[addressKey];

                            let startDate = streetAddress.startTime
                                ? getDate(
                                      streetAddress.startTime.value,
                                      streetAddress.startTime.datePrecision,
                                  )
                                : null;

                            let endDate = streetAddress.endTime
                                ? getDate(
                                      streetAddress.endTime.value,
                                      streetAddress.endTime.datePrecision,
                                  )
                                : null;

                            if (streetAddress.value == selectedAddress.label) {
                                if (startDate) {
                                    selectedAddress.startDate = startDate;
                                }

                                if (endDate) {
                                    selectedAddress.endDate = endDate;
                                }
                            } else {
                                let foundAdditionalAddress = false;

                                additionalAddresses.some(
                                    (additionalAddress) => {
                                        if (
                                            streetAddress.value ==
                                            additionalAddress.label
                                        ) {
                                            foundAdditionalAddress = true;

                                            if (startDate) {
                                                additionalAddress.startDate =
                                                    startDate;
                                            }

                                            if (endDate) {
                                                additionalAddress.endDate =
                                                    endDate;
                                            }

                                            return true;
                                        }
                                    },
                                );

                                if (!foundAdditionalAddress) {
                                    additionalAddresses.push({
                                        endDate: endDate,
                                        label: streetAddress.value,
                                        latLng: null,
                                        startDate: startDate,
                                    });
                                }
                            }
                        });
                    }
                }

                selectedPlace.value.addresses.additional.sort(sortByDate);

                selectedPlace.value.employeeCounts = [];

                if (place.employeeCounts) {
                    for (const [statementId, employeeCount] of Object.entries(
                        place.employeeCounts,
                    )) {
                        let pointInTime = employeeCount.pointInTime
                            ? getDate(
                                  employeeCount.pointInTime.value,
                                  employeeCount.pointInTime.datePrecision,
                              )
                            : null;

                        let sourcingCircumstance = "";
                        if (
                            employeeCount.sourcingCircumstance &&
                            employeeCount.sourcingCircumstance.id in
                                sourceCircumstances.value
                        ) {
                            sourcingCircumstance =
                                sourceCircumstances.value[
                                    employeeCount.sourcingCircumstance.id
                                ];
                        }

                        selectedPlace.value.employeeCounts.push({
                            pointInTime: pointInTime,
                            sourcingCircumstance: sourcingCircumstance,
                            value: employeeCount.value,
                        });
                    }

                    selectedPlace.value.employeeCounts.sort(sortByPointInTime);
                }

                selectedPlace.value.directors = getPropertyData(
                    place.directors,
                    true,
                    false,
                    true,
                );

                selectedPlace.value.prisonerCounts = [];

                if (place.prisonerCounts) {
                    for (const [statementId, prisonerCount] of Object.entries(
                        place.prisonerCounts,
                    )) {
                        let sourcingCircumstance = "";
                        if (
                            prisonerCount.sourcingCircumstance &&
                            prisonerCount.sourcingCircumstance.id in
                                sourceCircumstances.value
                        ) {
                            sourcingCircumstance =
                                sourceCircumstances.value[
                                    prisonerCount.sourcingCircumstance.id
                                ];
                        }

                        selectedPlace.value.prisonerCounts.push({
                            sourcingCircumstance: sourcingCircumstance,
                            value: prisonerCount.value,
                        });
                    }
                }

                selectedPlace.value.isDeportationEvent = hasItemPropertyClass(
                    place,
                    "instances",
                    "deportation",
                );

                selectedPlace.value.significantEvents = [];

                if (place.significantEvents) {
                    for (const [
                        statementId,
                        significantEvent,
                    ] of Object.entries(place.significantEvents)) {
                        let hasLocationMarker = locationMarkers.value[
                            significantEvent.id
                        ]
                            ? true
                            : false;

                        selectedPlace.value.significantEvents.push({
                            hasLocationMarker: hasLocationMarker,
                            id: significantEvent.id,
                            label: significantEvent.value,
                        });
                    }
                }

                selectedPlace.value.hasParts = Object.values(place.hasParts || {})
                    .filter(item => locationMarkers.value[item.id])
                    .map(item => ({ id: item.id, label: item.value }))
                    .sort((a, b) => a.label.localeCompare(b.label, "de"));

                selectedPlace.value.partOfs = Object.values(place.partOfs || {})
                    .filter(item => locationMarkers.value[item.id])
                    .map(item => ({ id: item.id, label: item.value }));

                selectedPlace.value.parentOrganizations = [];

                if (place.parentOrganizations) {
                    for (const [
                        statementId,
                        parentOrganization,
                    ] of Object.entries(place.parentOrganizations)) {
                        let hasLocationMarker = locationMarkers.value[
                            parentOrganization.id
                        ]
                            ? true
                            : false;

                        let startDate = parentOrganization.startTime
                            ? getDate(
                                  parentOrganization.startTime.value,
                                  parentOrganization.startTime.datePrecision,
                              )
                            : null;

                        let endDate = parentOrganization.endTime
                            ? getDate(
                                  parentOrganization.endTime.value,
                                  parentOrganization.endTime.datePrecision,
                              )
                            : null;

                        selectedPlace.value.parentOrganizations.push({
                            endDate: endDate,
                            hasLocationMarker: hasLocationMarker,
                            id: parentOrganization.id,
                            label: parentOrganization.value,
                            startDate: startDate,
                        });
                    }

                    selectedPlace.value.parentOrganizations.sort(sortByDate);
                }

                selectedPlace.value.childOrganizations = [];

                if (place.subsidiaries) {
                    for (const [statementId, subsidiary] of Object.entries(
                        place.subsidiaries,
                    )) {
                        let hasLocationMarker = locationMarkers.value[
                            subsidiary.id
                        ]
                            ? true
                            : false;

                        let startDate = subsidiary.startTime
                            ? getDate(
                                  subsidiary.startTime.value,
                                  subsidiary.startTime.datePrecision,
                              )
                            : null;

                        let endDate = subsidiary.endTime
                            ? getDate(
                                  subsidiary.endTime.value,
                                  subsidiary.endTime.datePrecision,
                              )
                            : null;

                        selectedPlace.value.childOrganizations.push({
                            endDate: endDate,
                            hasLocationMarker: hasLocationMarker,
                            id: subsidiary.id,
                            label: subsidiary.value,
                            startDate: startDate,
                        });
                    }

                    selectedPlace.value.childOrganizations.sort(sortByDate);
                }

                selectedPlace.value.predecessors = [];

                if (place.replaces) {
                    for (const [statementId, replace] of Object.entries(
                        place.replaces,
                    )) {
                        let hasLocationMarker = locationMarkers.value[
                            replace.id
                        ]
                            ? true
                            : false;

                        let pointInTime = replace.pointInTime
                            ? getDate(
                                  replace.pointInTime.value,
                                  replace.pointInTime.datePrecision,
                              )
                            : null;

                        selectedPlace.value.predecessors.push({
                            hasLocationMarker: hasLocationMarker,
                            id: replace.id,
                            label: replace.value,
                            pointInTime: pointInTime,
                        });
                    }

                    selectedPlace.value.predecessors.sort(sortByPointInTime);
                }

                selectedPlace.value.successors = [];

                if (place.replacedBys) {
                    for (const [statementId, replacedBy] of Object.entries(
                        place.replacedBys,
                    )) {
                        let hasLocationMarker = locationMarkers.value[
                            replacedBy.id
                        ]
                            ? true
                            : false;

                        let pointInTime = replacedBy.pointInTime
                            ? getDate(
                                  replacedBy.pointInTime.value,
                                  replacedBy.pointInTime.datePrecision,
                              )
                            : null;

                        selectedPlace.value.successors.push({
                            hasLocationMarker: hasLocationMarker,
                            id: replacedBy.id,
                            label: replacedBy.value,
                            pointInTime: pointInTime,
                        });
                    }

                    selectedPlace.value.successors.sort(sortByPointInTime);
                }

                selectedPlace.value.sources = [];
                if (place.describedBySources) {
                    for (const [
                        statementId,
                        describedBySource,
                    ] of Object.entries(place.describedBySources)) {
                        selectedPlace.value.sources.push({
                            label: describedBySource.value,
                            pages: describedBySource.pages
                                ? describedBySource.pages.value
                                : "",
                            inventoryNumber: describedBySource.inventoryNumber
                                ? describedBySource.inventoryNumber.value
                                : "",
                        });
                    }
                }
                selectedPlace.value.urlSources = [];
                if (place.describedByUrlSources) {
                    for (const [_, urlSource] of Object.entries(
                        place.describedByUrlSources,
                    )) {
                        selectedPlace.value.urlSources.push({
                            url: urlSource.value || "",
                        });
                    }
                }

                selectedPlace.value.website = "";
                if (place.officialWebsite) {
                    for (const [statementId, website] of Object.entries(
                        place.officialWebsite,
                    )) {
                        selectedPlace.value.website = website.value;
                        break;
                    }
                }

                selectedPlace.value.operators = [];
                if (place.operators) {
                    for (const [statementId, operator] of Object.entries(
                        place.operators,
                    )) {
                        selectedPlace.value.operators.push(operator.value);
                    }
                }

                selectedPlace.value.hasUses = [];
                if (place.hasUses) {
                    for (const [statementId, hasUse] of Object.entries(
                        place.hasUses,
                    )) {
                        const value =
                            hasUse.value === "Ausbildung"
                                ? "Bildungsarbeit"
                                : hasUse.value;
                        selectedPlace.value.hasUses.push(value);
                    }
                }

                selectedPlace.value.startDate = {
                    locale: "",
                    value: null,
                };

                if (place.startTime) {
                    for (const [statementId, startDate] of Object.entries(
                        place.startTime,
                    )) {
                        selectedPlace.value.startDate = getDate(
                            startDate.value,
                            startDate.datePrecision,
                        );
                        break;
                    }
                }

                selectedPlace.value.openingDate = {
                    locale: "",
                    value: null,
                };

                if (place.openingDate) {
                    for (const [statementId, openingDate] of Object.entries(
                        place.openingDate,
                    )) {
                        selectedPlace.value.openingDate = getDate(
                            openingDate.value,
                            openingDate.datePrecision,
                        );
                        break;
                    }
                }

                selectedPlace.value.endDate = {
                    locale: "",
                    value: null,
                };

                if (place.endTime) {
                    for (const [statementId, endTime] of Object.entries(
                        place.endTime,
                    )) {
                        selectedPlace.value.endDate = getDate(
                            endTime.value,
                            endTime.datePrecision,
                        );
                        break;
                    }
                }

                selectedPlace.value.pointInTime = {
                    locale: "",
                    value: null,
                };

                if (place.pointInTime) {
                    for (const [statementId, pointInTime] of Object.entries(
                        place.pointInTime,
                    )) {
                        selectedPlace.value.pointInTime = getDate(
                            pointInTime.value,
                            pointInTime.datePrecision,
                        );
                        break;
                    }
                }

                selectedPlace.value.commemorates = [];
                if (place.commemorates) {
                    for (const [statementId, commemorate] of Object.entries(
                        place.commemorates,
                    )) {
                        let hasLocationMarker = locationMarkers.value[
                            commemorate.id
                        ]
                            ? true
                            : false;

                        selectedPlace.value.commemorates.push({
                            hasLocationMarker: hasLocationMarker,
                            id: commemorate.id,
                            label: commemorate.value,
                        });
                    }
                }

                selectedPlace.value.perpetrators = [];
                if (place.perpetrators) {
                    for (const [statementId, perpetrator] of Object.entries(
                        place.perpetrators,
                    )) {
                        let hasPersonData = persons.value.perpetrators[
                            perpetrator.id
                        ]
                            ? true
                            : false;
                        let hasLocationMarker = locationMarkers.value[
                            perpetrator.id
                        ]
                            ? true
                            : false;

                        selectedPlace.value.perpetrators.push({
                            hasLocationMarker: hasLocationMarker,
                            hasPersonData: hasPersonData,
                            id: perpetrator.id,
                            label: perpetrator.value,
                        });
                    }
                }

                selectedPlace.value.victims = [];
                let gatheredVictimIds = [];
                if (place.victims) {
                    for (const [statementId, victim] of Object.entries(
                        place.victims,
                    )) {
                        gatheredVictimIds.push(victim.id);

                        let hasPersonData = persons.value.victims[victim.id]
                            ? true
                            : false;

                        selectedPlace.value.victims.push({
                            hasPersonData: hasPersonData,
                            id: victim.id,
                            name: victim.value,
                        });
                    }
                }

                selectedPlace.value.targets = [];
                if (place.targets) {
                    for (const [statementId, target] of Object.entries(
                        place.targets,
                    )) {
                        selectedPlace.value.targets.push(target.value);
                    }
                }

                selectedPlace.value.numberOfCasualties = [];
                if (place.numberOfCasualties) {
                    for (const [
                        statementId,
                        numberOfCasualties,
                    ] of Object.entries(place.numberOfCasualties)) {
                        let pointInTime = numberOfCasualties.pointInTime
                            ? getDate(
                                  numberOfCasualties.pointInTime.value,
                                  numberOfCasualties.pointInTime.datePrecision,
                              )
                            : null;

                        let sourcingCircumstance = "";
                        if (
                            numberOfCasualties.sourcingCircumstance &&
                            numberOfCasualties.sourcingCircumstance.id in
                                sourceCircumstances.value
                        ) {
                            sourcingCircumstance =
                                sourceCircumstances.value[
                                    numberOfCasualties.sourcingCircumstance.id
                                ];
                        }

                        selectedPlace.value.numberOfCasualties.push({
                            pointInTime: pointInTime,
                            sourcingCircumstance: sourcingCircumstance,
                            value: numberOfCasualties.value,
                        });
                    }
                }

                selectedPlace.value.numberOfDeaths = [];
                if (place.numberOfDeaths) {
                    for (const [statementId, numberOfDeaths] of Object.entries(
                        place.numberOfDeaths,
                    )) {
                        let pointInTime = numberOfDeaths.pointInTime
                            ? getDate(
                                  numberOfDeaths.pointInTime.value,
                                  numberOfDeaths.pointInTime.datePrecision,
                              )
                            : null;

                        let sourcingCircumstance = "";
                        if (
                            numberOfDeaths.sourcingCircumstance &&
                            numberOfDeaths.sourcingCircumstance.id in
                                sourceCircumstances.value
                        ) {
                            sourcingCircumstance =
                                sourceCircumstances.value[
                                    numberOfDeaths.sourcingCircumstance.id
                                ];
                        }

                        selectedPlace.value.numberOfDeaths.push({
                            pointInTime: pointInTime,
                            sourcingCircumstance: sourcingCircumstance,
                            value: numberOfDeaths.value,
                        });
                    }
                }

                selectedPlace.value.numberOfSurvivors = [];
                if (place.numberOfSurvivors) {
                    for (const [
                        statementId,
                        numberOfSurvivors,
                    ] of Object.entries(place.numberOfSurvivors)) {
                        let pointInTime = numberOfSurvivors.pointInTime
                            ? getDate(
                                  numberOfSurvivors.pointInTime.value,
                                  numberOfSurvivors.pointInTime.datePrecision,
                              )
                            : null;

                        let sourcingCircumstance = "";
                        if (
                            numberOfSurvivors.sourcingCircumstance &&
                            numberOfSurvivors.sourcingCircumstance.id in
                                sourceCircumstances.value
                        ) {
                            sourcingCircumstance =
                                sourceCircumstances.value[
                                    numberOfSurvivors.sourcingCircumstance.id
                                ];
                        }

                        selectedPlace.value.numberOfSurvivors.push({
                            pointInTime: pointInTime,
                            sourcingCircumstance: sourcingCircumstance,
                            value: numberOfSurvivors.value,
                        });
                    }
                }

                selectedPlace.value.numberOfArrests = [];
                if (place.numberOfArrests) {
                    for (const [statementId, numberOfArrests] of Object.entries(
                        place.numberOfArrests,
                    )) {
                        selectedPlace.value.numberOfArrests.push(
                            numberOfArrests.value,
                        );
                    }
                }

                selectedPlace.value.locations = [];
                if (place.locations) {
                    for (const [statementId, location] of Object.entries(
                        place.locations,
                    )) {
                        selectedPlace.value.locations.push(location.value);
                    }
                }

                selectedPlace.value.significantPlaces = [];
                if (place.significantPlaces) {
                    for (const [
                        statementId,
                        significantPlace,
                    ] of Object.entries(place.significantPlaces)) {
                        selectedPlace.value.significantPlaces.push(
                            significantPlace.value,
                        );
                    }
                }

                selectedPlace.value.startPoints = [];
                if (place.startPoints) {
                    for (const [statementId, startPoint] of Object.entries(
                        place.startPoints,
                    )) {
                        selectedPlace.value.startPoints.push(startPoint.value);
                    }
                }

                selectedPlace.value.destinationPoints = [];
                if (place.destinationPoints) {
                    for (const [
                        statementId,
                        destinationPoint,
                    ] of Object.entries(place.destinationPoints)) {
                        selectedPlace.value.destinationPoints.push(
                            destinationPoint.value,
                        );
                    }
                }

                selectedPlace.value.commemoratedBy = [];
                selectedPlace.value.employees = [];
                selectedPlace.value.prisoners = [];

                const rawPlaceData = toRaw(placeData);
                let derivedPlacesData = rawPlaceData[selectedPlace.value.id];

                if (!derivedPlacesData) {
                    return;
                }

                if (derivedPlacesData.commemoratedBy) {
                    selectedPlace.value.commemoratedBy =
                        derivedPlacesData.commemoratedBy;

                    for (const [statementId, commemoratedBy] of Object.entries(
                        selectedPlace.value.commemoratedBy,
                    )) {
                        commemoratedBy.hasLocationMarker = locationMarkers
                            .value[commemoratedBy.id]
                            ? true
                            : false;
                    }
                }

                let directorIds = selectedPlace.value.directors.map(
                    (director) => director.id,
                );
                // only employees who are not directors of a location
                selectedPlace.value.employees = getPropertyData(
                    derivedPlacesData.employees,
                    true,
                    false,
                    false,
                    directorIds,
                );

                if (derivedPlacesData.prisoners) {
                    // personal data is always available, there is no need to check if the person can be linked
                    selectedPlace.value.prisoners = derivedPlacesData.prisoners;
                }

                if (derivedPlacesData.victims) {
                    for (const [index, victim] of Object.entries(
                        derivedPlacesData.victims,
                    )) {
                        if (gatheredVictimIds.includes(victim.id)) {
                            continue;
                        }

                        selectedPlace.value.victims.push(victim);
                    }
                }
                //console.log("selectedPlace", selectedPlace);
            } catch (error) {
                console.error("Error in setSelectedPlace:", error);
            }
        };
        /**
         * Check, if one of the property values of an item matches a certain class of Wikidata ID's.
         *
         * @param item
         * @param propertyName
         * @param className
         * @returns {boolean}
         */
        const hasItemPropertyClass = (item, propertyName, className) => {
            let hasItemPropertyClass = false;

            if (item[propertyName]) {
                const wikidataItemIds = WIKIDATA_ID_CLASSES[className];

                for (const [statementId, propertyValue] of Object.entries(
                    item[propertyName],
                )) {
                    if (wikidataItemIds.includes(propertyValue.id)) {
                        hasItemPropertyClass = true;
                        break;
                    }
                }
            }

            return hasItemPropertyClass;
        };
        /**
         * Get locale date string base on Wikidata time precision.
         *
         * @param {string} dateTimeString   Wikidata datetime
         * @param {int} datePrecision       9 => year precision, 10 => month precision, 11 => day precision
         * @param {string} locale           default 'de-de'
         *
         * @returns {{locale: string, value: Date}|{locale: string, value: null}}
         */
        const getDate = (dateTimeString, datePrecision, locale = "de-de") => {
            let dateFormatOptions = {};

            switch (datePrecision) {
                case "9":
                    dateFormatOptions = {
                        year: "numeric",
                    };
                    break;

                case "10":
                    dateFormatOptions = {
                        year: "numeric",
                        month: "short",
                    };
                    break;

                case "11":
                    dateFormatOptions = {
                        year: "numeric",
                        month: "short",
                        day: "numeric",
                    };
                    break;

                default:
                    return {
                        locale: "",
                        value: null,
                    };
            }

            let date = new Date(dateTimeString);

            return {
                locale: date.toLocaleDateString(locale, dateFormatOptions),
                value: date,
            };
        };
        /**
         * Compare items by date, used by sort array items, for e.g. addresses.
         *
         * @param {object} itemA The first element for comparison.
         * @param {object} itemB The second element for comparison.
         *
         * @returns {number}     > 0 sort A after B
         *                       < 0  sort A before B
         *                       === 0 keep original order of A and B
         */
        const sortByDate = (itemA, itemB) => {
            if (itemA.startDate && itemB.startDate) {
                return (
                    itemA.startDate.value.getTime() -
                    itemB.startDate.value.getTime()
                );
            } else if (itemA.endDate && itemB.endDate) {
                return (
                    itemA.endDate.value.getTime() -
                    itemB.endDate.value.getTime()
                );
            } else if (itemA.startDate && itemB.endDate) {
                return (
                    itemA.startDate.value.getTime() -
                    itemB.endDate.value.getTime()
                );
            } else if (itemA.endDate && itemB.startDate) {
                return (
                    itemA.endDate.value.getTime() -
                    itemB.startDate.value.getTime()
                );
            } else if (itemA.startDate || itemA.endDate) {
                // case: only item A has a date
                return -1;
            } else if (itemB.startDate || itemB.endDate) {
                // case: only item B has a date
                return 1;
            } else {
                return 0;
            }
        };
        /**
         * Compare items by a point in time, used by sort array items, for e.g. number of employees.
         *
         * @param {object} itemA The first element for comparison.
         * @param {object} itemB The second element for comparison.
         *
         * @returns {number}     > 0 sort A after B
         *                       < 0  sort A before B
         *                       === 0 keep original order of A and B
         */
        const sortByPointInTime = (itemA, itemB) => {
            if (itemA.pointInTime && itemB.pointInTime) {
                return (
                    itemA.pointInTime.value.getTime() -
                    itemB.pointInTime.value.getTime()
                );
            } else if (itemA.pointInTime) {
                // case: only item A has a point in time
                return -1;
            } else if (itemB.pointInTime) {
                // case: only item B has a point in time
                return 1;
            } else {
                return 0;
            }
        };
        /**
         * Create a layer group for markers, activate layer group on map and
         * add layer group checkbox at Leaflet layer control.
         *
         * @param string placeGroupName Layer name.
         * @param array placeMarkers Leaflet markers.
         */
        const createPlacesLayerGroups = (placeGroupName, placeMarkers) => {
            try {
                if (!map.value) {
                    console.error("Map is not initialized");
                    return;
                }
                const rawMap = toRaw(map.value);

                // Check if a layer group already exists for this place group
                if (groupedPlaces.value[placeGroupName].layerGroup) {
                    // If it exists, clear it and add the new markers
                    const existingLayerGroup =
                        groupedPlaces.value[placeGroupName].layerGroup;
                    existingLayerGroup.clearLayers();

                    // Add new markers to the existing layer group
                    placeMarkers.forEach((marker) => {
                        existingLayerGroup.addLayer(marker);
                    });
                } else {
                    // Create a new layer group if it doesn't exist
                    const layerGroup = L.layerGroup(placeMarkers);

                    if (layerGroup && typeof layerGroup.addTo === "function") {
                        layerGroup.addTo(rawMap);

                        if (
                            layers.value &&
                            typeof layers.value.addOverlay === "function"
                        ) {
                            const rawLayers = toRaw(layers.value);
                            rawLayers.addOverlay(
                                layerGroup,
                                groupedPlaces.value[placeGroupName]
                                    ?.layerName || "",
                            );
                        }

                        // Aktualisieren Sie die Referenz in groupedPlaces
                        if (groupedPlaces.value[placeGroupName]) {
                            groupedPlaces.value[placeGroupName].layerGroup =
                                layerGroup;
                        }
                    }
                }
            } catch (error) {
                console.error("Error creating layer group:", error);
            }
        };
        /**
         * Derive location data to make it easily accessible to other locations,
         * e.g. to know if a location is commemorated by a memorial.
         */
        const derivePlaceData = (groupedPlaces) => {
            try {
                // Sicherheitsprüfung für groupedPlaces
                if (!groupedPlaces || !groupedPlaces.memorials) {
                    console.warn("Memorials data not available");
                    return;
                }

                const memorials = groupedPlaces.memorials;
                if (!memorials) {
                    console.warn("No memorial places found");
                    return;
                }

                // Initialisiere derivedPlacesData wenn nötig
                if (!derivedPlacesData.value) {
                    derivedPlacesData.value = {};
                }

                for (const [memorialId, memorial] of Object.entries(
                    memorials,
                )) {
                    if (!memorial || !memorial.commemorates) continue;

                    for (const [statementId, commemorate] of Object.entries(
                        memorial.commemorates,
                    )) {
                        if (!commemorate || !commemorate.id) continue;

                        if (!derivedPlacesData.value[commemorate.id]) {
                            derivedPlacesData.value[commemorate.id] = {
                                commemoratedBy: [],
                            };
                        } else if (
                            !derivedPlacesData.value[commemorate.id]
                                .commemoratedBy
                        ) {
                            derivedPlacesData.value[
                                commemorate.id
                            ].commemoratedBy = [];
                        }

                        derivedPlacesData.value[
                            commemorate.id
                        ].commemoratedBy.push({
                            id: memorialId,
                            name: memorial.label || "",
                        });
                    }
                }
            } catch (error) {
                console.error("Error deriving place data:", error);
            }
        };
        /**
         * Deriving location employees from perpetrator data to make them easily accessible for location data.
         */
        const deriveLocationEmployees = () => {
            try {
                // Lösche bestehende Mitarbeiter-Daten
                for (const [locationId, location] of Object.entries(
                    derivedPlacesData.value,
                )) {
                    if (location) {
                        delete location.employees;
                    }
                }

                // Hole Täter-Daten
                const perpetrators = persons.value?.perpetrators;
                if (!perpetrators) {
                    console.warn("No perpetrators data available");
                    return;
                }

                // Verarbeite Täter-Daten
                for (const [perpetratorId, perpetrator] of Object.entries(
                    perpetrators,
                )) {
                    if (!perpetrator?.employers) continue;

                    for (const [statementId, employer] of Object.entries(
                        perpetrator.employers,
                    )) {
                        if (!employer?.id) continue;

                        // Initialisiere die Datenstruktur wenn nötig
                        if (!derivedPlacesData.value[employer.id]) {
                            derivedPlacesData.value[employer.id] = {
                                employees: [],
                            };
                        } else if (
                            !derivedPlacesData.value[employer.id].employees
                        ) {
                            derivedPlacesData.value[employer.id].employees = [];
                        }

                        // Füge Mitarbeiter-Daten hinzu
                        derivedPlacesData.value[employer.id].employees.push({
                            earliestDate: employer.earliestDate || null,
                            earliestEndDate: employer.earliestEndDate || null,
                            endTime: employer.endTime || null,
                            id: perpetratorId,
                            latestDate: employer.latestDate || null,
                            latestStartDate: employer.latestStartDate || null,
                            startTime: employer.startTime || null,
                            value: perpetrator.label || "",
                        });
                    }
                }
            } catch (error) {
                console.error("Error deriving location employees:", error);
            }
        };
        /**
         * Deriving location prisoners and event victims
         * from victim data to make them easily accessible for location/event data.
         */
        const deriveDataFromVictims = () => {
            try {
                // Überprüfe ob derivedPlacesData initialisiert ist
                if (!derivedPlacesData.value) {
                    console.warn("derivedPlacesData is not initialized");
                    return;
                }

                // Lösche bestehende Daten
                Object.entries(derivedPlacesData.value).forEach(
                    ([locationId, location]) => {
                        if (location) {
                            delete location.prisoners;
                            delete location.victims;
                        }
                    },
                );

                // Überprüfe ob victims Daten existieren
                if (!persons.value?.victims) {
                    console.warn("No victims data available");
                    return;
                }

                // Verarbeite die Opfer-Daten
                Object.entries(persons.value.victims).forEach(
                    ([victimId, victim]) => {
                        // Verarbeite Haftorte
                        if (victim?.detentionPlaces) {
                            Object.entries(victim.detentionPlaces).forEach(
                                ([statementId, detentionPlace]) => {
                                    if (!detentionPlace?.id) return;

                                    // Initialisiere die Datenstruktur für Gefangene
                                    if (
                                        !derivedPlacesData.value[
                                            detentionPlace.id
                                        ]
                                    ) {
                                        derivedPlacesData.value[
                                            detentionPlace.id
                                        ] = {
                                            prisoners: [],
                                        };
                                    } else if (
                                        !derivedPlacesData.value[
                                            detentionPlace.id
                                        ].prisoners
                                    ) {
                                        derivedPlacesData.value[
                                            detentionPlace.id
                                        ].prisoners = [];
                                    }

                                    // Füge Gefangenen hinzu
                                    derivedPlacesData.value[
                                        detentionPlace.id
                                    ].prisoners.push({
                                        id: victimId,
                                        name: victim.label || "",
                                    });
                                },
                            );
                        }

                        // Verarbeite bedeutende Ereignisse
                        if (victim?.significantEvents) {
                            Object.entries(victim.significantEvents).forEach(
                                ([statementId, significantEvent]) => {
                                    if (!significantEvent?.id) return;

                                    // Initialisiere die Datenstruktur für Opfer
                                    if (
                                        !derivedPlacesData.value[
                                            significantEvent.id
                                        ]
                                    ) {
                                        derivedPlacesData.value[
                                            significantEvent.id
                                        ] = {
                                            victims: [],
                                        };
                                    } else if (
                                        !derivedPlacesData.value[
                                            significantEvent.id
                                        ].victims
                                    ) {
                                        derivedPlacesData.value[
                                            significantEvent.id
                                        ].victims = [];
                                    }

                                    // Füge Opfer hinzu
                                    derivedPlacesData.value[
                                        significantEvent.id
                                    ].victims.push({
                                        hasPersonData: true,
                                        id: victimId,
                                        name: victim.label || "",
                                    });
                                },
                            );
                        }
                    },
                );
            } catch (error) {
                console.error("Error processing victims data:", error);
            }
        };
        /**
         * Check for URL query parameters to display a specific location on map.
         */
        const checkUrlForLocation = async () => {
            try {
                const searchParams = new URLSearchParams(
                    window.location.search,
                );
                const qId = searchParams.get("id");

                if (qId && locationMarkers.value[qId]) {
                    await ensureDataProcessed();

                    forceConsistentUIState();

                    switchLocation({
                        locationId: qId,
                        zoom: 14,
                    });
                }
            } catch (error) {
                console.error("Error checking URL for location:", error);
            }
        };
        /**
         * Check for URL query parameters to display a specific person in map info sidebar.
         */
        const checkUrlForPerson = async () => {
            try {
                const searchParams = new URLSearchParams(
                    window.location.search,
                );
                const qId = searchParams.get("pid");

                if (qId) {
                    await ensureDataProcessed();

                    forceConsistentUIState();

                    let personGroup = "";
                    if (persons.value.victims[qId]) {
                        personGroup = "victims";
                    } else if (persons.value.perpetrators[qId]) {
                        personGroup = "perpetrators";
                    }

                    if (personGroup) {
                        showPerson({
                            id: qId,
                            group: personGroup,
                        });
                    }
                }
            } catch (error) {
                console.error("Error checking URL for person:", error);
            }
        };

        const ensureDataProcessed = () => {
            return new Promise((resolve) => {
                // Prüfen, ob derivedPlacesData bereits initialisiert ist
                if (Object.keys(derivedPlacesData.value).length > 0) {
                    // Daten sind bereits verarbeitet
                    resolve();
                    return;
                }

                // Ansonsten ausdrücklich die Ableitung der Daten durchführen
                derivePlaceData(groupedPlaces.value);
                deriveLocationEmployees();
                deriveDataFromVictims();

                // Nach einer kurzen Verzögerung fortfahren
                setTimeout(() => {
                    resolve();
                }, 500);
            });
        };

        /**
         * Switch location on map and location info.
         *
         * @param {string}              locationId
         * @param {object|null}         latLng      default null
         * @param {number|undefined}    zoom        zoom level, default undefined
         */
        const switchLocation = async ({
            locationId,
            latLng = null,
            zoom = undefined,
        }) => {
            try {
                await ensureDataProcessed();
                let markers = locationMarkers.value[locationId];
                const rawMap = toRaw(map.value);

                if (!markers) {
                    console.warn("No markers found for location:", locationId);
                    return;
                }

                let locationMarker = null;

                if (latLng) {
                    locationMarker = markers[Object.values(latLng).join(",")];
                } else {
                    let locationMarkerLatLng = Object.keys(markers)[0];
                    locationMarker =
                        locationMarkers.value[locationId][locationMarkerLatLng];
                    latLng = locationMarker.getLatLng();
                }

                if (!locationMarker) {
                    console.warn(
                        "No marker found for the specified coordinates",
                    );
                    return;
                }

                // workaround to bring a marker icon to foreground when multiple markers overlap on same coordinates
                locationMarker.remove();
                locationMarker.addTo(rawMap);

                forceConsistentUIState();

                rawMap.flyTo(latLng, zoom);

                locationMarker.fire("click", {
                    latlng: latLng,
                });
            } catch (error) {
                console.error("Error in switchLocation:", error);
            }
        };
        /**
         * Show person data in map info sidebar.
         *
         * @param {string} id    Wikidata item id.
         * @param {string} group Group of person.
         */
        const showPerson = async ({ id, group = "" }) => {
            try {
                await ensureDataProcessed();
                toggleShowPlaceInfoSidebar(true);
                selectedPlace.value.groupName = group;

                // Überprüfe und setze die korrekte Gruppe
                if (
                    !["perpetrators", "victims"].includes(
                        selectedPlace.value.groupName,
                    )
                ) {
                    if (id in persons.value.victims) {
                        selectedPlace.value.groupName = "victims";
                    } else if (id in persons.value.perpetrators) {
                        selectedPlace.value.groupName = "perpetrators";
                    } else {
                        console.warn("Person not found in any group:", id);
                        toggleShowPlaceInfoSidebar(false);
                        return;
                    }
                }

                // Hole Person-Daten
                const person = persons.value[selectedPlace.value.groupName][id];
                if (!person) {
                    console.error("Person data not found:", { id, group });
                    return;
                }

                // Verarbeite Person-Daten basierend auf Gruppe
                if (selectedPlace.value.groupName === "victims") {
                    setSelectedVictimData(person);
                } else if (selectedPlace.value.groupName === "perpetrators") {
                    setSelectedPerpetratorData(person);
                }

                // Setze allgemeine Person-Daten
                setSelectedPersonData(person);

                // Aktualisiere URL
                window.history.replaceState(
                    null,
                    null,
                    encodeURI(
                        `${document.location.pathname}?pid=${person.id}&group=${selectedPlace.value.groupName}`,
                    ),
                );
            } catch (error) {
                console.error("Error in showPerson:", error);
                toggleShowPlaceInfoSidebar(false);
            }
        };
        /**
         * Set the perpetrator data of the selected person to be displayed in the map info sidebar.
         *
         * @param {object} person
         */
        const setSelectedPerpetratorData = (person) => {
            try {
                selectedPlace.value.layerName = "Täter*innen";

                selectedPlace.value.employers = getPropertyData(
                    person.employers,
                    true,
                    true,
                    false,
                );
            } catch (error) {
                console.error("Error setting perpetrator data:", error);
            }
        };
        /**
         * Get item property data and optional include time periods, linked locations and linked persons.
         *
         * @param {object} itemProperty
         * @param {boolean} addTimePeriods
         * @param {boolean} addLinkedLocation
         * @param {boolean} addLinkedPerson
         * @param {array} excludedIds
         * @returns {*[]}
         */
        const getPropertyData = (
            itemProperty,
            addTimePeriods = false,
            addLinkedLocation = false,
            addLinkedPerson = false,
            excludedIds = [],
        ) => {
            let propertyDataArray = [];

            if (!itemProperty) {
                return propertyDataArray;
            }

            for (const [statementId, propertyData] of Object.entries(
                itemProperty,
            )) {
                if (excludedIds.includes(propertyData.id)) {
                    continue;
                }

                let data = {
                    id: propertyData.id,
                    name: propertyData.value,
                };

                if (addTimePeriods) {
                    data = {
                        ...data,
                        ...getPropertyDataTimePeriods(propertyData),
                    };
                }

                if (addLinkedLocation) {
                    data.hasLocationMarker = hasLocationMarker(propertyData.id);
                }

                if (addLinkedPerson) {
                    data.hasPersonData = hasPersonData(propertyData.id);
                }

                propertyDataArray.push(data);
            }

            propertyDataArray.sort(sortByDate);

            return propertyDataArray;
        };
        /**
         * Get time periods for item property data.
         *
         * @param {object} propertyData
         * @returns {{
         *              maxStartDate: (null|{locale: string, value: Date}|{locale: string, value: null}),
         *              endDate: (null|{locale: string, value: Date}|{locale: string, value: null}),
         *              minEndDate: (null|{locale: string, value: Date}|{locale: string, value: null}),
         *              startDate: (null|{locale: string, value: Date}|{locale: string, value: null}),
         *          }}
         */
        const getPropertyDataTimePeriods = (propertyData) => {
            let startDate = null;

            if (propertyData.startTime) {
                startDate = getDate(
                    propertyData.startTime.value,
                    propertyData.startTime.datePrecision,
                );
            } else if (propertyData.earliestDate) {
                startDate = getDate(
                    propertyData.earliestDate.value,
                    propertyData.earliestDate.datePrecision,
                );
            }

            let maxStartDate = propertyData.latestStartDate
                ? getDate(
                      propertyData.latestStartDate.value,
                      propertyData.latestStartDate.datePrecision,
                  )
                : null;

            let endDate = null;

            if (propertyData.endTime) {
                endDate = getDate(
                    propertyData.endTime.value,
                    propertyData.endTime.datePrecision,
                );
            } else if (propertyData.latestDate) {
                endDate = getDate(
                    propertyData.latestDate.value,
                    propertyData.latestDate.datePrecision,
                );
            }

            let minEndDate = propertyData.earliestEndDate
                ? getDate(
                      propertyData.earliestEndDate.value,
                      propertyData.earliestEndDate.datePrecision,
                  )
                : null;

            return {
                endDate: endDate,
                maxStartDate: maxStartDate,
                minEndDate: minEndDate,
                startDate: startDate,
            };
        };
        /**
         * Check if the location has a map marker.
         *
         * @param {string} locationId A Wikidata item Q-Id.
         * @returns {boolean}
         */
        const hasLocationMarker = (locationId) => {
            return locationMarkers[locationId] ? true : false;
        };
        /**
         * Check if person details are available.
         *
         * @param {string} personId A Wikidata item Q-Id.
         * @returns {boolean}
         */
        const hasPersonData = (personId) => {
            return persons.value.victims[personId] ||
                persons.value.perpetrators[personId]
                ? true
                : false;
        };
        /**
         * Set the victim data of the selected person to be displayed in the map info sidebar.
         *
         * @param {object} person
         */
        const setSelectedVictimData = (person) => {
            selectedPlace.value.layerName = "Geschädigte";

            selectedPlace.value.detentions = [];

            if (person.detentionPlaces) {
                for (const [statementId, detention] of Object.entries(
                    person.detentionPlaces,
                )) {
                    let hasLocationMarker = locationMarkers.value[detention.id]
                        ? true
                        : false;

                    let startDate = detention.startTime
                        ? getDate(
                              detention.startTime.value,
                              detention.startTime.datePrecision,
                          )
                        : null;

                    let endDate = detention.endTime
                        ? getDate(
                              detention.endTime.value,
                              detention.endTime.datePrecision,
                          )
                        : null;

                    selectedPlace.value.detentions.push({
                        endDate: endDate,
                        hasLocationMarker: hasLocationMarker,
                        id: detention.id,
                        name: detention.value,
                        startDate: startDate,
                    });
                }

                selectedPlace.value.detentions.sort(sortByDate);
            }
        };
        /**
         * Set the default data for the selected person to be displayed in the map info sidebar.
         *
         * @param {object} person
         */
        const setSelectedPersonData = (person) => {
            try {
                // Basis-Informationen
                selectedPlace.value.id = person.id;
                selectedPlace.value.label = person.label;
                selectedPlace.value.description = person.description;

                // Bild-Informationen
                processImageData(person);

                // Persönliche Informationen
                processPersonalInfo(person);

                // Orts- und Datumsinformationen
                processLocationAndDates(person);

                // Zusätzliche Informationen
                processAdditionalInfo(person);
            } catch (error) {
                console.error("Error in setSelectedPersonData:", error);
            }
        };

        const processImageData = (person) => {
            try {
                selectedPlace.value.mainImageUrl = "";
                selectedPlace.value.mainImageLegend = "";

                if (person.images) {
                    const [_, firstImage] =
                        Object.entries(person.images)[0] || [];
                    if (firstImage) {
                        selectedPlace.value.mainImageUrl = firstImage.value;
                        if (
                            selectedPlace.value.mainImageUrl &&
                            selectedPlace.value.mainImageUrl.startsWith("http:")
                        ) {
                            selectedPlace.value.mainImageUrl =
                                selectedPlace.value.mainImageUrl.replace(
                                    "http:",
                                    "https:",
                                );
                        }
                        selectedPlace.value.mainImageLegend =
                            firstImage.mediaLegend?.value || "";
                    }
                }
            } catch (error) {
                console.error("Error processing image data:", error);
            }
        };

        const processPersonalInfo = (person) => {
            try {
                selectedPlace.value.givenName = getFirstPropertyValue(
                    person.givenName,
                );
                selectedPlace.value.familyName = getFirstPropertyValue(
                    person.familyName,
                );
                selectedPlace.value.gender = getFirstPropertyValue(
                    person.gender,
                );
                selectedPlace.value.citizenships = getAllPropertyValues(
                    person.citizenships,
                );
            } catch (error) {
                console.error("Error processing personal info:", error);
            }
        };

        const processLocationAndDates = (person) => {
            try {
                // Geburt und Tod
                selectedPlace.value.placeOfBirth = getFirstPropertyValue(
                    person.placeOfBirth,
                );
                selectedPlace.value.placeOfDeath = getFirstPropertyValue(
                    person.placeOfDeath,
                );

                // Datums-Informationen
                selectedPlace.value.dateOfBirth = {
                    locale: "",
                    value: null,
                };
                selectedPlace.value.dateOfDeath = {
                    locale: "",
                    value: null,
                };

                if (person.dateOfBirth) {
                    const [_, firstDate] =
                        Object.entries(person.dateOfBirth)[0] || [];
                    if (firstDate) {
                        selectedPlace.value.dateOfBirth = getDate(
                            firstDate.value,
                            firstDate.datePrecision,
                        );
                    }
                }

                if (person.dateOfDeath) {
                    const [_, firstDate] =
                        Object.entries(person.dateOfDeath)[0] || [];
                    if (firstDate) {
                        selectedPlace.value.dateOfDeath = getDate(
                            firstDate.value,
                            firstDate.datePrecision,
                        );
                    }
                }
            } catch (error) {
                console.error("Error processing location and dates:", error);
            }
        };

        const processAdditionalInfo = (person) => {
            try {
                // Quellen
                selectedPlace.value.sources = [];
                if (person.describedBySources) {
                    for (const [_, source] of Object.entries(
                        person.describedBySources,
                    )) {
                        selectedPlace.value.sources.push({
                            label: source.value,
                            pages: source.pages?.value || "",
                            inventoryNumber:
                                source.inventoryNumber?.value || "",
                        });
                    }
                }

                selectedPlace.value.urlSources = [];
                if (person.describedByUrlSources) {
                    for (const [_, urlSource] of Object.entries(
                        person.describedByUrlSources,
                    )) {
                        selectedPlace.value.urlSources.push({
                            url: urlSource.value || "",
                        });
                    }
                }

                // Bedeutende Ereignisse
                selectedPlace.value.significantEvents = [];
                if (person.significantEvents) {
                    for (const [_, event] of Object.entries(
                        person.significantEvents,
                    )) {
                        const hasLocationMarker =
                            locationMarkers.value[event.id] || false;
                        selectedPlace.value.significantEvents.push({
                            hasLocationMarker,
                            id: event.id,
                            label: event.value,
                        });
                    }
                }
            } catch (error) {
                console.error("Error processing additional info:", error);
            }
        };

        // Hilfsfunktionen
        const getFirstPropertyValue = (property) => {
            if (!property) return "";
            const [_, firstItem] = Object.entries(property)[0] || [];
            return firstItem?.value || "";
        };

        const getAllPropertyValues = (property) => {
            if (!property) return [];
            return Object.values(property).map((item) => item.value);
        };

        /**
         * Show/Hide place info sidebar and close opened navigation sidebar.
         *
         * @param bool status Show or hide place info sidebar.
         */
        const toggleShowPlaceInfoSidebar = (status) => {
            showPlaceInfoSidebar.value = status;

            if (status) {
                openPlaceInfo();
                closeMapOptions();
                document.getElementById("closeNavigationSidebar")?.click();
            } else {
                closePlaceInfo();
            }
        };
        /**
         * Switch to cached map view.
         */
        const restoreCachedMapView = () => {
            if (map.value && cachedMapView.value) {
                setMapView(
                    cachedMapView.value.latLng,
                    cachedMapView.value.zoomLevel,
                );
            }
        };

        const debounce = (func, wait) => {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        };

        /**
         * Fly to new map view and optionally cache the current map view.
         *
         * @param object latLng
         * @param int zoomLevel
         * @param bool cacheCurrentMapView
         */
        const setMapView = (latLng, zoomLevel, cacheCurrentMapView = false) => {
            if (map.value && map.value._loaded) {
                if (cacheCurrentMapView) {
                    setCachedMapView();
                }
                const rawMap = toRaw(map.value);
                if (latLng) {
                    rawMap.setView(latLng, zoomLevel, {
                        animate: true,
                        duration: 0.5,
                        easeLinearity: 0.25,
                        noMoveStart: true,
                    });
                } else {
                    rawMap.setView(rawMap.getCenter(), zoomLevel, {
                        animate: false,
                        duration: 0.5,
                        easeLinearity: 0.25,
                        noMoveStart: true,
                    });
                }
            }
        };

        function patchLeafletForNullChecks() {
            if (!L.Popup.prototype._originalAnimateZoom) {
                // Speichere die Original-Funktion
                L.Popup.prototype._originalAnimateZoom =
                    L.Popup.prototype._animateZoom;

                // Überschreibe mit einer sichereren Version
                L.Popup.prototype._animateZoom = function (e) {
                    try {
                        if (!this._map || !this._latlng) {
                            this.close(); // Schließe das Popup, wenn grundlegende Daten fehlen
                            return;
                        }

                        // Sicherstellen, dass _latLngToNewLayerPoint existiert
                        if (
                            typeof this._map._latLngToNewLayerPoint !==
                            "function"
                        ) {
                            console.warn(
                                "Missing _latLngToNewLayerPoint method, closing popup",
                            );
                            this.close();
                            return;
                        }

                        // Original-Funktion mit try-catch aufrufen
                        this._originalAnimateZoom.call(this, e);
                    } catch (error) {
                        console.warn(
                            "Error during popup animation, closing popup",
                            error,
                        );
                        this.close();
                    }
                };
            }
        }

        const debouncedApplyFilters = debounce(applyFilters, 250);

        const handleTimelineUpdate = (minDate, maxDate) => {
            debouncedApplyFilters(minDate, maxDate, null);
        };

        const handleLayerGroupsUpdate = (newGroups) => {
            debouncedApplyFilters(null, null, newGroups);
        };

        /**
         * Cache current map view (latitude, longitude, zoom-level).
         */
        const setCachedMapView = () => {
            if (map.value) {
                const rawMap = toRaw(map.value);
                cachedMapView.value = {
                    latLng: rawMap.getCenter(),
                    zoomLevel: rawMap.getZoom(),
                };
            }
        };

        return {
            map,
            groupedPlaces,
            groupedPlacesShownOnMap,
            mapMarkerIconsPath,
            showPlaceInfoSidebar,
            selectedPlace,
            showPerson,
            closeMapOptions,
            switchLocation,
            toggleShowPlaceInfoSidebar,
            restoreCachedMapView,
            setMapView,
            handleLayerGroupsUpdate,
            handleTimelineUpdate,
            MapTimeline,
            setSelectedPlace,
            derivedPlacesData,
            getDerivedPlacesData,
            isTimelineFilterEnabled,
            handleTimelineFilterChange,
            isLoading,
        };
    },
};
</script>

<style scoped>
.map-loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(255, 255, 255, 0.85);
    z-index: 1000;
    display: flex;
    justify-content: center;
    align-items: center;
    transition: opacity 0.5s ease-out;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.loading-text {
    font-size: 1.2rem;
    font-weight: 500;
    color: #333;
    font-family: "Special Elite", serif;
}

/* Bestehende Styles */
#leafletMapId {
    height: 100%;
    position: absolute;
    width: 100%;
    left: 0;
    top: 0;
    z-index: 0;
}
</style>
<style>
/* top-right Leaflet control */
.leaflet-bottom.leaflet-right {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: auto;
    left: 0;
}
.leaflet-control {
    display: flex;
}
.leaflet-control a {
    border-radius: 2px !important;
    border-bottom: 1px solid #ccc !important;
}
.leaflet-control-zoom span {
    display: block;
    transform: translateY(-2px);
}
.leaflet-control-attribution {
    display: block;
}
.leaflet-control-layers {
    display: none !important;
}

/* greyscale map tiles */
.leaflet-tile-pane {
    filter: grayscale(1);
}
</style>

<style scoped>
@import "leaflet/dist/leaflet.css";

#leafletMapId {
    height: 100%;
    position: absolute;
    width: 100%;
    left: 0;
    top: 0;
    z-index: 0;
}
</style>
