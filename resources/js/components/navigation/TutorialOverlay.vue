<template>
    <div>
        <!--
          Help button to start tutorial
          Always visible unless the tutorial is currently active
          Will remain visible after tutorial completion
        -->
        <v-btn
            v-if="!active"
            position="fixed"
            location="bottom left"
            class="tutorial-button"
            :rounded="$vuetify.display.smAndUp ? 'pill' : 'circle'"
            :size="$vuetify.display.smAndUp ? 'default' : '56px'"
            color="primary"
            @click="startTutorial"
        >
            <v-icon>mdi-help-circle</v-icon>
            <span v-if="$vuetify.display.smAndUp" class="ml-2 text-none"
                >Tutorial</span
            >
        </v-btn>

        <!-- Chapter menu button -->
        <v-btn
            v-if="active && !chapterMenuOpen"
            position="fixed"
            location="bottom left"
            :rounded="$vuetify.display.smAndUp ? 'pill' : 'circle'"
            :size="$vuetify.display.smAndUp ? 'default' : '56px'"
            color="secondary"
            class="chapter-menu-button"
            @click="toggleChapterMenu"
        >
            <v-icon>mdi-menu-open</v-icon>
            <span v-if="$vuetify.display.smAndUp" class="ml-2 text-none"
                >Kapitel</span
            >
        </v-btn>

        <!-- Chapter menu panel -->
        <div v-if="chapterMenuOpen" class="chapter-menu-panel">
            <div class="chapter-menu-header">
                <h3>Kapitel</h3>
                <v-btn icon size="small" @click="toggleChapterMenu">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </div>
            <div class="chapter-menu-content">
                <v-btn
                    v-for="(chapter, index) in chapters"
                    :key="index"
                    :color="currentChapterIndex === index ? 'primary' : ''"
                    @click="
                        goToChapter(index);
                        toggleChapterMenu();
                    "
                    block
                    class="mb-2"
                    variant="outlined"
                >
                    {{ chapter.title }}
                </v-btn>
            </div>
        </div>
        <div
            v-if="active || isFirstVisit"
            class="tutorial-overlay"
            :class="{ active: active || isFirstVisit }"
        >
            <div class="tutorial-backdrop" :style="backdropStyle"></div>

            <div class="tutorial-content" :style="positionStyle">
                <div class="tutorial-step">
                    <!-- Chapter indicator instead of navigation chips -->
                    <div class="chapter-indicator">
                        <span class="chapter-label"
                            >Kapitel {{ currentChapterIndex + 1 }}/{{
                                chapters.length
                            }}:</span
                        >
                        <span class="chapter-title">{{
                            currentChapter.title
                        }}</span>
                    </div>
                    <v-btn class="close-tutorial" @click="skipTutorial" icon>
                        <v-icon>mdi-close</v-icon>
                    </v-btn>

                    <h3>{{ currentStep.title }}</h3>
                    <p v-html="currentStep.description"></p>

                    <div class="tutorial-navigation">
                        <v-btn
                            v-if="canGoBack"
                            variant="text"
                            @click="prevStep"
                            size="small"
                        >
                            <v-icon>mdi-arrow-left</v-icon> Zurück
                        </v-btn>

                        <v-spacer></v-spacer>

                        <template
                            v-if="
                                isFirstVisit &&
                                currentChapterIndex === 0 &&
                                currentStepIndexInChapter === 0
                            "
                        >
                            <v-btn
                                variant="outlined"
                                @click="skipTutorial"
                                size="small"
                                class="mr-2"
                            >
                                Tutorial abbrechen
                            </v-btn>
                            <v-btn
                                color="primary"
                                @click="nextStep"
                                size="small"
                            >
                                Weiter <v-icon>mdi-arrow-right</v-icon>
                            </v-btn>
                        </template>
                        <template v-else>
                            <v-btn
                                v-if="
                                    currentChapterIndex < chapters.length - 1 &&
                                    currentStepIndexInChapter ===
                                        currentChapterSteps.length - 1
                                "
                                color="primary"
                                @click="nextChapter"
                                size="small"
                            >
                                Nächstes Kapitel
                                <v-icon>mdi-arrow-right</v-icon>
                            </v-btn>
                            <v-btn
                                v-else-if="!isLastStep"
                                color="primary"
                                @click="nextStep"
                                size="small"
                            >
                                Weiter <v-icon>mdi-arrow-right</v-icon>
                            </v-btn>
                            <v-btn
                                v-else
                                color="success"
                                @click="completeTutorial"
                                size="small"
                            >
                                Verstanden <v-icon>mdi-check</v-icon>
                            </v-btn>
                        </template>
                    </div>
                </div>
            </div>

            <div
                v-if="
                    currentStep.highlightSelector && !currentStep.highlightClass
                "
                class="tutorial-highlight"
                :style="highlightStyle"
            ></div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";

const props = defineProps({
    tutorialCompleted: {
        type: Boolean,
        default: false,
    },
    toggleTimeline: {
        type: Function,
        required: false,
    },
    openTimeline: {
        type: Function,
        required: false,
    },
    closeTimeline: {
        type: Function,
        required: false,
    },
    toggleMapOptions: {
        type: Function,
        required: false,
    },
    openMapOptions: {
        type: Function,
        required: false,
    },
    closeMapOptions: {
        type: Function,
        required: false,
    },
    setMapView: {
        type: Function,
        required: false,
    },
    zoomToLocation: Function,
    openGlossaryEntry: Function,
    activateFilter: Function,
    deactivateAllFilters: Function,
    selectLocation: Function,
    openPersonDetails: Function,
    goBack: Function,
    closeDetails: Function,
    openMainMenu: Function,
    setTimeFilter: Function,
    switchTimeFilterMode: Function,
});

const emit = defineEmits(["complete", "skip", "start"]);

const active = ref(false);
const isFirstVisit = ref(false);
const currentChapterIndex = ref(0);
const currentStepIndexInChapter = ref(0);
const customBackdropOpacity = ref(0.5); // Default backdrop opacity
const highlightElement = ref(null);
const chapterMenuOpen = ref(false);
const glossaryButtonsFound = ref(false);
// const glossaryButtonsCoords = ref({ top: 0, left: 0, width: 0, height: 0 });

