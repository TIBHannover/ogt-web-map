<template>
    <section class="sticky-top pt-3">
        <div class="toc-border-left">
            <v-list-item
                v-for="(heading, index) in headings"
                :key="heading.href"
            >
                <v-list-item-title
                    v-if="index == 0"
                    class="font-weight-bold mb-4"
                >
                    Inhaltsübersicht
                </v-list-item-title>
                <v-list-item-subtitle>
                    <v-row>
                        <v-col cols="1">&#10132;</v-col>
                        <v-col>
                            <a class="white-space-normal" :href="heading.href">
                                {{ heading.label }}
                            </a>
                        </v-col>
                    </v-row>
                </v-list-item-subtitle>
            </v-list-item>
            <v-divider></v-divider>

            <v-list-item lines="three">
                <v-list-item-title class="font-weight-bold mb-4">
                    Kooperationsprojekt
                </v-list-item-title>
                <v-list-item-subtitle class="mb-2">
                    <a href="https://www.tib.eu/de/" target="_blank">
                        <v-img
                            alt="Logo der Technische Informationsbibliothek (TIB)"
                            contain
                            max-width="200px"
                            :src="
                                `${ogtGlobals.proxyPath}` +
                                '/images/logo-tib.png'
                            "
                        ></v-img>
                    </a>
                </v-list-item-subtitle>
                <v-list-item-subtitle>
                    <a href="https://www.stiftung-ng.de/" target="_blank">
                        <v-img
                            alt="Logo der Stiftung niedersächsische Gedenkstätten"
                            contain
                            max-height="150px"
                            max-width="200px"
                            :src="
                                `${ogtGlobals.proxyPath}` +
                                '/images/logo-sng.svg'
                            "
                        ></v-img>
                    </a>
                </v-list-item-subtitle>
            </v-list-item>
            <v-divider></v-divider>

            <v-list-item lines="three">
                <v-list-item-title class="font-weight-bold mb-4">
                    Förderung
                </v-list-item-title>
                <v-list-item-subtitle class="mb-2">
                    <a
                        href="https://www.mwk.niedersachsen.de/startseite/"
                        target="_blank"
                    >
                        <v-img
                            alt="Logo der Förderung durch das Niedersächsisches Ministerium für Wissenschaft und Kultur"
                            contain
                            max-width="200px"
                            :src="
                                `${ogtGlobals.proxyPath}` +
                                '/images/logo-mwk-funding.png'
                            "
                        ></v-img>
                    </a>
                </v-list-item-subtitle>
                <v-list-item-subtitle>
                    <a
                        class="hyphens-auto white-space-normal"
                        href="https://www.mwk.niedersachsen.de/startseite/aktuelles/presseinformationen/land-fordert-geistes-kultur-und-sozialwissenschaften-mit-4-5-mio-euro-191014.html"
                        lang="de"
                        target="_blank"
                    >
                        Förderprogramm
                        <span class="text-no-wrap">
                            PRO*Niedersachsen<v-icon size="small"
                                >mdi-open-in-new</v-icon
                            >
                        </span>
                    </a>
                </v-list-item-subtitle>
            </v-list-item>
            <v-divider></v-divider>

            <v-list-item lines="two">
                <v-list-item-title class="font-weight-bold mb-4">
                    Laufzeit
                </v-list-item-title>
                <v-list-item-subtitle> 11/2020 – 12/2025 </v-list-item-subtitle>
            </v-list-item>
            <v-divider></v-divider>

            <v-list-item v-if="route.path != '/workshops'">
                <v-list-item-title class="font-weight-bold mb-4 mt-2">
                    Nächste Werkstatt für historische Daten:
                </v-list-item-title>
                <div v-if="nextWorkshop">
                    <p>
                        <strong>{{ nextWorkshop.label }}</strong>
                        <br />
                        Am {{ formatDate(nextWorkshop.date) }} Uhr ({{
                            nextWorkshop.place
                        }})
                        <br />
                        <a
                            :href="nextWorkshop.link"
                            target="_blank"
                            v-if="nextWorkshop.link"
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
                                            :href="nextWorkshop.link"
                                            target="_blank"
                                            v-if="nextWorkshop.link"
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
            </v-list-item>
        </div>
        <v-divider></v-divider>
    </section>
</template>

<script setup>
import { ref, inject, computed } from "vue";
import { workshops } from "../../data/workshops";
import { useRoute } from "vue-router";
const route = useRoute();
const ogtGlobals = inject("ogtGlobals");

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
</script>

<script>
export default {
    name: "TableOfContentsSidebar",
    props: ["headings"],
};
</script>

<style scoped>
.hyphens-auto {
    hyphens: auto;
}

.sticky-top {
    position: sticky;
    top: 0;
}

.toc-border-left {
    border-color: #0000001f;
    border-left-style: solid;
    border-left-width: thin;
}

/* to enable linebreaks for long labels */
.white-space-normal {
    white-space: normal;
}
</style>
