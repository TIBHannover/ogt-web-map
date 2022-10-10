<template>
    <v-container class="grey lighten-3" fluid>

        <!-- header -->
        <v-row>
            <v-col>
                <h1 class="text-h4 text-md-h3 text-center py-4">
                    Mitforschen
                </h1>
            </v-col>
        </v-row>

        <v-row>
            <!-- space reserved for opened navigation menu -->
            <v-col class="hidden-sm-and-down" style="min-width: 375px; max-width: 375px"></v-col>

            <v-col class="col-12 col-sm-2 order-sm-last" style="min-width: 300px">
                <table-of-contents-sidebar :headings="headings"></table-of-contents-sidebar>
            </v-col>

            <!-- scrollable content -->
            <v-col class="hyphens-auto text-justify" lang="de">
                <section id="citizenScience" class="mt-3 mb-16">
                    <h1 class="text-h5 text-md-h4 mb-5">
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

                <section id="examples" class="mt-3 mb-16">
                    <h1 class="text-h5 text-md-h4 mb-5">
                        Beispiele
                    </h1>
                    <p>
                        Die interaktive Karte befindet sich im Aufbau. Folgende Datensätze sind weitestgehend
                        ausgearbeitet und können als Beispiele dienen:
                    </p>
                    <ul class="mb-4">
                        <li>
                            Gestapoterrororte
                            <ul>
                                <li>Staatspolizeileitstelle: Staatspolizeileitstelle Hannover</li>
                                <li>Staatspolizeistelle: Staatspolizeistelle Osnabrück</li>
                                <li>Außendienststelle: Staatspolizeiaußenstelle Osnabrück</li>
                            </ul>
                        </li>
                        <li>
                            Ereignisse
                            <ul>
                                <li>Verfolgung von Hilde Reinköster und Josef Grzeskowiak (Melle)</li>
                                <li>Erschießungen auf dem Seelhorster Friedhof 1945 (Hannover)</li>
                            </ul>
                        </li>
                        <li>
                            Erinnerungsorte
                            <ul>
                                <li>Gedenkstätte Augustaschacht (Osnabrück)</li>
                                <li>Gedenkstätte Gestapokeller (Osnabrück)</li>
                            </ul>
                        </li>
                    </ul>
                </section>

                <section id="coResearch" class="mt-3 mb-16">
                    <h1 class="text-h5 text-md-h4 mb-5">
                        Wie kann ich mitforschen?
                    </h1>
                    <v-expansion-panels focusable inset>
                        <v-expansion-panel class="grey lighten-3">
                            <v-expansion-panel-header class="text-h6 text-md-h5">
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
                                <ul class="mb-4">
                                    <li>
                                        <p>Selbstständige Teilnahme</p>
                                        <p>
                                            Lehrende können Projekteinheiten selbstständig vorbereiten. Zur Klärung von
                                            Fragen kann die Projektkoordination unter der E-Mailadresse
                                            <email encryptedEmail="phu/qspkfdu" :showEmail=true></email>
                                            erreicht werden.
                                        </p>
                                    </li>
                                    <li>
                                        <p>Kooperation "Remember Me!"</p>
                                        <p>
                                            Im Rahmen des Projektes „Remember Me!“ werden kostenlos betreute Workshops
                                            angeboten. Diese können nach Absprache vor Ort in der Schule, an
                                            ausgesuchten Gedenkorten in der Nähe oder virtuell stattfinden. Weitere
                                            Informationen zu "Remember Me!" und Teilnahmemöglichkeiten finden sich hier:
                                            <a href="https://projects.tib.eu/rememberme-data/ueber-das-projekt/"
                                               target="_blank"
                                            >
                                                Über das Projekt - Remember Me
                                            </a>
                                        </p>
                                    </li>
                                </ul>
                                <p>
                                    Einige Projektideen finden sich hier:
                                    <a class="hyphens-auto" href="#projectIdeas" lang="de">Projektideen</a>
                                </p>
                            </v-expansion-panel-content>
                        </v-expansion-panel>

                        <v-expansion-panel class="grey lighten-3">
                            <v-expansion-panel-header class="text-h6 text-md-h5">
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
                                    <email encryptedEmail="phu/qspkfdu" :showEmail=true></email>
                                    für Absprachen zur Verfügung.
                                </p>
                                <p>
                                    Eine Zusammenarbeit mit Gedenkstätten ist zudem im Rahmen von Projekten mit
                                    schulischen und außerschulischen Jugendgruppen stattfinden. Einige Projektideen
                                    finden sich hier:
                                    <a class="hyphens-auto" href="#projectIdeas" lang="de">Projektideen</a>
                                </p>
                                <p>
                                    Des Weiteren können von Gedenkstätten erhobene Forschungsdaten eingelesen werden.
                                </p>
                            </v-expansion-panel-content>
                        </v-expansion-panel>

                        <v-expansion-panel class="grey lighten-3">
                            <v-expansion-panel-header class="text-h6 text-md-h5">
                                Geschichtswissenschaftler*innen
                            </v-expansion-panel-header>
                            <v-expansion-panel-content>
                                <p>
                                    Forschungsdaten können auf "Gestapo.Terror.Orte" veröffentlicht und mit weiteren
                                    Daten in Verbindung gesetzt werden. Die Projektkoordination steht über die
                                    E-Mailadresse
                                    <email encryptedEmail="phu/qspkfdu" :showEmail=true></email>
                                    für Absprachen zur Verfügung.
                                </p>
                            </v-expansion-panel-content>
                        </v-expansion-panel>

                        <v-expansion-panel class="grey lighten-3">
                            <v-expansion-panel-header class="text-h6 text-md-h5">
                                Geschichtsvereine, Initiativen, Einzelpersonen
                            </v-expansion-panel-header>
                            <v-expansion-panel-content>
                                <p>
                                    Geschichtsvereine, Initiativen, Einzelpersonen können Daten zur Geschichte der
                                    Gestapo erheben und diese auf der interaktiven Karte anzeigen lassen.
                                </p>
                                <p>
                                    Die Projektkoordination steht über die E-Mailadresse
                                    <email encryptedEmail="phu/qspkfdu" :showEmail=true></email>
                                    zur Beratung zur Verfügung.
                                </p>
                                <p>
                                    Einige Projektideen finden sich hier:
                                    <a class="hyphens-auto" href="#projectIdeas" lang="de">Projektideen</a>
                                </p>
                            </v-expansion-panel-content>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </section>

                <section id="projectIdeas" class="mt-3 mb-16">
                    <h1 class="text-h5 text-md-h4 mb-5">
                        Projektideen
                    </h1>
                    <p>
                        Hier findet sich eine Auswahl verschiedener Projektideen, mittels derer zur interaktiven Karte
                        „Gestapo.Terror.Orte“ beigetragen werden kann. Sie dienen lediglich als Anregung – viele weitere
                        Projekte sind möglich.
                    </p>

                    <h2 class="text-h6 text-md-h5 mb-3">
                        Spuren der Geschichte in meiner Umgebung – ein Fotoprojekt
                    </h2>
                    <p>
                        Die auf „Gestapo.Terror.Orte“ vorzeichneten Orte des Gestapoterrors werden besucht und dort
                        Fotografien angefertigt, die anschließend auf
                        <a href="https://commons.wikimedia.org" target="_blank">Wikimedia Commons</a>
                        hochgeladen und schließlich auf „Gestapo.Terror.Orte“ angezeigt werden.
                    </p>

                    <h2 class="text-h6 text-md-h5 mb-3">
                        Biografie Projekt
                    </h2>
                    <p>
                        Biografien von Geschädigten oder Gestapo Mitarbeiter:innen werden recherchiert. Die gewonnenen
                        Daten werden mit Hilfe von
                        <a href="https://www.wikidata.org" target="_blank">Wikidata</a>
                        der Allgemeinheit zur Verfügung gestellt. Diese Daten werden anschließend auf
                        „Gestapo.Terror.Orte“ veröffentlicht.
                    </p>

                    <h2 class="text-h6 text-md-h5 mb-3">
                        Archivfunde
                    </h2>
                    <p>
                        In örtliche Archive können Archivalien recherchiert werden, die mit dem Gestapoterror in
                        Verbindung stehen. Nach Absprache mit den Archiven können die Archivalien digitalisiert und auf
                        „Gestapo.Terror.Orte“ veröffentlicht werden.
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
    name: 'Collaboration',
    components: {Email, TableOfContentsSidebar},
    data() {
        return {
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
        };
    },
};
</script>

<style scoped>
.hyphens-auto {
    hyphens: auto;
}
</style>