// Define chapters and steps based on the document
const chapters = [
    {
        title: "Einführung",
        steps: [
            {
                title: "Willkommen",
                description:
                    "Willkommen auf Gestapo.Terror.Orte – der interaktiven Plattform zur Erforschung des Gestapo-Terrors in Niedersachsen.",
                position: {
                    top: "50%",
                    left: "50%",
                    transform: "translate(-50%, -50%)",
                },
                onStepPrepare: null,
                onStepShow: null,
                onStepNext: null,
            },
            {
                title: "Tutorial-Anleitung",
                description:
                    "Wir zeigen Dir hier, wie die Karte funktioniert. Du kannst das Tutorial jederzeit abbrechen und die Karte selbst erkunden. Das Tutorial kann beliebig oft neu gestartet werden.",
                highlightSelector: null,
                position: {
                    top: "50%",
                    left: "50%",
                    transform: "translate(-50%, -50%)",
                },
                highlightClass: "highlight-tutorial",
                onStepPrepare: null,
                onStepShow: null,
                onStepNext: null,
            },
            {
                title: "Kapitel-Menü",
                description:
                    "Über diesen Button erreichst du das Kapitel-Menü des Tutorials. <br> Dort kannst du zwischen den einzelnen Kapiteln springen.",
                highlightSelector: ".tutorial-button",
                position: { bottom: "100px", left: "80px" },
                highlightClass: "highlight-tutorial",
                onStepPrepare: null,
                onStepShow: null,
                onStepNext: null,
            },
            {
                title: "Menüs während des Tutorial",
                description:
                    "Alle Menüs sind deaktiviert solange das Tutorial aktiviert ist. Wenn du das Tutorial verlässt werden die Menüs wieder aktiviert.",
                highlightSelector: ".tutorial-button",
                position: { bottom: "100px", left: "80px" },
                highlightClass: "highlight-tutorial",
                onStepPrepare: null,
                onStepShow: null,
                onStepNext: null,
            },
            {
                title: "Los gehts",
                description: "Dann fangen wir an mit dem zweiten Kapitel",
                highlightSelector: null,
                position: {
                    top: "50%",
                    left: "50%",
                    transform: "translate(-50%, -50%)",
                },
                highlightClass: "highlight-tutorial",
                onStepPrepare: null,
                onStepShow: null,
                onStepNext: null,
            },
        ],
    },
    {
        title: "Kartenoptionen",
        steps: [
            {
                title: "Kartenoptionen-Menü",
                description:
                    "Hier kannst Du das Menü für Kartenoptionen öffnen.",
                highlightSelector: ".map-options-button", // Selector for the Map Options button
                position: { top: "100px", right: "80px" },
                highlightClass: "highlight-tutorial",
                onStepPrepare: "closeMapOptions",
                onStepShow: "setMapToCenter",
                onStepNext: null,
            },
            {
                title: "Kartenoptionen-Menü",
                description:
                    "In den Kartenoptionen siehst du die einzelnen Kategorien, die auf der Karte angezeigt werden.",
                highlightSelector: null, // Selector for the Map Options button
                position: { top: "100px", right: "400px" },
                highlightClass: "highlight-tutorial",
                onStepPrepare: "openMapOptions",
                onStepShow: "hideGlossaryHighlight",
                onStepNext: null,
            },
            {
                title: "Glossareinträge",
                description:
                    "In der rot markierten Spalte rechts findest du Glossar-Icons. Diese bieten dir Zugang zu Hintergrundinformationen über die verschiedenen Orte und Begriffe.",
                highlightSelector: null, // Not using highlight selector anymore
                position: { top: "120px", right: "400px" },
                highlightClass: null, // Not using highlight class anymore
                onStepPrepare: "openMapOptions",
                onStepShow: "showGlossaryHighlight", // Use our custom action to show highlight
                onStepNext: null,
            },
            {
                title: "Filteroptionen",
                description:
                    "Hier kannst Du auswählen, welche Kategorien auf der Karte angezeigt werden sollen.",
                highlightSelector: null,
                position: { top: "100px", right: "400px" },
                highlightClass: "highlight-tutorial",
                onStepPrepare: "openMapOptions",
                onStepShow: "showFiltersRowHighlight",
                onStepNext: null,
            },
            {
                title: "Alle Filter",
                description:
                    "In der Grundeinstellung werden alle Kategorien angezeigt. Hier kannst Du festlegen, ob alle oder keine Kategorien dargestellt werden sollen. <br> Wir deaktivieren einmal alle Kategorien.",
                highlightSelector: null,
                position: { top: "150px", right: "400px" },
                onStepShow: "showFiltersCheckboxAllHighlight",
                highlightClass: "highlight-tutorial",
                onStepPrepare: "openMapOptions",
                onStepNext: "deactivateAllFilters",
            },
            {
                title: "Kategorie auswählen",
                description:
                    "Jetzt kannst Du gezielt eine Kategorie auswählen. In diesem Beispiel aktivieren wir nur die Kategorie 'Staatspolizeileitstellen'.",
                position: { top: "180px", right: "400px" },
                onStepShow:
                    "showFilterCheckboxStaatspolizeileitstelleHighlight",
                highlightClass: "highlight-tutorial",
                onStepPrepare: "openMapOptions",
                onStepNext: "activateStaatspolizeileitstellen",
            },
            {
                title: "Gefilterte Anzeige",
                description:
                    "Nun werden ausschließlich Staatspolizeileitstellen auf der Karte angezeigt.",
                position: { top: "200px", right: "400px" },
                onStepPrepare: "openMapOptions",
                onStepShow: null,
                onStepNext: null,
                backdropOpacity: 30,
                backdropBlur: 0,
            },
            {
                title: "Standorte auswählen",
                description:
                    "Du kannst Dir jetzt alle Standorte der Staatspolizeileitstellen auf der Karte ansehen oder über das Dropdown-Menü einen bestimmten Standort auswählen bzw. nach einem suchen.",
                highlightSelector: null,
                position: { bottom: "150px", right: "400px" },
                highlightClass: "highlight-tutorial",
                onStepPrepare: "openMapOptions",
                onStepShow: "showFiltersDropdownHighlight",
                onStepNext: null,
            },
        ],
    },
    {
        title: "Standortdetails",
        steps: [
            {
                title: "Detailansicht Staatspolizeileitstelle",
                description:
                    "Wir haben nun den Standort der Staatspolizeileitstelle ausgewählt und können uns diesen im Detail ansehen.",
                highlightSelector: null,
                position: {
                    top: "150px",
                    left: "400px",
                },
                onStepShow: "selectLocation",
                highlightClass: "highlight-tutorial",
                onStepPrepare: "selectLocation",
                onStepNext: null,
                backdropOpacity: 30,
                backdropBlur: 0,
            },
            {
                title: "Informationsübersicht",
                description:
                    "Hier findest Du verschiedene Informationen – z. B. zur Laufzeit, zu den einzelnen Standorten, zum Personal der Gestapostelle, zu Ereignissen mit Beteiligung der Gestapostelle sowie zu nachgeordneten Gestapo-Orten und heutigen Erinnerungsorten.",
                highlightSelector: null,
                position: {
                    top: "200px",
                    left: "400px",
                },
                onStepPrepare: "initDetailOptionsHighlights",
                highlightClass: "highlight-tutorial",
            },
            {
                title: "Standorte",
                description:
                    "Die Staatspolizeileitstelle Hannover hatte mehrere Standorte. Hier siehst du den Standort in der Schlägerstraße. Alle unterstrichenen Angaben sind klickbar.",
                highlightSelector: null,
                onStepPrepare: "initDetailOptionsHighlights",
                onStepShow: "showInformationAddressHighlight",
                scrollTo: "#information-address-highlight",
                position: {
                    top: "250px",
                    left: "400px",
                },
            },
            {
                title: "Personal-Informationen",
                description:
                    "Per Klick kannst Du auch Informationen über das Personal abrufen. Wir klicken beispielsweise auf die Person 'Walter Blume'.",
                highlightSelector: null,
                onStepPrepare: "initDetailOptionsHighlights",
                onStepShow: "showInformationPersonalHighlight",
                scrollTo: "#information-personal-highlight",
                position: {
                    top: "300px",
                    left: "400px",
                },
            },
            {
                title: "Personendetails",
                description:
                    "Hier siehst Du die Detailinformationen zur Person.",
                highlightSelector: null,
                position: {
                    top: "350px",
                    left: "400px",
                },
                onStepPrepare: "initDetailOptionsHighlights",
                onStepShow: "openPersonDetails",
                onStepNext: null,
            },
            {
                title: "Navigation zurück",
                description:
                    "Mit diesem Button gelangst Du zurück zur Staatspolizeileitstelle Hannover.",
                highlightSelector: null,
                position: { top: "150px", left: "400px" },
                onStepPrepare: "initDetailOptionsHighlights",
                onStepShow: "showInformationBackHighlight",
                scrollTo: "#information-back-highlight",
                onStepNext: null,
            },
            {
                title: "Ereignisse",
                description:
                    "Hier findest Du Ereignisse, an denen die Staatspolizeileitstelle Hannover als Täterinstitution beteiligt war. In diesem Beispiel: die Überwachung des sogenannten 'Judenhauses' in der Knochenhauerstraße 61.",
                highlightSelector: null, // Selector for events section
                onStepPrepare: "selectLocation",
                onStepShow: "showInformationEventsHighlight",
                scrollTo: "#information-events-highlight",
                position: {
                    top: "400px",
                    left: "400px",
                },
            },
            {
                title: "Ereignisse Details",
                description:
                    "Hier stehen alle Detail Informationen über die entsprechenden Ereignisse.",
                highlightSelector: null,
                onStepShow: "openEvent",
                onStepPrepare: "initDetailOptionsHighlights",
                position: {
                    top: "450px",
                    left: "400px",
                },
            },
            {
                title: "Täterinstitutionen",
                description:
                    "Über einen Klick auf die Täterinstitution – in diesem Fall die Staatspolizeileitstelle Hannover – kommst Du zurück zum entsprechenden Eintrag.",
                highlightSelector: ".perpetrator-link", // Selector for perpetrator link
                onStepPrepare: "initDetailOptionsHighlights",
                onStepShow: "showInformationInstitutionsHighlight",
                position: {
                    top: "450px",
                    left: "400px",
                },
            },
            {
                title: "Verknüpfte Organisationen",
                description:
                    "Auch nachgeordnete Organisationen, Vorgängerorganisation (zeitlich) und Erinnerungsorte sind anklickbar.",
                onStepPrepare: "selectLocation",
                onStepShow: "showInformationOrganisationsHighlight",
                scrollTo: "#information-organisations-highlight",
                position: {
                    top: "500px",
                    left: "400px",
                },
            },
            {
                title: "Wikidata",
                description:
                    "Hier kannst Du den zugehörigen Wikidata Eintrag öffnen.",
                position: { top: "0px", left: "400px" },
                onStepPrepare: "initDetailOptionsHighlights",
                onStepShow: "showWikidataButtonHighlight",
                scrollTo: "#information-wikidata-highlight",
                onStepNext: null,
            },
            {
                title: "Ansicht schließen",
                description:
                    "Hier kannst Du die Detailansicht des Standorts wieder schließen.",
                position: { top: "0px", left: "400px" },
                onStepPrepare: "initDetailOptionsHighlights",
                onStepShow: "showInformationCloseButtonHighlight",
                scrollTo: "#information-button-back-highlight",
                onStepNext: null,
            },
        ],
    },
    {
        title: "Zeitfilter",
        steps: [
            {
                title: "Zeitfilter aktivieren",
                description:
                    "Hier kannst Du den Zeitfilter aktivieren, um gezielt nach Zeiträumen oder Zeitpunkten zu filtern. <br> Wir klicken auf den Zeitfilter-Button, um die Filteroption zu öffnen.",
                highlightSelector: ".button-timeline", // Selector for timeline button
                position: { bottom: "100px", right: "100px" },
                highlightClass: "highlight-tutorial",
                onStepShow: "closeTimeline",
                onStepPrepare: "closeAllNavigation",
                onStepNext: "setMapToCenter",
            },
            {
                title: "Zeitfilter deaktivieren",
                description:
                    "Hier kannst Du den Zeitfilter wieder deaktivieren und alle Ereignisse ohne zeitliche Einschränkung anzeigen lassen.",
                onStepPrepare: "openTimeline",
                onStepShow: "showTimelineButtonHighlight",
                position: { bottom: "200px", right: "100px" },
            },
            {
                title: "Filtermodus",
                description:
                    "Hier kannst Du den Filtermodus wechseln. Aktuell ist der Modus 'Zeitraum' aktiv.",
                onStepPrepare: "initTimelineOptionsHighlights",
                onStepShow: "showTimelineModeHighlight",
                position: { bottom: "200px", right: "100px" },
            },
            {
                title: "Zeitraum festlegen",
                description:
                    "In diesem Modus kannst Du einen Zeitraum festlegen – mit den beiden roten Reglern links und rechts.",
                onStepPrepare: "initTimelineOptionsHighlights",
                onStepShow: "showTimelineFilterZoneHighlight",
                position: { bottom: "200px", right: "100px" },
                onStepNext: null,
            },
            {
                title: "Zeitraumfilterung",
                description:
                    "Die Karte zeigt nun alle Ereignisse und Orte, die im gewählten Zeitraum relevant sind.",
                onStepPrepare: "initTimelineOptionsHighlights",
                onStepShow: "setFilterTimeZone",
                backdropOpacity: 30,
                backdropBlur: 0,
                position: { bottom: "200px", right: "100px" },
            },
            {
                title: "Zeitpunkt-Modus",
                description: "Wechseln wir nun in den Modus 'Zeitpunkt'.",
                onStepPrepare: "initTimelineOptionsHighlights",
                onStepShow: "showTimelineModeHighlight",
                onStepNext: "setTimePointMode",
                position: { bottom: "200px", right: "100px" },
            },
            {
                title: "Zeitpunkt wählen",
                description:
                    "In diesem Modus kannst Du gezielt zu einem bestimmten Zeitpunkt springen.",
                onStepPrepare: "initTimelineOptionsHighlights",
                onStepShow: "showTimelineFilterZoneHighlight",
                onStepNext: "setFilterTimePoint",
                position: { bottom: "200px", right: "100px" },
            },
            {
                title: "Ereignisse im Zeitpunkt",
                description:
                    "Jetzt werden nur noch die Ereignisse angezeigt, die im Oktober 1936 stattgefunden haben – zum Beispiel zur Verfolgung der Sozialistischen Front.",
                onStepPrepare: "zoomHannover",
                onStepShow: "initTimelineOptionsHighlights",
                backdropOpacity: 30,
                backdropBlur: 0,
                position: { bottom: "200px", right: "100px" },
            },
            {
                title: "Zeitfilter schließen",
                description:
                    "Über diesen Button kannst du den Zeitfilter schließen.",
                onStepPrepare: "openTimeline",
                onStepShow: "showTimelineCloseButtonHighlight",
                position: { bottom: "200px", right: "100px" },
            },
        ],
    },
    {
        title: "Abschluss",
        steps: [
            {
                title: "Vielen Dank",
                description:
                    "Danke, dass Du Dich mit der Geschichte des Gestapo-Terrors in Niedersachsen auseinandersetzt. <br />Hiermit sind wir am Ende des Tutorials angekommen. <br /> Du kannst das Tutorial jederzeit neustarten indem du den 'Tutorial' Button drückst. Wenn Du Fragen oder Anmerkungen hast, kontaktiere uns gerne per E-Mail: ogt.project@tib.eu.",
                position: { bottom: "100px", left: "80px" },
                highlightClass: "highlight-tutorial",
                onStepPrepare: "closeTimeline",
                onStepShow: null,
                onStepNext: null,
            },
        ],
    },
];

