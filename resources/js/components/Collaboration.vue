<template>
    <v-container id="container" class="mb-110">

        <!-- header -->
        <v-row :style="{
            'padding-left': (freeClientWidth < 75 && ! isMenuDisplayed) ? ((75 - freeClientWidth) + 'px') : 0
        }">
            <v-col>
                <h1 class="font-family-special-elite font-weight-bold hyphens-auto py-4 text-h4 text-md-h3" lang="de">
                    Mitforschen
                </h1>
            </v-col>
        </v-row>

        <v-row>
            <v-col class="col-12 col-sm-2 order-sm-last" style="min-width: 310px">
                <table-of-contents-sidebar :headings="headings"></table-of-contents-sidebar>
            </v-col>

            <!-- scrollable content -->
            <v-col class="hyphens-auto" lang="de">
                <section id="citizenScience">
                    <h1 class="font-weight-medium text-h5 text-md-h4 mb-5"
                        :class="{ 'font-size-29': $vuetify.breakpoint.mdAndUp }"
                    >
                        "Gestapo.Terror.Orte" - Ein Citizen Science Projekt
                    </h1>
                    <p>
                        Wo befanden sich Orte des Gestapoterrors in Niedersachsen? Welche Gebäude nutzte die Politische
                        Polizei während des Nationalsozialismus? Wo waren die Dienstsitze, wo die Haftstätten der
                        Geheimen Staatspolizei? Wie sehen die Orte des Gestapoterrors heute aus? Wie wurden die Orte
                        nach 1945 genutzt? Welche Berichte können uns den Terror der Gestapo nachvollziehen lassen? Wer
                        waren die Täter:innen? Was haben die Täter:innen nach 1945 gemacht? Wurden ihre Taten geahndet?
                        Wer wurde von der Gestapo verfolgt? Wie erinnern wir heute an die Verbrechen der Gestapo?
                    </p>
                    <p>
                        Nur durch die Mithilfe vieler können Daten zu diesen Forschungsfrage erhoben werden.
                        „Gestapo.Terror.Orte“ ist aus diesem Grund ein Citizen Science Projekt. Alle Interessierten
                        können sich am Forschungs- und Datenerhebungsprozess beteiligen und so dazu beitragen, dass das
                        Wissen über die Geschichte der Gestapo in Niedersachsen anwächst und digital zugänglich wird.
                        Zudem kann so die Erinnerung an die Menschen, die unter dem Terror leiden mussten, erhalten
                        bleiben.
                    </p>
                </section>

                <v-divider class="my-6"></v-divider>

                <section id="examples">
                    <h1 class="font-weight-medium text-h5 text-md-h4 mb-5"
                        :class="{ 'font-size-29': $vuetify.breakpoint.mdAndUp }"
                    >
                        Beispiele
                    </h1>
                    <p>
                        Die interaktive Karte befindet sich im Aufbau. Folgende Datensätze sind weitestgehend
                        ausgearbeitet und können als Beispiele dienen:
                    </p>
                    <p v-for="example in examples" v-if="$ogtGlobals.isTestingEnv || example.group != 'Ereignisse'">
                        <span class="font-weight-bold">{{ example.group }}</span>
                        <br>
                        <span v-for="location in example.locations">
                            &#10132;
                            <router-link :to="{ name: 'map', query: { qId: location.qId } }">
                                {{ location.label }}
                            </router-link>
                            <br>
                        </span>
                    </p>
                </section>

                <v-divider class="my-6"></v-divider>

                <section id="coResearch">
                    <h1 class="font-weight-medium text-h5 text-md-h4 mb-5"
                        :class="{ 'font-size-29': $vuetify.breakpoint.mdAndUp }"
                    >
                        Wie kann ich mitforschen?
                    </h1>
                    <v-expansion-panels focusable>
                        <v-expansion-panel>
                            <v-expansion-panel-header class="font-weight-bold">
                                Schulische und außerschulische Jugendgruppen
                            </v-expansion-panel-header>
                            <v-expansion-panel-content>
                                <p>
                                    Zur Karte beitragen können Klassen der Stufen Sek I und Sek II im Rahmen des
                                    Politiks-, Ethiks-, Geschichts- oder Informatikunterrichts. Es bieten sich darüber
                                    hinaus auch Stufenübergreifende AG oder Projekttage an. Auch außerschulische
                                    Jugendgruppen steht eine Teilnahme offen.
                                </p>
                                <p>
                                    Wie ist eine Teilnahme möglich?
                                </p>
                                <p>
                                    Hierfür gibt es verschiedene Möglichkeiten:
                                </p>
                                <p>
                                    <span class="font-weight-bold">Selbstständige Teilnahme</span>
                                    <br>
                                    <span>
                                        Lehrende können Projekteinheiten selbstständig vorbereiten. Zur Klärung von
                                        Fragen kann die Projektkoordination unter der E-Mailadresse
                                        <email encryptedEmail="phu/qspkfdu" :showEmail=true :showIcon=true></email>
                                        erreicht werden.
                                    </span>
                                </p>
                                <p>
                                    <span class="font-weight-bold">Kooperation "Remember Me!"</span>
                                    <br>
                                    <span>
                                        Im Rahmen des Projektes „Remember Me!“ werden kostenlos betreute Workshops
                                        angeboten. Diese können nach Absprache vor Ort in der Schule, an
                                        ausgesuchten Gedenkorten in der Nähe oder virtuell stattfinden. Weitere
                                        Informationen zu "Remember Me!" und Teilnahmemöglichkeiten finden sich hier:
                                        <a class="text-no-wrap"
                                           href="https://projects.tib.eu/rememberme-data/ueber-das-projekt/"
                                           target="_blank"
                                        >
                                            Über das Projekt - Remember Me
                                            <v-icon small class="vertical-align-top">mdi-open-in-new</v-icon>
                                        </a>
                                    </span>
                                </p>
                                <p>
                                    Einige Projektideen finden sich hier:
                                    <span class="text-no-wrap">
                                        &#10132;
                                        <a class="hyphens-auto" href="#projectIdeas" lang="de">Projektideen</a>
                                    </span>
                                </p>
                            </v-expansion-panel-content>
                        </v-expansion-panel>

                        <v-expansion-panel>
                            <v-expansion-panel-header class="font-weight-bold">
                                Gedenkstätten
                            </v-expansion-panel-header>
                            <v-expansion-panel-content>
                                <p>
                                    Niedersächsische Gedenkstätten nehmen eine entscheidende Rolle in der Erforschung
                                    und Vermittlung des Themas Gestapoterror in Niedersachsen ein. Das Projekt
                                    Gestapo.Terror.Orte möchte diese Arbeit sichtbar machen. Informationen zur
                                    Geschichte und Angeboten von Gedenkstätten werden auf der interaktiven Landkarte
                                    angezeigt und können von den Gedenkstätten ergänzt werden. Die Projektkoordination
                                    steht über die E-Mailadresse
                                    <email encryptedEmail="phu/qspkfdu" :showEmail=true :showIcon=true></email>
                                    für Absprachen zur Verfügung.
                                </p>
                                <p>
                                    Eine Zusammenarbeit mit Gedenkstätten ist zudem im Rahmen von Projekten mit
                                    schulischen und außerschulischen Jugendgruppen stattfinden. Einige Projektideen
                                    finden sich hier:
                                    <span class="text-no-wrap">
                                        &#10132;
                                        <a class="hyphens-auto" href="#projectIdeas" lang="de">Projektideen</a>
                                    </span>
                                </p>
                                <p>
                                    Des Weiteren können von Gedenkstätten erhobene Forschungsdaten eingelesen werden.
                                </p>
                            </v-expansion-panel-content>
                        </v-expansion-panel>

                        <v-expansion-panel>
                            <v-expansion-panel-header class="font-weight-bold">
                                Geschichtswissenschaftler*innen
                            </v-expansion-panel-header>
                            <v-expansion-panel-content>
                                <p>
                                    Forschungsdaten können auf "Gestapo.Terror.Orte" veröffentlicht und mit weiteren
                                    Daten in Verbindung gesetzt werden. Die Projektkoordination steht über die
                                    E-Mailadresse
                                    <email encryptedEmail="phu/qspkfdu" :showEmail=true :showIcon=true></email>
                                    für Absprachen zur Verfügung.
                                </p>
                            </v-expansion-panel-content>
                        </v-expansion-panel>

                        <v-expansion-panel>
                            <v-expansion-panel-header class="font-weight-bold">
                                Geschichtsvereine, Initiativen, Einzelpersonen
                            </v-expansion-panel-header>
                            <v-expansion-panel-content>
                                <p>
                                    Geschichtsvereine, Initiativen, Einzelpersonen können Daten zur Geschichte der
                                    Gestapo erheben und diese auf der interaktiven Karte anzeigen lassen.
                                </p>
                                <p>
                                    Die Projektkoordination steht über die E-Mailadresse
                                    <email encryptedEmail="phu/qspkfdu" :showEmail=true :showIcon=true></email>
                                    zur Beratung zur Verfügung.
                                </p>
                                <p>
                                    Einige Projektideen finden sich hier:
                                    <span class="text-no-wrap">
                                        &#10132;
                                        <a class="hyphens-auto" href="#projectIdeas" lang="de">Projektideen</a>
                                    </span>
                                </p>
                            </v-expansion-panel-content>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </section>

                <v-divider class="mb-6 mt-8"></v-divider>

                <section id="projectIdeas">
                    <h1 class="font-weight-medium text-h5 text-md-h4 mb-5"
                        :class="{ 'font-size-29': $vuetify.breakpoint.mdAndUp }"
                    >
                        Projektideen
                    </h1>
                    <p>
                        Hier findet sich eine Auswahl verschiedener Projektideen, mittels derer zur interaktiven Karte
                        „Gestapo.Terror.Orte“ beigetragen werden kann. Sie dienen lediglich als Anregung – viele weitere
                        Projekte sind möglich.
                    </p>

                    <h2 class="font-weight-medium text-h6 text-md-h5">
                        Spuren der Geschichte in meiner Umgebung – ein Fotoprojekt
                    </h2>
                    <p>
                        Die auf „Gestapo.Terror.Orte“ vorzeichneten Orte des Gestapoterrors werden besucht und dort
                        Fotografien angefertigt, die anschließend auf
                        <a class="text-no-wrap" href="https://commons.wikimedia.org" target="_blank">
                            Wikimedia Commons
                            <v-icon small class="vertical-align-top">mdi-open-in-new</v-icon>
                        </a>
                        hochgeladen und schließlich auf „Gestapo.Terror.Orte“ angezeigt werden.
                    </p>

                    <h2 class="font-weight-medium text-h6 text-md-h5">
                        Biografie Projekt
                    </h2>
                    <p>
                        Biografien von Geschädigten oder Gestapo Mitarbeiter:innen werden recherchiert. Die gewonnenen
                        Daten werden mit Hilfe von
                        <a class="text-no-wrap" href="https://www.wikidata.org" target="_blank">
                            Wikidata
                            <v-icon small class="vertical-align-top">mdi-open-in-new</v-icon>
                        </a>
                        der Allgemeinheit zur Verfügung gestellt. Diese Daten werden anschließend auf
                        „Gestapo.Terror.Orte“ veröffentlicht.
                    </p>

                    <h2 class="font-weight-medium text-h6 text-md-h5">
                        Archivfunde
                    </h2>
                    <p>
                        In örtliche Archive können Archivalien recherchiert werden, die mit dem Gestapoterror in
                        Verbindung stehen. Nach Absprache mit den Archiven können die Archivalien digitalisiert und auf
                        „Gestapo.Terror.Orte“ veröffentlicht werden.
                    </p>
                </section>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
