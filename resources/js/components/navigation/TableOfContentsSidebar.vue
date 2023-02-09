<template>
    <section class="sticky-top pt-3">
        <div class="toc-border-left">
            <v-list-item v-for="(heading, index) in headings" :key=heading.href>
                <v-list-item-content>
                    <v-list-item-title v-if="index == 0" class="font-weight-bold mb-4">
                        Inhaltsübersicht
                    </v-list-item-title>
                    <v-list-item-subtitle>
                        <v-row>
                            <v-col cols="1">&#10132;</v-col>
                            <v-col>
                                <a class="white-space-normal" :href=heading.href>
                                    {{ heading.label }}
                                </a>
                            </v-col>
                        </v-row>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>

            <v-list-item three-line>
                <v-list-item-content>
                    <v-list-item-title class="font-weight-bold mb-4">
                        Kooperationsprojekt
                    </v-list-item-title>
                    <v-list-item-subtitle class="mb-2">
                        <a href="https://www.tib.eu/de/" target="_blank">
                            <v-img
                                alt="Logo der Technische Informationsbibliothek (TIB)"
                                contain
                                max-width="200px"
                                :src="this.$ogtGlobals.proxyPath + '/images/logo-tib.png'"
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
                                :src="this.$ogtGlobals.proxyPath + '/images/logo-sng.svg'"
                            ></v-img>
                        </a>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>

            <v-list-item three-line>
                <v-list-item-content>
                    <v-list-item-title class="font-weight-bold mb-4">
                        Förderung
                    </v-list-item-title>
                    <v-list-item-subtitle class="mb-2">
                        <a href="https://www.mwk.niedersachsen.de/startseite/" target="_blank">
                            <v-img
                                alt="Logo der Förderung durch das Niedersächsisches Ministerium für Wissenschaft und Kultur"
                                contain
                                max-width="200px"
                                :src="this.$ogtGlobals.proxyPath + '/images/logo-mwk-funding.png'"
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
                                PRO*Niedersachsen<v-icon small>mdi-open-in-new</v-icon>
                            </span>
                        </a>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>

            <v-list-item two-line>
                <v-list-item-content>
                    <v-list-item-title class="font-weight-bold mb-4">
                        Laufzeit
                    </v-list-item-title>
                    <v-list-item-subtitle>
                        11/2020 – 01/2023
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </div>
        <v-divider></v-divider>
    </section>
</template>

<script>
export default {
    name: 'TableOfContentsSidebar',
    props: ['headings'],
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