// Computed properties for current chapter and step
const currentChapter = computed(() => {
    return chapters[currentChapterIndex.value] || {};
});

// Compute the backdrop style with opacity and blur based on the current step
const backdropStyle = computed(() => {
    let opacity = 0.5; // Default 50% opacity
    let blur = 2; // Default 2px blur

    // Set custom opacity if provided
    if (
        currentStep.value &&
        typeof currentStep.value.backdropOpacity !== "undefined"
    ) {
        opacity = currentStep.value.backdropOpacity / 100;
    }

    // Set custom blur if provided
    if (
        currentStep.value &&
        typeof currentStep.value.backdropBlur !== "undefined"
    ) {
        blur = currentStep.value.backdropBlur;
    }

    return {
        backgroundColor: `rgba(0, 0, 0, ${opacity})`,
        backdropFilter: blur === 0 ? "none" : `blur(${blur}px)`,
    };
});

const currentChapterSteps = computed(() => {
    return currentChapter.value.steps || [];
});

const currentStep = computed(() => {
    return currentChapterSteps.value[currentStepIndexInChapter.value] || {};
});

const isLastStep = computed(() => {
    return (
        currentChapterIndex.value === chapters.length - 1 &&
        currentStepIndexInChapter.value === currentChapterSteps.value.length - 1
    );
});

