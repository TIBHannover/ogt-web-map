<template>
    <div>
        <!--
          Help button to start tutorial
          Always visible unless the tutorial is currently active
          Will remain visible after tutorial completion
        -->
        <v-btn
            v-if="!active"
            icon
            color="primary"
            class="tutorial-button"
            size="large"
            @click="startTutorial"
        >
            <v-icon>mdi-help-circle</v-icon>
        </v-btn>

        <!-- Chapter menu button -->
        <v-btn
            v-if="active && !chapterMenuOpen"
            icon
            color="secondary"
            class="chapter-menu-button"
            size="large"
            @click="toggleChapterMenu"
        >
            <v-icon>mdi-menu-open</v-icon>
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
            <div class="tutorial-backdrop" @click="skipTutorial"></div>

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
    resetAllNavigation: {
        type: Function,
        required: false,
        default: () => {
            console.warn(
                "Default resetAllNavigation called - props not properly passed",
            );
            // Try direct DOM manipulation as fallback
            try {
                const mapOptionsButton = document.querySelector(
                    ".map-options-button",
                );
                const closeButton = document.querySelector(
                    ".v-navigation-drawer .v-btn .mdi-close",
                );
                if (mapOptionsButton && closeButton) {
                    console.log("Found close button, clicking as fallback");
                    closeButton.click();
                }
            } catch (e) {
                console.error("Fallback close failed:", e);
            }
        },
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
const highlightElement = ref(null);
const chapterMenuOpen = ref(false);
const glossaryButtonsFound = ref(false);
const glossaryButtonsCoords = ref({ top: 0, left: 0, width: 0, height: 0 });

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
            /*
            {
                title: "Glossareinträge öffnen",
                description:
                    "Klicke auf eines der Glossar-Icons (🔍) in der rot markierten Spalte, um einen Eintrag zu öffnen. Als Beispiel öffnen wir den Eintrag zum Begriff 'Gestapo'.",
                highlightSelector: null, // Not using highlight selector anymore
                position: { top: "120px", right: "400px" },
                highlightClass: null, // Not using highlight class anymore
                onStepPrepare: "openMapOptions",
                onStepShow: "showGlossaryHighlight", // Use our custom action to show highlight
                onStepNext: "openGlossaryEntry",
            },
            {
                title: "Weitere Schlüsselbegriffe",
                description:
                    "Hier findest Du auch weitere Schlüsselbegriffe zur Geschichte der Gestapo.",
                highlightSelector: ".glossary-content", // Selector for the Glossary content
                position: { top: "100px", right: "80px" },
                highlightClass: "highlight-tutorial",
                onStepPrepare: "closeMapOptions",
                onStepShow: "setMapToCenter",
                onStepNext: null,
            },
            */
            {
                title: "Filteroptionen",
                description:
                    "Hier kannst Du auswählen, welche Kategorien auf der Karte angezeigt werden sollen.",
                highlightSelector: ".filter-options", // Selector for the Filter options
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
                highlightSelector: ".filter-checkbox-all", // Selector for "All Filters" checkbox
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
                highlightSelector: ".filter-staatspolizeileitstellen", // Selector for the specific filter
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
            },
            {
                title: "Standorte auswählen",
                description:
                    "Du kannst Dir jetzt alle Standorte der Staatspolizeileitstellen auf der Karte ansehen oder über das Dropdown-Menü einen bestimmten Standort auswählen bzw. nach einem suchen.",
                highlightSelector: ".location-filters", // Selector for the location filters
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
                highlightSelector: ".location-detail", // Generic selector for location detail
                position: {
                    top: "150px",
                    left: "400px",
                },
                onStepShow: "selectLocation",
                highlightClass: "highlight-tutorial",
                onStepPrepare: "selectLocation",
                onStepNext: null,
            },
            {
                title: "Informationsübersicht",
                description:
                    "Hier findest Du verschiedene Informationen – z. B. zur Laufzeit, zu den einzelnen Standorten, zum Personal der Gestapostelle, zu Ereignissen mit Beteiligung der Gestapostelle sowie zu nachgeordneten Gestapo-Orten und heutigen Erinnerungsorten.",
                highlightSelector: ".detail-scroll-area", // Selector for the scroll area
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
                highlightSelector: ".standort-links", // Selector for location links
                onStepPrepare: "initDetailOptionsHighlights",
                onStepShow: "showInformationAddressHighlight",
                position: {
                    top: "250px",
                    left: "400px",
                },
            },
            {
                title: "Personal-Informationen",
                description:
                    "Per Klick kannst Du auch Informationen über das Personal abrufen. Wir klicken beispielsweise auf die Person 'Walter Blume'.",
                highlightSelector: ".personal-section",
                onStepPrepare: "initDetailOptionsHighlights",
                onStepShow: "showInformationPersonalHighlight",
                position: {
                    top: "300px",
                    left: "400px",
                },
            },
            {
                title: "Personendetails",
                description:
                    "Hier siehst Du die Detailinformationen zur Person.",
                highlightSelector: ".person-details", // Selector for person details
                position: {
                    top: "350px",
                    left: "400px",
                },
                onStepShow: "openPersonDetails",
                onStepNext: null,
            },
            {
                title: "Navigation zurück",
                description:
                    "Mit diesem Button gelangst Du zurück zur Staatspolizeileitstelle Hannover.",
                highlightSelector: ".back-button", // Selector for back button
                position: { top: "150px", left: "400px" },
                onStepShow: "goBack",
                onStepNext: null,
            },
            {
                title: "Ereignisse",
                description:
                    "Hier findest Du Ereignisse, an denen die Staatspolizeileitstelle Hannover als Täterinstitution beteiligt war. In diesem Beispiel: die Überwachung des sogenannten 'Judenhauses' in der Knochenhauerstraße 61.",
                highlightSelector: ".events-section", // Selector for events section
                position: {
                    top: "400px",
                    left: "400px",
                    transform: "translateX(-50%)",
                },
            },
            {
                title: "Täterinstitutionen",
                description:
                    "Über einen Klick auf die Täterinstitution – in diesem Fall die Staatspolizeileitstelle Hannover – kommst Du zurück zum entsprechenden Eintrag.",
                highlightSelector: ".perpetrator-link", // Selector for perpetrator link
                position: {
                    top: "450px",
                    left: "400px",
                    transform: "translateX(-50%)",
                },
            },
            {
                title: "Verknüpfte Organisationen",
                description:
                    "Auch nachgeordnete Organisationen, Vorgängerorganisation (zeitlich) und Erinnerungsorte sind anklickbar.",
                highlightSelector: ".related-organizations", // Selector for related organizations
                position: {
                    top: "500px",
                    left: "400px",
                    transform: "translateX(-50%)",
                },
            },
            {
                title: "Ansicht schließen",
                description:
                    "Hier kannst Du die Detailansicht des Standorts wieder schließen.",
                highlightSelector: ".close-button", // Selector for close button
                position: { top: "100px", left: "400px" },
                onStepShow: "closeDetails",
                onStepNext: null,
            },
            {
                title: "Hauptmenü",
                description:
                    "Über diesen Button kannst Du das Hauptmenü öffnen und z. B. direkt ins Glossar wechseln.",
                highlightSelector: ".main-menu-button", // Selector for main menu button
                position: { top: "50px", left: "100px" },
                onStepShow: "openMainMenu",
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
                    "Hier kannst Du den Zeitfilter aktivieren, um gezielt nach Zeiträumen oder Zeitpunkten zu filtern.",
                highlightSelector: ".timeline-button", // Selector for timeline button
                position: { bottom: "100px", right: "100px" },
                onStepShow: null,
                onStepPrepare: "openTimeline",
                onStepNext: null,
            },
            {
                title: "Zeitfilter deaktivieren",
                description:
                    "Hier kannst Du den Zeitfilter wieder deaktivieren und alle Ereignisse ohne zeitliche Einschränkung anzeigen lassen.",
                highlightSelector: ".timeline-disable-button", // Selector for timeline disable button
                position: { bottom: "150px", right: "100px" },
            },
            {
                title: "Filtermodus",
                description:
                    "Hier kannst Du den Filtermodus wechseln. Aktuell ist der Modus 'Zeitraum' aktiv.",
                highlightSelector: ".timeline-mode-switch", // Selector for timeline mode switch
                position: { bottom: "200px", right: "100px" },
            },
            {
                title: "Zeitraum festlegen",
                description:
                    "In diesem Modus kannst Du einen Zeitraum festlegen – mit den beiden roten Reglern links und rechts.",
                highlightSelector: ".timeline-range-sliders", // Selector for timeline range sliders
                position: { bottom: "250px", right: "100px" },
                onStepShow: "setTimeFilter",
                onStepNext: null,
            },
            {
                title: "Zeitraumfilterung",
                description:
                    "Die Karte zeigt nun alle Ereignisse und Orte, die im gewählten Zeitraum relevant sind.",
                highlightSelector: ".timeline-display", // Selector for timeline display
                position: { bottom: "300px", right: "100px" },
            },
            {
                title: "Zeitpunkt-Modus",
                description: "Wechseln wir nun in den Modus 'Zeitpunkt'.",
                highlightSelector: ".timeline-point-mode", // Selector for timeline point mode
                position: { bottom: "350px", right: "100px" },
                onStepShow: "switchTimeFilterMode",
                onStepNext: null,
            },
            {
                title: "Zeitpunkt wählen",
                description:
                    "In diesem Modus kannst Du gezielt zu einem bestimmten Zeitpunkt springen.",
                highlightSelector: ".timeline-point-slider", // Selector for timeline point slider
                position: { bottom: "400px", right: "100px" },
            },
            {
                title: "Ereignisse im Zeitpunkt",
                description:
                    "Jetzt werden nur noch die Ereignisse angezeigt, die im Oktober 1936 stattgefunden haben – zum Beispiel zur Verfolgung der Sozialistischen Front.",
                highlightSelector: ".timeline-events", // Selector for timeline events
                position: { bottom: "450px", right: "100px" },
            },
        ],
    },
    {
        title: "Abschluss",
        steps: [
            {
                title: "Tutorial neu starten",
                description:
                    "Hier kannst Du das Tutorial jederzeit neu starten.",
                highlightSelector: ".tutorial-button", // Selector for tutorial button
                position: { bottom: "100px", left: "80px" },
                highlightClass: "highlight-tutorial",
                onStepPrepare: null,
                onStepShow: null,
                onStepNext: null,
            },
            {
                title: "Vielen Dank",
                description:
                    "Danke, dass Du Dich mit der Geschichte des Gestapo-Terrors in Niedersachsen auseinandersetzt. Wenn Du Fragen oder Anmerkungen hast, kontaktiere uns gerne per E-Mail: ogt.project@tib.eu.",
                position: {
                    top: "50%",
                    left: "50%",
                    transform: "translate(-50%, -50%)",
                },
            },
        ],
    },
];

