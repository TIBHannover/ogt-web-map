<template>
    <v-container fluid class="placeselection-div">
        <!-- selection for location groups -->
        <v-row>
            <v-col class="py-0" style="max-width: 60px"></v-col>
            <v-col class="pa-0">
                <div class="select-all" @click="selectAll()">
                    <div></div>
                    Alle
                </div>
            </v-col>
        </v-row>
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
import { ref, watch, defineProps, computed, inject } from "vue";
import { useRouter } from "vue-router";

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

const router = useRouter();
const ogtGlobals = inject("ogtGlobals");

const checkedPlaceLayerGroups = ref(Object.keys(props.groupedPlaces));
const emit = defineEmits(["update-layer-groups"]);

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

.select-all {
    display: flex;
    align-items: center;
    cursor: pointer;
    margin-top: 18px;
    margin-bottom: 12px;
    width: fit-content;
    white-space: nowrap;
}
.select-all > div {
    content: "";
    flex: 1 0 auto;
    display: block;
    position: relative;
    margin-left: 4px;
    margin-right: 14px;
    width: 15px;
    height: 15px;
    background-color: #d0d0d0;
    border-radius: 2px;
}
.select-all > div::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    margin-top: -17.5px;
    margin-left: -17.5px;
    width: 35px;
    height: 35px;
    background-color: #f0f0f0;
    border-radius: 100%;
    opacity: 0;
    z-index: -1;
}
.select-all > div:hover::after {
    opacity: 0.5;
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
