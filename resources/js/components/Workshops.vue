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
                    Werkstätte für historische Daten
                </h1>
            </v-col>
        </v-row>

        <v-row>
            <v-col
                class="col-12 col-sm-2 order-sm-last"
                style="min-width: 310px"
            >
                <table-of-contents-sidebar
                    :headings="headings"
                ></table-of-contents-sidebar>
            </v-col>

            <!-- scrollable content -->
            <v-col class="hyphens-auto" lang="de">
                <section id="citizenScience">
                    <h1
                        class="font-weight-medium text-h5 text-md-h4 mb-5"
                        :class="{ 'font-size-29': $vuetify.display.mdAndUp }"
                    >
                        "Gestapo.Terror.Orte" - Ein Citizen Science Projekt
                    </h1>
                    <p>
                        Wo befanden sich Orte des Gestapoterrors in
                        Niedersachsen? Welche Gebäude nutzte die Politische
                        Polizei während des Nationalsozialismus? Wo waren die
                        Dienstsitze, wo die Haftstätten der Geheimen
                        Staatspolizei? Wie sehen die Orte des Gestapoterrors
                        heute aus? Wie wurden die Orte nach 1945 genutzt? Welche
                        Berichte können uns den Terror der Gestapo
                        nachvollziehen lassen? Wer waren die Täter*innen? Was
                        haben die Täter*innen nach 1945 gemacht? Wurden ihre
                        Taten geahndet? Wer wurde von der Gestapo verfolgt? Wie
                        erinnern wir heute an die Verbrechen der Gestapo?
                    </p>
                    <p>
                        Nur durch die Mithilfe vieler können Daten zu diesen
                        Forschungsfrage erhoben werden. „Gestapo.Terror.Orte“
                        ist aus diesem Grund ein Citizen Science Projekt. Alle
                        Interessierten können sich am Forschungs- und
                        Datenerhebungsprozess beteiligen und so dazu beitragen,
                        dass das Wissen über die Geschichte der Gestapo in
                        Niedersachsen anwächst und digital zugänglich wird.
                        Zudem kann so die Erinnerung an die Menschen, die unter
                        dem Terror leiden mussten, erhalten bleiben.
                    </p>
                </section>

                <v-divider class="my-6"></v-divider>

                <section id="nextWorkshops">
                    <h2
                        class="font-weight-medium text-h5 text-md-h4 mb-5"
                        :class="{ 'font-size-29': $vuetify.display.mdAndUp }"
                    >
                        Die nächsten Werkstätte
                    </h2>

                    <p
                        v-for="workshop in upcomingWorkshops"
                        :key="workshop.date"
                    >
                        <span v-if="workshop.date">
                            <strong>
                                <a
                                    :href="workshop.link"
                                    target="_blank"
                                    v-if="workshop.link"
                                >
                                    {{ workshop.label }}</a
                                ></strong
                            >
                            <br />
                            <span>
                                Am {{ formatDate(workshop.date) }} Uhr
                            </span>
                            <br />
                            <span v-if="workshop.place === 'digital'">
                                Findet digital statt
                            </span>
                            <span v-else> Ort: {{ workshop.place }} </span>
                        </span>
                    </p>
                </section>
            </v-col>
        </v-row>
        <v-row>
            <v-col>
                <section id="results">
                    <h2
                        class="font-weight-medium text-h5 text-md-h4 mb-5"
                        :class="{ 'font-size-29': $vuetify.display.mdAndUp }"
                    >
                        Visualisierung der Anzahl der angezeigten Datensätze auf
                        der Karte
                    </h2>

                    <p style="max-width: 800px">
                        Die Daten für den aktuellen Stand von den einzelnen
                        Kategorien auf dieser Seite werden direkt auf Wikidata
                        abgefragt und so ständig aktualisiert. Je mehr Daten
                        vorhanden sind, desto höher fällt die Zahl unter
                        "Aktuell" aus. Diese Zahl kann nur durch die Hilfe
                        vieler erhöht werden. Informationen, wie mitgeforscht
                        werden kann, finde sich hier:
                        <span class="text-no-wrap">
                            &#10132;
                            <router-link to="/collaboration"
                                >Mitforschen</router-link
                            >
                        </span>
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
                                        <span> 28.02.2025 </span>
                                        <v-icon
                                            color="blue"
                                            width="25px"
                                            v-tooltip:top="
                                                'Nach dem zweiten Workshop'
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
                                        <span>26.02.2025</span>
                                        <v-icon
                                            color="blue"
                                            width="25px"
                                            v-tooltip:top="
                                                'Nach dem ersten Workshop'
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
                </section>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup>