// Computed properties for current chapter and step
const currentChapter = computed(() => {
    return chapters[currentChapterIndex.value] || {};
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

// We don't need this computed property anymore as we're using a fixed element
const glossaryHighlightStyle = computed(() => {
    // Return empty style
    return { display: "none" };
});

// Methods for navigation
// Function to update the highlight element when the step changes
const updateHighlightElement = () => {
    if (!currentStep.value?.highlightSelector) {
        highlightElement.value = null;
        glossaryButtonsFound.value = false;
        return;
    }

    // We're using custom events for the glossary highlight now, so we don't need this code

    // Use querySelectorAll to handle multiple elements matching the selector
    const elements = document.querySelectorAll(
        currentStep.value.highlightSelector,
    );

    // If we have at least one matching element, use the first one
    if (elements.length > 0) {
        highlightElement.value = elements[0];
    } else {
        highlightElement.value = null;
    }

    // If we're highlighting glossary buttons but can't find them yet,
    // try again after a delay (map options might still be opening)
    if (
        currentStep.value?.highlightSelector === ".glossary-buttons-column" &&
        !glossaryButtonsFound.value
    ) {
        setTimeout(updateHighlightElement, 500);
    }
};

const nextStep = () => {
    if (
        currentStepIndexInChapter.value <
        currentChapterSteps.value.length - 1
    ) {
        currentStepIndexInChapter.value++;
        console.log("Moving to next step:", currentStep.value?.title);

        // The glossary highlight is now handled by custom events

        setTimeout(() => {
            // Execute the onStepPrepare action if defined - runs before the step is shown
            if (currentStep.value?.onStepPrepare) {
                console.log(
                    "Executing onStepPrepare action:",
                    currentStep.value.onStepPrepare,
                );
                // Execute the preparation action
                executeAction(currentStep.value.onStepPrepare);

                // Add a more substantial delay for updating the highlight
                // This ensures the menu has time to open fully
                setTimeout(updateHighlightElement, 1000);
            }

            updateHighlightElement();
            applyHighlightClass();

            // Execute onStepShow action after a slight delay - primary action when step is visible
            setTimeout(() => {
                executeAction(currentStep.value?.onStepShow);

                // Execute onStepNext action after a further delay - secondary action
                setTimeout(() => {
                    executeAction(currentStep.value?.onStepNext);

                    // Update highlight element again after onStepNext executes
                    if (
                        currentStep.value?.highlightSelector ===
                        ".glossary-buttons-column"
                    ) {
                        setTimeout(() => {
                            updateHighlightElement();
                            applyHighlightClass();
                        }, 500);
                    }
                }, 500);
            }, 200);
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

const executeAction = (actionName) => {
    if (!actionName) {
        console.log("No action name provided");
        return;
    }

    console.log("Executing action:", actionName);
    // Ensure highlight elements are updated with every action
    setTimeout(() => {
        console.log("Updating highlight elements - first pass");
        updateHighlightElement();
        applyHighlightClass();
    }, 300);
    // Update again after a longer delay to catch async UI changes
    setTimeout(() => {
        console.log("Updating highlight elements - second pass");
        updateHighlightElement();
        applyHighlightClass();
    }, 800);
    // Add a third update pass with even longer delay
    setTimeout(() => {
        console.log("Updating highlight elements - third pass");
        updateHighlightElement();
        applyHighlightClass();
    }, 1500);

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
                // First switch to the specific address (Schlägerstr.)
                if (props.selectLocation) {
                    props.selectLocation({
                        locationId: "Q106625639",
                        lat: 52.364983111,
                        lng: 9.748284833,
                        zoom: 16,
                    });
                }
                // Then show the highlight
                setTimeout(() => {
                    window.dispatchEvent(
                        new Event("show-information-address-highlight"),
                    );
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
            // Navigation Control Actions
            case "resetAllNavigation":
                if (props.resetAllNavigation) props.resetAllNavigation();
                break;
            case "openTimeline":
                if (props.openTimeline) {
                    props.openTimeline();
                } else if (props.toggleTimeline) {
                    const isTimelineOpen = document.querySelector(
                        ".timeline-drawer.v-navigation-drawer--active",
                    );
                    if (!isTimelineOpen) {
                        props.toggleTimeline();
                    }
                }
                break;
            case "closeTimeline":
                if (props.closeTimeline) {
                    props.closeTimeline();
                } else if (props.toggleTimeline) {
                    const isTimelineOpen = document.querySelector(
                        ".timeline-drawer.v-navigation-drawer--active",
                    );
                    if (isTimelineOpen) {
                        props.toggleTimeline();
                    }
                }
                break;
            // Map Options Actions
            case "openMapOptions":
                if (props.openMapOptions) {
                    props.openMapOptions();
                    resetHighlights();
                    setTimeout(updateHighlightElement, 800);
                }
                break;
            case "closeMapOptions":
                if (props.closeMapOptions) {
                    props.closeMapOptions();
                }
                break;
            case "handleMapOptions":
                if (props.toggleMapOptions) {
                    props.toggleMapOptions();
                } else if (props.openMapOptions) {
                    props.openMapOptions();
                } else {
                    // Try direct DOM approach
                    const mapOptionsButton = document.querySelector(
                        ".map-options-button",
                    );
                    if (mapOptionsButton) {
                        mapOptionsButton.click();
                    }
                }
                break;
            case "handleCloseMapOptions":
                if (props.closeMapOptions) {
                    props.closeMapOptions();
                } else if (props.toggleMapOptions) {
                    props.toggleMapOptions();
                }
                break;
            // This case was already handled earlier in the switch statement
            case "handleDuplicateMapOptions":
                if (props.openMapOptions) {
                    props.openMapOptions();
                } else if (props.toggleMapOptions) {
                    props.toggleMapOptions();
                } else {
                    // Try direct DOM approach
                    const mapOptionsButton = document.querySelector(
                        ".map-options-button",
                    );
                    if (mapOptionsButton) {
                        mapOptionsButton.click();
                    }
                }
                break;
            case "handleGlossaryEntry":
                if (props.openGlossaryEntry) {
                    props.openGlossaryEntry("Gestapo");
                    // Update highlight after opening glossary entry
                    setTimeout(() => {
                        updateHighlightElement();
                        applyHighlightClass();
                    }, 500);

                    // Update again after a longer delay to ensure the UI has settled
                    setTimeout(() => {
                        updateHighlightElement();
                        applyHighlightClass();
                    }, 1000);
                }
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
            // Navigation Actions
            case "goBack":
                if (props.goBack) props.goBack();
                break;
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
            default:
                console.warn(`Unbekannte Aktion: ${actionName}`);
        }
    }, 100); // Small delay to ensure UI is ready
};

const resetHighlights = () => {
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

    // Hide any active tutorial highlights
    window.dispatchEvent(new Event("hide-glossary-highlight"));
    window.dispatchEvent(new Event("hide-filters-row-highlight"));
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

        const timelineDrawer = document.querySelector(
            ".timeline-drawer.v-navigation-drawer--active",
        );
        if (timelineDrawer) {
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

// Watch for changes and update UI
watch(
    () => currentStep.value.highlightSelector,
    () => {
        updateHighlightElement();
        setTimeout(applyHighlightClass, 50);
    },
);
watch([active, isFirstVisit], ([activeValue, firstVisitValue]) => {
    if (activeValue || firstVisitValue) {
        setTimeout(updateHighlightElement, 100);
        executeAction(currentStep.value?.onStepShow);
        // Execute onStepNext after a delay
        setTimeout(() => {
            if (currentStep.value?.onStepNext) {
                executeAction(currentStep.value.onStepNext);
            }
        }, 500);
        setTimeout(applyHighlightClass, 100);
    }
});

// Helper method to ensure all navigation is closed
const closeAllNavigation = () => {
    // First try the proper function if available
    if (props.resetAllNavigation) {
        try {
            props.resetAllNavigation();
        } catch {
            // Silently handle error
        }
    } else {
        // No reset function available
    }

    // Then use direct DOM method as backup
    const isOpen = isAnyNavigationOpen();

    if (isOpen) {
        closeNavigationDirectly();
    }

    setTimeout(() => {
        if (isAnyNavigationOpen()) {
            if (props.resetAllNavigation) props.resetAllNavigation();
            closeNavigationDirectly();
        }
    }, 200);
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
    background-color: rgba(0, 0, 0, 0.6);
    pointer-events: auto; /* Capture clicks */
    z-index: 900;
    backdrop-filter: blur(2px);
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
    z-index: 1100; /* Higher than backdrop but lower than sidebar */
    pointer-events: none;
    animation: pulse 2s infinite;
    transition: all 0.3s ease-in-out;
    border-radius: 4px;
}

.highlight-tutorial {
    position: absolute;
    box-shadow: 0 0 0 2000px rgba(0, 0, 0, 0.7);
    border: 3px solid #ff5252;
    z-index: 1100; /* Higher than backdrop but lower than sidebar */
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
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    position: fixed;
    bottom: 20px;
    left: 20px;
    z-index: 10001;
}

.tutorial-button {
    position: fixed;
    bottom: 20px;
    left: 20px;
    z-index: 950;
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

.highlight-tutorial {
    z-index: 2200 !important;
    transform: scale(1.05);
    transition: all 0.3s ease;
    box-shadow: 0 0 15px 5px rgba(255, 82, 82, 0.7);
    border-radius: 4px;
    animation: tutorial-pulse 2s infinite;
    pointer-events: auto !important;
    cursor: pointer;
    position: relative !important;
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
