<template>
    <v-container fluid class="placeselection-div">
        <!-- selection for location groups -->
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
            <v-col v-if="groupedPlaces[groupName].linkedMainGroupItem" class="py-0" style="max-width: 55px"></v-col>
            <v-col v-else class="py-0" style="max-width: 60px">
                <v-img
                    :max-width="34"
                    :src="`${ogtGlobals?.proxyPath}${mapMarkerIconsPath}${groupedPlaces[groupName].iconName}`"
                ></v-img>
            </v-col>

            <v-col v-if="groupedPlaces[groupName].linkedMainGroupItem" class="py-0" style="max-width: 35px; font-size: 30px;">
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
                            :to="{ name: 'glossary', hash: '#' + groupName }"
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

        <v-divider class="mt-4"></v-divider>
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
    </v-container>
</template>

<script setup>
import { ref, watch, defineProps, computed, inject } from "vue";

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

const areAllGroupedPlacesShownOnMap = computed({
    get() {
        return proxyGroupedPlacesShownOnMap.value.length === Object.keys(props.groupedPlaces).length;
    },
    set(value) {
        proxyGroupedPlacesShownOnMap.value = value ? Object.keys(props.groupedPlaces) : [];
    },
});

const orderedGroupPlaceNames = computed(() => {
    return Object.keys(props.groupedPlaces)
    .sort(
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
</style>

<style scoped>
.py-0 {
    padding-left: 0.75rem !important;
    padding-right: 0.25rem !important;
}
.pa-0 {
    padding-left: 0 !important;
}
</style>
