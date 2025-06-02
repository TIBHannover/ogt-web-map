<template>
    <v-container id="container" class="mb-110">
        <!-- header -->
        <v-row :style="{ 'padding-left': computedPadding }">
            <v-col>
                <h1
                    class="font-family-special-elite font-weight-bold hyphens-auto py-4 text-h4 text-md-h3"
                    lang="de"
                >
                    Datenvisualisierung
                </h1>
            </v-col>
        </v-row>

        <!-- charts overview -->
        <section v-if="showOverview">
            <v-row>
                <v-col class="hyphens-auto" cols="12" lang="de">
                    <!--
                <h2 class="mb-3 text-h6 text-md-h5">
                        Visualisierung von Daten zu Organisationen, Ereignissen,
                        Erinnerungsorten, Tätern und Geschädigten
                    </h2>
                    <p>
                        Die Daten auf dieser Seite werden direkt auf Wikidata
                        abgefragt und so ständig aktualisiert. Je mehr Daten
                        vorhanden sind, desto vollständiger werden die
                        Übersichten. Dieses Ziel kann nur durch die Hilfe vieler
                        erreicht werden. Informationen, wie mitgeforscht werden
                        kann, finde sich hier:
                        <span class="text-no-wrap">
                            &#10132;
                            <router-link to="/collaboration"
                                >Mitforschen</router-link
                            >
                        </span>
                    </p>
                    -->
                </v-col>
            </v-row>

            <!-- charts -->
            <v-row>
                <v-col cols="12">
                    <section id="results">
                        <h2
                            class="font-weight-medium text-h5 text-md-h4 mb-5"
                            :class="{
                                'font-size-29': $vuetify.display.mdAndUp,
                            }"
                        >
                            Ergebnisse der "Werkstatt historische Daten"
                        </h2>

                        <p style="max-width: 800px">
                            Die folgende Datenvisualisierung macht die
                            Fortschritte der partizipativen Arbeit in der
                            Werkstatt historische Daten nachvollziehbar und
                            zeigt Entwicklungen im Projektverlauf auf.
                        </p>
                        <v-table width="500px" id="table-counts">
                            <thead>
                                <tr>
                                    <th width="300">Ort oder Person</th>
                                    <th width="100" class="text-center">
                                        Aktuell:
                                    </th>
                                    <th width="100" class="text-center">
                                        <span
                                            class="w-full justify-center d-flex ga-2"
                                        >
                                            <span> 28.05.2025 </span>
                                            <v-icon
                                                color="blue"
                                                width="25px"
                                                v-tooltip="{
                                                    text: 'Nach der vierten Werkstatt // Teilnehmer: 5',
                                                    html: true,
                                                }"
                                            >
                                                mdi-information-outline
                                            </v-icon>
                                        </span>
                                    </th>
                                    <th width="100" class="text-center">
                                        <span
                                            class="w-full justify-center d-flex ga-2"
                                        >
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
                                        <span
                                            class="w-full justify-center d-flex ga-2"
                                        >
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
                                        <span
                                            class="w-full justify-center d-flex ga-2"
                                        >
                                            <span> 27.03.2025 </span>
                                            <v-icon
                                                color="blue"
                                                width="25px"
                                                v-tooltip:top="
                                                    'Nach der zweiten Werkstatt'
                                                "
                                            >
                                                mdi-information-outline
                                            </v-icon>
                                        </span>
                                    </th>
                                    <th width="100" class="text-center">
                                        <span
                                            class="w-full justify-center d-flex ga-2"
                                        >
                                            <span> 28.02.2025 </span>
                                            <v-icon
                                                color="blue"
                                                width="25px"
                                                v-tooltip:top="
                                                    'Nach der ersten Werkstatt'
                                                "
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
                                            <v-row justify="center">
                                                <v-col cols="3">
                                                    <span>
                                                        <v-img
                                                            :width="34"
                                                            :src="`${mapMarkerIconsPath}${groupName.iconName}`"
                                                        ></v-img>
                                                    </span>
                                                </v-col>
                                                <v-col
                                                    cols="9"
                                                    class="align-center align-content-center"
                                                >
                                                    <span>
                                                        {{
                                                            groupName.layerName
                                                        }}
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
                    </section>
                </v-col>
                <!--
                <v-col
                    cols="12"
                    md="6"
                    v-for="(chart, chartName) in this.charts"
                    :key="chartName"
                >
                    <v-card>
                        <v-img
                            :src="`${ogtGlobals.proxyPath}` + chart.imageUrl"
                        ></v-img>
                        <v-card-title>
                            {{ chart.title }}
                        </v-card-title>
                        <v-card-subtitle class="hyphens-auto" lang="de">
                            {{ chart.subtitle }}
                        </v-card-subtitle>
                        <v-card-actions>
                            <v-btn
                                @click.stop="showChart(chart)"
                                variant="outlined"
                                rounded
                            >
                                Anzeigen
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-col>
                -->
            </v-row>
        </section>

        <!-- chart visualization -->
        <section v-if="!showOverview">
            <v-row justify="space-between">
                <v-col cols="12" md="6">
                    <v-btn
                        @click.stop="showOverview = !showOverview"
                        variant="outlined"
                        rounded
                    >
                        <v-icon start> mdi-arrow-left </v-icon>
                        Zurück zur Übersicht
                    </v-btn>
                </v-col>
                <v-col class="maxWidth100" cols="12" md="6">
                    <v-btn
                        class="float-sm-right"
                        :href="selectedChart.queryUrl"
                        variant="outlined"
                        rounded
                        target="_blank"
                    >
                        Im Wikidata Query Service anzeigen
                        <v-icon end> mdi-open-in-new </v-icon>
                    </v-btn>
                </v-col>
            </v-row>

            <h1 class="text-h5 text-md-h4 my-5">
                {{ selectedChart.title }}
            </h1>
            <p class="hyphens-auto" lang="de">
                {{ selectedChart.subtitle }}
            </p>

            <iframe
                referrerpolicy="origin"
                sandbox="allow-scripts allow-same-origin allow-popups"
                :src="selectedChart.queryUrl"
            ></iframe>
        </section>
    </v-container>