const canGoBack = computed(() => {
    return !(
        currentChapterIndex.value === 0 && currentStepIndexInChapter.value === 0
    );
});

// Position and highlight styles
const positionStyle = computed(() => {
    if (!currentStep.value.position) return {};

    return {
        position: "absolute",
        ...currentStep.value.position,
    };
});

const highlightStyle = computed(() => {
    if (!highlightElement.value) return { display: "none" };

    // Skip creating highlight for glossary buttons - we'll use the custom one
    if (currentStep.value?.highlightSelector === ".glossary-buttons-column") {
        return { display: "none" };
    }

    const rect = highlightElement.value.getBoundingClientRect();
    const padding = currentStep.value.highlightPadding || 10;

    return {
        top: `${rect.top - padding}px`,
        left: `${rect.left - padding}px`,
        width: `${rect.width + padding * 2}px`,
        height: `${rect.height + padding * 2}px`,
        borderRadius: currentStep.value.highlightBorderRadius || "4px",
    };
});

// Methods for navigation
// Function to update the highlight element when the step changes
const updateHighlightElement = () => {
    if (!currentStep.value?.highlightSelector) {
        highlightElement.value = null;
        glossaryButtonsFound.value = false;
        return;
    }

    // Use querySelectorAll to handle multiple elements matching the selector
    const elements = document.querySelectorAll(
        currentStep.value.highlightSelector,
    );

    // If we have at least one matching element, use the first one
    if (elements.length > 0) {
        highlightElement.value = elements[0];
        console.log(
            "Found highlight element:",
            currentStep.value.highlightSelector,
        );

        // Special case for timeline button to ensure visibility
        if (currentStep.value.highlightSelector === ".button-timeline") {
            // Force higher z-index for the button
            highlightElement.value.style.zIndex = "10001";
        }

        // If we found the element, no need to retry
        if (
            currentStep.value?.highlightSelector === ".glossary-buttons-column"
        ) {
            glossaryButtonsFound.value = true;
        }
    } else {
        highlightElement.value = null;
        console.warn(
            "No element found for selector:",
            currentStep.value.highlightSelector,
        );

        // Only retry once if needed for special cases
        if (
            currentStep.value?.highlightSelector ===
                ".glossary-buttons-column" &&
            !glossaryButtonsFound.value
        ) {
            glossaryButtonsFound.value = true; // Mark as tried
            setTimeout(updateHighlightElement, 500);
        }
    }
};

const nextStep = () => {
    if (
        currentStepIndexInChapter.value <
        currentChapterSteps.value.length - 1
    ) {
        currentStepIndexInChapter.value++;
        console.log("Moving to next step:", currentStep.value?.title);

        // Proceed with normal tutorial flow
        // Simplify the execution flow to reduce multiple calls
        setTimeout(() => {
            // First, handle onStepPrepare action if defined
            if (currentStep.value?.onStepPrepare) {
                console.log(
                    "Executing onStepPrepare action:",
                    currentStep.value.onStepPrepare,
                );
                executeAction(currentStep.value.onStepPrepare);
            }

            // Then update highlights and handle onStepShow after preparation completes
            setTimeout(() => {
                updateHighlightElement();
                applyHighlightClass();

                // Handle onStepShow action
                if (currentStep.value?.onStepShow) {
                    console.log(
                        "Executing onStepShow action:",
                        currentStep.value.onStepShow,
                    );
                    executeAction(currentStep.value.onStepShow);
                }

                if (currentStep.value?.scrollTo) {
                    // Small delay to make sure highlight is visible before scrolling
                    setTimeout(() => {
                        executeAction("scrollTo", currentStep.value.scrollTo);
                    }, 300);
                }

                // Handle onStepNext action with delay
                if (currentStep.value?.onStepNext) {
                    setTimeout(() => {
                        console.log(
                            "Executing onStepNext action:",
                            currentStep.value.onStepNext,
                        );
                        executeAction(currentStep.value.onStepNext);
                    }, 500);
                }
            }, 600);
        }, 100);
    } else if (currentChapterIndex.value < chapters.length - 1) {
        nextChapter();
    }
};

const prevStep = () => {
    if (currentStepIndexInChapter.value > 0) {
        currentStepIndexInChapter.value--;

        setTimeout(() => {
            updateHighlightElement();
            applyHighlightClass();

            // Execute onStepPrepare action if defined
            if (currentStep.value?.onStepPrepare) {
                console.log(
                    "Executing onStepPrepare in prevStep:",
                    currentStep.value.onStepPrepare,
                );
                executeAction(currentStep.value.onStepPrepare);

                // Execute onStepShow after the preparation action has taken effect
                setTimeout(() => {
                    if (currentStep.value?.onStepShow) {
                        executeAction(currentStep.value?.onStepShow);
                    }

                    // Execute onStepNext after a further delay
                    setTimeout(() => {
                        if (currentStep.value?.onStepNext) {
                            executeAction(currentStep.value?.onStepNext);
                        }
                    }, 500);
                }, 500);
            }
        }, 200);
    } else if (currentChapterIndex.value > 0) {
        prevChapter();
    }
};

const nextChapter = () => {
    if (currentChapterIndex.value < chapters.length - 1) {
        currentChapterIndex.value++;
        currentStepIndexInChapter.value = 0;

        setTimeout(() => {
            updateHighlightElement();
            applyHighlightClass();

            // Execute onStepPrepare action if defined
            if (currentStep.value?.onStepPrepare) {
                console.log(
                    "Executing onStepPrepare in nextChapter:",
                    currentStep.value.onStepPrepare,
                );
                executeAction(currentStep.value.onStepPrepare);

                // Execute onStepShow after the preparation action has taken effect
                setTimeout(() => {
                    if (currentStep.value?.onStepShow) {
                        executeAction(currentStep.value?.onStepShow);
                    }

                    // Execute onStepNext after a further delay
                    setTimeout(() => {
                        if (currentStep.value?.onStepNext) {
                            executeAction(currentStep.value?.onStepNext);
                        }
                    }, 500);
                }, 500);
            }
        }, 200);
    }
};

const prevChapter = () => {
    if (currentChapterIndex.value > 0) {
        currentChapterIndex.value--;
        currentStepIndexInChapter.value = currentChapter.value.steps.length - 1;

        // Execute the step prepare action if defined
        if (currentStep.value?.onStepPrepare) {
            executeAction(currentStep.value.onStepPrepare);

            // Add a delay for updating the highlight if it's a glossary step
            if (
                currentStep.value?.highlightSelector ===
                ".glossary-buttons-column"
            ) {
                setTimeout(updateHighlightElement, 1000);
            }
        }

        setTimeout(() => {
            updateHighlightElement();
            applyHighlightClass();
        }, 100);
        executeAction(currentStep.value?.onShowAction);
    }
};

