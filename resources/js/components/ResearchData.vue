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

                    <h2 class="font-weight-medium text-h5 text-md-h4 mt-8 mb-4">
                        Dienstsitze der Gestapo
                    </h2>

                    <!-- Staatspolizeileitstellen -->
                    <v-card class="mb-6 pa-4">
                        <h3 class="text-h6 mb-2">
                            Staatspolizeileitstellen in Niedersachsen
                        </h3>
                        <p>
                            Enthält Daten zu den Staatspolizeileitstellen, die
                            regional zuständige Dienststellen der Geheimen
                            Staatspolizei waren.
                        </p>
                        <v-btn
                            @click="downloadExport('staatspolizeileitstellen')"
                            color="primary"
                            class="mt-2"
                            :loading="
                                isExporting === 'staatspolizeileitstellen'
                            "
                        >
                            <v-icon start>mdi-download</v-icon>
                            Staatspolizeileitstellen exportieren
                        </v-btn>
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
                        <v-btn
                            @click="downloadExport('staatspolizeistellen')"
                            color="primary"
                            class="mt-2"
                            :loading="isExporting === 'staatspolizeistellen'"
                        >
                            <v-icon start>mdi-download</v-icon>
                            Staatspolizeistellen exportieren
                        </v-btn>
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
                        <v-btn
                            @click="downloadExport('aussendienststellen')"
                            color="primary"
                            class="mt-2"
                            :loading="isExporting === 'aussendienststellen'"
                        >
                            <v-icon start>mdi-download</v-icon>
                            Außendienststellen exportieren
                        </v-btn>
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
                        <v-btn
                            @click="downloadExport('gefaengnisse')"
                            color="primary"
                            class="mt-2"
                            :loading="isExporting === 'gefaengnisse'"
                        >
                            <v-icon start>mdi-download</v-icon>
                            Gefängnisse exportieren
                        </v-btn>
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
                        <v-btn
                            @click="
                                downloadExport('erweitertes_polizeigefaengnis')
                            "
                            color="primary"
                            class="mt-2"
                            :loading="
                                isExporting === 'erweitertes_polizeigefaengnis'
                            "
                        >
                            <v-icon start>mdi-download</v-icon>
                            Erweiterte Polizeigefängnisse exportieren
                        </v-btn>
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
                        <v-btn
                            @click="downloadExport('arbeitserziehungslager')"
                            color="primary"
                            class="mt-2"
                            :loading="isExporting === 'arbeitserziehungslager'"
                        >
                            <v-icon start>mdi-download</v-icon>
                            Arbeitserziehungslager exportieren
                        </v-btn>
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
                        <v-btn
                            @click="downloadExport('ereignisse')"
                            color="primary"
                            class="mt-2"
                            :loading="isExporting === 'ereignisse'"
                        >
                            <v-icon start>mdi-download</v-icon>
                            Ereignisse exportieren
                        </v-btn>
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
                        <v-btn
                            @click="downloadExport('erinnerungsorte')"
                            color="primary"
                            class="mt-2"
                            :loading="isExporting === 'erinnerungsorte'"
                        >
                            <v-icon start>mdi-download</v-icon>
                            Erinnerungsorte exportieren
                        </v-btn>
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
                        <v-btn
                            @click="downloadExport('persons_perpetrators')"
                            color="primary"
                            class="mt-2"
                            :loading="isExporting === 'persons_perpetrators'"
                        >
                            <v-icon start>mdi-download</v-icon>
                            Täter exportieren
                        </v-btn>
                    </v-card>

                    <!-- Geschädigte -->
                    <v-card class="mb-6 pa-4">
                        <h3 class="text-h6 mb-2">Geschädigte</h3>
                        <p>
                            Enthält Daten zu Personen, die im Zusammenhang mit
                            Maßnahmen, Überwachung oder Verfolgung durch die
                            Gestapo in Niedersachsen geschädigt wurden.
                        </p>
                        <v-btn
                            @click="downloadExport('persons_victims')"
                            color="primary"
                            class="mt-2"
                            :loading="isExporting === 'persons_victims'"
                        >
                            <v-icon start>mdi-download</v-icon>
                            Geschädigte exportieren
                        </v-btn>
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
const isExporting = ref(false);

// Function to download export by type
const downloadExport = async (type) => {
    isExporting.value = type;

    try {
        // Create a link element for direct download
        const downloadLink = document.createElement("a");

        // Set the href with the export type as a query parameter
        downloadLink.href = `/export/download?type=${encodeURIComponent(type)}`;

        // Set download attribute to force download
        downloadLink.setAttribute("download", "");

        // Hide the link
        downloadLink.style.display = "none";

        // Add to document, click it, and remove it
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);

        // Reset loading state after a short delay
        setTimeout(() => {
            isExporting.value = false;
        }, 2000);
    } catch (error) {
        console.error("Export error:", error);
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
