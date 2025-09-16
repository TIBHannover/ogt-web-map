<template>
    <v-container fluid class="placeselection-div glossary-buttons-parent">
        <!-- selection for location groups -->
        <v-row>
            <v-col class="py-0" style="max-width: 60px"></v-col>
            <v-col class="pa-0">
                <v-checkbox
                    :model-value="allSelected"
                    color="grey-darken-2"
                    density="compact"
                    hide-details
                    label="Alle"
                    @click="selectAll()"
                ></v-checkbox>
            </v-col>
        </v-row>
        <div class="placetype-filter">
            <!-- Special highlight element for tutorial - positioned relative to placetype-filter -->
            <div
                id="glossary-column-highlight"
                class="glossary-column-highlight"
                style="display: none"
            ></div>
            <v-row
                v-for="groupName in groupedPlacesOrder"
                class="pt-1"
                :key="groupName + '-row'"
            >
                <v-col class="py-0" style="max-width: 60px">
                    <v-img
                        :max-width="34"
                        :src="`${ogtGlobals?.proxyPath}${mapMarkerIconsPath}${groupedPlaces[groupName].iconName}`"
                    ></v-img>
                </v-col>

                <v-col class="pa-0">
                    <v-checkbox
                        v-model="checkedPlaceLayerGroups"
                        :color="groupedPlaces[groupName].color"
                        density="compact"
                        hide-details
                        :label="groupedPlaces[groupName].layerName"
                        :value="groupName"
                    ></v-checkbox>
                </v-col>

                <v-col
                    class="py-0 glossary-buttons-area glossary-buttons-column"
                    style="max-width: 55px; position: relative; z-index: 10"
                >
                    <v-tooltip
                        :color="groupedPlaces[groupName].color"
                        location="left"
                    >
                        <template v-slot:activator="{ props }">
                            <v-btn
                                icon
                                rounded="circle"
                                size="26px"
                                class="mt-1 glossary-button"
                                :to="{
                                    name: 'glossary',
                                    hash: '#' + groupName,
                                }"
                                v-bind="props"
                            >
                                <v-icon
                                    :color="groupedPlaces[groupName].color"
                                    style="position: relative; z-index: 10001"
                                >
                                    mdi-information-outline
                                </v-icon>
                            </v-btn>
                        </template>
                        <span>Im Glossar anzeigen</span>
                    </v-tooltip>
                </v-col>
            </v-row>
        </div>

        <v-divider class="mt-4"></v-divider>
        <v-list-subheader>Erfasste Gestapo Terror Orte</v-list-subheader>
        <v-autocomplete
            v-for="groupName in groupedPlacesOrder"
            v-model="selectedPlaces[groupName]"
            v-show="checkedPlaceLayerGroups.includes(groupName)"
            class="mx-4 my-2"
            color="grey-darken-4"
            density="compact"
            hide-details
            item-title="placeLabelWithIndex"
            :items="groupedPlaces[groupName].placesByCoordinates"
            :key="groupName"
            :label="groupedPlaces[groupName].layerName"
            :data-label="groupedPlaces[groupName].layerName"
            :data-groupName="groupName"
            :ref="
                (el) => {
                    if (el) autoCompleteRefs[groupName] = el;
                }
            "
            variant="outlined"
            return-object
            rounded
            clearable
        ></v-autocomplete>
    </v-container>
</template>

<script setup>
import {
    ref,
    watch,
    defineProps,
    computed,
    inject,
    onMounted,
    onBeforeUnmount,
} from "vue";

const props = defineProps({
    groupedPlaces: {
        type: Object,
        required: true,
    },
    map: {
        type: Object,
        required: true,
    },
    mapMarkerIconsPath: {
        type: String,
        required: true,
    },
});

const ogtGlobals = inject("ogtGlobals");

const checkedPlaceLayerGroups = ref(Object.keys(props.groupedPlaces));
const emit = defineEmits(["update-layer-groups"]);

// Function to show the glossary highlight
const showGlossaryHighlight = () => {
    const highlight = document.getElementById("glossary-column-highlight");

    if (highlight) {
        // The highlight is inside the placetype-filter div, so its positioning is already correct
        highlight.style.display = "block";
    }
};