const goToChapter = (chapterIndex) => {
    if (chapterIndex >= 0 && chapterIndex < chapters.length) {
        currentChapterIndex.value = chapterIndex;
        currentStepIndexInChapter.value = 0;

        // Execute the preparation action if defined
        const targetChapter = chapters[chapterIndex];
        const targetStep = targetChapter.steps[0];
        if (targetStep.onStepPrepare) {
            executeAction(targetStep.onStepPrepare);
        }

        // Execute the show action after a delay
        setTimeout(() => {
            if (targetStep.onStepShow) {
                executeAction(targetStep.onStepShow);
            }

            // Execute the next action after a further delay
            setTimeout(() => {
                if (targetStep.onStepNext) {
                    executeAction(targetStep.onStepNext);
                }
            }, 500);
        }, 500);

        setTimeout(() => {
            updateHighlightElement();
            applyHighlightClass();

            // Execute onStepShow action
            if (currentStep.value?.onStepShow) {
                executeAction(currentStep.value.onStepShow);

                // Execute onStepNext after a delay
                setTimeout(() => {
                    if (currentStep.value?.onStepNext) {
                        executeAction(currentStep.value.onStepNext);
                    }
                }, 500);
            }
        }, 500);
    }
};

const toggleChapterMenu = () => {
    chapterMenuOpen.value = !chapterMenuOpen.value;
};

const executeAction = (actionName, options = null) => {
    if (!actionName) {
        console.log("No action name provided");
        return;
    }

    console.log("Executing action:", actionName, options);
    setTimeout(() => {
        updateHighlightElement();
        applyHighlightClass();
    }, 500);

    // No need to update the highlight here as we're using custom events
    setTimeout(() => {
        switch (actionName) {
            // MapOptions Highlights
            case "showGlossaryHighlight":
                window.dispatchEvent(new Event("show-glossary-highlight"));
                break;
            case "hideGlossaryHighlight":
                window.dispatchEvent(new Event("hide-glossary-highlight"));
                break;
            case "showFiltersRowHighlight":
                window.dispatchEvent(new Event("show-filters-row-highlight"));
                break;
            case "hideFiltersRowHighlight":
                window.dispatchEvent(new Event("hide-filters-row-highlight"));
                break;
            case "showFiltersCheckboxAllHighlight":
                window.dispatchEvent(
                    new Event("show-filters-checkbox-all-highlight"),
                );
                break;
            case "hideFiltersCheckboxAllHighlight":
                window.dispatchEvent(
                    new Event("hide-filters-checkbox-all-highlight"),
                );
                break;
            case "showFilterCheckboxStaatspolizeileitstelleHighlight":
                window.dispatchEvent(
                    new Event(
                        "show-filter-checkbox-staatspolizeileitstellen-highlight",
                    ),
                );
                break;
            case "hideFilterCheckboxStaatspolizeileitstelleHighlight":
                window.dispatchEvent(
                    new Event(
                        "hide-filter-checkbox-staatspolizeileitstellen-highlight",
                    ),
                );
                break;
            case "showFiltersDropdownHighlight":
                window.dispatchEvent(
                    new Event("show-filter-dropdown-highlight"),
                );
                break;
            case "hideFiltersDropdownHighlight":
                window.dispatchEvent(
                    new Event("hide-filter-dropdown-highlight"),
                );
                break;
            // Place Detail Highlights
            case "showInformationAddressHighlight":
                window.dispatchEvent(
                    new Event("show-information-address-highlight"),
                );
                setTimeout(() => {
                    if (props.selectLocation) {
                        props.selectLocation({
                            locationId: "Q106625639",
                            lat: 52.364983111,
                            lng: 9.748284833,
                            zoom: 16,
                        });
                    }
                }, 500);
                break;
            case "hideInformationAddressHighlight":
                window.dispatchEvent(
                    new Event("hide-information-address-highlight"),
                );
                break;
            case "showInformationPersonalHighlight":
                window.dispatchEvent(
                    new Event("show-information-personal-highlight"),
                );
                break;
            case "hideInformationPersonalHighlight":
                window.dispatchEvent(
                    new Event("hide-information-personal-highlight"),
                );
                break;
            case "showInformationBackHighlight":
                window.dispatchEvent(
                    new Event("show-information-back-highlight"),
                );
                break;
            case "hideInformationBackHighlight":
                window.dispatchEvent(
                    new Event("show-information-back-highlight"),
                );
                break;
            case "showInformationEventsHighlight":
                window.dispatchEvent(
                    new Event("show-information-events-highlight"),
                );
                break;
            case "hideInformationEventsHighlight":
                window.dispatchEvent(
                    new Event("show-information-events-highlight"),
                );
                break;
            case "showInformationInstitutionsHighlight":
                window.dispatchEvent(
                    new Event("show-information-institutions-highlight"),
                );
                break;
            case "hideInformationInstitutionsHighlight":
                window.dispatchEvent(
                    new Event("hide-information-institutions-highlight"),
                );
                break;
            case "showInformationOrganisationsHighlight":
                window.dispatchEvent(
                    new Event("show-information-organisations-highlight"),
                );
                break;
            case "hideInformationOrganisationsHighlight":
                window.dispatchEvent(
                    new Event("hide-information-organisations-highlight"),
                );
                break;
            case "showInformationCloseButtonHighlight":
                window.dispatchEvent(
                    new Event("show-information-close-button-highlight"),
                );
                break;
            case "hideInformationCloseButtonHighlight":
                window.dispatchEvent(
                    new Event("hide-information-close-button-highlight"),
                );
                break;
            case "showInformationWikidatanHighlight":
                window.dispatchEvent(
                    new Event("show-information-wikidata-highlight"),
                );
                break;
            case "hideInformationWikidatanHighlight":
                window.dispatchEvent(
                    new Event("hide-information-wikidata-highlight"),
                );
                break;
            case "showTimelineButtonHighlight":
                window.dispatchEvent(
                    new Event("show-timeline-button-highlight"),
                );
                break;
            case "hideTimelineButtonHighlight":
                window.dispatchEvent(
                    new Event("hide-timeline-button-highlight"),
                );
                break;
            case "showTimelineModeHighlight":
                window.dispatchEvent(new Event("show-timeline-mode-highlight"));
                break;
            case "hideTimelineModeHighlight":
                window.dispatchEvent(new Event("hide-timeline-mode-highlight"));
                break;
            case "showTimelineFilterZoneHighlight":
                window.dispatchEvent(
                    new Event("show-timeline-filterzone-highlight"),
                );
                break;
            case "hideTimelineFilterZoneHighlight":
                window.dispatchEvent(
                    new Event("hide-timeline-filterzone-highlight"),
                );
                break;
            case "showTimelineCloseButtonHighlight":
                window.dispatchEvent(
                    new Event("hide-timeline-close-button-highlight"),
                );
                break;

            // Filter actions
            case "deactivateAllFilters":
                window.dispatchEvent(
                    new Event("show-filters-checkbox-all-highlight"),
                );
                setTimeout(() => {
                    window.dispatchEvent(new Event("deactivate-all-filters"));
                }, 500);
                break;
            case "activateStaatspolizeileitstellen":
                window.dispatchEvent(
                    new Event("hide-filters-checkbox-all-highlight"),
                );
                setTimeout(() => {
                    window.dispatchEvent(
                        new Event("activate-staatspolizeileitstellen"),
                    );
                }, 500);
                break;
            case "openTimeline":
                // Always use NavigationHelper function directly
                if (props.openTimeline) {
                    console.log(
                        "Opening timeline using NavigationHelper function",
                    );
                    props.openTimeline();

                    // Also dispatch event to activate the filter
                    window.dispatchEvent(new Event("open-timeline-filter"));

                    // Clear any previous highlights
                    closeAllHighlights();

                    // Update highlight elements after timeline is open
                    setTimeout(() => {
                        updateHighlightElement();
                        applyHighlightClass();
                    }, 800);
                } else {
                    console.error("Timeline control function not available!");
                }
                break;
            case "closeTimeline":
                if (props.closeTimeline) {
                    console.log(
                        "Closing timeline using NavigationHelper function",
                    );
                    props.closeTimeline();
                    // Send event to both close timeline and deactivate the filter
                    window.dispatchEvent(new Event("close-timeline-filter"));
                    closeAllHighlights();
                    setTimeout(updateHighlightElement, 500);
                } else {
                    console.error("Timeline close function not available!");
                }
                break;
            // Map Options Actions
            case "openMapOptions":
                if (props.openMapOptions) {
                    console.log(
                        "Opening map options using NavigationHelper function",
                    );
                    props.openMapOptions();
                    resetMapOptionsHighlights();
                    setTimeout(() => {
                        updateHighlightElement();
                        applyHighlightClass();
                    }, 500);
                } else {
                    console.error(
                        "Map options control function not available!",
                    );
                }
                break;
            case "closeMapOptions":
                if (props.closeMapOptions) {
                    console.log(
                        "Closing map options using NavigationHelper function",
                    );
                    props.closeMapOptions();
                    closeAllHighlights();
                    setTimeout(updateHighlightElement, 500);
                } else {
                    console.error("Map options close function not available!");
                }
                break;
            case "closeAllNavigation":
                closeAllNavigation();
                break;
            case "handleMapOptions":
                if (props.openMapOptions) {
                    console.log("Handling map options with openMapOptions");
                    props.openMapOptions();
                    resetMapOptionsHighlights();
                    setTimeout(updateHighlightElement, 500);
                } else if (props.toggleMapOptions) {
                    console.log(
                        "Handling map options with toggleMapOptions (fallback)",
                    );
                    props.toggleMapOptions();
                    resetMapOptionsHighlights();
                    setTimeout(updateHighlightElement, 500);
                } else {
                    console.error(
                        "No map options control functions available!",
                    );
                }
                break;
            case "handleCloseMapOptions":
                if (props.closeMapOptions) {
                    console.log(
                        "Closing map options using NavigationHelper function",
                    );
                    props.closeMapOptions();
                    closeAllHighlights();
                    setTimeout(updateHighlightElement, 500);
                } else {
                    console.error("Map options close function not available!");
                }
                break;
            // This case was already handled earlier in the switch statement
            case "handleDuplicateMapOptions":
                if (props.openMapOptions) {
                    console.log(
                        "Opening map options using NavigationHelper function",
                    );
                    props.openMapOptions();
                    resetMapOptionsHighlights();
                    setTimeout(updateHighlightElement, 500);
                } else {
                    console.error("Map options function not available!");
                }
                break;
            case "setFilterTimeZone":
                window.dispatchEvent(new Event("set-filter-timezone"));
                break;
            case "setTimePointMode":
                window.dispatchEvent(new Event("set-timepoint-mode"));
                break;
            case "setTimeZoneMode":
                window.dispatchEvent(new Event("set-timezone-mode"));
                break;
            case "setFilterTimePoint":
                window.dispatchEvent(new Event("set-filter-timepoint"));
                break;
            case "closeTimelineFilter":
                window.dispatchEvent(new Event("close-timeline-filter"));
                break;
            case "openTimelineFilter":
                window.dispatchEvent(new Event("open-timeline-filter"));
                break;
            // Filter Actions
            case "activateFilter":
                if (props.activateFilter)
                    props.activateFilter("Staatspolizeileitstellen");
                break;
            // Location Actions
            case "selectLocation":
                if (props.selectLocation) props.selectLocation("Q106625639");
                break;
            // Opens Event Q134597104: "Überwachung des 'Judenhauses' in der Brabeckstraße 86"
            case "openEvent":
                if (props.selectLocation) props.selectLocation("Q134597104");
                initDetailOptionsHighlights();
                break;
            // Person Details Actions
            case "openPersonDetails":
                if (props.openPersonDetails) {
                    props.openPersonDetails({
                        id: "Q68656",
                        group: "perpetrators",
                    });
                }
                break;
            case "initDetailOptionsHighlights":
                initDetailOptionsHighlights();
                break;
            case "initTimelineOptionsHighlights":
                initTimelineOptionsHighlights();
                break;
            // Navigation Actions
            case "closeDetails":
                if (props.closeDetails) props.closeDetails();
                break;
            case "openMainMenu":
                if (props.openMainMenu) props.openMainMenu();
                break;
            // Time Filter Actions
            case "setTimeFilter":
                if (props.setTimeFilter)
                    props.setTimeFilter({ start: "1933-08", end: "1945-03" });
                break;
            case "switchTimeFilterMode":
                if (props.switchTimeFilterMode)
                    props.switchTimeFilterMode("point");
                break;
            case "setMapToCenter":
                if (props.setMapView) {
                    props.setMapView([52.8398667, 9.0759744], 8);
                }
                break;
            case "zoomHannover":
                if (props.setMapView) {
                    props.setMapView(
                        [52.37711869644347, 9.749078750610353],
                        13,
                    );
                }
                break;
            case "scrollTo":
                if (options && typeof options === "string") {
                    setTimeout(() => {
                        const element = document.querySelector(options);
                        if (element) {
                            // Make sure element is visible first
                            element.style.display = "block";

                            // Use both methods for better compatibility
                            // 1. Traditional scrollIntoView
                            element.scrollIntoView({
                                behavior: "smooth",
                                block: "center",
                                inline: "nearest",
                            });

                            // 2. Also scroll container if element is inside a scrollable container
                            const scrollContainer = element.closest(
                                ".v-navigation-drawer__content, .placeselection-div, .place-info-sidebar",
                            );
                            if (scrollContainer) {
                                const elementRect =
                                    element.getBoundingClientRect();
                                const containerRect =
                                    scrollContainer.getBoundingClientRect();

                                // Calculate the scroll offset to center the element in the container
                                const offset =
                                    elementRect.top -
                                    containerRect.top -
                                    (containerRect.height -
                                        elementRect.height) /
                                        2;

                                scrollContainer.scrollBy({
                                    top: offset,
                                    behavior: "smooth",
                                });
                            }
                        } else {
                            console.warn(
                                `Element not found for scrollTo: ${options}`,
                            );
                        }
                    }, 300); // Additional delay to ensure element is rendered
                } else {
                    console.warn("scrollTo action requires a selector string");
                }
                break;
            default:
                console.warn(`Unbekannte Aktion: ${actionName}`);
        }
    }, 100); // Small delay to ensure UI is ready
};

