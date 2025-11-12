<template>
    <v-container id="container" class="mb-110">
        <v-row :style="{ 'padding-left': computedPadding }">
            <v-col>
                <h1
                    class="font-family-special-elite font-weight-bold hyphens-auto py-4 text-h4 text-md-h3"
                    lang="de"
                >
                    Lege einen neuen Eintrag an
                </h1>
            </v-col>
        </v-row>

        <v-row>
            <v-col>
                <div class="entry-info-container">
                    <div class="intro-section">
                        <h2 class="section-title">
                            Wie funktioniert der Prozess?
                        </h2>
                        <p>
                            Gestapo.Terror.Orte dokumentiert Orte, Personen und
                            Ereignisse zur Geschichte der Gestapo in
                            Niedersachsen.
                            <br />
                            Um die Integrität und Qualität unserer Daten zu
                            gewährleisten, werden alle Einträge vor der
                            Veröffentlichung geprüft.
                        </p>
                        <p>
                            Mit diesem Formular kannst du einen neuen Eintrag
                            für die Karte vorschlagen. Nach der Einreichung wird
                            dein Eintrag von unserem Team gesichtet und geprüft.
                            Bei positiver Bewertung wird der Eintrag
                            freigeschaltet und auf der Karte sichtbar.
                        </p>
                        <div class="important-note">
                            <v-icon color="warning" class="mr-2"
                                >mdi-alert</v-icon
                            >
                            <span
                                ><strong>Wichtig:</strong> Quellenangaben sind
                                verpflichtend für jeden Eintrag. Ohne
                                ausreichende wissenschaftliche Belege kann dein
                                Vorschlag nicht genehmigt werden.</span
                            >
                        </div>
                    </div>

                    <div class="requirements-section">
                        <h2 class="section-title">
                            Was benötigst du für einen Eintrag?
                        </h2>
                        <ul class="requirements-list">
                            <li>
                                <v-icon color="primary" class="mr-2"
                                    >mdi-file-document-outline</v-icon
                                >
                                <strong
                                    >Wissenschaftliche Informationen:</strong
                                >
                                <p>
                                    Gut recherchierte Fakten aus
                                    vertrauenswürdigen Quellen
                                    (wissenschaftliche Bücher, Archive,
                                    wissenschaftliche Artikel).
                                </p>
                            </li>
                            <li>
                                <v-icon color="primary" class="mr-2"
                                    >mdi-bookmark-outline</v-icon
                                >
                                <strong>Quellenangaben:</strong>
                                <p>
                                    Informationen zu deinen Quellen (Im Falle
                                    von Büchern: Autor, Titel, Jahr, Seite etc.
                                    Im Falle von Archivmaterial: Inventarnummer
                                    etc).
                                </p>
                            </li>
                            <li>
                                <v-icon color="primary" class="mr-2"
                                    >mdi-map-marker</v-icon
                                >
                                <strong>Geographische Daten:</strong>
                                <p>
                                    Genaue Standortinformationen oder
                                    bestmögliche Annäherung.
                                </p>
                            </li>
                            <li>
                                <v-icon color="primary" class="mr-2"
                                    >mdi-calendar-range</v-icon
                                >
                                <strong>Zeitliche Einordnung:</strong>
                                <p>
                                    Datum oder Zeitraum des Ereignisses bzw. der
                                    Existenz des Ortes.
                                </p>
                            </li>
                            <li>
                                <v-icon color="primary" class="mr-2"
                                    >mdi-image-outline</v-icon
                                >
                                <strong>Optional: Bildmaterial</strong>
                                <p>
                                    mit Angaben zur Quelle und Rechteinhaber.
                                    Die Bilder müssen bei &nbsp;
                                    <a
                                        href="https://commons.wikimedia.org/wiki/Main_Page"
                                        target="_blank"
                                        >Wikimedia Comons</a
                                    >
                                    &nbsp;hochgeladen werden.
                                </p>
                            </li>
                        </ul>

                        <div class="important-note">
                            <v-icon color="warning" class="mr-2"
                                >mdi-alert</v-icon
                            >
                            <span
                                ><strong>Wichtig:</strong> Diese Angaben müssen
                                nicht vollständig sein. Einzelne bekannte
                                Informationen sind wertvoll und können im
                                Nachgang noch geändert oder ergänzt
                                werden.</span
                            >
                        </div>
                    </div>

                    <div class="process-section">
                        <h2 class="section-title">Der Prozess im Überblick</h2>
                        <div class="process-timeline">
                            <div class="timeline-step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h3>Materialsammlung</h3>
                                    <p>
                                        Recherchiere Informationen aus Büchern,
                                        Archiven, wissenschaftlichen
                                        Online-Quellen und anderen
                                        vertrauenswürdigen Materialien.
                                    </p>
                                </div>
                            </div>
                            <div class="timeline-step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h3>Eintragstyp auswählen</h3>
                                    <p>
                                        Wähle den passenden Typ für deinen
                                        Eintrag:
                                    </p>
                                    <ul class="entry-types-list">
                                        <li>
                                            <strong
                                                >Dienst- und Haftstätten der
                                                Gestapo</strong
                                            >
                                            - Zu den Dienst- und Haftstätten der
                                            Gestapo zählen:<br />
                                            <ul>
                                                <li>
                                                    <b
                                                        >Staatspolizeileitstelle</b
                                                    >: Regionale Dienstellen der
                                                    Geheimen Staatspolizei
                                                </li>

                                                <li>
                                                    <b>Staatspolizeistelle</b>:
                                                    Regionale Dienstellen der
                                                    Geheimen Staatspolizei,
                                                    unterstehen einer
                                                    Staatspolizeileitstelle
                                                </li>
                                                <li>
                                                    <b>Außendienststelle</b>:
                                                    Lokale Zweigstellen einer
                                                    Staatspolizeistelle
                                                </li>
                                                <li>
                                                    <b>Gefängnis</b>:
                                                    Haftstätte, die von der
                                                    Gestapo (mit-) genutzt
                                                    wurde.
                                                </li>
                                                <li>
                                                    <b
                                                        >Erweitertes
                                                        Polizeigefängnis</b
                                                    >: Von der Gestapo
                                                    betriebenes Lager zur
                                                    kurzfristigen Inhaftierung.
                                                </li>
                                                <li>
                                                    <b>Arbeitserziehungslager</b
                                                    >: Von der Gestapo
                                                    eingerichtetes Lager, um
                                                    Zwangsarbeiter*innen und
                                                    andere als „arbeitsscheu“
                                                    geltende Personen zu
                                                    bestrafen.
                                                </li>
                                            </ul>
                                        </li>
                                        <li>
                                            <strong>Erinnerungsort</strong> -
                                            Physisch erlebbare Orte, die an die
                                            Verbrechen der Gestapo erinnern
                                        </li>
                                        <li>
                                            <strong>Ereignis</strong> - Von der
                                            Gestapo begangene Verbrechen wie
                                            bspw. der Inhaftierung einzelner
                                            Personen oder Personengruppen, aber
                                            auch die Ermordung von Menschen
                                        </li>
                                        <li>
                                            <strong>Person</strong> - Personen,
                                            die im Zusammenhang mit der Gestapo
                                            standen, etwa als Täter*innen oder
                                            als Geschädigte
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="timeline-step">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h3>Informationen eintragen</h3>
                                    <p>
                                        Fülle das Formular mit allen relevanten
                                        Informationen aus. Je detaillierter und
                                        präziser, desto besser.
                                    </p>
                                    <h3>Quellenangaben hinzufügen</h3>
                                    <p>
                                        Füge alle Quellen hinzu, aus denen du
                                        Informationen bezogen hast.
                                    </p>
                                    <h3>Eintrag einreichen</h3>
                                    <p>
                                        Sende deinen Antrag zur Prüfung ein. Du
                                        kannst auswählen, ob du per E-Mail über
                                        den Status informiert werden möchtest.
                                    </p>
                                </div>
                            </div>
                            <div class="timeline-step">
                                <div class="step-number">4</div>
                                <div class="step-content">
                                    <h3>Prüfung abwarten</h3>
                                    <p>
                                        Dein Eintrag wird von unserem Team
                                        geprüft. Dies kann einige Zeit in
                                        Anspruch nehmen. Du kannst den Status
                                        jederzeit über den Link in der
                                        Bestätigungsnachricht abrufen.
                                    </p>
                                </div>
                            </div>
                            <div class="timeline-step">
                                <div class="step-number">5</div>
                                <div class="step-content">
                                    <h3>Eintrag auf der Karte anschauen</h3>
                                    <p>
                                        Nach der Freischaltung wird dein Eintrag
                                        auf der Karte sichtbar und Teil unserer
                                        Dokumentation.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="action-section">
                        <h2 class="section-title">
                            Bereit, einen Eintrag zu erstellen?
                        </h2>
                        <div class="button-container">
                            <router-link
                                to="/neuer-eintrag"
                                class="entry-button"
                            >
                                <v-btn color="primary" size="large">
                                    Neuen Eintrag anlegen
                                    <v-icon right class="ml-2"
                                        >mdi-arrow-right</v-icon
                                    >
                                </v-btn>
                            </router-link>
                        </div>
                    </div>
                </div>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, inject } from "vue";
