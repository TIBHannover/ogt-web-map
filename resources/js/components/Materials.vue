<template>
    <!-- MaterialsPage component -->
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
                    Materialien
                </h1>
            </v-col>
        </v-row>

        <v-row>
            <v-col cols="12" md="4" class="order-md-last">
                <table-of-contents-sidebar
                    :headings="headings"
                ></table-of-contents-sidebar>
            </v-col>

            <!-- scrollable content -->
            <v-col cols="12" md="8" class="hyphens-auto" lang="de">
                <section id="materials">
                    <h2
                        class="font-weight-medium text-h5 text-md-h4 mb-5"
                        :class="{ 'font-size-29': $vuetify.display.mdAndUp }"
                    >
                        Anleitungen zur Werkstatt historische Daten
                    </h2>
                    <p>
                        Du möchtest Daten zur Geschichte der Gestapo erfassen
                        und sie auf der digitalen Karte Gestapo.Terror.Orte
                        sichtbar machen?<br />
                        Hier findest du Materialien, die dich dabei
                        unterstützen.
                    </p>

                    <v-grid>
                        <v-row class="align-center">
                            <v-col cols="12" md="7" class="pb-0 pb-md-2">
                                <h3>Wikidata Basics</h3>
                                <p class="mb-0">
                                    Diese PDF erklärt zentrale Grundbegriffe
                                    rund um Wikidata. <br />Sie beantwortet
                                    Fragen wie:<br />
                                    Was ist Wikidata und was sind strukturierte
                                    Daten?<br />
                                    Warum nutzt Gestapo.Terror.Orte Wikidata als
                                    Datenbank?<br />
                                    Wie ist Wikidata aufgebaut?
                                </p>
                            </v-col>
                            <v-col cols="12" md="5" class="pt-0 pt-md-2">
                                <v-btn
                                    class="background-color-grey border-none justify-start ml-1 mr-5 my-3 mb-6 text-h6 py-3 text-transform-none"
                                    color="black"
                                    background="grey"
                                    elevation="24"
                                    min-width="200px"
                                    style="height: auto"
                                    variant="outlined"
                                    rounded
                                    href="./resources/Wikidata_Basics_compressed.pdf"
                                    target="_blank"
                                >
                                    <v-icon start class="ml-3">
                                        mdi-download
                                    </v-icon>
                                    <span class="whitespace-normal"
                                        >Anleitung Wikidata Basics</span
                                    >
                                </v-btn>
                            </v-col>
                        </v-row>
                        <v-row class="align-center">
                            <v-col cols="12" md="7" class="pb-0 pb-md-2">
                                <h3>Wikidata-Anleitung</h3>
                                <p class="mb-0">
                                    Diese Schritt-für-Schritt-Anleitung zeigt,
                                    wie du neue Datenobjekte in Wikidata anlegst
                                    oder bestehende ergänzt.
                                </p>
                            </v-col>
                            <v-col cols="12" md="5" class="pt-0 pt-md-2">
                                <v-btn
                                    class="background-color-grey border-none justify-start ml-1 mr-5 my-3 mb-6 text-h6 py-3 text-transform-none"
                                    color="black"
                                    background="grey"
                                    elevation="24"
                                    min-width="200px"
                                    style="height: auto"
                                    variant="outlined"
                                    rounded
                                    href="./resources/Anleitung_seite_compressed.pdf"
                                    target="_blank"
                                >
                                    <v-icon start class="ml-3">
                                        mdi-download
                                    </v-icon>
                                    <span class="whitespace-normal"
                                        >Wikidata Anleitung
                                    </span>
                                </v-btn>
                            </v-col>
                        </v-row>
                        <v-row class="align-center">
                            <v-col cols="12" md="7" class="pb-0 pb-md-2">
                                <h3>Datenmodelle</h3>
                                <p class="mb-0">
                                    In Wikidata können Informationen auf
                                    unterschiedliche Weise erfasst werden. Diese
                                    Übersicht zeigt, wie Daten zu Gestapoorten,
                                    Erinnerungsorten, Geschädigten, Täter*innen
                                    und Ereignissen erfasst werden müssen, damit
                                    sie auf Gestapo.Terror.Orte angezeigt
                                    werden. Wichtig: Nur wenn du dich an die
                                    Datenmodelle hältst, erscheinen deine
                                    Einträge auf der Karte. Nicht alle Daten
                                    müssen ausgefüllt werden – verpflichtend
                                    sind nur die farbig markierten Angaben.
                                    Zusätzliche Informationen können ebenfalls
                                    ergänzt werden, auch wenn sie nicht explizit
                                    im Modell vorgesehen sind.
                                </p>
                            </v-col>
                            <v-col cols="12" md="5" class="pt-0 pt-md-2">
                                <v-btn
                                    class="background-color-grey border-none justify-start ml-1 mr-5 my-3 mb-6 text-h6 py-3 text-transform-none"
                                    color="black"
                                    background="grey"
                                    elevation="24"
                                    min-width="200px"
                                    variant="outlined"
                                    style="height: auto"
                                    rounded
                                    href="./resources/Datenmodelle_final_compressed.pdf"
                                    target="_blank"
                                >
                                    <v-icon start class="ml-3">
                                        mdi-download
                                    </v-icon>
                                    <span class="whitespace-normal"
                                        >Beschreibung der Datenmodelle
                                    </span>
                                </v-btn>
                            </v-col>
                        </v-row>
                        <v-row class="align-center">
                            <v-col cols="12" md="7" class="pb-0 pb-md-2">
                                <h3>Beispiel Quellenanalyse</h3>
                                <p class="mb-0">
                                    Anhand eines konkreten Beispiels wird
                                    gezeigt, wie der Weg von einer Archivalie zu
                                    einem Eintrag auf Gestapo.Terror.Orte
                                    aussieht: Welche Informationen lassen sich
                                    aus historischen Quellen gewinnen? Wie sehen
                                    die entsprechenden Einträge in Wikidata und
                                    auf der Karte aus?
                                </p>
                            </v-col>
                            <v-col cols="12" md="5" class="pt-0 pt-md-2">
                                <v-btn
                                    class="background-color-grey border-none justify-start ml-1 mr-5 my-3 mb-6 text-h6 py-3 text-transform-none"
                                    color="black"
                                    background="grey"
                                    elevation="24"
                                    min-width="200px"
                                    variant="outlined"
                                    style="height: auto"
                                    rounded
                                    size="x-large"
                                    href="./resources/Beispiel_Quellenanalyse_compressed.pdf"
                                    target="_blank"
                                >
                                    <v-icon start class="ml-3">
                                        mdi-download
                                    </v-icon>
                                    <span class="whitespace-normal"
                                        >Beispiel einer Quellenanalyse
                                    </span>
                                </v-btn>
                            </v-col>
                        </v-row>
                    </v-grid>

                    <br />
                </section>

                <section id="export">
                    <h2
                        class="font-weight-medium text-h5 text-md-h4 mb-5"
                        :class="{ 'font-size-29': $vuetify.display.mdAndUp }"
                    >
                        Wikidata Daten exportieren
                    </h2>
                    <p>
                        Hier kannst du die Wikidata-Daten zur Geschichte der Gestapo als CSV-Datei herunterladen, die du in Excel oder anderen Tabellenkalkulationsprogrammen öffnen kannst.
                    </p>

                    <v-form @submit.prevent="submitExportForm" ref="exportForm">
                        <h3 class="mt-4 mb-3">Datentyp wählen:</h3>
                        <v-select
                            v-model="exportType"
                            :items="exportOptions"
                            item-title="text"
                            item-value="value"
                            return-object="false"
                            label="Wähle einen Datentyp"
                            class="mb-6"
                        ></v-select>

                        <v-alert type="info" class="mb-4" color="info" icon="mdi-information">
                            Der Export großer Datenmengen kann einige Momente dauern. Bitte warten Sie, bis der Download abgeschlossen ist.
                        </v-alert>

                        <v-btn 
                            type="submit" 
                            color="primary" 
                            class="mb-4"
                            :loading="isExporting"
                        >
                            <v-icon start>mdi-download</v-icon>
                            CSV-Datei herunterladen
                        </v-btn>
                    </v-form>

                    <h3 class="mt-6 mb-3">Über die exportierten Daten:</h3>
                    <ul>
                        <li>Die Daten werden direkt von Wikidata abgerufen</li>
                        <li>CSV-Dateien können in Excel oder ähnlichen Tabellenkalkulationsprogrammen geöffnet werden</li>
                        <li>Die Datei enthält eine UTF-8 BOM-Markierung für korrekte Zeichendarstellung in Excel</li>
                    </ul>

                    <h3 class="mt-4 mb-3">Für den Orte-Export:</h3>
                    <p>Enthält ID, Label, Beschreibung, Typ, Koordinaten, Adresse, Gründungs- und Auflösungsdaten sowie Website-Links.</p>

                    <h3 class="mt-4 mb-3">Für den Orte-Export ohne Ereignisse:</h3>
                    <p>Enthält die gleichen Daten wie der Orte-Export, filtert aber Einträge vom Typ "NS-Verbrechen" (Wikidata Q6983405) aus.</p>
                    
                    <h3 class="mt-4 mb-3">Für den Orte-Export (minimal):</h3>
                    <p>Enthält nur Orte vom Typ Arbeitserziehungslager, Gefängnis oder Erweitertes Polizeigefängnis.</p>

                    <h3 class="mt-4 mb-3">Für den Personen-Export:</h3>
                    <p>Enthält ID, Name, Beschreibung, Geschlecht, Geburts- und Sterbedaten, Geburts- und Sterbeorte, Staatsbürgerschaften und Personengruppe.</p>
                    
                    <h3 class="mt-4 mb-3">Für den Täter/Geschädigte-Export:</h3>
                    <p>Filtert die Personen nach ihrer Rolle (Täter oder Geschädigte) im NS-Regime.</p>
                </section>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup name="MaterialsPage">
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