const resetMapOptionsHighlights = () => {
    // MapOption Highlights
    window.dispatchEvent(new Event("hide-filters-row-highlight"));
    window.dispatchEvent(new Event("hide-glossary-highlight"));
    window.dispatchEvent(new Event("hide-filters-checkbox-all-highlight"));
    window.dispatchEvent(
        new Event("hide-filter-checkbox-staatspolizeileitstellen-highlight"),
    );
    window.dispatchEvent(new Event("hide-filter-dropdown-highlight"));
};

const initDetailOptionsHighlights = () => {
    window.dispatchEvent(new Event("hide-information-address-highlight"));
    window.dispatchEvent(new Event("hide-information-personal-highlight"));
    window.dispatchEvent(new Event("hide-information-back-highlight"));
    window.dispatchEvent(new Event("hide-information-events-highlight"));
    window.dispatchEvent(new Event("hide-information-institutions-highlight"));
    window.dispatchEvent(new Event("hide-information-back-button-highlight"));
    window.dispatchEvent(new Event("hide-information-wikidata-highlight"));
    window.dispatchEvent(new Event("hide-information-organisations-highlight"));
};

const initTimelineOptionsHighlights = () => {
    window.dispatchEvent(new Event("hide-timeline-button-highlight"));
    window.dispatchEvent(new Event("hide-timeline-mode-highlight"));
    window.dispatchEvent(new Event("hide-timeline-filterzone-highlight"));
    window.dispatchEvent(new Event("hide-timeline-close-button-highlight"));
};