const isMobileView = ref(false);
const isMenuDisplayed = inject("isMenuDisplayed");

const freeClientWidth = ref(document.documentElement.clientWidth);

const setFreeClientWidth = () => {
    const container = document.getElementById("container");
    freeClientWidth.value =
        (document.documentElement.clientWidth - container.clientWidth) / 2;
};

const checkScreenSize = () => {
    isMobileView.value = window.innerWidth < 1200;
};

const computedPadding = computed(() => {
    return freeClientWidth.value < 75 && !isMenuDisplayed.value
        ? `${75 - freeClientWidth.value}px`
        : "0";
});

onMounted(() => {
    setFreeClientWidth();
    window.addEventListener("resize", setFreeClientWidth);
    window.addEventListener("resize", checkScreenSize);
});

onUnmounted(() => {
    window.removeEventListener("resize", setFreeClientWidth);
    window.removeEventListener("resize", checkScreenSize);
});
</script>

<style>
.font-family-special-elite {
    font-family: "Special Elite" !important;
}

.hyphens-auto {
    hyphens: auto;
}

.entry-info-container {
    max-width: 1000px;
}

.intro-section,
.requirements-section,
.process-section,
.action-section {
    margin-bottom: 40px;
    background-color: #fff;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.section-title {
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #2196f3;
    font-weight: bold;
    font-size: 1.5rem;
}