import Email from './helpers/Email';
import TableOfContentsSidebar from './navigation/TableOfContentsSidebar';

export default {
    name: 'Collaboration',
    props: ['isMenuDisplayed'],
    components: {Email, TableOfContentsSidebar},
    data() {
        return {
            examples: [
                {
                    group: 'Gestapoterrororte',
                    locations: [
                        {
                            label: 'Staatspolizeileitstelle: Staatspolizeileitstelle Hannover',
                            qId: 'Q106625639',
                        },
                        {
                            label: 'Staatspolizeistelle: Staatspolizeistelle Osnabrück',
                            qId: 'Q106625285',
                        },
                        {
                            label: 'Außendienststelle: Staatspolizeiaußenstelle Osnabrück',
                            qId: 'Q106625716',
                        },
                    ],
                },
                {
                    group: 'Ereignisse',
                    locations: [
                        {
                            label: 'Verfolgung von Hilde Reinköster und Josef Grzeskowiak (Melle)',
                            qId: 'Q111009615',
                        },
                        {
                            label: 'Erschießungen auf dem Seelhorster Friedhof 1945 (Hannover)',
                            qId: 'Q108733201',
                        },
                    ],
                },
                {
                    group: 'Täter:innen',
                    locations: [
                        {
                            label: 'Friedrich Kicker',
                            qId: 'Q111050692',
                        },
                    ],
                },
                {
                    group: 'Erinnerungsorte',
                    locations: [
                        {
                            label: 'Gedenkstätte Augustaschacht (Osnabrück)',
                            qId: 'Q106104164',
                        },
                        {
                            label: 'Gedenkstätte Gestapokeller (Osnabrück)',
                            qId: 'Q106626141',
                        },
                    ],
                },
            ],
            headings: [
                {
                    href: '#citizenScience',
                    label: '"Gestapo.Terror.Orte" - Ein Citizen Science Projekt',
                },
                {
                    href: '#examples',
                    label: 'Beispiele',
                },
                {
                    href: '#coResearch',
                    label: 'Wie kann ich mitforschen?',
                },
                {
                    href: '#projectIdeas',
                    label: 'Projektideen',
                },
            ],
            freeClientWidth: document.documentElement.clientWidth,
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
