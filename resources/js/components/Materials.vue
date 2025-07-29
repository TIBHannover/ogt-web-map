<template>
    <!-- MaterialsPage component -->
    <v-container id="container" class="mb-110" :class="{ 'pa-md-4': mdAndUp }">
        <!-- header -->
        <v-row
            :style="{
                'padding-left':
                    freeClientWidth < 75 && !isMenuDisplayed
                        ? computedPadding
                        : 0,
            }"
        >
            <v-col>
                <h1
                    class="font-family-special-elite font-weight-bold hyphens-auto py-4 text-h4 text-md-h3"
                    lang="de"
                    :class="{ 'text-center': ogtGlobals.isMobile }"
                >
                    Materialien
                </h1>
            </v-col>
        </v-row>

        <v-row>
            <v-col cols="12" md="4" class="order-md-last">
                <table-of-contents-sidebar
                    :headings="headings"
                ></table-of-contents-sidebar>
            </v-col>

            <!-- scrollable content -->
            <v-col cols="12" md="8" class="hyphens-auto" lang="de">
                <section id="materials">
                    <h2
                        class="font-weight-medium text-h5 text-md-h4 mb-5"
                        :class="{ 'font-size-29': $vuetify.display.mdAndUp }"
                    >
                        Anleitungen zur Werkstatt historische Daten
                    </h2>
                    <p>
                        Du möchtest Daten zur Geschichte der Gestapo erfassen
                        und sie auf der digitalen Karte Gestapo.Terror.Orte
                        sichtbar machen?<br />
                        Hier findest du Materialien, die dich dabei
                        unterstützen.
                    </p>

                    <v-grid>
                        <v-row class="align-center">
                            <v-col cols="12" md="7" class="pb-0 pb-md-2">
                                <h3>Wikidata Basics</h3>
                                <p class="mb-0">
                                    Diese PDF erklärt zentrale Grundbegriffe
                                    rund um Wikidata. <br />Sie beantwortet
                                    Fragen wie:<br />
                                    Was ist Wikidata und was sind strukturierte
                                    Daten?<br />
                                    Warum nutzt Gestapo.Terror.Orte Wikidata als
                                    Datenbank?<br />
                                    Wie ist Wikidata aufgebaut?
                                </p>
                            </v-col>
                            <v-col cols="12" md="5" class="pt-0 pt-md-2">
                                <v-btn
                                    class="background-color-grey border-none justify-start ml-1 mr-5 my-3 mb-6 text-h6 py-3 text-transform-none"
                                    color="black"
                                    background="grey"
                                    elevation="24"
                                    min-width="200px"
                                    style="height: auto"
                                    variant="outlined"
                                    rounded
                                    href="./resources/Wikidata_Basics_compressed.pdf"
                                    target="_blank"
                                >
                                    <v-icon start class="ml-3">
                                        mdi-download
                                    </v-icon>
                                    <span class="whitespace-normal"
                                        >Anleitung Wikidata Basics</span
                                    >
                                </v-btn>
                            </v-col>
                        </v-row>
                        <v-row class="align-center">
                            <v-col cols="12" md="7" class="pb-0 pb-md-2">
                                <h3>Wikidata-Anleitung</h3>
                                <p class="mb-0">
                                    Diese Schritt-für-Schritt-Anleitung zeigt,
                                    wie du neue Datenobjekte in Wikidata anlegst
                                    oder bestehende ergänzt.
                                </p>
                            </v-col>
                            <v-col cols="12" md="5" class="pt-0 pt-md-2">
                                <v-btn
                                    class="background-color-grey border-none justify-start ml-1 mr-5 my-3 mb-6 text-h6 py-3 text-transform-none"
                                    color="black"
                                    background="grey"
                                    elevation="24"
                                    min-width="200px"
                                    style="height: auto"
                                    variant="outlined"
                                    rounded
                                    href="./resources/Anleitung_seite_compressed.pdf"
                                    target="_blank"
                                >
                                    <v-icon start class="ml-3">
                                        mdi-download
                                    </v-icon>
                                    <span class="whitespace-normal"
                                        >Wikidata Anleitung
                                    </span>
                                </v-btn>
                            </v-col>
                        </v-row>
                        <v-row class="align-center">
                            <v-col cols="12" md="7" class="pb-0 pb-md-2">
                                <h3>Datenmodelle</h3>
                                <p class="mb-0">
                                    In Wikidata können Informationen auf
                                    unterschiedliche Weise erfasst werden. Diese
                                    Übersicht zeigt, wie Daten zu Gestapoorten,
                                    Erinnerungsorten, Geschädigten, Täter*innen
                                    und Ereignissen erfasst werden müssen, damit
                                    sie auf Gestapo.Terror.Orte angezeigt
                                    werden. Wichtig: Nur wenn du dich an die
                                    Datenmodelle hältst, erscheinen deine
                                    Einträge auf der Karte. Nicht alle Daten
                                    müssen ausgefüllt werden – verpflichtend
                                    sind nur die farbig markierten Angaben.
                                    Zusätzliche Informationen können ebenfalls
                                    ergänzt werden, auch wenn sie nicht explizit
                                    im Modell vorgesehen sind.
                                </p>
                            </v-col>
                            <v-col cols="12" md="5" class="pt-0 pt-md-2">
                                <v-btn
                                    class="background-color-grey border-none justify-start ml-1 mr-5 my-3 mb-6 text-h6 py-3 text-transform-none"
                                    color="black"
                                    background="grey"
                                    elevation="24"
                                    min-width="200px"
                                    variant="outlined"
                                    style="height: auto"
                                    rounded
                                    href="./resources/Datenmodelle_final_compressed.pdf"
                                    target="_blank"
                                >
                                    <v-icon start class="ml-3">
                                        mdi-download
                                    </v-icon>
                                    <span class="whitespace-normal"
                                        >Beschreibung der Datenmodelle
                                    </span>
                                </v-btn>
                            </v-col>
                        </v-row>
                        <v-row class="align-center">
                            <v-col cols="12" md="7" class="pb-0 pb-md-2">
                                <h3>Beispiel Quellenanalyse</h3>
                                <p class="mb-0">
                                    Anhand eines konkreten Beispiels wird
                                    gezeigt, wie der Weg von einer Archivalie zu
                                    einem Eintrag auf Gestapo.Terror.Orte
                                    aussieht: Welche Informationen lassen sich
                                    aus historischen Quellen gewinnen? Wie sehen
                                    die entsprechenden Einträge in Wikidata und
                                    auf der Karte aus?
                                </p>
                            </v-col>
                            <v-col cols="12" md="5" class="pt-0 pt-md-2">
                                <v-btn
                                    class="background-color-grey border-none justify-start ml-1 mr-5 my-3 mb-6 text-h6 py-3 text-transform-none"
                                    color="black"
                                    background="grey"
                                    elevation="24"
                                    min-width="200px"
                                    variant="outlined"
                                    style="height: auto"
                                    rounded
                                    size="x-large"
                                    href="./resources/Beispiel_Quellenanalyse_compressed.pdf"
                                    target="_blank"
                                >
                                    <v-icon start class="ml-3">
                                        mdi-download
                                    </v-icon>
                                    <span class="whitespace-normal"
                                        >Beispiel einer Quellenanalyse
                                    </span>
                                </v-btn>
                            </v-col>
                        </v-row>
                    </v-grid>

                    <br />
                </section>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup name="MaterialsPage">
import { useDisplay } from "vuetify";
import {
    ref,
    inject,
    defineProps,
    onMounted,
    onUnmounted,
    computed,
} from "vue";
import TableOfContentsSidebar from "./navigation/TableOfContentsSidebar";

// Get display breakpoints and use them in the template
const { mdAndUp } = useDisplay();

// Global state for consistent access across components
const ogtGlobals = inject("ogtGlobals");

// Props
const props = defineProps({
    isMenuDisplayed: Boolean,
});

// Reactive references
const freeClientWidth = ref(document.documentElement.clientWidth);

// Used in template for dynamic padding calculation
const computedPadding = computed(() => {
    return freeClientWidth.value < 75 && !props.isMenuDisplayed
        ? `${75 - freeClientWidth.value}px`
        : "0";
});

const setFreeClientWidth = () => {
    const container = document.getElementById("container");
    if (container) {
        freeClientWidth.value =
            (document.documentElement.clientWidth - container.clientWidth) / 2;
    }
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
        href: "#materials",
        label: "Anleitungen zur Werkstatt historische Daten",
    },
]);
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
.whitespace-normal {
    white-space: normal !important;
}
</style>