const closeAllHighlights = () => {
    // Execute these functions only once to avoid multiple DOM operations
    initDetailOptionsHighlights();
    initTimelineOptionsHighlights();
    resetMapOptionsHighlights();
    // Reset highlight element
    highlightElement.value = null;
};

// Tutorial control methods
const startTutorial = () => {
    closeAllNavigation();

    currentChapterIndex.value = 0;
    currentStepIndexInChapter.value = 0;
    active.value = true;
    isFirstVisit.value = false;
    emit("start");

    // Wait for Vue to update
    setTimeout(() => {
        updateHighlightElement();
        applyHighlightClass();

        // Execute onStepPrepare action if defined
        if (currentStep.value?.onStepPrepare) {
            console.log(
                "Executing onStepPrepare in startTutorial:",
                currentStep.value.onStepPrepare,
            );
            executeAction(currentStep.value.onStepPrepare);

            // Execute onStepShow after a slight delay
            setTimeout(() => {
                if (currentStep.value?.onStepShow) {
                    executeAction(currentStep.value.onStepShow);
                }

                // Execute onStepNext after a further delay
                setTimeout(() => {
                    if (currentStep.value?.onStepNext) {
                        executeAction(currentStep.value.onStepNext);
                    }
                }, 500);
            }, 500);
        }
    }, 200);
};

const completeTutorial = () => {
    active.value = false;
    isFirstVisit.value = false;
    emit("complete");
    localStorage.setItem("tutorialCompleted", "true");

    // Hide any active tutorial highlights
    window.dispatchEvent(new Event("hide-glossary-highlight"));
    window.dispatchEvent(new Event("hide-filters-row-highlight"));
    window.dispatchEvent(new Event("hide-filters-checkbox-all-highlight"));
};

const skipTutorial = () => {
    active.value = false;
    isFirstVisit.value = false;
    emit("skip");
    localStorage.setItem("tutorialCompleted", "true");
    closeAllHighlights();
};

// Apply highlighting class to the target element
const applyHighlightClass = () => {
    // First remove the class from any elements that might have it
    document.querySelectorAll(".highlight-tutorial").forEach((el) => {
        el.classList.remove("highlight-tutorial");
        // Also remove any inline styles we might have added
        el.style.removeProperty("z-index");
        el.style.removeProperty("position");
        el.style.removeProperty("box-shadow");
        el.style.removeProperty("display");
        el.style.removeProperty("width");
        el.style.removeProperty("border-radius");
    });

    if (highlightElement.value && currentStep.value.highlightClass) {
        // Add the class to the current highlighted element
        highlightElement.value.classList.add(currentStep.value.highlightClass);

        // Only set z-index to ensure it's above the backdrop
        highlightElement.value.style.zIndex = "10000";
    }
};

const isAnyNavigationOpen = () => {
    try {
        const openDrawers = document.querySelectorAll(
            ".v-navigation-drawer--active",
        );
        if (openDrawers.length > 0) {
            return true;
        }

        const mapOptionsSidebar = document.querySelector(
            ".menuOptionsidebar.v-navigation-drawer--active, .map-options-sidebar.v-navigation-drawer--active",
        );
        if (mapOptionsSidebar) {
            return true;
        }

        const timelineToolbar = document.querySelector(
            ".timeline-toolbar.isOpen",
        );
        if (timelineToolbar) {
            return true;
        }

        return false;
    } catch {
        return false;
    }
};

const closeNavigationDirectly = () => {
    try {
        if (!isAnyNavigationOpen()) {
            return;
        }

        const mapOptionsSidebar = document.querySelector(
            ".menuOptionsidebar.v-navigation-drawer--active, .map-options-sidebar.v-navigation-drawer--active",
        );
        if (mapOptionsSidebar) {
            const mapOptionsCloseBtn = mapOptionsSidebar.querySelector(
                ".v-list-item-action .v-btn",
            );
            if (mapOptionsCloseBtn) {
                mapOptionsCloseBtn.click();
                return;
            }
        }

        const allCloseButtons = Array.from(
            document.querySelectorAll(".v-navigation-drawer--active .v-btn"),
        );
        const closeButtons = allCloseButtons.filter((btn) => {
            const iconEl = btn.querySelector(".mdi-close, .v-icon");
            const rect = btn.getBoundingClientRect();
            const isSmallButton = rect.width < 50 && rect.height < 50;
            return iconEl !== null && isSmallButton;
        });

        if (closeButtons.length > 0) {
            closeButtons.forEach((btn) => btn.click());
            return;
        }

        const backdrop = document.querySelector(".v-overlay__scrim");
        if (backdrop) {
            backdrop.click();
            return;
        }

        const mapOptionsButton = document.querySelector(".map-options-button");
        if (mapOptionsButton && mapOptionsSidebar) {
            mapOptionsButton.click();
        }
    } catch {
        // Silently ignore errors during DOM manipulation
    }
};

// Watch for changes and update UI - with optimized execution
watch(
    () => currentStep.value?.highlightSelector,
    (newSelector) => {
        if (newSelector) {
            // Single update when selector changes
            setTimeout(() => {
                updateHighlightElement();
                applyHighlightClass();
            }, 100);
        }
    },
);

// Only run actions once when tutorial becomes active
watch([active, isFirstVisit], ([activeValue, firstVisitValue]) => {
    if (activeValue || firstVisitValue) {
        // Use a single timeout to handle all initial actions
        setTimeout(() => {
            // Update highlight first
            updateHighlightElement();
            applyHighlightClass();

            // Execute initial actions in sequence with proper timing
            if (currentStep.value?.onStepShow) {
                executeAction(currentStep.value.onStepShow);

                // Only execute next action if it exists and after show action completes
                if (currentStep.value?.onStepNext) {
                    setTimeout(() => {
                        executeAction(currentStep.value.onStepNext);
                    }, 800);
                }
            }
        }, 200);
    }
});

// Helper method to ensure all navigation is closed
const closeAllNavigation = () => {
    // First try the proper function if available
    if (props.resetAllNavigation) {
        try {
            props.resetAllNavigation();
        } catch (e) {
            console.warn("Error resetting navigation:", e);
        }
    }

    // Use direct DOM method as backup - only once
    if (isAnyNavigationOpen()) {
        closeNavigationDirectly();

        // Add a final check after a small delay to ensure everything is closed
        setTimeout(() => {
            if (isAnyNavigationOpen()) {
                closeNavigationDirectly();
            }
        }, 250);
    }
};

// On component mount
// Special computed property for keyboard events
onMounted(() => {
    closeAllNavigation();

    const tutorialCompleted = localStorage.getItem("tutorialCompleted");

    if (!tutorialCompleted && !props.tutorialCompleted) {
        setTimeout(closeAllNavigation, 300);

        isFirstVisit.value = true;
        active.value = true;

        setTimeout(() => {
            updateHighlightElement();
            applyHighlightClass();
        }, 300);
    }
});

// Clean up on component unmount
onUnmounted(() => {
    // Hide any active tutorial highlights
    window.dispatchEvent(new Event("hide-glossary-highlight"));
    window.dispatchEvent(new Event("hide-filters-row-highlight"));
    window.dispatchEvent(new Event("hide-filters-checkbox-all-highlight"));

    // Clear the update interval
    if (window.tutorialHighlightInterval) {
        clearInterval(window.tutorialHighlightInterval);
        window.tutorialHighlightInterval = null;
    }
});
</script>

