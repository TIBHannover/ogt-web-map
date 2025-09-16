<template>
    <div class="position-relative">
        <div
            id="information-address-highlight"
            class="information-address-highlight tutorial-rectangle-highlight"
            style="display: none"
        ></div>
        <!-- addresses with associated coordinates and time periods, as well as option to zoom in/out of location. -->
        <v-list-item density="compact">
            <v-row no-gutters>
                <v-col cols="8">
                    <v-list-item-title>Standort</v-list-item-title>
                    <v-list-item-subtitle>
                        <div class="hyphens-auto white-space-normal" lang="de">
                            {{
                                props.selectedPlace.addresses.selected.label
                                    ? props.selectedPlace.addresses.selected
                                          .label
                                    : UNKNOWN_ADDRESS
                            }}
                        </div>
                        <ul>
                            <li
                                v-if="
                                    props.selectedPlace.addresses.selected
                                        .startDate ||
                                    props.selectedPlace.addresses.selected
                                        .endDate
                                "
                            >
                                <template
                                    v-if="
                                        props.selectedPlace.addresses.selected
                                            .startDate
                                    "
                                >
                                    von
                                    {{
                                        props.selectedPlace.addresses.selected
                                            .startDate.locale
                                    }}
                                </template>
                                <template
                                    v-if="
                                        props.selectedPlace.addresses.selected
                                            .endDate
                                    "
                                >
                                    bis
                                    {{
                                        props.selectedPlace.addresses.selected
                                            .endDate.locale
                                    }}
                                </template>
                            </li>
                            <li
                                v-if="
                                    props.selectedPlace.addresses.selected
                                        .latLng &&
                                    !props.selectedPlace.addresses.selected
                                        .isUncertain
                                "
                            >
                                {{
                                    props.selectedPlace.addresses.selected
                                        .latLng.lat
                                }},
                                {{
                                    props.selectedPlace.addresses.selected
                                        .latLng.lng
                                }}
                            </li>
                            <li v-else>
                                {{ UNKNOWN_COORDINATES }}
                            </li>
                        </ul>
                    </v-list-item-subtitle>
                </v-col>
                <v-col cols="4">
                    <v-list-item class="align-right">
                        <v-list-item-action
                            class="flex-direction-row ml-0 my-0 ga-2"
                        >
                            <v-btn
                                @click.stop="$emit('zoomIntoPlace')"
                                size="x-small"
                                variant="outlined"
                                icon
                            >
                                <v-icon>mdi-magnify-plus</v-icon>
                            </v-btn>
                            <v-btn
                                @click.stop="$emit('undoZoomIntoPlace')"
                                variant="outlined"
                                size="x-small"
                                icon
                            >
                                <v-icon>mdi-undo-variant</v-icon>
                            </v-btn>
                        </v-list-item-action>
                    </v-list-item>
                </v-col>
            </v-row>
        </v-list-item>
        <v-list-item
            v-if="props.selectedPlace.addresses.additional.length > 0"
            density="compact"
        >
            <v-list-item-title>Weitere Standorte</v-list-item-title>
            <v-list-item-subtitle>
                <div
                    v-for="additionalAddress in props.selectedPlace.addresses
                        .additional"
                    class="mb-3"
                >
                    <div class="hyphens-auto white-space-normal" lang="de">
                        <a
                            v-if="additionalAddress.latLng"
                            @click.stop="
                                $emit('switchLocation', {
                                    locationId: props.selectedPlace.id,
                                    latLng: additionalAddress.latLng,
                                })
                            "
                            href="#"
                        >
                            {{
                                additionalAddress.label
                                    ? additionalAddress.label
                                    : UNKNOWN_ADDRESS
                            }}
                        </a>
                        <template v-else>
                            {{
                                additionalAddress.label
                                    ? additionalAddress.label
                                    : UNKNOWN_ADDRESS
                            }}
                        </template>
                    </div>
                    <ul>
                        <li
                            v-if="
                                additionalAddress.startDate ||
                                additionalAddress.endDate
                            "
                        >
                            <template v-if="additionalAddress.startDate">
                                von {{ additionalAddress.startDate.locale }}
                            </template>
                            <template v-if="additionalAddress.endDate">
                                bis {{ additionalAddress.endDate.locale }}
                            </template>
                        </li>
                        <li
                            v-if="
                                additionalAddress.latLng &&
                                !additionalAddress.isUncertain
                            "
                        >
                            {{ additionalAddress.latLng.lat }},
                            {{ additionalAddress.latLng.lng }}
                        </li>
                        <li v-else>
                            {{ UNKNOWN_COORDINATES }}
                        </li>
                    </ul>
                </div>
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

defineEmits(["zoomIntoPlace", "undoZoomIntoPlace", "switchLocation"]);

// Constants
const UNKNOWN_ADDRESS = "Die Adresse ist unbekannt.";
const UNKNOWN_COORDINATES = "Der genaue Standort ist unbekannt.";

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

const showInformationAdressHighlightHandler = () =>
    showTutorialHighlight("information-address-highlight");
const hideInformationAdressHighlightHandler = () =>
    hideTutorialHighlight("information-address-highlight");

onMounted(() => {
    // Highlight events
    window.addEventListener(
        "show-information-address-highlight",
        showInformationAdressHighlightHandler,
    );
    window.addEventListener(
        "hide-information-address-highlight",
        hideInformationAdressHighlightHandler,
    );
});

onBeforeUnmount(() => {
    window.removeEventListener(
        "show-information-address-highlight",
        showInformationAdressHighlightHandler,
    );
    window.removeEventListener(
        "hide-information-address-highlight",
        hideInformationAdressHighlightHandler,
    );
});
</script>

<style scoped>
/* move to top right */
.flex-direction-row {
    align-self: flex-start;
    flex-direction: row;
    padding-top: 7px;
}

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

.information-address-highlight {
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