import { useDisplay } from "vuetify";
import {
    ref,
    inject,
    defineProps,
    onMounted,
    onUnmounted,
    computed,
    defineExpose,
    nextTick,
} from "vue";
import Email from "./helpers/Email";
import axios from "axios";
import TableOfContentsSidebar from "./navigation/TableOfContentsSidebar";
import { workshops } from "../data/workshops";
import { groupedPlaces } from "../data/counts";

const { mdAndUp } = useDisplay();
const ogtGlobals = inject("ogtGlobals");

defineProps({
    isMenuDisplayed: {
        type: Boolean,
        default: false,
    },
});

const upcomingWorkshops = computed(() => {
    const now = new Date();
    return workshops
        .filter((workshop) => new Date(workshop.date) > now)
        .sort((a, b) => new Date(a.date) - new Date(b.date));
});

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat("de-DE", {
        day: "2-digit",
        month: "long",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
};

const freeClientWidth = ref(0);

const setFreeClientWidth = (event) => {
    const container = document.getElementById("container");
    freeClientWidth.value =
        (document.documentElement.clientWidth - container.clientWidth) / 2;
};

// Lifecycle hooks
onMounted(() => {
    setFreeClientWidth();
    window.addEventListener("resize", setFreeClientWidth);
});

onUnmounted(() => {
    window.removeEventListener("resize", setFreeClientWidth);
});

const headings = ref([
    {
        href: "#citizenScience",
        label: '"Gestapo.Terror.Orte" - Ein Citizen Science Projekt',
    },
    {
        href: "#nextWorkshops",
        label: "Die nächsten Werkstätte",
    },
    {
        href: "#results",
        label: "Ergebnisse",
    },
]);

const persons = ref({
    perpetrators: {},
    victims: {},
});

const mapMarkerIconsPath = ref(
    "/images/leaflet/markerIcons/coloredFilledWhite/",
);

onMounted(async () => {
    try {
        // Warten auf DOM-Update
        await nextTick();

        const CountTable = document.getElementById("table-counts");
        if (CountTable) {
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

            let groupTableDatas = document.querySelectorAll(
                '[data-groupname="' + groupName + '"]',
            );
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

    const victimsEntries = Object.entries(persons.value.victims);
    const perpetratorsEntries = Object.entries(persons.value.perpetrators);

    // Update the DOM elements with the counts
    const perpetratorsSpan = document.querySelector(
        '[data-groupuid="perpetrators"]',
    );
    const victimsSpan = document.querySelector('[data-groupuid="victims"]');

    if (perpetratorsSpan) {
        perpetratorsSpan.innerHTML = perpetratorsCount;
    }

    if (victimsSpan) {
        victimsSpan.innerHTML = victimsCount;
    }
};
</script>

<style scoped>
.font-family-special-elite {
    font-family: "Special Elite" !important;
}

.font-size-29 {
    font-size: 1.8125rem !important;
}

.hyphens-auto {
    hyphens: auto;
}

/* space for footer to avoid that footer covers content */
.mb-110 {
    margin-bottom: 110px;
}

/* for external link icon */
.vertical-align-top {
    vertical-align: top;
}
</style>
