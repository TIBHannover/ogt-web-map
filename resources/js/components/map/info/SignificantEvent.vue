<template>
    <div class="position-relative">
        <div
            id="information-events-highlight"
            class="information-events-highlight tutorial-rectangle-highlight"
            style="display: none"
        ></div>
        <!-- associated significant events - https://www.wikidata.org/wiki/Property:P793 -->
        <v-list-item density="compact">
            <v-list-item-title>Ereignisse</v-list-item-title>
            <v-list-item-subtitle>
                <ul class="hyphens-auto white-space-normal" lang="de">
                    <li v-for="event in props.selectedPlace.significantEvents">
                        <a
                            v-if="event.hasLocationMarker"
                            @click.stop="
                                $emit('switchLocation', {
                                    locationId: event.id,
                                })
                            "
                            href="#"
                        >
                            {{ event.label }}
                        </a>
                        <template v-else>
                            {{ event.label }}
                        </template>
                    </li>
                </ul>
            </v-list-item-subtitle>
        </v-list-item>
        <v-divider></v-divider>
    </div>
</template>

<script setup>
import { defineProps, defineEmits, onMounted, onBeforeUnmount } from "vue";

const props = defineProps({
    selectedPlace: {
        type: Object,
        required: true,
    },
});

const showTutorialHighlight = (highlightId) => {
    const highlight = document.getElementById(highlightId);
    if (highlight) {
        highlight.style.display = "block";
    }
};

const hideTutorialHighlight = (highlightId) => {
    const highlight = document.getElementById(highlightId);
    if (highlight) {
        highlight.style.display = "none";
    }
};

const showInformationEventsHighlightHandler = () =>
    showTutorialHighlight("information-events-highlight");
const hideInformationEventsHighlightHandler = () =>
    hideTutorialHighlight("information-events-highlight");

onMounted(() => {
    // Highlight events
    window.addEventListener(
        "show-information-events-highlight",
        showInformationEventsHighlightHandler,
    );
    window.addEventListener(
        "hide-information-events-highlight",
        hideInformationEventsHighlightHandler,
    );
});

onBeforeUnmount(() => {
    window.removeEventListener(
        "show-information-events-highlight",
        showInformationEventsHighlightHandler,
    );
    window.removeEventListener(
        "hide-information-events-highlight",
        hideInformationEventsHighlightHandler,
    );
});

defineEmits(["switchLocation"]);
</script>

<style scoped>
.hyphens-auto {
    hyphens: auto;
}

/* to enable linebreaks for long labels */
.white-space-normal {
    white-space: normal;
}

.tutorial-rectangle-highlight {
    position: absolute;
    top: 0;
    height: 100%;
    border: 6px solid #ff0000;
    box-shadow: 0 0 30px 10px rgba(255, 0, 0, 0.7);
    background-color: transparent;
    pointer-events: none;
    z-index: 9999;
    border-radius: 8px;
    animation: rectangle-highlight-pulse 2s infinite;
}

.information-events-highlight {
    width: calc(100% + 20px);
    height: calc(100% + 20px);
    top: -10px;
    left: -10px;
}

@keyframes rectangle-highlight-pulse {
    0% {
        box-shadow: 0 0 20px 10px rgba(255, 0, 0, 0.7);
        border-color: #ff0000;
    }
    50% {
        box-shadow: 0 0 25px 15px rgba(255, 0, 0, 0.7);
        border-color: #ff5555;
    }
    100% {
        box-shadow: 0 0 20px 10px rgba(255, 0, 0, 0.7);
        border-color: #ff0000;
    }
}
</style>