// Function to hide the glossary column highlight
const hideGlossaryHighlight = () => {
    const highlight = document.getElementById("glossary-column-highlight");
    if (highlight) {
        highlight.style.display = "none";
    }
};

// Add event listeners for tutorial events
onMounted(() => {
    window.addEventListener("show-glossary-highlight", showGlossaryHighlight);
    window.addEventListener("hide-glossary-highlight", hideGlossaryHighlight);
});

// Clean up event listeners
onBeforeUnmount(() => {
    window.removeEventListener(
        "show-glossary-highlight",
        showGlossaryHighlight,
    );
    window.removeEventListener(
        "hide-glossary-highlight",
        hideGlossaryHighlight,
    );
});

const allSelected = computed(() => {
    return (
        checkedPlaceLayerGroups.value.length ===
        Object.keys(props.groupedPlaces).length
    );
});

const groupedPlacesOrder = [
    "statePoliceHeadquarters",
    "statePoliceOffices",
    "fieldOffices",
    "prisons",
    "extPolicePrisons",
    "laborEducationCamps",
    "events",
    "memorials",
];

const selectedPlaces = ref({});
const autoCompleteRefs = ref({});
groupedPlacesOrder.forEach((groupName) => {
    selectedPlaces.value[groupName] = null;
});

const clearOtherSelections = (currentGroupName) => {
    Object.keys(selectedPlaces.value).forEach((groupName) => {
        if (groupName !== currentGroupName) {
            selectedPlaces.value[groupName] = null;
        }
    });
    setTimeout(() => {
        clearFocus();
    }, 1000);
};

const clearFocus = () => {
    let placeselectionInputs = document.querySelectorAll(
        '.placeselection-div input[type="text"]',
    );
    if (placeselectionInputs) {
        placeselectionInputs.forEach((placeselectionInput) => {
            placeselectionInput.blur();
        });
    }
};

const selectAll = () => {
    checkedPlaceLayerGroups.value =
        checkedPlaceLayerGroups.value.length !==
        Object.keys(props.groupedPlaces).length
            ? Object.keys(props.groupedPlaces)
            : [];
};

// Watchers
//
groupedPlacesOrder.forEach((groupName) => {
    watch(
        () => selectedPlaces.value[groupName],
        (newPlace) => {
            if (newPlace) {
                clearOtherSelections(groupName);
                props.map.flyTo(newPlace.latLng);
                newPlace.marker.fire("click", { latlng: newPlace.latLng });
            }
        },
    );
});

watch(checkedPlaceLayerGroups, (newGroups) => {
    // Emit event to parent instead of directly accessing it
    emit("update-layer-groups", newGroups);
});
</script>

<style>
/* set width of drop-down-lists of selectable places */
.v-autocomplete__content {
    max-width: 307px;
}
</style>

<style scoped>
.py-0 {
    padding-left: 0.75rem !important;
    padding-right: 0.25rem !important;
}
.pa-0 {
    padding-left: 0 !important;
}

.placetype-filter {
    position: relative;
}

.glossary-buttons-area {
    position: relative;
    z-index: 1000;
}

.glossary-buttons-column {
    display: flex;
    flex-direction: column;
    pointer-events: auto !important;
}

.glossary-buttons-parent .glossary-button {
    transition: transform 0.2s ease;
    position: relative;
    z-index: 10001 !important;
    pointer-events: auto !important;
}

.glossary-buttons-parent .glossary-button:hover {
    transform: scale(1.2);
    box-shadow: 0 0 10px rgba(255, 82, 82, 0.8);
    z-index: 10002 !important;
}

.glossary-column-highlight {
    position: absolute;
    top: 0;
    right: -15px;
    width: 70px;
    height: 100%;
    border: 6px solid #ff0000;
    box-shadow: 0 0 30px 10px rgba(255, 0, 0, 0.7);
    background-color: transparent;
    pointer-events: none;
    z-index: 9999;
    border-radius: 8px;
    animation: glossary-highlight-pulse 2s infinite;
}

@keyframes glossary-highlight-pulse {
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
