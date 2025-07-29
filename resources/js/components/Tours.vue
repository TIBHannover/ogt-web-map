<template>
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
                    Touren
                </h1>

                <v-grid>
                    <v-row class="tour-section align-center">
                        <v-col cols="12" md="5" class="pt-0 pt-md-2">
                            <v-img
                                alt="Leitfaden für erinnerungskulturelle Touren"
                                contain
                                max-width="500px"
                                :src="
                                    ogtGlobals.proxyPath +
                                    '/images/thumbs/thumb_leitfadentouren.jpg'
                                "
                            ></v-img>
                        </v-col>
                        <v-col cols="12" md="7" class="pb-0 pb-md-2">
                            <h2>Leitfaden für erinnerungskulturelle Touren</h2>
                            <p>
                                Wie lässt sich Geschichte an konkreten Orten
                                sichtbar und zugänglich machen – ohne zu
                                vereinfachen oder zu überfordern? Unser
                                Leitfaden unterstützt alle, die eigene Touren im
                                Rahmen von Gestapo.Terror.Orte planen oder
                                durchführen möchten. Ob Stadtspaziergang,
                                Fahrradtour oder Route mit öffentlichen
                                Verkehrsmitteln – der Leitfaden bietet
                                praktische Hinweise zur Konzeption, Methodik und
                                Umsetzung erinnerungskultureller
                                Bildungsformate.
                            </p>
                            <p>
                                Geeignet für verschiedene Zielgruppen – auch für
                                Schulklassen. Modular nutzbar, erweiterbar und
                                offen für vielfältige Kontexte.
                            </p>
                            <div class="download-section">
                                <a
                                    href="./resources/Leitfaden_Touren_Gestapo_Terror_Orte_compressed.pdf"
                                    class="download-button"
                                    download
                                >
                                    <v-icon start> mdi-download </v-icon>
                                    Leitfaden für erinnerungskulturelle Touren
                                    herunterladen
                                </a>
                            </div>
                        </v-col>
                    </v-row>
                    <v-row
                        class="tour-section align-center flex-column-reverse flex-md-row"
                    >
                        <v-col cols="12" md="7" class="pb-0 pb-md-2">
                            <h2>Fragekarten</h2>
                            <p>
                                Hier kannst du ein Set mit 12 Fragekarten
                                herunterladen, das für den Einsatz während
                                erinnerungskultureller Touren entwickelt
                                wurde.<br />
                                Die Karten regen zur Beobachtung, Reflexion und
                                zum Gespräch an. <br />
                                Sie sind als Fragepaare gestaltet und farblich
                                gekennzeichnet, können aber auch einzeln
                                verwendet werden. Das Design darf gerne als
                                Vorlage für eigene Karten weitergenutzt und
                                angepasst werde
                            </p>
                            <div class="download-section">
                                <a
                                    href="./resources/Frage_Karten_compressed.pdf"
                                    class="download-button"
                                    download
                                >
                                    <v-icon start> mdi-download </v-icon>
                                    Fragekarten herunterladen
                                </a>
                            </div>
                        </v-col>
                        <v-col cols="12" md="5" class="pt-0 pt-md-2">
                            <v-img
                                alt="Leitfaden für erinnerungskulturelle Touren"
                                contain
                                max-width="500px"
                                :src="
                                    ogtGlobals.proxyPath +
                                    '/images/thumbs/thumb_tourenkarten.jpg'
                                "
                            ></v-img>
                        </v-col>
                    </v-row>
                </v-grid>
                <!--
            <div class="tour-section">
              <h2>Verfügbare Touren</h2>
              <p>
                Entdecken Sie unsere thematischen Stadtführungen zu historischen Orten der Gestapo-Verfolgung.
                Die Touren bieten eine einzigartige Möglichkeit, die Geschichte vor Ort zu erfahren und zu verstehen.
              </p>
              </div>-->
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
</script>

<style scoped>
.font-family-special-elite {
    font-family: "Special Elite" !important;
}

.font-size-29 {
    font-size: 1.8125rem !important;
}
.tours-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.tour-section {
    margin-bottom: 3rem;
    background-color: #f9f9f9;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.download-section {
    margin-top: 1.5rem;
}

.download-button {
    display: inline-block;
    padding: 0.8rem 1.5rem;
    background-color: #3490dc;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-weight: bold;
    transition: background-color 0.3s;
}

.download-button:hover {
    background-color: #2779bd;
}
</style>
