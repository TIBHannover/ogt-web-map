<template>
    <v-container
        class="background bg-grey-lighten-3 justify-center"
        :class="{ 'xs-bg-x-shifted': $vuetify.display.xs }"
        fill-height
        fluid
        :style="{
            backgroundImage:
                'url(' +
                backgroundImageUrl +
                '), url(' +
                backgroundImageUrlFallback1 +
                '), url(' +
                backgroundImageUrlFallback2 +
                ')',
        }"
    >
        <div>
            <div class="text-white">
                <div
                    class="font-family-special-elite mb-2 text-h4 text-sm-h3 text-md-h2 text-lg-h1"
                >
                    Gestapo.Terror.Orte
                </div>
                <div class="mb-7 ml-1 text-h5 text-md-h4 text-lg-h3">
                    in Niedersachsen 1933 – 1945
                </div>
            </div>
            <template v-for="menuButton in menuButtons">
                <router-link
                    :to="menuButton.routeTo"
                    class="text-decoration-none"
                >
                    <v-btn
                        class="background-color-grey border-none justify-start ml-1 mr-5 my-3 text-h6 text-transform-none"
                        color="white"
                        elevation="24"
                        min-width="200px"
                        variant="outlined"
                        rounded
                        size="x-large"
                    >
                        <v-icon start> mdi-arrow-right-thick </v-icon>
                        {{ menuButton.label }}
                    </v-btn>
                </router-link>
            </template>
            <v-divider :thickness="5" class="my-12"></v-divider>

            <v-banner
                class="py-6 mb-6"
                icon="mdi-map"
                color="black"
                border="1"
                bg-color="rgba(255, 255, 255, 0.5)"
            >
                <v-banner-text>
                    <div
                        class="font-family-special-elite mb-2 text-h5 text-sm-h5 text-md-h5 text-lg-h4"
                    >
                        Werkstatt historische Daten
                    </div>
                    <div class="">
                        Mach mit: Trage mit uns historische Daten zu
                        Gestapo-Orten in Wikidata ein <br />
                        – online, offen für alle, ohne Vorkenntnisse!
                        <br />
                        Jetzt anmelden und mitgestalten!
                    </div>
                    <h1
                        class="font-family-special-elite mt-3 mb-2 text-h6 text-sm-h6 text-md-h6 text-lg-h5"
                        :class="{
                            'font-size-29': $vuetify.display.mdAndUp,
                        }"
                    >
                        Die nächste Werkstatt
                    </h1>

                    <div v-if="nextWorkshop">
                        <p>
                            <strong>{{ nextWorkshop.label }}</strong>
                            <br />
                            Am {{ formatDate(nextWorkshop.date) }} Uhr
                            <br />
                            <span v-if="nextWorkshop.place === 'digital'">
                                Findet digital statt
                            </span>
                            <span v-else> Ort: {{ workshop.place }} </span>
                            <a
                                :href="nextWorkshop.link"
                                target="_blank"
                                v-if="nextWorkshop.link"
                                class="ml-3"
                            >
                                <v-icon start size="sm" class="mb-1"
                                    >mdi-open-in-new</v-icon
                                >
                                Mehr Informationen zum Termin
                            </a>
                        </p>
                    </div>
                    <p v-else>Aktuell ist kein Workshop geplant.</p>

                    <v-expansion-panels focusable>
                        <v-expansion-panel>
                            <v-expansion-panel-title
                                class="font-weight-bold"
                                color="rgba(255, 255, 255, 0.5)"
                                flat
                            >
                                Weitere Werkstatt-Termine
                            </v-expansion-panel-title>
                            <v-expansion-panel-text>
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
                                                >{{ workshop.label }}</a
                                            ></strong
                                        >
                                        <br />
                                        <span>
                                            {{ formatDate(workshop.date) }}
                                            Uhr
                                        </span>
                                    </span>
                                </p>
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </v-banner-text>

                <v-banner-actions class="flex-column">
                    <v-btn
                        class="background-color-grey border-none justify-start ml-1 mr-5 my-3 text-h6 text-transform-none"
                        color="white"
                        elevation="24"
                        min-width="200px"
                        variant="outlined"
                        rounded
                        size="x-large"
                        to="/workshops"
                    >
                        <v-icon start class="ml-3">
                            mdi-arrow-right-thick
                        </v-icon>
                        Weitere Infos
                    </v-btn>
                    <v-btn
                        class="background-color-grey border-none justify-start ml-1 mr-5 my-3 text-h6 text-transform-none"
                        color="white"
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
                        Zur Werkstattanmeldung
                    </v-btn>
                </v-banner-actions>
            </v-banner>
        </div>
    </v-container>
</template>

<script setup>
import { ref, inject, computed } from "vue";
import { useDisplay } from "vuetify";
import { useRouter, useRoute } from "vue-router";
import { workshops } from "../data/workshops";

const { mdAndUp } = useDisplay();
const router = useRouter();
const route = useRoute();
const ogtGlobals = inject("ogtGlobals");

const backgroundImageUrl = ref(
    ogtGlobals.proxyPath + "/images/static/startPageBackground.jpg",
);
const backgroundImageUrlFallback1 = ref(
    "https://service.tib.eu/ogt/images/static/startPageBackground.jpg",
);
const backgroundImageUrlFallback2 = ref(
    ogtGlobals.proxyPath + "/images/solid-grey.svg",
);

const nextWorkshop = computed(() => {
    const now = new Date();
    return (
        workshops
            .filter((workshop) => new Date(workshop.date) > now)
            .sort((a, b) => new Date(a.date) - new Date(b.date))[0] || null
    );
});

const upcomingWorkshops = computed(() => {
    const now = new Date();
    return workshops
        .filter((workshop) => new Date(workshop.date) > now)
        .sort((a, b) => new Date(a.date) - new Date(b.date))
        .slice(1);
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

const menuButtons = ref([
    {
        label: "Projekt",
        routeTo: "/project",
    },
    {
        label: "Kartenansicht",
        routeTo: "/map",
    },
    {
        label: "Mitforschen",
        routeTo: "/collaboration",
    },
]);
</script>

<style scoped>
.background {
    height: 100vh;
    display: flex;
    align-items: center;
    background-position: center center;
    background-repeat: no-repeat;
    background-size: cover;
}

.background-color-grey {
    background-color: rgba(94, 94, 94, 0.35);
}

.border-none {
    border: none;
}

.font-family-special-elite {
    font-family: "Special Elite" !important;
}

/* to lowercase text within Vuetify buttons */
.text-transform-none {
    text-transform: none;
}

/* shift center of background image for extra small window sizes (< 600px) */
.xs-bg-x-shifted {
    background-position-x: 75%;
}
</style>
