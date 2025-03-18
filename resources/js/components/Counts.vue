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

                        <th>
                            28.02.2025
                            <v-icon
                                color="blue"
                                width="25px"
                                v-tooltip:top="'Nach dem zweiten Workshop'"
                            >
                                mdi-information-outline
                            </v-icon>
                        </th>

                        <th>
                            26.02.2025
                            <v-icon
                                color="blue"
                                width="25px"
                                v-tooltip:top="'Nach dem ersten Workshop'"
                            >
                                mdi-information-outline
                            </v-icon>
                        </th>
                        <th>24.02.2025</th>
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
            Object.entries(groupedPlaces).forEach(([groupName, groupData]) => {
                if (groupData) {
                    // Updating Count number for current state
                    const LayerCountSpan = document.querySelector(
                        '[data-groupuid="' + groupName + '"]',
                    );
                    const groupLength = Object.keys(groupData).length - 1;
                    if (LayerCountSpan) {
                        LayerCountSpan.innerHTML = groupLength;
                    }

                    // Adding latest added items
                    console.log("groupData " + groupName, groupData);

                    let groupTableDatas = document.querySelectorAll(
                        '[data-groupname="' + groupName + '"]',
                    );
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

        const updatePersonInfoData = () => {
            const perpetratorsCount = Object.keys(
                persons.value.perpetrators,
            ).length;
            const victimsCount = Object.keys(persons.value.victims).length - 1;

            const victimsEntries = Object.entries(persons.value.victims);
            console.log(victimsEntries);
            console.log(
                "Last 10 victimsEntries",
                victimsEntries.slice(Math.max(victimsEntries.length - 10, 0)),
            );

            const perpetratorsEntries = Object.entries(
                persons.value.perpetrators,
            );
            console.log(perpetratorsEntries);
            console.log(
                "Last 10 perpetratorsEntries",
                perpetratorsEntries.slice(
                    Math.max(perpetratorsEntries.length - 10, 0),
                ),
            );

            // Update the DOM elements with the counts
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
        };

        return {
            map,
            groupedPlaces,
            mapMarkerIconsPath,
            showPlaceInfoSidebar,
            selectedPlace,
            derivedPlacesData,
            getDerivedPlacesData,
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