<style scoped>
@keyframes pulse {
    0% {
        box-shadow: 0 0 30px 15px rgba(255, 0, 0, 0.8);
    }
    50% {
        box-shadow: 0 0 40px 20px rgba(255, 0, 0, 1);
        border-color: #ff5555;
        border-width: 12px;
        background-color: rgba(255, 0, 0, 0.6);
    }
    100% {
        box-shadow: 0 0 30px 15px rgba(255, 0, 0, 0.8);
    }
}

/* Animation for the direct DOM highlight */
@keyframes glossary-highlight-pulse {
    0% {
        box-shadow: 0 0 30px 15px rgba(255, 0, 0, 0.8);
        border-color: #ff0000;
    }
    50% {
        box-shadow: 0 0 40px 20px rgba(255, 0, 0, 1);
        border-color: #ff5555;
    }
    100% {
        box-shadow: 0 0 30px 15px rgba(255, 0, 0, 0.8);
        border-color: #ff0000;
    }
}

/* Add the animation to any element with this class */
.glossary-highlight-pulse {
    animation: glossary-highlight-pulse 2s infinite;
}

@keyframes pulse-button {
    0% {
        box-shadow: 0 0 12px 4px rgba(255, 82, 82, 0.8);
    }
    50% {
        box-shadow: 0 0 16px 6px rgba(255, 82, 82, 1);
        transform: scale(1.05);
    }
    100% {
        box-shadow: 0 0 12px 4px rgba(255, 82, 82, 0.8);
    }
}

.tutorial-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1000; /* Base z-index for the tutorial overlay */
    pointer-events: none; /* Let clicks through by default */
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}

.tutorial-overlay.active {
    pointer-events: all;
    opacity: 1;
}

.tutorial-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: auto; /* Capture clicks */
    z-index: 900;
    transition:
        background-color 0.3s ease,
        backdrop-filter 0.3s ease;
}

.tutorial-content {
    position: absolute;
    padding: 24px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    min-width: 450px;
    max-width: 500px;
    z-index: 1000;
    border-left: 4px solid #1976d2;
    margin-right: 20px;
}

.tutorial-highlight {
    position: absolute;
    box-shadow: 0 0 0 2000px rgba(0, 0, 0, 0.7);
    border: 3px solid #ff5252;
    z-index: 10000; /* Higher z-index to ensure visibility over other elements */
    pointer-events: none;
    animation: pulse 2s infinite;
    border-radius: 4px;
}

.highlight-tutorial {
    position: absolute;
    box-shadow: 0 0 0 2000px rgba(0, 0, 0, 0.7);
    border: 3px solid #ff5252;
    z-index: 10000; /* Higher than backdrop but lower than sidebar */
    pointer-events: none;
    animation: pulse 2s infinite;
    border-radius: 4px;
}

.highlight-tutorial-column {
    position: fixed !important;
    box-shadow: 0 0 0 2000px rgba(0, 0, 0, 0.7);
    border: 8px solid #ff0000;
    z-index: 99999 !important; /* Extremely high z-index to ensure visibility */
    pointer-events: none;
    animation: pulse 2s infinite;
    background-color: rgba(255, 0, 0, 0.25);
    transition: all 0.3s ease-in-out;
    border-radius: 0; /* No border radius for the sidebar */
    padding: 0;
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    backdrop-filter: none !important;
}

.tutorial-backdrop {
    z-index: 900; /* Lower z-index to allow sidebar to appear above it */
    pointer-events: auto;
}

.close-tutorial {
    position: absolute;
    top: 10px;
    right: 10px;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 2000px rgba(0, 0, 0, 0.7);
    }
    50% {
        box-shadow: 0 0 0 2000px rgba(0, 0, 0, 0.5);
    }
    100% {
        box-shadow: 0 0 0 2000px rgba(0, 0, 0, 0.7);
    }
}

.tutorial-navigation {
    display: flex;
    margin-top: 20px;
}

.chapter-indicator {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.chapter-label {
    font-weight: bold;
    margin-right: 8px;
}

.chapter-title {
    font-style: italic;
}

.chapter-menu-button {
    margin: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    z-index: 10001;
}

.tutorial-button {
    z-index: 950;
    margin: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;
    background-color: #1976d2 !important;
    opacity: 1 !important;
    visibility: visible !important;
    display: flex !important;
    pointer-events: auto !important;
    color: white !important;
}

.tutorial-button:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}

.chapter-menu-panel {
    position: fixed;
    bottom: 80px;
    left: 20px;
    width: 250px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
    z-index: 10001;
    overflow: hidden;
}

.chapter-menu-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background-color: #f5f5f5;
    border-bottom: 1px solid #e0e0e0;
}

.chapter-menu-header h3 {
    margin: 0;
    font-size: 16px;
}

.chapter-menu-content {
    padding: 12px;
    max-height: 300px;
    overflow-y: auto;
}

.tutorial-overlay.active .glossary-button {
    z-index: 10000 !important;
    position: relative !important;
    pointer-events: auto !important;
    transform: scale(1.05);
}

.tutorial-overlay.active .glossary-button:hover {
    transform: scale(1.2);
    transition: transform 0.2s ease;
    box-shadow: 0 0 8px rgba(255, 255, 255, 0.7) !important;
}

:deep(.menuOptionsidebar.v-navigation-drawer) {
    z-index: 3000 !important;
}

:deep(.glossary-buttons-column) {
    position: relative;
    z-index: 1000 !important;
}

:deep(.glossary-button) {
    z-index: 10000 !important;
    position: relative;
    cursor: pointer;
    transition: transform 0.2s ease;
}

:deep(.glossary-button:hover) {
    transform: scale(1.1);
}

:deep(.v-navigation-drawer--active) {
    z-index: 3000 !important;
    overflow: visible !important;
    position: relative !important;
}

:deep(.v-navigation-drawer--active *) {
    position: relative !important;
    z-index: 20000 !important;
    opacity: 1 !important;
    visibility: visible !important;
}

:deep(.glossary-button) {
    z-index: 20000 !important;
    position: relative !important;
    cursor: pointer !important;
    border: 2px dashed transparent !important;
}

.glossary-buttons-column.highlight-tutorial {
    position: relative;
    z-index: 1000 !important;
}

.glossary-buttons-column.highlight-tutorial {
    z-index: 100001 !important;
    box-shadow: 0 0 20px 10px rgba(255, 82, 82, 0.9) !important;
    background-color: rgba(255, 82, 82, 0.1) !important;
    border-radius: 8px !important;
}

.highlight-tutorial-column {
    z-index: 100001 !important;
    position: absolute !important;
    box-shadow: 0 0 25px 15px rgba(255, 82, 82, 0.9) !important;
    border-radius: 8px !important;
    background-color: rgba(255, 82, 82, 0.1) !important;
    pointer-events: none !important;
    animation: tutorial-pulse 2s infinite !important;
}

@keyframes tutorial-pulse {
    0% {
        box-shadow: 0 0 15px 5px rgba(255, 82, 82, 0.7);
    }
    50% {
        box-shadow: 0 0 20px 10px rgba(255, 82, 82, 0.9);
    }
    100% {
        box-shadow: 0 0 15px 5px rgba(255, 82, 82, 0.7);
    }
}

.glossary-button {
    z-index: 100002 !important;
    position: relative !important;
}

.tutorial-overlay.active .glossary-button {
    pointer-events: auto !important;
    transition: transform 0.3s ease !important;
}

.tutorial-overlay.active .glossary-button:hover {
    transform: scale(1.2) !important;
    box-shadow: 0 0 10px 5px rgba(255, 255, 255, 0.5) !important;
    background-color: rgba(255, 255, 255, 0.2) !important;
}
</style>
