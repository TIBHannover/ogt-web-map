<template>
    <v-container class="grey lighten-3" fluid>

        <!-- header -->
        <v-row>
            <v-col>
                <h1 class="text-h4 text-md-h3 text-center py-4">
                    Projekt
                </h1>
            </v-col>
        </v-row>

        <v-row>
            <!-- table of contents sidebar -->
            <v-col class="col-12 col-sm-2 order-sm-last" style="min-width: 300px">
                <section class="sticky-top pt-3">
                    <h6 class="text-h6">Inhaltsübersicht</h6>
                    <div class="toc-border-left">
                        <v-list-item>
                            <v-list-item-content>
                                <v-list-item-subtitle>
                                    <a class="hyphens-auto white-space-normal" href="#researchObjectives" lang="de">
                                        Forschungsfragen & Projektziele
                                    </a>
                                </v-list-item-subtitle>
                            </v-list-item-content>
                        </v-list-item>
                        <v-list-item>
                            <v-list-item-content>
                                <v-list-item-subtitle>
                                    <a class="hyphens-auto white-space-normal" href="#teamAndPartners" lang="de">
                                        Team & Partner:innen
                                    </a>
                                </v-list-item-subtitle>
                            </v-list-item-content>
                        </v-list-item>
                        <v-list-item>
                            <v-list-item-content>
                                <v-list-item-subtitle>
                                    <a class="hyphens-auto white-space-normal"
                                       href="#presentationsAndPublications"
                                       lang="de"
                                    >
                                        Projektpräsentationen & Publikationen
                                    </a>
                                </v-list-item-subtitle>
                            </v-list-item-content>
                        </v-list-item>
                        <v-list-item>
                            <v-list-item-content>
                                <v-list-item-subtitle>
                                    <a class="hyphens-auto white-space-normal" href="#pressReports" lang="de">
                                        Presseberichte
                                    </a>
                                </v-list-item-subtitle>
                            </v-list-item-content>
                        </v-list-item>
                        <v-divider></v-divider>

                        <v-list-item three-line>
                            <v-list-item-content>
                                <v-list-item-title class="mb-4">
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
                                <v-list-item-title class="mb-4">
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
                                    <a class="hyphens-auto white-space-normal"
                                       href="https://www.mwk.niedersachsen.de/startseite/aktuelles/presseinformationen/land-fordert-geistes-kultur-und-sozialwissenschaften-mit-4-5-mio-euro-191014.html"
                                       lang="de"
                                       target="_blank">Förderprogramm PRO*Niedersachsen</a>
                                </v-list-item-subtitle>
                            </v-list-item-content>
                        </v-list-item>
                        <v-divider></v-divider>

                        <v-list-item two-line>
                            <v-list-item-content>
                                <v-list-item-title class="mb-4">
                                    Laufzeit
                                </v-list-item-title>
                                <v-list-item-subtitle>
                                    11/2020 – 01/2023
                                </v-list-item-subtitle>
                            </v-list-item-content>
                        </v-list-item>
                        <v-divider></v-divider>
                    </div>
                </section>
            </v-col>

            <!-- scrollable content -->
            <v-col class="hyphens-auto" lang="de">
                <section id="researchObjectives" class="mt-3 mb-16">
                    <h1 class="text-h5 text-md-h4 mb-5">
                        Forschungsfragen & Projektziele
                    </h1>
                </section>

                <section id="teamAndPartners" class="mb-16">
                    <h1 class="text-h5 text-md-h4 mb-5">
                        Team & Partner:innen
                    </h1>
                    <h2 class="text-h6 text-md-h5 mb-3">
                        Einleitung
                    </h2>
                    <p>
                        Das Projekt „Orte des Gestapoterrors im heutigen Niedersachsen“ (OGT) ist ein
                        Kooperationsprojekt zwischen der Stiftung niedersächsische Gedenkstätten (SnG), zu deren Auftrag
                        die Forschungs- und Bildungsarbeit über die nationalsozialistische Verfolgungspolitik zählt, und
                        der Technischen Informationsbibliothek (TIB), die auf die digitale Erschließung und Aufbereitung
                        von Daten und deren freie Zugänglichkeit spezialisiert ist.
                    </p>
                    <h2 class="text-h6 text-md-h5 mb-3">
                        Team
                    </h2>
                    <h3 class="text-subtitle-1 text-md-h6 mb-1">
                        Technische Informationsbibliothek (TIB) – Open Science Lab (OSL)
                    </h3>
                    <ul class="mb-4">
                        <li>
                            <a href="https://vivo.tib.eu/fis/individual/n0000-0003-0232-7085" target="_blank">
                                Lambert Heller
                            </a>
                            &nbsp;– Leitung Open Science Lab der TIB
                        </li>
                        <li>
                            <a href="https://vivo.tib.eu/fis/individual/n0000-0002-3075-7640" target="_blank">
                                Prof. Dr. Ina Blümel
                            </a>
                            &nbsp;– Projektleitung
                        </li>
                        <li>Lisa Groh-Trautmann (ab 02/2022) – Projektkoordination & Recherche</li>
                        <li>Dr. Janine Doerry (bis 10/2021) – Projektkoordination & Recherche</li>
                        <li>Nils Casties – Webentwicklung & Projektkoordination</li>
                        <li>
                            <a href="https://vivo.tib.eu/fis/individual/n0000-0002-2593-8754" target="_blank">
                                Lucia Sohmen
                            </a>
                            &nbsp;– Wissenschaftliche Mitarbeiterin
                        </li>
                        <li>
                            <a href="https://nadinekloever.com/" target="_blank">
                                Nadine Klöver
                            </a>
                            &nbsp;– Grafik-Design | Advertising-Design
                        </li>
                        <li>Tobias Mielich – Studentischer Mitarbeiter, Fachmaster Geschichte (Master of Arts)</li>
                    </ul>

                    <h2 class="text-h6 text-md-h5 mb-3">
                        Partner
                    </h2>
                    <h3 class="text-subtitle-1 text-md-h6 mb-1">
                        Stiftung niedersächsische Gedenkstätten (SnG)
                    </h3>
                    <p>
                        <a href="https://www.stiftung-ng.de/" target="_blank">
                            <v-img
                                alt="Logo der Stiftung niedersächsische Gedenkstätten"
                                contain
                                max-height="150px"
                                max-width="200px"
                                :src="this.$ogtGlobals.proxyPath + '/images/logo-sng.svg'"
                            ></v-img>
                        </a>
                    </p>

                    <h3 class="text-subtitle-1 text-md-h6 mb-1">
                        Gedenkstätten Gestapokeller und Augustaschacht
                    </h3>
                    <div class="pb-16">
                        <div class="float-left">
                            <a href="https://gedenkstaetten-augustaschacht-osnabrueck.de/augustaschacht"
                               target="_blank"
                            >
                                <v-img
                                    alt="Logo der Gedenkstätte Augustaschacht"
                                    height="60px"
                                    :src="this.$ogtGlobals.proxyPath + '/images/logo-augustaschacht.svg'"
                                    width="175px"
                                ></v-img>
                            </a>
                        </div>
                        <div class="float-left">
                            <a href="https://gedenkstaetten-augustaschacht-osnabrueck.de/gestapokeller" target="_blank">
                                <v-img
                                    alt="Logo der Gedenkstätte Gestapokeller"
                                    height="60px"
                                    :src="this.$ogtGlobals.proxyPath + '/images/logo-gestapokeller.svg'"
                                    width="175px"
                                ></v-img>
                            </a>
                        </div>
                    </div>
                </section>

                <section id="presentationsAndPublications" class="mb-16">
                    <h1 class="text-h5 text-md-h4 mb-5">
                        Projektpräsentationen & Publikationen
                    </h1>
                </section>

                <section id="pressReports" class="mb-16">
                    <h1 class="text-h5 text-md-h4 mb-5">
                        Presseberichte
                    </h1>
                </section>
            </v-col>
        </v-row>

    </v-container>
</template>

<script>
export default {
    name: 'Project',
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