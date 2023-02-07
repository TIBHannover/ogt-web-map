<template>
    <v-container id="container" :class="{'pl-391': isMenuDisplayed}">

        <!-- header -->
        <v-row :style="{
            'padding-left': (freeClientWidth < 75 && ! isMenuDisplayed) ? ((75 - freeClientWidth) + 'px') : 0
        }">
            <v-col>
                <h1 class="font-family-special-elite font-weight-bold hyphens-auto py-4 text-h4 text-md-h3" lang="de">
                    Projekt
                </h1>
            </v-col>
        </v-row>

        <v-row>
            <v-col class="col-12 col-sm-2 order-sm-last" style="min-width: 310px">
                <table-of-contents-sidebar :headings="headings"></table-of-contents-sidebar>
            </v-col>

            <!-- scrollable content -->
            <v-col class="hyphens-auto" lang="de">
                <section id="researchObjectives" class="mt-3 mb-16">
                    <h1 class="text-h5 text-md-h4 mb-5">
                        Forschungsfragen & Projektziele
                    </h1>
                    <p>
                        Wo befanden sich Orte des Gestapoterrors in Niedersachsen? Welche Gebäude nutzte die politische
                        Polizei während des Nationalsozialismus? Wo waren die Dienstsitze, wo die Haftstätten der
                        Geheimen Staatspolizei?
                    </p>
                    <p>
                        Mit Hilfe der interaktive Landkarte „Gestapo.Terror.Orte in Niedersachsen 1933–1945“ werden
                        Daten zu den Orten des Gestapoterrors im heutigen Niedersachsen erhoben, dargestellt und
                        ausgewertet.
                    </p>
                    <p>
                        So wird die Tätigkeit der Gestapo mit Blick auf deren Standorte und Infrastruktur erforscht und
                        das vorhandene Wissen über die Verbrechen der Gestapo und die spätere Erinnerung daran digital
                        zugänglich gemacht. Die interaktive Landkarte kann von niedersächsischen Gedenkstätten und
                        Bildungseinrichtungen sowie Forschenden, Studierenden und anderen Interessierten genutzt und
                        ergänzt werden.
                    </p>

                    <h2 class="text-h6 text-md-h5 mb-3">
                        Standorte und Infrastruktur flächendeckend untersuchen
                    </h2>
                    <p>
                        Die Gestapo war eines der wichtigsten Terrorinstrumente des nationalsozialistischen Staates. Sie
                        verfolgte unter anderem politische Gegnerinnen und Gegner, setzte den staatlichen Antisemitismus
                        und Rassismus durch und erhielt die Kriegswirtschaft aufrecht.
                    </p>
                    <p>
                        Der Gestapo gelang es wie in vielen anderen Regionen auch im Bereich des heutigen Landes
                        Niedersachsen, ihre Unterlagen kurz vor Ende des Zweiten Weltkriegs weitgehend zu vernichten.
                        Insbesondere Nachkriegsermittlungen und Parallelüberlieferungen ermöglichen jedoch die
                        Rekonstruktion der Tätigkeit der Gestapo.
                    </p>
                    <p>
                        Im Mittelpunkt des Forschungsprojektes stehen die Orte des Gestapoterrors im Bereich des Landes
                        Niedersachsen von 1933–1945. Die damaligen Dienstsitze und Haftstätten werden erstmals
                        flächendeckend als grundlegende Faktoren der Gestapotätigkeit untersucht.
                    </p>

                    <h2 class="text-h6 text-md-h5 mb-3">
                        Geschichtliches Erbe digital zugänglich machen
                    </h2>
                    <p>
                        Anhand von Standorten und Infrastruktur werden nicht nur die Tätigkeit der Gestapo erforscht,
                        sondern auch das Wissen über deren Verbrechen und die spätere Erinnerung daran digital
                        zugänglich gemacht. Als kulturelles Erbe Niedersachsens werden dabei insbesondere
                        Gedenkstättensammlungen, themenspezifische Archivbestände und Baudenkmale erschlossen und
                        erforscht. Das so aufbereitete Geschichtswissen lag bislang vor allem in Form analoger Texte
                        vor. OGT trägt somit zur Digitalisierung und interaktiven Dissemination dieser Forschungsdaten
                        bei und bietet Tools, um diese auswerten.
                    </p>

                    <h2 class="text-h6 text-md-h5 mb-3">
                        Daten zum Gestapoterror miteinander vernetzen
                    </h2>
                    <p>
                        Insbesondere werden Datensätze zu historischen Stätten, Institutionen und Ereignissen verlinkt,
                        die in frei zugänglichen Datenbanken wie dem Digitalen Denkmalatlas Niedersachsen, Wikidata,
                        Wikimedia Commons, Pelagios Commons und Open Street Map hinterlegt sind. Hinzu kommen außerdem
                        Objekte und Beschreibungen auf den Webseiten der Stiftung niedersächsische Gedenkstätten und
                        einzelne niedersächsische Erinnerungsorte.
                    </p>

                    <h2 class="text-h6 text-md-h5 mb-3">
                        „Citizen Science“
                    </h2>
                    <p>
                        Interessierte können kontinuierlich weitere Informationen in die Plattform einpflegen. Das ist
                        Partizipation im Sinne von „Citizen Science“ – oder Bürgerwissenschaft –, wo Laien die
                        Möglichkeit haben, sich in wissenschaftliche Projekte einzubringen. Die Plattform kann darüber
                        hinaus auch in der schulischen und außerschulischen Bildungsarbeit genutzt werden.
                    </p>
                    <p>
                        Weitere Informationen zum Thema Mitforschen finden sich hier:
                        <span class="text-no-wrap">
                            &#10132;
                            <router-link to="/collaboration">Mitforschen</router-link>
                        </span>
                    </p>
                </section>

                <section id="teamAndPartners" class="mb-16">
                    <h1 class="text-h5 text-md-h4 mb-5">
                        Team & Partner:innen
                    </h1>
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
                    <h3 class="text-subtitle-2 text-md-h6 mb-1">
                        Technische Informationsbibliothek (TIB) – Open Science Lab (OSL)
                    </h3>
                    <p>
                        <span class="font-weight-bold">Lambert Heller</span>
                        / Leitung Open Science Lab der TIB
                        <br>
                        <a href="https://vivo.tib.eu/fis/individual/n0000-0003-0232-7085" target="_blank">
                            https://vivo.tib.eu/fis/individual/n0000-0003-0232-7085
                            <v-icon small class="vertical-align-top">mdi-open-in-new</v-icon>
                        </a>
                    </p>
                    <p>
                        <span class="font-weight-bold">Prof. Dr. Ina Blümel</span>
                        / Projektleitung
                        <br>
                        <a href="https://vivo.tib.eu/fis/individual/n0000-0002-3075-7640" target="_blank">
                            https://vivo.tib.eu/fis/individual/n0000-0002-3075-7640
                            <v-icon small class="vertical-align-top">mdi-open-in-new</v-icon>
                        </a>
                        <br>
                        <email encryptedEmail="job/cmvfnfm" :showEmail=true></email>
                    </p>
                    <p>
                        <span class="font-weight-bold">Lisa Groh-Trautmann</span>
                        (M.A. Geschichtswissenschaften) / Wissenschaftliche Mitarbeiterin, Projektkoordination &
                        Recherche
                        <br>
                        <email encryptedEmail="mjtb/hspi" :showEmail=true></email>
                    </p>
                    <p>
                        <span class="font-weight-bold">Nils Casties</span>
                        / Webentwicklung & Projektkoordination
                        <br>
                        <email encryptedEmail="ojmt/dbtujft" :showEmail=true></email>
                    </p>
                    <p>
                        <span class="font-weight-bold">Lucia Sohmen</span>
                        / Wissenschaftliche Mitarbeiterin
                        <br>
                        <a href="https://vivo.tib.eu/fis/individual/n0000-0002-2593-8754" target="_blank">
                            https://vivo.tib.eu/fis/individual/n0000-0002-2593-8754
                            <v-icon small class="vertical-align-top">mdi-open-in-new</v-icon>
                        </a>
                    </p>
                    <p>
                        <span class="font-weight-bold">Nadine Klöver</span>
                        / Grafik-Design
                        <br>
                        <a href="https://nadinekloever.com" target="_blank">
                            https://nadinekloever.com
                            <v-icon small class="vertical-align-top">mdi-open-in-new</v-icon>
                        </a>
                    </p>
                    <p>
                        <span class="font-weight-bold">Tobias Mielich</span>
                        / Studentischer Mitarbeiter, Fachmaster Geschichte (Master of Arts)
                    </p>

                    <h2 class="text-h6 text-md-h5 mb-3">
                        Partner:innen
                    </h2>
                    <h3 class="text-subtitle-2 text-md-h6 mb-1">
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
                    <p>
                        <span class="font-weight-bold">Dr. Elke Gryglewski</span>
                        <br>
                        Geschäftsführung Stiftung niedersächsische Gedenkstätten
                        <br>
                        Leitung der Gedenkstätte Bergen-Belsen
                    </p>
                    <p>
                        <span class="font-weight-bold">Dr. Rolf Keller</span>
                        <br>
                        Leitung Gedenkstättenförderung Niedersachsen
                    </p>
                    <p>
                        <span class="font-weight-bold">Marc Ellinghaus</span>
                        <br>
                        Leitung Bildung und Begegnung der Gedenkstätte Bergen-Belsen
                    </p>
                    <p>
                        <span class="font-weight-bold">Stephanie Billib</span>
                        <br>
                        Presse- und Öffentlichkeitsarbeit
                    </p>
                    <p>
                        <span class="font-weight-bold">Dr. Janine Doerry</span>
                        <br>
                        Wissenschaftliche Mitarbeiterin
                    </p>
                    <p>
                        <span class="font-weight-bold">Juliane Hummel M.A.</span>
                        <br>
                        Wissenschaftliche Mitarbeiterin
                    </p>
                    <p>
                        <span class="font-weight-bold">Silke Petry M.A.</span>
                        <br>
                        Wissenschaftliche Mitarbeiterin
                    </p>

                    <h3 class="text-subtitle-2 text-md-h6 mb-1">
                        Gedenkstätten Gestapokeller und Augustaschacht
                    </h3>
                    <v-row class="mb-4" justify="start">
                        <v-col style="max-width: 185px">
                            <a href="https://gedenkstaetten-augustaschacht-osnabrueck.de/augustaschacht"
                               target="_blank"
                            >
                                <v-img
                                    alt="Logo der Gedenkstätte Augustaschacht"
                                    contain
                                    max-height="64px"
                                    :src="this.$ogtGlobals.proxyPath + '/images/logo-augustaschacht.svg'"
                                ></v-img>
                            </a>
                        </v-col>
                        <v-col style="max-width: 185px">
                            <a href="https://gedenkstaetten-augustaschacht-osnabrueck.de/gestapokeller" target="_blank">
                                <v-img
                                    alt="Logo der Gedenkstätte Gestapokeller"
                                    contain
                                    max-height="64px"
                                    :src="this.$ogtGlobals.proxyPath + '/images/logo-gestapokeller.svg'"
                                ></v-img>
                            </a>
                        </v-col>
                    </v-row>
                </section>

                <section id="presentationsAndPublications" class="mb-16">
                    <h1 class="text-h5 text-md-h4 mb-5">
                        Projektpräsentationen & Publikationen
                    </h1>
                    <h2 class="text-h6 text-md-h5 mb-3">
                        Präsentationen
                    </h2>
                    <p>
                        03.07.2022
                        <br>
                        <a href="https://zenodo.org/record/6817273" target="_blank">
                            Überwachung und Terror – die Gestapo als Instrument des NS-Regimes
                            <v-icon small class="vertical-align-top">mdi-open-in-new</v-icon>
                        </a>
                    </p>
                    <p>
                        22.09.2021
                        <br>
                        <a href="https://zenodo.org/record/5589811" target="_blank">
                            Projektpräsentation zur Tagung „Digital Memory – Digital History – Digital Mapping.
                            Transformationen von Erinnerungskulturen und Holocaust-Education“
                            <v-icon small class="vertical-align-top">mdi-open-in-new</v-icon>
                        </a>
                    </p>

                    <h2 class="text-h6 text-md-h5 mb-3">
                        Publikationen
                    </h2>
                    <p>
                        <a href="https://www.stiftung-ng.de/fileadmin/dateien/Stiftung/ueber_uns/Jahresberichte/Jahresbericht_SNG_2020_Web.pdf"
                           target="_blank"
                        >
                            Stiftung niedersächsische Gedenkstätten – Jahresbericht 2020
                            <v-icon small class="vertical-align-top">mdi-open-in-new</v-icon>
                        </a>
                        <br>
                        <a href="https://projects.tib.eu/ogt-orte-des-gestapoterrors/projekt/"
                           target="_blank"
                        >
                            OGT Projektwebsite der TIB
                            <v-icon small class="vertical-align-top">mdi-open-in-new</v-icon>
                        </a>
                        <br>
                        <a href="https://www.tib.eu/de/forschung-entwicklung/projektuebersicht/projektsteckbrief/ogt-orte-des-gestapoterrors"
                           target="_blank"
                        >
                            Projektprofil
                            <v-icon small class="vertical-align-top">mdi-open-in-new</v-icon>
                        </a>
                        <br>
                        <a href="https://zenodo.org/record/4313236" target="_blank">
                            Auszug aus dem Projektantrag
                            <v-icon small class="vertical-align-top">mdi-open-in-new</v-icon>
                        </a>
                    </p>

                    <h2 class="text-h6 text-md-h5 mb-3">
                        Presseberichte
                    </h2>
                    <p>
                        14.09.2020
                        <br>
                        BuB – Forum Bibliothek und Information –
                        <a href="https://www.b-u-b.de/detail/verbrechen-der-gestapo-digital-sichtbar-machen"
                           target="_blank"
                        >
                            Verbrechen der Gestapo digital sichtbar machen
                            <v-icon small class="vertical-align-top">mdi-open-in-new</v-icon>
                        </a>
                    </p>
                    <p>
                        25.08.2020
                        <br>
                        TIB-Pressemitteilung –
                        <a href="https://www.tib.eu/de/die-tib/neuigkeiten-und-termine/aktuelles/detail/spuren-der-vergangenheit-verbrechen-der-gestapo-digital-sichtbar-machen"
                           target="_blank"
                        >
                            Spuren der Vergangenheit: Verbrechen der Gestapo digital sichtbar machen
                            <v-icon small class="vertical-align-top">mdi-open-in-new</v-icon>
                        </a>
                    </p>
                </section>
                <br><br>
            </v-col>
        </v-row>

    </v-container>