</template>

<script setup>
import { ref, inject, onMounted, onUnmounted, computed, nextTick } from "vue";
import { useDisplay } from "vuetify";
import chartsData from "../data/chartsData";
import { groupedPlaces } from "../data/counts";

// Injected values
const ogtGlobals = inject("ogtGlobals");
const isMenuDisplayed = inject("isMenuDisplayed");

// Reactive references
const freeClientWidth = ref(document.documentElement.clientWidth);
const charts = ref(chartsData);
const selectedChart = ref(null);
const showOverview = ref(true);
const persons = ref({
    perpetrators: {},
    victims: {},
});
const mapMarkerIconsPath = ref(
    "/images/leaflet/markerIcons/coloredFilledWhite/",
);

// Computed
const computedPadding = computed(() => {
    return freeClientWidth.value < 75 && !isMenuDisplayed.value
        ? `${75 - freeClientWidth.value}px`
        : "0";
});

// Methods
const setFreeClientWidth = () => {
    const container = document.getElementById("container");
    if (container) {
        freeClientWidth.value =
            (document.documentElement.clientWidth - container.clientWidth) / 2;
    }
};

const showChart = (chart) => {
    selectedChart.value = chart;
    showOverview.value = false;
};

const getGroupedPlaces = async () => {
    try {
        let groupedPlaces = {};
        const response = await axios.get("/api/wikidata/places");
        groupedPlaces = response.data;

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
            const LayerCountSpan = document.querySelector(
                '[data-groupuid="' + groupName + '"]',
            );
            const groupLength = Object.keys(groupData).length;
            if (LayerCountSpan) {
                LayerCountSpan.innerHTML = groupLength;
            }
        }
    });
};

const getPersons = async () => {
    try {
        const response = await axios.get("/api/wikidata/persons/cache");
        persons.value = response.data;
        await nextTick();
        await new Promise((resolve) => setTimeout(resolve, 100));
        updatePersonInfoData();
    } catch (error) {
        console.error("Error fetching persons:", error);
    }
};

const updatePersonInfoData = () => {
    const perpetratorsCount = Object.keys(persons.value.perpetrators).length;
    const victimsCount = Object.keys(persons.value.victims).length - 1;

    const perpetratorsSpan = document.querySelector(
        '[data-groupuid="perpetrators"]',
    );
    const victimsSpan = document.querySelector('[data-groupuid="victims"]');

    if (perpetratorsSpan) perpetratorsSpan.innerHTML = perpetratorsCount;
    if (victimsSpan) victimsSpan.innerHTML = victimsCount;
};

// Lifecycle hooks
onMounted(async () => {
    try {
        await nextTick();
        const CountTable = document.getElementById("table-counts");
        if (CountTable) {
            await nextTick();
            await getGroupedPlaces();
            await getPersons();
        }
        setFreeClientWidth();
        window.addEventListener("resize", setFreeClientWidth);
    } catch (error) {
        console.error("Error during component mount:", error);
    }
});

onUnmounted(() => {
    window.removeEventListener("resize", setFreeClientWidth);
});

// Expose components methods/properties if needed
defineExpose({
    showChart,
    showOverview,
    selectedChart,
});
</script>

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
</style>
