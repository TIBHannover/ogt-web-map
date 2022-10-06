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
            <!-- space reserved for opened navigation menu -->
            <v-col class="hidden-sm-and-down" style="min-width: 375px; max-width: 375px"></v-col>

            <v-col class="col-12 col-sm-2 order-sm-last" style="min-width: 300px">
                <table-of-contents-sidebar :headings="headings"></table-of-contents-sidebar>
            </v-col>

            <!-- scrollable content -->
            <v-col class="hyphens-auto text-justify" lang="de">
                <section id="researchObjectives" class="mt-3 mb-16">
                    <h1 class="text-h5 text-md-h4 mb-5">
                        Forschungsfragen & Projektziele
                    </h1>
                    <h2 class="text-h6 text-md-h5 mb-3">
                        Gestapo.Terror.Orte in Niedersachsen 1933–1945
                    </h2>
                    <h3 class="text-subtitle-2 text-md-h6 mb-1">
                        Spuren der Vergangenheit: Verbrechen der Gestapo digital sichtbar machen
                    </h3>
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
                    <blockquote class="blockquote">
                        „Die Gestapo war mit besonderer Macht ausgestattet: Sie konnte eigenmächtig Strafen verhängen
                        und vollstrecken, sie folterte Verdächtige und tötete Gefangene“, betont Dr. Jens-Christian
                        Wagner, Geschäftsführer der Stiftung niedersächsische Gedenkstätten.
                    </blockquote>
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
                    <blockquote class="blockquote">
                        „Standorte und Infrastruktur der Gestapo sind neue Untersuchungsfelder. Bisher hatte die
                        Forschung vor allem Personal, Aufgaben und Organisation der Gestapo im Blick“, erläutert Dr.
                        Janine Doerry.
                    </blockquote>
                    <p>
                        Als Mitarbeiterin der Gedenkstätten Gestapokeller und Augustaschacht e. V. aus Osnabrück hat sie
                        die Antragsteller beraten.
                    </p>

                    <h2 class="text-h6 text-md-h5 mb-3">
                        Geschichtliches Erbe digital zugänglich machen
                    </h2>
                    <p>
                        Anhand von Standorten und Infrastruktur werden nicht nur die Tätigkeit der Gestapo erforscht,
                        sondern auch das Wissen über deren Verbrechen und die spätere Erinnerung daran digital
                        zugänglich gemacht. Als kulturelles Erbe Niedersachsens werden dabei insbesondere
                        Gedenkstättensammlungen, themenspezifische Archivbestände und Baudenkmale erschlossen und
                        erforscht. Die während der NS-Zeit mehrfach wechselnden Standorte der Gestapo und die heutigen
                        Erinnerungsorte werden in der vierdimensionalen digitalen Landkarte – mit orts- und
                        zeitbezogenen Daten – dargestellt.
                    </p>
                    <blockquote class="blockquote">
                        „Wir als TIB sind für die digitale Erschließung und Aufbereitung der Daten zuständig und werden
                        dafür sorgen, dass sie frei zugänglich sind und damit beispielsweise für die Bildungsarbeit oder
                        für weitere Forschung verwendet werden können“ , erklärt Prof. Dr. Ina Blümel vom Open Science
                        Lab (OSL) der TIB, wo das Projekt angesiedelt ist.
                    </blockquote>

                    <h2 class="text-h6 text-md-h5 mb-3">
                        Daten zum Gestapoterror miteinander vernetzen
                    </h2>
                    <blockquote class="blockquote">
                        „Die im Projekt entwickelte digitale Landkarte bezieht neben den neuen Forschungsergebnissen
                        auch Informationen zu digitalen Objekten ein, die bereits an anderer Stelle existieren“ , so
                        Prof. Dr. Blümel.
                    </blockquote>
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
                    <blockquote class="blockquote">
                        „Jenseits des Klassenzimmers können hier neben den klassischen Lernmethoden auch lokale
                        Gedenkorte einbezogen werden. Hierzu finden gezielte Weiterbildungsmaßnahmen für Lehrerinnen und
                        Lehrer, Mitarbeitende von Gedenkstätten sowie andere Vermittlungspersonen statt“, sagt Lambert
                        Heller, Leiter des OSL.
                    </blockquote>
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
                    <h3 class="text-subtitle-2 text-md-h6 mb-1">
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
                            &nbsp;– Projektleitung –
                            <email encryptedEmail="job/cmvfnfm"></email>
                        </li>
                        <li>
                            Lisa Groh-Trautmann (M.A. Geschichtswissenschaften) – Wissenschaftliche Mitarbeiterin,
                            Projektkoordination & Recherche –
                            <email encryptedEmail="mjtb/hspi"></email>
                        </li>
                        <li>
                            Nils Casties – Webentwicklung & Projektkoordination –
                            <email encryptedEmail="ojmt/dbtujft"></email>
                        </li>
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
                    <ul class="mb-4">
                        <li>
                            Dr. Elke Gryglewski – Geschäftsführung Stiftung niedersächsische Gedenkstätten | Leitung der
                            Gedenkstätte Bergen-Belsen
                        </li>
                        <li>Dr. Rolf Keller – Leitung Gedenkstättenförderung Niedersachsen</li>
                        <li>Marc Ellinghaus – Leitung Bildung und Begegnung der Gedenkstätte Bergen-Belsen</li>
                        <li>Stephanie Billib – Presse- und Öffentlichkeitsarbeit</li>
                        <li>Dr. Janine Doerry – Wissenschaftliche Mitarbeiterin</li>
                        <li>Juliane Hummel M.A. – Wissenschaftliche Mitarbeiterin</li>
                        <li>Silke Petry M.A. – Wissenschaftliche Mitarbeiterin</li>
                    </ul>

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
                    <ul class="mb-4">
                        <li>
                            03.07.2022 –
                            <a href="https://zenodo.org/record/6817273" target="_blank">
                                Überwachung und Terror – die Gestapo als Instrument des NS-Regimes
                            </a>
                        </li>
                        <li>
                            22.09.2021 –
                            <a href="https://zenodo.org/record/5589811" target="_blank">
                                Projektpräsentation zur Tagung „Digital Memory – Digital History – Digital Mapping.
                                Transformationen von Erinnerungskulturen und Holocaust-Education“
                            </a>
                        </li>
                    </ul>

                    <h2 class="text-h6 text-md-h5 mb-3">
                        Publikationen
                    </h2>
                    <ul class="mb-4">
                        <li>
                            <a href="https://www.stiftung-ng.de/fileadmin/dateien/Stiftung/ueber_uns/Jahresberichte/Jahresbericht_SNG_2020_Web.pdf"
                               target="_blank"
                            >
                                Stiftung niedersächsische Gedenkstätten – Jahresbericht 2020
                            </a>
                        </li>
                        <li>
                            <a href="https://projects.tib.eu/ogt-orte-des-gestapoterrors/projekt/"
                               target="_blank"
                            >
                                OGT Projektwebsite der TIB
                            </a>
                        </li>
                        <li>
                            <a href="https://www.tib.eu/de/forschung-entwicklung/projektuebersicht/projektsteckbrief/ogt-orte-des-gestapoterrors"
                               target="_blank"
                            >
                                Projektprofil
                            </a>
                        </li>
                        <li>
                            <a href="https://zenodo.org/record/4313236" target="_blank">
                                Auszug aus dem Projektantrag
                            </a>
                        </li>
                    </ul>
                </section>

                <section id="pressReports" class="mb-16">
                    <h1 class="text-h5 text-md-h4 mb-5">
                        Presseberichte
                    </h1>
                    <ul class="mb-4">
                        <li>
                            14.09.2020 – BuB – Forum Bibliothek und Information –
                            <a href="https://b-u-b.de/verbrechen-der-gestapo-digital-sichtbar-machen/#intro"
                               target="_blank"
                            >
                                Verbrechen der Gestapo digital sichtbar machen
                            </a>
                        </li>
                        <li>
                            25.08.2020 – TIB-Pressemitteilung –
                            <a href="https://www.tib.eu/de/die-tib/neuigkeiten-und-termine/aktuelles/detail/spuren-der-vergangenheit-verbrechen-der-gestapo-digital-sichtbar-machen"
                               target="_blank"
                            >
                                Spuren der Vergangenheit: Verbrechen der Gestapo digital sichtbar machen
                            </a>
                        </li>
                    </ul>
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
    components: {Email, TableOfContentsSidebar},
    data() {
        return {
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
                {
                    href: '#pressReports',
                    label: 'Presseberichte',
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