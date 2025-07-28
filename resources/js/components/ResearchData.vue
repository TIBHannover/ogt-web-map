<template>
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
                    Forschungsdaten
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
                <section id="export">
                    <h2
                        class="font-weight-medium text-h5 text-md-h4 mb-5"
                        :class="{ 'font-size-29': $vuetify.display.mdAndUp }"
                    >
                        Wikidata Daten exportieren
                    </h2>
                    <p>
                        Hier kannst du die Wikidata-Daten zur Geschichte der
                        Gestapo in Niedersachsen als CSV-Datei herunterladen,
                        die du in Excel oder anderen
                        Tabellenkalkulationsprogrammen öffnen kannst.
                    </p>
                    <p>
                        Die hier bereitgestellten Daten spiegeln den aktuellen
                        Stand der Recherchen auf Wikidata wider und erheben
                        keinen Anspruch auf Vollständigkeit. Die Erfassung von
                        Orten, Ereignissen und Akteur*innen im Zusammenhang mit
                        dem Gestapo-Terror ist ein fortlaufender Prozess. Je
                        mehr Menschen ihr Wissen einbringen, desto dichter und
                        genauer wird der gemeinsame Forschungsstand. Du kannst
                        dich gerne beteiligen und zur Weiterentwicklung der
                        offenen Daten beitragen.
                    </p>

                    <h3 class="mt-6 mb-3">Über die exportierten Daten:</h3>
                    <ul>
                        <li>Die Daten werden direkt von Wikidata abgerufen</li>
                        <li>
                            CSV-Dateien können in Excel oder ähnlichen
                            Tabellenkalkulationsprogrammen geöffnet werden
                        </li>
                        <li>
                            Die Datei enthält eine UTF-8 BOM-Markierung für
                            korrekte Zeichendarstellung in Excel
                        </li>
                    </ul>
                    <!--
                    <v-card class="mb-6 pa-4 bg-grey-lighten-4">
                        <h3 class="text-h6 mb-2">
                            Hilfe: So öffnen Sie die Dateien
                        </h3>

                        <p class="mb-4">
                            <strong>Excel-Button:</strong> Erstellt eine echte Excel-Datei (.xlsx), die sich direkt in Microsoft Excel und anderen Tabellenkalkulationsprogrammen öffnen lässt, ohne dass zusätzliche Schritte erforderlich sind.
                        </p>

                        <p class="mb-4">
                            <strong>CSV-Button:</strong> Erstellt eine Standard-CSV-Datei mit Komma als Trennzeichen, die für die meisten internationalen Tabellenkalkulationsprogramme geeignet ist.
                        </p>

                        <h4 class="font-weight-medium mb-2">Falls die Datei nicht automatisch korrekt geöffnet wird:</h4>

                        <h5 class="font-weight-medium mb-2">In Microsoft Excel:</h5>
                        <ol>
                            <li>
                                Öffnen Sie Excel und wählen Sie <strong>Datei → Öffnen</strong>
                            </li>
                            <li>
                                Wählen Sie die heruntergeladene CSV-Datei aus (Sie müssen eventuell bei "Dateityp" auf "Alle Dateien" umstellen)
                            </li>
                            <li>
                                Der Text-Import-Assistent sollte erscheinen. Falls nicht, klicken Sie auf <strong>Daten → Aus Text</strong>
                            </li>
                            <li>
                                Im ersten Schritt wählen Sie <strong>Getrennt</strong> und <strong>UTF-8</strong> als Dateiursprung
                            </li>
                            <li>
                                Im zweiten Schritt aktivieren Sie als Trennzeichen das <strong>Komma</strong> (für CSV) oder <strong>Semikolon</strong> (für Excel-Format)
                            </li>
                            <li>
                                Klicken Sie auf <strong>Fertig stellen</strong>, um die Daten zu importieren
                            </li>
                        </ol>

                        <h5 class="font-weight-medium mt-4 mb-2">In LibreOffice Calc:</h5>
                        <ol>
                            <li>
                                Öffnen Sie LibreOffice Calc und wählen Sie <strong>Datei → Öffnen</strong>
                            </li>
                            <li>
                                Wählen Sie die heruntergeladene CSV-Datei aus
                            </li>
                            <li>
                                Im Textimport-Dialog stellen Sie folgendes ein:
                                <ul>
                                    <li>Zeichensatz: <strong>Unicode (UTF-8)</strong></li>
                                    <li>Sprache: <strong>Deutsch</strong></li>
                                    <li>Trennoptionen: <strong>Komma</strong> (für CSV) oder <strong>Semikolon</strong> (für Excel-Format) aktivieren</li>
                                </ul>
                            </li>
                            <li>
                                Klicken Sie auf <strong>OK</strong>, um die Daten zu importieren
                            </li>
                        </ol>

                        <p class="mt-4">
                            <strong>Tipp:</strong> Falls die Spalten nicht richtig angezeigt werden, können Sie in Excel oder LibreOffice Calc die Breite der Spalten anpassen, indem Sie auf die Trennlinie zwischen den Spaltenüberschriften doppelklicken.
                        </p>
                    </v-card>
