<template>
    <v-btn
        v-if="!active"
        icon
        color="primary"
        class="tutorial-button"
        fixed
        bottom
        left
        size="large"
        @click="startTutorial"
    >
        <v-icon>mdi-help-circle</v-icon>
    </v-btn>
    <div>
        <div v-if="active" class="tutorial-overlay" :class="{ active }">
            <div class="tutorial-backdrop" @click="skipTutorial"></div>

            <div class="tutorial-content" :style="positionStyle">
                <div class="tutorial-step">
                    <h3>{{ currentStep.title }}</h3>
                    <p v-html="currentStep.description"></p>

                    <div class="tutorial-navigation">
                        <v-btn
                            v-if="currentStepIndex > 0"
                            variant="text"
                            @click="prevStep"
                            size="small"
                        >
                            <v-icon>mdi-arrow-left</v-icon> Zurück
                        </v-btn>

                        <v-spacer></v-spacer>

                        <v-btn
                            v-if="currentStepIndex < steps.length - 1"
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
                    </div>
                </div>
            </div>

            <div
                v-if="currentStep.highlightSelector"
                class="tutorial-highlight"
                :style="highlightStyle"
            ></div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";

const props = defineProps({
    active: {
        type: Boolean,
        default: false,
    },
    steps: {
        type: Array,
        default: () => [],
    },
    initialStep: {
        type: Number,
        default: 0,
    },
    tutorialCompleted: {
        type: Boolean,
        default: false,
    },
    toggleTimeline: Function,
    // Weitere Aktionen könnten übergeben werden
    toggleMapOptions: Function,
    zoomToLocation: Function,
});

const emit = defineEmits(["complete", "skip", "start"]);

const currentStepIndex = ref(props.initialStep);
const highlightElement = ref(null);

const currentStep = computed(() => {
    return props.steps[currentStepIndex.value] || {};
});

watch(
    () => props.active,
    (newValue) => {
        if (newValue) {
            setTimeout(updateHighlightElement, 100);

            // Führe die onShow-Aktion des aktuellen Schritts aus, falls vorhanden
            if (currentStep.value?.onShowAction) {
                executeAction(currentStep.value.onShowAction);
            }
        }
    },
);

const executeAction = (actionName) => {
    switch (actionName) {
        case "toggleTimeline":
            if (props.toggleTimeline) props.toggleTimeline();
            break;
        case "toggleMapOptions":
            if (props.toggleMapOptions) props.toggleMapOptions();
            break;
        // Weitere Aktionen hier definieren
        default:
            console.warn(`Unbekannte Aktion: ${actionName}`);
    }
};

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
    if (currentStepIndex.value < props.steps.length - 1) {
        currentStepIndex.value++;
        setTimeout(updateHighlightElement, 100);
    }
};

const prevStep = () => {
    if (currentStepIndex.value > 0) {
        currentStepIndex.value--;
        setTimeout(updateHighlightElement, 100);
    }
};

const startTutorial = () => {
    emit("start");
};

const completeTutorial = () => {
    emit("complete");
    localStorage.setItem("tutorialCompleted", "true");
};

const skipTutorial = () => {
    emit("skip");
    localStorage.setItem("tutorialCompleted", "true");
};

watch(() => currentStep.value.highlightSelector, updateHighlightElement);
watch(
    () => props.active,
    (newValue) => {
        if (newValue) {
            setTimeout(updateHighlightElement, 100);

            // Führe die onShow-Funktion des aktuellen Schritts aus, falls vorhanden
            if (currentStep.value?.onShow) {
                currentStep.value.onShow();
            }
        }
    },
);

onMounted(() => {
    if (props.active) {
        setTimeout(updateHighlightElement, 300);
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
    backdrop-filter: blur(2px);
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
    bottom: 20px !important;
    left: 20px !important;
    z-index: 1000 !important;
}
</style>
