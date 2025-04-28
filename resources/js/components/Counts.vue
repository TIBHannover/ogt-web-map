<template>
    <v-container id="container" class="mb-110">
        <!-- header -->
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
                    Aktuelle Zahlen
                </h1>
            </v-col>
        </v-row>

        <!-- charts overview -->

        <v-row>
            <v-col class="hyphens-auto" cols="12" lang="de">
                <h2 class="mb-3 text-h6 text-md-h5">
                    Visualisierung der Anzahl der angezeigten Datensätze auf der
                    Karte
                </h2>
                <p>
                    Die Daten für den aktuellen Stand von den einzelnen
                    Kategorien auf dieser Seite werden direkt auf Wikidata
                    abgefragt und so ständig aktualisiert. Je mehr Daten
                    vorhanden sind, desto höher fällt die Zahl unter "Aktuell"
                    aus. Diese Zahl kann nur durch die Hilfe vieler erhöht
                    werden. Informationen, wie mitgeforscht werden kann, finde
                    sich hier:
                    <span class="text-no-wrap">
                        &#10132;
                        <router-link to="/collaboration"
                            >Mitforschen</router-link
                        >
                    </span>
                </p>
            </v-col>
        </v-row>

        <!-- charts -->
        <v-row>
            <v-table id="table-counts">
                <thead>
                    <tr class="">
                        <th>Ort oder Person</th>
                        <th>Aktuell:</th>

                        <th width="100" class="text-center">
                            <span class="w-full justify-center d-flex ga-2">
                                <span> 24.04.2025 </span>
                                <v-icon
                                    color="blue"
                                    width="25px"
                                    v-tooltip="{
                                        text: 'Nach der dritten Werkstatt // Teilnehmer: 5',
                                        html: true,
                                    }"
                                >
                                    mdi-information-outline
                                </v-icon>
                            </span>
                        </th>
                        <th width="100" class="text-center">
                            <span class="w-full justify-center d-flex ga-2">
                                <span> 04.04.2025 </span>
                                <v-icon
                                    color="blue"
                                    width="25px"
                                    v-tooltip="{
                                        text: 'Nach der Nacht der Bibliotheken // Teilnehmer: 7',
                                        html: true,
                                    }"
                                >
                                    mdi-information-outline
                                </v-icon>
                            </span>
                        </th>
                        <th width="100" class="text-center">
                            <span class="w-full justify-center d-flex ga-2">
                                <span> 27.03.2025 </span>
                                <v-icon
                                    color="blue"
                                    width="25px"
                                    v-tooltip:top="'Nach der zweiten Werkstatt'"
                                >
                                    mdi-information-outline
                                </v-icon>
                            </span>
                        </th>
                        <th width="100" class="text-center">
                            <span class="w-full justify-center d-flex ga-2">
                                <span> 28.02.2025 </span>
                                <v-icon
                                    color="blue"
                                    width="25px"
                                    v-tooltip:top="'Nach der ersten Werkstatt'"
                                >
                                    mdi-information-outline
                                </v-icon>
                            </span>
                        </th>

                        <th width="100" class="text-center">
                            <span>24.02.2025</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="groupName in groupedPlaces"
                        :key="groupName + '-row'"
                        density="compact"
                        hide-details
                    >
                        <td>
                            <v-grid>
                                <v-row>
                                    <v-col cols="3">
                                        <span>
                                            <v-img
                                                :width="34"
                                                :src="`${mapMarkerIconsPath}${groupName.iconName}`"
                                            ></v-img>
                                        </span>
                                    </v-col>
                                    <v-col cols="9" class="align-center">
                                        <span>
                                            {{ groupName.layerName }}
                                        </span>
                                    </v-col>
                                </v-row>
                            </v-grid>
                        </td>
                        <td
                            class="text-center"
                            data-count="current"
                            :data-groupName="groupName.layerKey"
                        >
                            <span
                                :data-groupUid="groupName.layerKey"
                                class="font-weight-bold"
                            >
                                0
                            </span>
                        </td>
                        <td
                            v-for="[date, value] in Object.entries(
                                groupName.countHistory,
                            )"
                            :key="date"
                            class="text-center"
                            :data-count="value"
                            :data-groupName="groupName.layerKey"
                        >
                            {{ value }}
                        </td>
                    </tr>
                </tbody>
            </v-table>
        </v-row>
        <v-row class="mt-8">
            <v-col cols="12">
                <h2 class="text-h6 text-md-h5 mb-4">Neueste Einträge</h2>
            </v-col>
        </v-row>

        <!-- Tabs für die verschiedenen Kategorien -->
        <v-row>
            <v-col cols="12">
                <v-tabs v-model="activeTab" bg-color="transparent" grow>
                    <v-tab value="places">Orte</v-tab>
                    <v-tab value="victims">Geschädigte</v-tab>
                    <v-tab value="perpetrators">Täter*innen</v-tab>
                </v-tabs>

                <v-window v-model="activeTab" class="mt-4">
                    <!-- Orte Tab -->
                    <v-window-item value="places">
                        <v-table>
                            <thead>
                                <tr>
                                    <th>Kategorie</th>
                                    <th>Name</th>
                                    <th>ID</th>
                                    <th>Beschreibung</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in latestPlaces" :key="item.id">
                                    <td>
                                        <v-img
                                            :width="24"
                                            :src="`${mapMarkerIconsPath}${getCategoryIcon(item.category)}`"
                                            class="mr-2"
                                        ></v-img>
                                        {{ getCategoryName(item.category) }}
                                    </td>
                                    <td>{{ item.label }}</td>
                                    <td>
                                        <a
                                            :href="`https://www.wikidata.org/wiki/${item.id}`"
                                            target="_blank"
                                        >
                                            {{ item.id }}
                                        </a>
                                    </td>
                                    <td>
                                        {{
                                            item.description ||
                                            "Keine Beschreibung"
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </v-table>
                    </v-window-item>

                    <!-- Geschädigte Tab -->
                    <v-window-item value="victims">
                        <v-table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>ID</th>
                                    <th>Beschreibung</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in latestVictims"
                                    :key="item.id"
                                >
                                    <td>{{ item.label }}</td>
                                    <td>
                                        <a
                                            :href="`https://www.wikidata.org/wiki/${item.id}`"
                                            target="_blank"
                                        >
                                            {{ item.id }}
                                        </a>
                                    </td>
                                    <td>
                                        {{
                                            item.description ||
                                            "Keine Beschreibung"
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </v-table>
                    </v-window-item>

                    <!-- Täter*innen Tab -->
                    <v-window-item value="perpetrators">
                        <v-table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>ID</th>
                                    <th>Beschreibung</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in latestPerpetrators"
                                    :key="item.id"
                                >
                                    <td>{{ item.label }}</td>
                                    <td>
                                        <a
                                            :href="`https://www.wikidata.org/wiki/${item.id}`"
                                            target="_blank"
                                        >
                                            {{ item.id }}
                                        </a>
                                    </td>
                                    <td>
                                        {{
                                            item.description ||
                                            "Keine Beschreibung"
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </v-table>
                    </v-window-item>
                </v-window>
            </v-col>
        </v-row>
    </v-container>
    <div id="leafletMapId" style="display: none"></div>
</template>

<script>
import { ref, onMounted, onUnmounted, nextTick, inject, toRaw } from "vue";
import { useRouter, useRoute } from "vue-router";
import axios from "axios";
import Leaflet from "leaflet/dist/leaflet";
import "leaflet/dist/leaflet.css";
import L from "leaflet/dist/leaflet";
import { groupedPlaces } from "../data/counts";
import MapOptionsSidebar from "./map/MapOptionsSidebar";
import PlaceInfoSidebar from "./map/PlaceInfoSidebar";
import leafletConfig from "../config/leafletConfig";
const showPlaceInfoSidebar = ref(false);
const sourceCircumstances = ref({
    Q5727902: "ca.",
    Q47035128: ">",
    Q52834024: "<",
});

// Constants
const WIKIDATA_ID_CLASSES = {
    deportation: ["Q379693", "Q5883983", "Q61927259"],
};

export default {
    name: "Map",
    components: {
        MapOptionsSidebar,
        PlaceInfoSidebar,
    },
    setup() {
        const router = useRouter();
        const route = useRoute();
        const configLeaflet = leafletConfig;
        const ogtGlobals = inject("ogtGlobals");
        const map = ref(null);
        const rawGroupedPlaces = ref({});
        const markers = ref([]);
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
        const derivedPlacesData = ref({});
        const getDerivedPlacesData = () => derivedPlacesData.value;
        const layers = ref(null);
        const locationMarkers = ref([]);
        const markersDate = ref(new Map());
        const mapMarkerIconsPath = ref(
            "/images/leaflet/markerIcons/coloredFilledWhite/",
        );
        const activeTab = ref("places");
        const latestPlaces = ref([]);
        const latestVictims = ref([]);
        const latestPerpetrators = ref([]);
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

        onMounted(async () => {
            try {
                // Warten auf DOM-Update
                await nextTick();

                const tablecount = document.getElementById("table-counts");
                if (tablecount) {
                    try {
                        await nextTick();
                        // Daten laden
                        await getGroupedPlaces();
                        await getPersons();
                    } catch (error) {
                        console.error("Error updating map:", error);
                    }
                }
            } catch (error) {
                console.error("Error during component mount:", error);
            }
        });

        /**
         * Request grouped places of Gestapo terror.
         */
        const getGroupedPlaces = async () => {
            try {
                let groupedPlaces = {};

                await axios
                    .get("/api/wikidata/places")
                    .then((response) => {
                        groupedPlaces = response.data;
                    })
                    .catch((error) => {
                        console.log(error);
                    });

                await nextTick();
                await new Promise((resolve) => setTimeout(resolve, 100));

                updatePlacesInfoData(groupedPlaces);
            } catch (error) {
                console.error("Error fetching grouped places:", error);
            }
        };

        const updatePlacesInfoData = (groupedPlaces) => {
            // Array für alle Orte erstellen
            let allPlaces = [];

            Object.entries(groupedPlaces).forEach(([groupName, groupData]) => {
                if (groupData) {
                    // Updating Count number for current state
                    const LayerCountSpan = document.querySelector(
                        '[data-groupuid="' + groupName + '"]',
                    );
                    // Stelle sicher, dass wir 'places' berücksichtigen falls vorhanden
                    const places = groupData.places || groupData;
                    const groupLength = Object.keys(places).length;
                    if (LayerCountSpan) {
                        LayerCountSpan.innerHTML = groupLength;
                    }

                    // Behalte die ursprünglichen console.log bei
                    console.log("groupData " + groupName, groupData);

                    // Konvertiere alle Orte in ein Array mit Kategorie-Info
                    const placesArray = Object.entries(places)
                        .filter(([id]) => id !== "layerGroup") // Filtere layerGroup-Eigenschaft aus
                        .map(([id, data]) => ({
                            id,
                            label: data.label || "Unbenannt",
                            description: data.description || "",
                            category: groupName,
                            // Speichere die ursprünglichen Daten für die Anzeige
                            originalData: data,
                            // Speichere die Q-Nummer als Zahl für die Sortierung
                            qNumber: parseInt(id.replace("Q", ""), 10),
                        }));

                    // Zu allen Orten hinzufügen
                    allPlaces = [...allPlaces, ...placesArray];

                    // Hole Tabellenzellen für diese Kategorie
                    let groupTableDatas = document.querySelectorAll(
                        '[data-groupname="' + groupName + '"]',
                    );

                    // Behalte die ursprüngliche Logik bei (kommentiert)
                    /*
                    if (groupTableDatas) {
                        let index = 0;
                        let currentNumber = 0;
                        let lastNumber = 0;
                        console.log("groupTableDatas", groupTableDatas);
                        groupTableDatas.forEach((groupTableData) => {
                            if (index == 0) {
                                currentNumber = groupLength;
                                index++;
                                return;
                            }
                            lastNumber = currentNumber;
                            currentNumber = groupTableData.dataset.count;
                            let diff = lastNumber - currentNumber;

                            console.log("lastNumber", lastNumber);
                            console.log("currentNumber", currentNumber);
                            console.log("diff", diff);
                            if (diff > 0) {
                                let groupDataArray = Object.entries(groupData);
                                groupDataArray.slice(
                                    Math.max(groupDataArray.length - diff, 0),
                                );
                                console.log(
                                    "groupDataArray stripped down",
                                    groupDataArray,
                                );
                            }

                            index++;
                        });
                    }*/
                }
            });

            // Nach Q-Nummer absteigend sortieren (höchste/neueste zuerst)
            allPlaces.sort((a, b) => b.qNumber - a.qNumber);

            // Log der sortierten Daten (ähnlich wie deine ursprünglichen Logs)
            console.log(
                "Alle sortierten Orte nach Q-Nummer (neueste zuerst):",
                allPlaces,
            );

            // Log der neuesten 10 Einträge für jede Kategorie
            const categories = [
                ...new Set(allPlaces.map((place) => place.category)),
            ];
            categories.forEach((category) => {
                const placesInCategory = allPlaces.filter(
                    (place) => place.category === category,
                );
                console.log(
                    `Neueste 10 Einträge für Kategorie ${category}:`,
                    placesInCategory.slice(0, 10),
                );
            });

            // Die neuesten 20 Einträge insgesamt für die Tabelle
            latestPlaces.value = allPlaces.slice(0, 20);
        };

        /**
         * Request persons data from cached file and Wikidata for perpetrators and victims.
         */
        const getPersons = async () => {
            try {
                const response = await axios.get("/api/wikidata/persons");
                persons.value = response.data;
                await nextTick();
                await new Promise((resolve) => setTimeout(resolve, 100));
                updatePersonInfoData();
            } catch (error) {
                console.error("Error fetching persons:", error);
            }
        };
        /*
        const updatePlacesInfoData = (groupedPlaces) => {
            Object.entries(groupedPlaces).forEach(([groupName, groupData]) => {
                if (groupData) {
                    // Updating Count number for current state
                    const LayerCountSpan = document.querySelector(
                        '[data-groupuid="' + groupName + '"]',
                    );
                    const groupLength = Object.keys(
                        groupData.places || {},
                    ).length;
                    if (LayerCountSpan) {
                        LayerCountSpan.innerHTML = groupLength;
                    }

                    // Die letzten 5 Einträge für jede Kategorie sammeln
                    if (groupData.places) {
                        const entries = Object.entries(groupData.places)
                            .map(([id, data]) => ({
                                id,
                                label: data.label || "Unbenannt",
                                description: data.description || "",
                                category: groupName,
                            }))
                            .slice(-5); // Die letzten 5 Einträge

                        // Zu den gesammelten Orten hinzufügen
                        latestPlaces.value = [
                            ...latestPlaces.value,
                            ...entries,
                        ];
                    }
                }
            });

            // Sortiere und beschränke auf die letzten 20 Einträge
            latestPlaces.value = latestPlaces.value.slice(-20);
        };
*/
        // Diese Funktion aktualisieren
        const updatePersonInfoData = () => {
            const perpetratorsCount = Object.keys(
                persons.value.perpetrators,
            ).length;
            const victimsCount = Object.keys(persons.value.victims).length - 1;

            // DOM-Elemente aktualisieren
            const perpetratorsSpan = document.querySelector(
                '[data-groupuid="perpetrators"]',
            );
            const victimsSpan = document.querySelector(
                '[data-groupuid="victims"]',
            );

            if (perpetratorsSpan) {
                perpetratorsSpan.innerHTML = perpetratorsCount;
            }

            if (victimsSpan) {
                victimsSpan.innerHTML = victimsCount;
            }

            // Opfer in sortiertes Array umwandeln
            const victimsArray = Object.entries(persons.value.victims).map(
                ([id, data]) => ({
                    id,
                    label: data.label || "Unbenannt",
                    description: data.description || "",
                    qNumber: parseInt(id.replace("Q", ""), 10),
                }),
            );

            // Täter in sortiertes Array umwandeln
            const perpetratorsArray = Object.entries(
                persons.value.perpetrators,
            ).map(([id, data]) => ({
                id,
                label: data.label || "Unbenannt",
                description: data.description || "",
                qNumber: parseInt(id.replace("Q", ""), 10),
            }));

            // Nach Q-Nummer absteigend sortieren (höchste/neueste zuerst)
            victimsArray.sort((a, b) => b.qNumber - a.qNumber);
            perpetratorsArray.sort((a, b) => b.qNumber - a.qNumber);

            // Die neuesten 20 Einträge nehmen
            latestVictims.value = victimsArray.slice(0, 20);
            latestPerpetrators.value = perpetratorsArray.slice(0, 20);
        };

        const sortByQNumber = (a, b) => {
            // Q-Nummern extrahieren (z.B. "Q12345" -> 12345)
            const aNum = parseInt(a.id.replace("Q", ""), 10);
            const bNum = parseInt(b.id.replace("Q", ""), 10);

            // Absteigend sortieren (neueste zuerst = höchste Q-Nummern)
            return bNum - aNum;
        };

        // Hilfsfunktion zum Abrufen des richtigen Icons für die Kategorie
        const getCategoryIcon = (category) => {
            const categoryMap = {
                events: "events.svg",
                extPolicePrisons: "extPolicePrisons.svg",
                fieldOffices: "fieldOffices.svg",
                laborEducationCamps: "laborEducationCamps.svg",
                memorials: "memorials.svg",
                prisons: "prisons.svg",
                statePoliceHeadquarters: "statePoliceHeadquarters.svg",
                statePoliceOffices: "statePoliceOffices.svg",
            };

            return categoryMap[category] || "default.svg";
        };

        // Hilfsfunktion zum Abrufen des Anzeigenamens für die Kategorie
        const getCategoryName = (category) => {
            const categoryMap = {
                events: "Ereignisse",
                extPolicePrisons: "Erweiterte Polizeigefängnisse",
                fieldOffices: "Außendienststellen",
                laborEducationCamps: "Arbeitserziehungslager",
                memorials: "Erinnerungsorte",
                prisons: "Gefängnisse",
                statePoliceHeadquarters: "Staatspolizeileitstellen",
                statePoliceOffices: "Staatspolizeistellen",
            };

            return categoryMap[category] || category;
        };

        return {
            map,
            groupedPlaces,
            mapMarkerIconsPath,
            showPlaceInfoSidebar,
            selectedPlace,
            derivedPlacesData,
            getDerivedPlacesData,
            activeTab,
            latestPlaces,
            latestVictims,
            latestPerpetrators,
            getCategoryIcon,
            getCategoryName,
        };
    },
};
</script>

<style scoped>
.wrapper {
    height: 100%;
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
.font-family-special-elite {
    font-family: "Special Elite" !important;
}

.hyphens-auto {
    hyphens: auto;
}

iframe {
    border: none;
    height: 80vh;
    width: 100%;
}

/* workaround to avoid the button not being fully visible on small devices */
.maxWidth100 {
    max-width: 100%;
}

/* space for footer to avoid that footer covers content */
.mb-110 {
    margin-bottom: 110px;
}
.align-center {
    align-content: center !important;
}
</style>