-->
                    <h2 class="font-weight-medium text-h5 text-md-h4 mt-8 mb-4">
                        Dienstsitze der Gestapo
                    </h2>

                    <!-- Staatspolizeileitstellen -->
                    <v-card class="mb-6 pa-4">
                        <h3 class="text-h6 mb-2">Staatspolizeileitstellen</h3>
                        <p>
                            Enthält Daten zu Staatspolizeileitstellen, die
                            entweder in Niedersachsen lagen oder als
                            übergeordnete Dienststellen für die in Niedersachsen
                            zuständigen Stellen der Geheimen Staatspolizei
                            fungierten.
                        </p>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <v-btn
                                v-if="false" <!-- Temporarily hidden due to umlaut issues -->
                                @click="
                                    downloadExport(
                                        'staatspolizeileitstellen',
                                        'csv',
                                    )
                                "
                                color="primary"
                                variant="outlined"
                                :loading="
                                    isExporting ===
                                    'staatspolizeileitstellen_csv'
                                "
                            >
                                <v-icon start>mdi-download</v-icon>
                                CSV exportieren
                            </v-btn>
                            <v-btn
                                @click="
                                    downloadExport(
                                        'staatspolizeileitstellen',
                                        'excel',
                                    )
                                "
                                color="success"
                                :loading="
                                    isExporting ===
                                    'staatspolizeileitstellen_excel'
                                "
                            >
                                <v-icon start>mdi-microsoft-excel</v-icon>
                                Excel exportieren
                            </v-btn>
                        </div>
                    </v-card>

                    <!-- Staatspolizeistellen -->
                    <v-card class="mb-6 pa-4">
                        <h3 class="text-h6 mb-2">
                            Staatspolizeistellen in Niedersachsen
                        </h3>
                        <p>
                            Enthält Daten zu den Staatspolizeistellen, die den
                            Staatspolizeileitstellen untergeordnet waren.
                        </p>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <v-btn
                                v-if="false" <!-- Temporarily hidden due to umlaut issues -->
                                @click="
                                    downloadExport(
                                        'staatspolizeistellen',
                                        'csv',
                                    )
                                "
                                color="primary"
                                variant="outlined"
                                :loading="
                                    isExporting === 'staatspolizeistellen_csv'
                                "
                            >
                                <v-icon start>mdi-download</v-icon>
                                CSV exportieren
                            </v-btn>
                            <v-btn
                                @click="
                                    downloadExport(
                                        'staatspolizeistellen',
                                        'excel',
                                    )
                                "
                                color="success"
                                :loading="
                                    isExporting === 'staatspolizeistellen_excel'
                                "
                            >
                                <v-icon start>mdi-microsoft-excel</v-icon>
                                Excel exportieren
                            </v-btn>
                        </div>
                    </v-card>

                    <!-- Außendienststellen -->
                    <v-card class="mb-6 pa-4">
                        <h3 class="text-h6 mb-2">
                            Außendienststellen in Niedersachsen
                        </h3>
                        <p>
                            Enthält Daten zu den Außendienststellen der Gestapo,
                            die für kleinere Städte und Regionen zuständig
                            waren.
                        </p>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <v-btn
                                v-if="false" <!-- Temporarily hidden due to umlaut issues -->
                                @click="
                                    downloadExport('aussendienststellen', 'csv')
                                "
                                color="primary"
                                variant="outlined"
                                :loading="
                                    isExporting === 'aussendienststellen_csv'
                                "
                            >
                                <v-icon start>mdi-download</v-icon>
                                CSV exportieren
                            </v-btn>
                            <v-btn
                                @click="
                                    downloadExport(
                                        'aussendienststellen',
                                        'excel',
                                    )
                                "
                                color="success"
                                :loading="
                                    isExporting === 'aussendienststellen_excel'
                                "
                            >
                                <v-icon start>mdi-microsoft-excel</v-icon>
                                Excel exportieren
                            </v-btn>
                        </div>
                    </v-card>

                    <!-- Haftstätten -->
                    <h2 class="font-weight-medium text-h5 text-md-h4 mt-8 mb-4">
                        Haftstätten
                    </h2>

                    <!-- Gefängnisse -->
                    <v-card class="mb-6 pa-4">
                        <h3 class="text-h6 mb-2">
                            Gefängnisse in Niedersachsen
                        </h3>
                        <p>
                            Enthält Daten zu Gefängnissen, die von der Gestapo
                            genutzt wurden.
                        </p>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <v-btn
                                v-if="false" <!-- Temporarily hidden due to umlaut issues -->
                                @click="downloadExport('gefaengnisse', 'csv')"
                                color="primary"
                                variant="outlined"
                                :loading="isExporting === 'gefaengnisse_csv'"
                            >
                                <v-icon start>mdi-download</v-icon>
                                CSV exportieren
                            </v-btn>
                            <v-btn
                                @click="downloadExport('gefaengnisse', 'excel')"
                                color="success"
                                :loading="isExporting === 'gefaengnisse_excel'"
                            >
                                <v-icon start>mdi-microsoft-excel</v-icon>
                                Excel exportieren
                            </v-btn>
                        </div>
                    </v-card>

                    <!-- Erweiterte Polizeigefängnisse -->
                    <v-card class="mb-6 pa-4">
                        <h3 class="text-h6 mb-2">
                            Erweiterte Polizeigefängnisse in Niedersachsen
                        </h3>
                        <p>
                            Enthält Daten zu Erweiterten Polizeigefängnissen,
                            die spezielle Hafteinrichtungen der Gestapo waren.
                        </p>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <v-btn
                                v-if="false" <!-- Temporarily hidden due to umlaut issues -->
                                @click="
                                    downloadExport(
                                        'erweitertes_polizeigefaengnis',
                                        'csv',
                                    )
                                "
                                color="primary"
                                variant="outlined"
                                :loading="
                                    isExporting ===
                                    'erweitertes_polizeigefaengnis_csv'
                                "
                            >
                                <v-icon start>mdi-download</v-icon>
                                CSV exportieren
                            </v-btn>
                            <v-btn
                                @click="
                                    downloadExport(
                                        'erweitertes_polizeigefaengnis',
                                        'excel',
                                    )
                                "
                                color="success"
                                :loading="
                                    isExporting ===
                                    'erweitertes_polizeigefaengnis_excel'
                                "
                            >
                                <v-icon start>mdi-microsoft-excel</v-icon>
                                Excel exportieren
                            </v-btn>
                        </div>
                    </v-card>

                    <!-- Arbeitserziehungslager -->
                    <v-card class="mb-6 pa-4">
                        <h3 class="text-h6 mb-2">
                            Arbeitserziehungslager in Niedersachsen
                        </h3>
                        <p>
                            Enthält Daten zu Arbeitserziehungslagern, die von
                            der Gestapo verwaltet wurden.
                        </p>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <v-btn
                                v-if="false" <!-- Temporarily hidden due to umlaut issues -->
                                @click="
                                    downloadExport(
                                        'arbeitserziehungslager',
                                        'csv',
                                    )
                                "
                                color="primary"
                                variant="outlined"
                                :loading="
                                    isExporting === 'arbeitserziehungslager_csv'
                                "
                            >
                                <v-icon start>mdi-download</v-icon>
                                CSV exportieren
                            </v-btn>
                            <v-btn
                                @click="
                                    downloadExport(
                                        'arbeitserziehungslager',
                                        'excel',
                                    )
                                "
                                color="success"
                                :loading="
                                    isExporting ===
                                    'arbeitserziehungslager_excel'
                                "
                            >
                                <v-icon start>mdi-microsoft-excel</v-icon>
                                Excel exportieren
                            </v-btn>
                        </div>
                    </v-card>

                    <h2 class="font-weight-medium text-h5 text-md-h4 mt-8 mb-4">
                        Ereignisse
                    </h2>

                    <!-- Ereignisse -->
                    <v-card class="mb-6 pa-4">
                        <h3 class="text-h6 mb-2">
                            Ereignisse in Niedersachsen
                        </h3>
                        <p>
                            Enthält Daten zu den Verbrechen der Gestapo in
                            Niedersachsen.
                        </p>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <v-btn
                                v-if="false" <!-- Temporarily hidden due to umlaut issues -->
                                @click="downloadExport('ereignisse', 'csv')"
                                color="primary"
                                variant="outlined"
                                :loading="isExporting === 'ereignisse_csv'"
                            >
                                <v-icon start>mdi-download</v-icon>
                                CSV exportieren
                            </v-btn>
                            <v-btn
                                @click="downloadExport('ereignisse', 'excel')"
                                color="success"
                                :loading="isExporting === 'ereignisse_excel'"
                            >
                                <v-icon start>mdi-microsoft-excel</v-icon>
                                Excel exportieren
                            </v-btn>
                        </div>
                    </v-card>

                    <h2 class="font-weight-medium text-h5 text-md-h4 mt-8 mb-4">
                        Erinnerungsorte
                    </h2>

                    <!-- Erinnerungsorte -->
                    <v-card class="mb-6 pa-4">
                        <h3 class="text-h6 mb-2">
                            Erinnerungsorte in Niedersachsen
                        </h3>
                        <p>
                            Enthält Daten zu Gedenkstätten und anderen Orten des
                            Erinnerns.
                        </p>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <v-btn
                                v-if="false" <!-- Temporarily hidden due to umlaut issues -->
                                @click="
                                    downloadExport('erinnerungsorte', 'csv')
                                "
                                color="primary"
                                variant="outlined"
                                :loading="isExporting === 'erinnerungsorte_csv'"
                            >
                                <v-icon start>mdi-download</v-icon>
                                CSV exportieren
                            </v-btn>
                            <v-btn
                                @click="
                                    downloadExport('erinnerungsorte', 'excel')
                                "
                                color="success"
                                :loading="
                                    isExporting === 'erinnerungsorte_excel'
                                "
                            >
                                <v-icon start>mdi-microsoft-excel</v-icon>
                                Excel exportieren
                            </v-btn>
                        </div>
                    </v-card>

                    <!-- Personen -->
                    <h2 class="font-weight-medium text-h5 text-md-h4 mt-8 mb-4">
                        Personen
                    </h2>

                    <!-- Täter -->
                    <v-card class="mb-6 pa-4">
                        <h3 class="text-h6 mb-2">Täter*innen</h3>
                        <p>
                            Enthält Daten zu Personen, die in den Strukturen der
                            Gestapo in Niedersachsen tätig waren.
                        </p>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <v-btn
                                v-if="false" <!-- Temporarily hidden due to umlaut issues -->
                                @click="
                                    downloadExport(
                                        'persons_perpetrators',
                                        'csv',
                                    )
                                "
                                color="primary"
                                variant="outlined"
                                :loading="
                                    isExporting === 'persons_perpetrators_csv'
                                "
                            >
                                <v-icon start>mdi-download</v-icon>
                                CSV exportieren
                            </v-btn>
                            <v-btn
                                @click="
                                    downloadExport(
                                        'persons_perpetrators',
                                        'excel',
                                    )
                                "
                                color="success"
                                :loading="
                                    isExporting === 'persons_perpetrators_excel'
                                "
                            >
                                <v-icon start>mdi-microsoft-excel</v-icon>
                                Excel exportieren
                            </v-btn>
                        </div>
                    </v-card>

                    <!-- Geschädigte -->
                    <v-card class="mb-6 pa-4">
                        <h3 class="text-h6 mb-2">Geschädigte</h3>
                        <p>
                            Enthält Daten zu Personen, die im Zusammenhang mit
                            Maßnahmen, Überwachung oder Verfolgung durch die
                            Gestapo in Niedersachsen geschädigt wurden.
                        </p>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <v-btn
                                v-if="false" <!-- Temporarily hidden due to umlaut issues -->
                                @click="
                                    downloadExport('persons_victims', 'csv')
                                "
                                color="primary"
                                variant="outlined"
                                :loading="isExporting === 'persons_victims_csv'"
                            >
                                <v-icon start>mdi-download</v-icon>
                                CSV exportieren
                            </v-btn>
                            <v-btn
                                @click="
                                    downloadExport('persons_victims', 'excel')
                                "
                                color="success"
                                :loading="
                                    isExporting === 'persons_victims_excel'
                                "
                            >
                                <v-icon start>mdi-microsoft-excel</v-icon>
                                Excel exportieren
                            </v-btn>
                        </div>
                    </v-card>
                </section>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup name="ForschungsdatenPage">
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
const isExporting = ref(null);

