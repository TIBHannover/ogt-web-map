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
                    Mitforschen: Werkstätte
                </h1>
            </v-col>
        </v-row>

        <v-row>
            <v-col
                class="col-12 col-sm-2 order-sm-last"
                style="max-width: 310px; min-width: 310px"
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

                <section id="workshopInfo">
                    <h1
                        class="font-weight-medium text-h5 text-md-h4 mb-5"
                        :class="{ 'font-size-29': $vuetify.display.mdAndUp }"
                    >
                        Werkstatt historische Daten
                    </h1>
                    <p>
                        <strong
                            >Mach mit: Werkstatt historische Daten zum
                            Gestapoterror in Wikidata</strong
                        >
                    </p>

                    <p>
                        Nimm an unseren Online Werkstatt historische Daten teil
                        und helfe dabei, Daten zum Gestapoterror in Wikidata
                        einzutragen und so die interaktive Karte weiter zu
                        füllen! So machen wir gemeinsam Wissen über die
                        Geschichte der Gestapo besser zugänglich.
                    </p>

                    <p>
                        <strong>Jede*r kann mitmachen!</strong><br />
                        Egal, ob du schon Daten oder Vorkenntnisse hast – alle
                        sind willkommen. Wenn du Informationen zur Geschichte
                        der Gestapo hast, bring sie gerne mit. Wir stellen aber
                        auch Material zur Verfügung, damit jede*r aktiv
                        beitragen kann.
                    </p>

                    <p>
                        <strong>Was du mitnimmst</strong> <br />

                        Du trägst nicht nur dazu bei, die Geschichte der Gestapo
                        zugänglicher zu machen, sondern du entwickelst auch
                        eigene Fähigkeiten weiter:
                    </p>
                    <ul class="mb-6 ml-6">
                        <li>
                            <strong>Digitale Kompetenzen:</strong> <br />Du
                            lernst, wie man mit Wikidata arbeitet, Daten
                            strukturiert und für die Forschung nutzbar macht.
                        </li>
                        <li>
                            <strong>Einblick in Forschungsarbeit:</strong><br />
                            Du erhältst eine Einführung in die Arbeit mit
                            historischen Forschungsdaten und verstehst, wie
                            wissenschaftliches Wissen entsteht und genutzt wird.
                        </li>
                        <li>
                            <strong>Austausch und Vernetzung:</strong><br />
                            Unsere Werkstatt bringt Menschen mit
                            unterschiedlichen Hintergründen zusammen. Tausche
                            dich mit Historiker*innen und anderen Teilnehmenden
                            aus der digitalen Welt aus, lerne voneinander und
                            profitiere von interdisziplinären Perspektiven.
                        </li>
                    </ul>
                    <p class="mb-0"><strong>So läuft es ab:</strong></p>
                    <ul class="mb-6 ml-6">
                        <li>
                            Die Workshops finden zu verschiedenen Terminen
                            online statt.
                        </li>
                        <li>
                            Du kannst jederzeit einsteigen. Für alle, die es
                            brauchen, gibt es eine kurze Einführung.
                        </li>

                        <li>
                            Eine Anmeldung über unser Formular ist nötig. Nach
                            der Anmeldung erhältst du den Zugangslink zum
                            Meeting per E-Mail.
                        </li>
                    </ul>
                    <v-btn
                        class="background-color-grey border-none justify-start ml-1 mr-5 my-3 mb-6 text-h6 text-transform-none"
                        color="black"
                        background="grey"
                        elevation="24"
                        min-width="200px"
                        variant="outlined"
                        rounded
                        size="x-large"
                        href="https://projects.tib.eu/ogt-orte-des-gestapoterrors/veranstaltungen/zur-werkstatt-historische-daten-anmelden/"
                        target="_blank"
                    >
                        <v-icon start class="ml-3">
                            mdi-arrow-right-thick
                        </v-icon>
                        Hier geht es zum Anmeldeformular!
                    </v-btn>

                    <p>
                        Wir freuen uns auf deine Teilnahme und dein Engagement!
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
                <section id="results">
                    <h2
                        class="font-weight-medium text-h5 text-md-h4 mb-5"
                        :class="{ 'font-size-29': $vuetify.display.mdAndUp }"
                    >
                        Ergebnisse der "Werkstatt historische Daten"
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
                                        <span>26.02.2025</span>
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
        href: "#workshopInfo",
        label: "Werkstatt historische Daten",
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