</template>

<script>
import Email from './helpers/Email';
import TableOfContentsSidebar from './navigation/TableOfContentsSidebar';

export default {
    name: 'Project',
    props: ['isMenuDisplayed'],
    components: {Email, TableOfContentsSidebar},
    data() {
        return {
            freeClientWidth: document.documentElement.clientWidth,
            headings: [
                {
                    href: '#researchObjectives',
                    label: 'Forschungsfragen & Projektziele',
                },
                {
                    href: '#teamAndPartners',
                    label: 'Team & Partner:innen',
                },
                {
                    href: '#presentationsAndPublications',
                    label: 'Projektpräsentationen & Publikationen',
                },
            ],
        };
    },
    created() {
        window.addEventListener('resize', this.setFreeClientWidth);
    },
    destroyed() {
        window.removeEventListener('resize', this.setFreeClientWidth);
    },
    mounted() {
        this.setFreeClientWidth();
    },
    methods: {
        /**
         * Determine width of free space between content and client border.
         *
         * @param event
         */
        setFreeClientWidth(event) {
            const container = document.getElementById('container');

            this.freeClientWidth = (document.documentElement.clientWidth - container.clientWidth) / 2;
        },
    },
};
</script>

<style scoped>
.font-family-special-elite {
    font-family: "Special Elite" !important;
}

.hyphens-auto {
    hyphens: auto;
}

/* space for opened navigation sidebar to avoid that navigation sidebar covers content */
.pl-391 {
    padding-left: 391px;
}

/* for external link icon */
.vertical-align-top {
    vertical-align: top;
}
</style>