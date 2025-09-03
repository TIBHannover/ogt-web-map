<template>
    <div>
        <!-- Help button to start tutorial -->
        <v-btn
            v-if="!active && !isFirstVisit"
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
import { ref, computed, watch, onMounted } from "vue";

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

// Define chapters and steps based on the document
const chapters = [
    {
        title: "Willkommensansprache und Tutorial",
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
                onStepStart: null,
            },
            {
                title: "Tutorial-Anleitung",
                description:
                    "Wir zeigen Dir hier, wie die Karte funktioniert. Du kannst das Tutorial jederzeit abbrechen und die Karte selbst erkunden. Das Tutorial kann beliebig oft neu gestartet werden.",
                highlightSelector: ".tutorial-button",
                position: { bottom: "100px", left: "80px" },
                highlightClass: "highlight-tutorial",
                onStepStart: null,
            },
        ],
    },
    {
        title: "Kartenoptionen",
        steps: [
            {
                title: "Kartenoptionen-Menü",
                description:
                    "Hier kannst Du das Menü für Kartenoptionen öffnen und siehst die einzelnen Kategorien, die auf der Karte angezeigt werden.",
                highlightSelector: ".map-options-button", // Selector for the Map Options button
                position: { top: "100px", right: "80px" },
                onShowAction: null, // Don't trigger twice
                highlightClass: "highlight-tutorial",
                onStepStart: "closeMapOptions",
            },
            {
                title: "Glossareinträge",
                description:
                    "Über dieses Icon gelangst Du zu den Glossareinträgen der jeweiligen Kategorien. Als Beispiel öffnen wir den Glossareintrag zu den Arbeitserziehungslagern.",
                highlightSelector: ".glossary-button", // Selector for the Glossary button
                position: { top: "150px", right: "400px" },
                onShowAction: "openGlossaryEntry",
                highlightClass: "highlight-tutorial",
                onStepStart: "openMapOptions",
            },
            {
                title: "Weitere Schlüsselbegriffe",
                description:
                    "Hier findest Du auch weitere Schlüsselbegriffe zur Geschichte der Gestapo.",
                highlightSelector: ".glossary-content", // Selector for the Glossary content
                position: { top: "200px", right: "400px" },
                highlightClass: "highlight-tutorial",
                onStepStart: "openMapOptions",
            },
            {
                title: "Filteroptionen",
                description:
                    "Hier kannst Du auswählen, welche Kategorien auf der Karte angezeigt werden sollen.",
                highlightSelector: ".filter-options", // Selector for the Filter options
                position: { top: "120px", right: "400px" },
                highlightClass: "highlight-tutorial",
                onStepStart: "openMapOptions",
            },
            {
                title: "Alle Filter",
                description:
                    "In der Grundeinstellung werden alle Kategorien angezeigt. Hier kannst Du festlegen, ob alle oder keine Kategorien dargestellt werden sollen.",
                highlightSelector: ".all-filters", // Selector for "All Filters"
                position: { top: "150px", right: "80px" },
                onShowAction: "deactivateAllFilters",
                highlightClass: "highlight-tutorial",
                onStepStart: "openMapOptions",
            },
            {
                title: "Kategorie auswählen",
                description:
                    "Jetzt kannst Du gezielt eine Kategorie auswählen. In diesem Beispiel aktivieren wir nur die Kategorie 'Staatspolizeileitstellen'.",
                highlightSelector: ".filter-staatspolizeileitstellen", // Selector for the specific filter
                position: { top: "180px", right: "80px" },
                onShowAction: "activateFilter",
                highlightClass: "highlight-tutorial",
                onStepStart: "openMapOptions",
            },
            {
                title: "Gefilterte Anzeige",
                description:
                    "Nun werden ausschließlich Staatspolizeileitstellen auf der Karte angezeigt.",
                position: { top: "200px", right: "80px" },
                onStepStart: "openMapOptions",
            },
            {
                title: "Standorte auswählen",
                description:
                    "Du kannst Dir jetzt alle Standorte der Staatspolizeileitstellen auf der Karte ansehen oder über das Dropdown-Menü einen bestimmten Standort auswählen bzw. nach einem suchen.",
                highlightSelector: ".location-filters", // Selector for the location filters
                position: { bottom: "150px", right: "80px" },
                highlightClass: "highlight-tutorial",
                onStepStart: "openMapOptions",
            },
        ],
    },
    {
        title: "Standortdetails",
        steps: [
            {
                title: "Detailansicht Hannover",
                description:
                    "Wir haben nun den Standort der Staatspolizeileitstelle Hannover ausgewählt und können uns diesen im Detail ansehen.",
                highlightSelector: ".location-hannover", // Selector for Hannover location
                position: {
                    top: "150px",
                    left: "50%",
                    transform: "translateX(-50%)",
                },
                onShowAction: "selectLocation",
                highlightClass: "highlight-tutorial",
                onStepStart: "selectLocation",
            },
            {
                title: "Informationsübersicht",
                description:
                    "Hier findest Du verschiedene Informationen – z. B. zur Laufzeit, zu den einzelnen Standorten, zum Personal der Gestapostelle, zu Ereignissen mit Beteiligung der Gestapostelle sowie zu nachgeordneten Gestapo-Orten und heutigen Erinnerungsorten.",
                highlightSelector: ".detail-scroll-area", // Selector for the scroll area
                position: {
                    top: "200px",
                    left: "50%",
                    transform: "translateX(-50%)",
                },
                highlightClass: "highlight-tutorial",
            },
            {
                title: "Verlinkungen",
                description:
                    "Alle unterstrichenen Angaben sind klickbar. Hier springen wir z. B. zu einem weiteren Standort der Staatspolizeileitstelle Hannover.",
                highlightSelector: ".standort-links", // Selector for location links
                position: {
                    top: "250px",
                    left: "50%",
                    transform: "translateX(-50%)",
                },
            },
            {
                title: "Personal-Informationen",
                description:
                    "Per Klick kannst Du auch Informationen über das Personal abrufen.",
                highlightSelector: ".personal-section", // Selector for the personnel section
                position: {
                    top: "300px",
                    left: "50%",
                    transform: "translateX(-50%)",
                },
            },
            {
                title: "Personendetails",
                description:
                    "Hier siehst Du die Detailinformationen zur Person.",
                highlightSelector: ".person-details", // Selector for person details
                position: {
                    top: "350px",
                    left: "50%",
                    transform: "translateX(-50%)",
                },
                onShowAction: "openPersonDetails",
            },
            {
                title: "Navigation zurück",
                description:
                    "Mit diesem Button gelangst Du zurück zur Staatspolizeileitstelle Hannover.",
                highlightSelector: ".back-button", // Selector for back button
                position: { top: "150px", left: "100px" },
                onShowAction: "goBack",
            },
            {
                title: "Ereignisse",
                description:
                    "Hier findest Du Ereignisse, an denen die Staatspolizeileitstelle Hannover als Täterinstitution beteiligt war. In diesem Beispiel: die Überwachung des sogenannten 'Judenhauses' in der Knochenhauerstraße 61.",
                highlightSelector: ".events-section", // Selector for events section
                position: {
                    top: "400px",
                    left: "50%",
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
                    left: "50%",
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
                    left: "50%",
                    transform: "translateX(-50%)",
                },
            },
            {
                title: "Ansicht schließen",
                description:
                    "Hier kannst Du die Detailansicht des Standorts wieder schließen.",
                highlightSelector: ".close-button", // Selector for close button
                position: { top: "100px", right: "100px" },
                onShowAction: "closeDetails",
            },
            {
                title: "Hauptmenü",
                description:
                    "Über diesen Button kannst Du das Hauptmenü öffnen und z. B. direkt ins Glossar wechseln.",
                highlightSelector: ".main-menu-button", // Selector for main menu button
                position: { top: "50px", left: "100px" },
                onShowAction: "openMainMenu",
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
                onShowAction: null,
                onStepStart: "openTimeline",
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
                onShowAction: "setTimeFilter",
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
                onShowAction: "switchTimeFilterMode",
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
                onStepStart: null,
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

    const rect = highlightElement.value.getBoundingClientRect();

    return {
        top: `${rect.top - 10}px`,
        left: `${rect.left - 10}px`,
        width: `${rect.width + 20}px`,
        height: `${rect.height + 20}px`,
        borderRadius: currentStep.value.highlightBorderRadius || "4px",
    };
});

// Methods for navigation
const updateHighlightElement = () => {
    if (currentStep.value.highlightSelector) {
        highlightElement.value = document.querySelector(
            currentStep.value.highlightSelector,
        );
    } else {
        highlightElement.value = null;
    }
};

const nextStep = () => {
    if (
        currentStepIndexInChapter.value <
        currentChapterSteps.value.length - 1
    ) {
        currentStepIndexInChapter.value++;
        console.log("Moving to next step:", currentStep.value?.title);

        setTimeout(() => {
            // Execute the step start action if defined - with a delay to ensure Vue has updated
            if (currentStep.value?.onStepStart) {
                console.log(
                    "Executing onStepStart action:",
                    currentStep.value.onStepStart,
                );
                console.log(
                    "Executing onStepStart:",
                    currentStep.value.onStepStart,
                );
                executeAction(currentStep.value.onStepStart);
            }

            updateHighlightElement();
            applyHighlightClass();

            // Execute onShowAction after a slight delay
            setTimeout(() => {
                executeAction(currentStep.value?.onShowAction);
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

            // Execute step start action if defined
            if (currentStep.value?.onStepStart) {
                console.log(
                    "Executing onStepStart in prevStep:",
                    currentStep.value.onStepStart,
                );
                executeAction(currentStep.value.onStepStart);

                // Execute onShowAction after the action has taken effect
                setTimeout(() => {
                    if (currentStep.value?.onShowAction) {
                        executeAction(currentStep.value?.onShowAction);
                    }
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

            // Execute step start action if defined
            if (currentStep.value?.onStepStart) {
                console.log(
                    "Executing onStepStart in nextChapter:",
                    currentStep.value.onStepStart,
                );
                executeAction(currentStep.value.onStepStart);

                // Execute onShowAction after the action has taken effect
                setTimeout(() => {
                    if (currentStep.value?.onShowAction) {
                        executeAction(currentStep.value?.onShowAction);
                    }
                }, 500);
            }
        }, 200);
    }
};

const prevChapter = () => {
    if (currentChapterIndex.value > 0) {
        currentChapterIndex.value--;
        currentStepIndexInChapter.value = currentChapter.value.steps.length - 1;

        // Execute the step start action if defined
        if (currentStep.value?.onStepStart) {
            executeAction(currentStep.value.onStepStart);
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

        // Execute the step start action if defined
        const targetChapter = chapters[chapterIndex];
        const targetStep = targetChapter.steps[0];
        if (targetStep.onStepStart) {
            executeAction(targetStep.onStepStart);
        }

        setTimeout(() => {
            updateHighlightElement();
            applyHighlightClass();
        }, 100);
        executeAction(currentStep.value?.onShowAction);
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
    setTimeout(() => {
        switch (actionName) {
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
            // Already handled elsewhere
            case "openMapOptions":
                if (props.openMapOptions) {
                    props.openMapOptions();
                } else if (props.toggleMapOptions) {
                    // Try multiple selectors to find the map options sidebar
                    const isMapOptionsOpen = document.querySelector(
                        ".menuOptionsidebar.v-navigation-drawer--active, .map-options-sidebar.v-navigation-drawer--active",
                    );
                    if (!isMapOptionsOpen) {
                        console.log("Opening map options via toggleMapOptions");
                        props.toggleMapOptions();
                    }
                }
                break;
            case "closeMapOptions":
                if (props.closeMapOptions) {
                    props.closeMapOptions();
                } else if (props.toggleMapOptions) {
                    // Try multiple selectors to find the map options sidebar
                    const isMapOptionsOpen = document.querySelector(
                        ".menuOptionsidebar.v-navigation-drawer--active, .map-options-sidebar.v-navigation-drawer--active",
                    );
                    if (isMapOptionsOpen) {
                        console.log("Closing map options via toggleMapOptions");
                        props.toggleMapOptions();
                    }
                }
                break;
            case "openGlossaryEntry":
                if (props.openGlossaryEntry)
                    props.openGlossaryEntry("Arbeitserziehungslager");
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
                if (props.openGlossaryEntry)
                    props.openGlossaryEntry("Arbeitserziehungslager");
                break;
            case "activateFilter":
                if (props.activateFilter)
                    props.activateFilter("Staatspolizeileitstellen");
                break;
            case "deactivateAllFilters":
                if (props.deactivateAllFilters) props.deactivateAllFilters();
                break;
            case "selectLocation":
                if (props.selectLocation) props.selectLocation("Hannover");
                break;
            case "openPersonDetails":
                if (props.openPersonDetails)
                    props.openPersonDetails("Walter Blume");
                break;
            case "goBack":
                if (props.goBack) props.goBack();
                break;
            case "closeDetails":
                if (props.closeDetails) props.closeDetails();
                break;
            case "openMainMenu":
                if (props.openMainMenu) props.openMainMenu();
                break;
            case "setTimeFilter":
                if (props.setTimeFilter)
                    props.setTimeFilter({ start: "1933-08", end: "1945-03" });
                break;
            case "switchTimeFilterMode":
                if (props.switchTimeFilterMode)
                    props.switchTimeFilterMode("point");
                break;
            default:
                console.warn(`Unbekannte Aktion: ${actionName}`);
        }
    }, 100); // Small delay to ensure UI is ready
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

        // Execute step start action if defined
        if (currentStep.value?.onStepStart) {
            console.log(
                "Executing onStepStart in startTutorial:",
                currentStep.value.onStepStart,
            );
            executeAction(currentStep.value.onStepStart);

            // Execute onShowAction after a slight delay if it exists
            setTimeout(() => {
                if (currentStep.value?.onShowAction) {
                    executeAction(currentStep.value?.onShowAction);
                }
            }, 500);
        }
    }, 200);
};

const completeTutorial = () => {
    active.value = false;
    isFirstVisit.value = false;
    emit("complete");
    localStorage.setItem("tutorialCompleted", "true");
};

const skipTutorial = () => {
    active.value = false;
    isFirstVisit.value = false;
    emit("skip");
    localStorage.setItem("tutorialCompleted", "true");
};

// Apply highlighting class to the target element
const applyHighlightClass = () => {
    // First remove the class from any elements that might have it
    document.querySelectorAll(".highlight-tutorial").forEach((el) => {
        el.classList.remove("highlight-tutorial");
        // Also remove any inline styles we might have added
        el.style.removeProperty("z-index");
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
        executeAction(currentStep.value?.onShowAction);
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
</script>

<style scoped>
.tutorial-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.3s ease;
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
    background-color: rgba(0, 0, 0, 0.5);
    //backdrop-filter: blur(2px);
}

.tutorial-content {
    position: absolute;
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    max-width: 350px;
    z-index: 10000;
}

.tutorial-highlight {
    position: absolute;
    box-shadow: 0 0 0 2000px rgba(0, 0, 0, 0.7);
    border: 2px solid #ff5252;
    z-index: 9998;
    pointer-events: none;
    animation: pulse 2s infinite;
}

/* Make sure the tutorial backdrop doesn't cover highlighted elements */
.tutorial-backdrop {
    z-index: 9000;
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

.tutorial-button {
    position: fixed;
    bottom: 20px;
    left: 20px;
    z-index: 100 !important;
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

/* Highlighting styles */
.highlight-tutorial {
    /* Preserve original positioning properties */
    z-index: 10000 !important;
    transform: scale(1.05);
    transition: all 0.3s ease;
    box-shadow: 0 0 15px 5px rgba(255, 82, 82, 0.7);
    border-radius: 4px;
    animation: tutorial-pulse 2s infinite;
    pointer-events: auto !important;
    /* Ensure button stays clickable */
    cursor: pointer;
}

@keyframes tutorial-pulse {
    0% {
        box-shadow: 0 0 15px 5px rgba(255, 82, 82, 0.7);
    }
    50% {
        box-shadow: 0 0 25px 8px rgba(255, 82, 82, 0.9);
    }
    100% {
        box-shadow: 0 0 15px 5px rgba(255, 82, 82, 0.7);
    }
}
</style>