// Function to download export by type
const downloadExport = async (type, format = "csv") => {
    // Create a unique key for this specific export with format
    const exportKey = `${type}_${format}`;
    isExporting.value = exportKey;

    try {
        // Create a link element for direct download
        const downloadLink = document.createElement("a");

        // Set the href with the export type and format as query parameters
        downloadLink.href = `/export/download?type=${encodeURIComponent(type)}&format=${format}`;

        // Set download attribute to force download
        downloadLink.setAttribute("download", "");

        // Hide the link
        downloadLink.style.display = "none";

        // Add to document, click it, and remove it
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);

        // Show a notification that download has started
        const notification = document.createElement("div");
        notification.textContent = "Der Download wurde gestartet. Die Datei sollte in Kürze heruntergeladen werden.";
        notification.style.position = "fixed";
        notification.style.bottom = "20px";
        notification.style.right = "20px";
        notification.style.backgroundColor = "#4CAF50";
        notification.style.color = "white";
        notification.style.padding = "15px";
        notification.style.borderRadius = "5px";
        notification.style.zIndex = "1000";
        notification.style.boxShadow = "0 4px 8px rgba(0,0,0,0.2)";
        document.body.appendChild(notification);

        // Remove notification after 5 seconds
        setTimeout(() => {
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
        }, 5000);

        // Reset loading state after 10 seconds
        setTimeout(() => {
            isExporting.value = null;
        }, 10000);
    } catch (error) {
        console.error("Export error:", error);
        isExporting.value = null;
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