.important-note {
    margin-top: 20px;
    padding: 15px;
    background-color: rgba(255, 193, 7, 0.1);
    border-left: 4px solid #ffc107;
    border-radius: 4px;
    display: flex;
    align-items: center;
}

.requirements-list li {
    margin-bottom: 15px;
    display: flex;
    align-items: flex-start;
}
.requirements-list li strong {
    width: 200px;
    min-width: 200px;
}

.entry-types-list li {
    margin-bottom: 8px;
}

.entry-types-list li ul {
    margin-left: 15px;
}

.entry-types-list li ul li {
    margin-bottom: 0;
}

.process-timeline {
    position: relative;
}

.process-timeline:before {
    content: "";
    position: absolute;
    top: 0;
    left: 19px;
    height: 100%;
    width: 2px;
    background: #2196f3;
}

.timeline-step {
    position: relative;
    padding-left: 60px;
    margin-bottom: 30px;
}

.timeline-step:last-child {
    margin-bottom: 0;
}

.step-number {
    position: absolute;
    left: 0;
    top: 0;
    width: 40px;
    height: 40px;
    background-color: #2196f3;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
    z-index: 2;
}

.step-content {
    background-color: #f9f9f9;
    border-radius: 8px;
    padding: 16px;
}

.step-content h3 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 1.1rem;
    font-weight: bold;
}

.button-container {
    display: flex;
    justify-content: center;
    margin-top: 20px;
}

@media (max-width: 768px) {
    .requirements-list li {
        flex-wrap: wrap;
    }

    .requirements-list li strong {
        min-width: 250px;
        width: 250px;
    }

    .process-timeline:before {
        left: 15px;
    }

    .timeline-step {
        padding-left: 50px;
    }

    .step-number {
        width: 30px;
        height: 30px;
        font-size: 1rem;
    }
}
</style>