// Export functionality
const exportType = ref("all");
const isExporting = ref(false);

// Export options for the dropdown
const exportOptions = [
    { text: "Alle Daten (Orte und Personen)", value: "all" },
    { text: "Nur Orte (Mit Ereignissen)", value: "places" },
    { text: "Nur Orte (ohne Ereignisse)", value: "places_no_events" },
    { text: "Nur Orte (minimal: Arbeitserziehungslager, Gefängnis, Erweitertes Polizeigefängnis)", value: "places_minimal" },
    { text: "Nur Personen (Alle)", value: "persons" },
    { text: "Nur Personen (Täter)", value: "persons_perpetrators" },
    { text: "Nur Personen (Geschädigte)", value: "persons_victims" },
];

// Submit the export form
const submitExportForm = async () => {
    isExporting.value = true;
    
    try {
        // Create a form element
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/export/download';
        form.style.display = 'none';
        
        // Add CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
        }
        
        // Add export type
        const typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.name = 'type';
        typeInput.value = exportType.value;
        form.appendChild(typeInput);
        
        // Add the form to the document and submit it
        document.body.appendChild(form);
        form.submit();
        
        // Remove the form after submission
        setTimeout(() => {
            document.body.removeChild(form);
            isExporting.value = false;
        }, 2000);
    } catch (error) {
        console.error('Export error:', error);
        isExporting.value = false;
    }
};

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

const headings = ref([
    {
        href: "#materials",
        label: "Anleitungen zur Werkstatt historische Daten",
    },
    {
        href: "#export",
        label: "Wikidata Daten exportieren",
    },
]);
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
.whitespace-normal {
    white-space: normal !important;
}
</style>