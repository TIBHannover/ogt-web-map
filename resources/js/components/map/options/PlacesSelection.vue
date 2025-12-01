<template>
    <v-container fluid class="placeselection-div glossary-buttons-parent">
        <!-- selection for location groups -->
        <div class="position-relative">
            <div
                id="filter-row-highlight"
                class="filter-row-highlight tutorial-rectangle-highlight"
                style="display: none"
            ></div>
            <v-row>
                <v-col class="py-0" style="max-width: 60px"></v-col>
                <v-col class="pa-0">
                    <v-checkbox
                        v-model="areAllGroupedPlacesShownOnMap"
                        color="grey-darken-2"
                        density="compact"
                        hide-details
                        label="Alle auswählen"
                    ></v-checkbox>
                </v-col>
            </v-row>
            <v-row
                v-for="groupName in orderedGroupPlaceNames"
                class="pt-1"
                :key="groupName + '-row'"
            >
                <v-col
                    v-if="groupedPlaces[groupName].linkedMainGroupItem"
                    class="py-0"
                    style="max-width: 55px"
                ></v-col>
                <v-col v-else class="py-0" style="max-width: 60px">
                    <v-img
                        :max-width="34"
                        :src="`${ogtGlobals?.proxyPath}${mapMarkerIconsPath}${groupedPlaces[groupName].iconName}`"
                    ></v-img>
                </v-col>

                <v-col
                    v-if="groupedPlaces[groupName].linkedMainGroupItem"
                    class="py-0"
                    style="max-width: 35px; font-size: 30px"
                >
                    +
                </v-col>

                <v-col class="pa-0">
                    <v-checkbox
                        v-model="proxyGroupedPlacesShownOnMap"
                        :color="groupedPlaces[groupName].color"
                        density="compact"
                        hide-details
                        :label="groupedPlaces[groupName].layerName"
                        :value="groupName"
                    ></v-checkbox>
                </v-col>

                <v-col class="py-0" style="max-width: 55px">
                    <v-tooltip
                        :color="groupedPlaces[groupName].color"
                        location="left"
                    >
                        <template v-slot:activator="{ props }">
                            <v-btn
                                icon
                                rounded="circle"
                                size="26px"
                                class="mt-1"
                                :to="{
                                    name: 'glossary',
                                    hash: '#' + groupName,
                                }"
                                v-bind="props"
                            >
                                <v-icon :color="groupedPlaces[groupName].color">
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
        <div class="position-relative">
            <div
                id="filter-dropdowns-highlight"
                class="filter-dropdowns-highlight tutorial-rectangle-highlight"
                style="display: none"
            ></div>
            <v-list-subheader>Erfasste Gestapo Terror Orte</v-list-subheader>
            <v-autocomplete
                v-for="groupName in orderedGroupPlaceNames"
                v-model="selectedPlaces[groupName]"
                v-show="proxyGroupedPlacesShownOnMap.includes(groupName)"
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
        </div>
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
    groupedPlacesShownOnMap: {
        type: Array,
        default: () => [],
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

const emit = defineEmits(["update:groupedPlacesShownOnMap"]);

const proxyGroupedPlacesShownOnMap = computed({
    get: () => props.groupedPlacesShownOnMap,
    set: (value) => emit("update:groupedPlacesShownOnMap", value),
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

// Create handler functions that we can reference for both adding and removing
const showGlossaryHandler = () =>
    showTutorialHighlight("glossary-column-highlight");
const hideGlossaryHandler = () =>
    hideTutorialHighlight("glossary-column-highlight");
const showFiltersHandler = () => showTutorialHighlight("filter-row-highlight");
const hideFiltersHandler = () => hideTutorialHighlight("filter-row-highlight");
const showFilterCheckboxAllHandler = () =>
    showTutorialHighlight("filter-checkbox-all-highlight");
const hideFilterCheckboxAllHandler = () =>
    hideTutorialHighlight("filter-checkbox-all-highlight");
const showFilterStaatspolizeileitstellenHandler = () =>
    showTutorialHighlight("filter-checkbox-staatspolizeileitstellen-highlight");
const hideFilterStaatspolizeileitstellenHandler = () =>
    hideTutorialHighlight("filter-checkbox-staatspolizeileitstellen-highlight");
const showFilterDropdownHandler = () =>
    showTutorialHighlight("filter-dropdowns-highlight");
const hideFilterDropdownHandler = () =>
    hideTutorialHighlight("filter-dropdowns-highlight");

onMounted(() => {
    // Highlight events
    window.addEventListener("show-glossary-highlight", showGlossaryHandler);
    window.addEventListener("hide-glossary-highlight", hideGlossaryHandler);
    window.addEventListener("show-filters-row-highlight", showFiltersHandler);
    window.addEventListener("hide-filters-row-highlight", hideFiltersHandler);
    window.addEventListener(
        "show-filters-checkbox-all-highlight",
        showFilterCheckboxAllHandler,
    );
    window.addEventListener(
        "hide-filters-checkbox-all-highlight",
        hideFilterCheckboxAllHandler,
    );
    window.addEventListener(
        "show-filter-checkbox-staatspolizeileitstellen-highlight",
        showFilterStaatspolizeileitstellenHandler,
    );
    window.addEventListener(
        "hide-filter-checkbox-staatspolizeileitstellen-highlight",
        hideFilterStaatspolizeileitstellenHandler,
    );
    window.addEventListener(
        "show-filter-dropdown-highlight",
        showFilterDropdownHandler,
    );
    window.addEventListener(
        "hide-filter-dropdown-highlight",
        hideFilterDropdownHandler,
    );

    // Filter action events
    window.addEventListener("deactivate-all-filters", deactivateAllFilters);
    window.addEventListener(
        "activate-staatspolizeileitstellen",
        activateStaatspolizeileitstellen,
    );
});

// Clean up event listeners
onBeforeUnmount(() => {
    // Remove all event listeners using the same handler references
    window.removeEventListener("show-glossary-highlight", showGlossaryHandler);
    window.removeEventListener("hide-glossary-highlight", hideGlossaryHandler);
    window.removeEventListener(
        "show-filters-row-highlight",
        showFiltersHandler,
    );
    window.removeEventListener(
        "hide-filters-row-highlight",
        hideFiltersHandler,
    );
    window.removeEventListener(
        "show-filters-checkbox-all-highlight",
        showFilterCheckboxAllHandler,
    );
    window.removeEventListener(
        "hide-filters-checkbox-all-highlight",
        hideFilterCheckboxAllHandler,
    );

    // Remove filter action event listeners
    window.removeEventListener("deactivate-all-filters", deactivateAllFilters);
    window.removeEventListener(
        "activate-staatspolizeileitstellen",
        activateStaatspolizeileitstellen,
    );
});

const allSelected = computed(() => {
    return (
        checkedPlaceLayerGroups.value.length ===
        Object.keys(props.groupedPlaces).length
    );
});

const areAllGroupedPlacesShownOnMap = computed({
    get() {
        return (
            proxyGroupedPlacesShownOnMap.value.length ===
            Object.keys(props.groupedPlaces).length
        );
    },
    set(value) {
        proxyGroupedPlacesShownOnMap.value = value
            ? Object.keys(props.groupedPlaces)
            : [];
    },
});

const orderedGroupPlaceNames = computed(() => {
    return Object.keys(props.groupedPlaces).sort(
        (a, b) =>
            props.groupedPlaces[a].displayOrder -
            props.groupedPlaces[b].displayOrder,
    );
});

const selectedPlaces = ref({});
const autoCompleteRefs = ref({});
orderedGroupPlaceNames.value.forEach((groupName) => {
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

// Function to deactivate all filters - called from tutorial
const deactivateAllFilters = () => {
    // Set to empty array to deselect all filters
    checkedPlaceLayerGroups.value = [];
};

// Function to activate only Staatspolizeileitstellen filter - called from tutorial
const activateStaatspolizeileitstellen = () => {
    // First clear all filters
    checkedPlaceLayerGroups.value = [];

    // Then add only the Staatspolizeileitstellen filter
    // Using a slight delay to ensure UI updates properly
    setTimeout(() => {
        checkedPlaceLayerGroups.value = ["statePoliceHeadquarters"];
    }, 100);
};

// Watchers
//
orderedGroupPlaceNames.value.forEach((groupName) => {
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
</script>

<style>
/* set width of drop-down-lists of selectable places */
.v-autocomplete__content {
    max-width: 307px;
}

/* Style for the filter checkbox highlight */
.filter-checkbox-all-highlight {
    position: absolute;
    border: 6px solid #ff0000;
    box-shadow: 0 0 30px 10px rgba(255, 0, 0, 0.7);
    background-color: rgba(255, 0, 0, 0.2);
    pointer-events: none;
    z-index: 9999;
    border-radius: 8px;
    animation: tutorial-highlight-pulse 2s infinite;
}

@keyframes tutorial-highlight-pulse {
    0% {
        box-shadow: 0 0 30px 10px rgba(255, 0, 0, 0.7);
        border-color: #ff0000;
    }
    50% {
        box-shadow: 0 0 40px 15px rgba(255, 0, 0, 0.9);
        border-color: #ff5555;
        background-color: rgba(255, 0, 0, 0.3);
    }
    100% {
        box-shadow: 0 0 30px 10px rgba(255, 0, 0, 0.7);
        border-color: #ff0000;
    }
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

.filter-dropdowns-highlight {
    width: calc(100% + 20px);
    height: calc(100% + 20px);
    top: -10px;
    left: -10px;
}

.filter-checkbox-staatspolizeileitstellen-highlight {
    height: 55px;
    top: -5px;
    left: -15px;
    width: calc(100% - 20px);
}

.filter-row-highlight {
    left: -15px;
    width: calc(100% - 20px);
}

.filter-checkbox-all-highlight {
    left: 0px;
    width: calc(100% - 40px);
}

.glossary-column-highlight {
    right: -15px;
    width: 70px;
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
