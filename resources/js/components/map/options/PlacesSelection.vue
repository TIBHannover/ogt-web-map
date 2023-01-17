<template>
    <v-container fluid>
        <!-- selection for location groups -->
        <v-row v-for="groupName in groupedPlacesOrder" class="pt-1" :key="groupName + '-row'">
            <v-col class="py-0" style="max-width: 60px">
                <v-img
                    max-width="34px"
                    :src="$ogtGlobals.proxyPath + mapMarkerIconsPath + groupedPlaces[groupName].iconName"
                ></v-img>
            </v-col>

            <v-col class="pa-0">
                <v-checkbox
                    v-model="checkedPlaceLayerGroups"
                    :color="groupedPlaces[groupName].color"
                    dense
                    hide-details
                    :label="groupedPlaces[groupName].layerName"
                    :value="groupName"
                ></v-checkbox>
            </v-col>

            <v-col class="py-0" style="max-width: 55px">
                <v-tooltip :color="groupedPlaces[groupName].color" left>
                    <template v-slot:activator="{ on, attrs }">
                        <v-btn icon :to="{ name: 'glossary', hash: '#' + groupName }" v-bind="attrs" v-on="on">
                            <v-icon class="mt-1" :color="groupedPlaces[groupName].color">
                                mdi-information-outline
                            </v-icon>
                        </v-btn>
                    </template>
                    <span>Im Glossar anzeigen</span>
                </v-tooltip>
            </v-col>
        </v-row>

        <v-divider class="mt-4"></v-divider>
        <v-subheader>Erfasste Gestapo Terror Orte</v-subheader>
        <v-autocomplete
            v-for="groupName in groupedPlacesOrder"
            v-model="selectedPlace"
            v-show="checkedPlaceLayerGroups.includes(groupName)"
            class="mx-4 my-2"
            color="grey darken-4"
            dense
            hide-details
            item-text="placeLabelWithIndex"
            :items="groupedPlaces[groupName].placesByCoordinates"
            :key="groupName"
            :label="groupedPlaces[groupName].layerName"
            outlined
            return-object
            rounded
        ></v-autocomplete>
    </v-container>
</template>

<script>
export default {
    name: 'PlacesSelection',
    props: ['groupedPlaces', 'map', 'mapMarkerIconsPath'],
    data() {
        return {
            checkedPlaceLayerGroups: Object.keys(this.groupedPlaces),
            groupedPlacesOrder: [
                'statePoliceHeadquarters',
                'statePoliceOffices',
                'fieldOffices',
                'prisons',
                'extPolicePrisons',
                'laborEducationCamps',
                'events',
                'memorials',
            ],
            selectedPlace: null,
        };
    },
    watch: {
        /**
         * Start fly & zoom-in animation to selected place.
         *
         * @param newSelectedPlace
         * @param oldSelectedPlace
         */
        selectedPlace(newSelectedPlace, oldSelectedPlace) {
            this.map.flyTo(newSelectedPlace.latLng);
            newSelectedPlace.marker.fire('click', {latlng: newSelectedPlace.latLng});
        },

        /**
         * Show/Hide enabled/disabled place layer groups on map.
         *
         * @param newCheckedPlaceLayerGroups
         * @param oldCheckedPlaceLayerGroups
         */
        checkedPlaceLayerGroups(newCheckedPlaceLayerGroups, oldCheckedPlaceLayerGroups) {
            for (const [group, places] of Object.entries(this.groupedPlaces)) {
                if (newCheckedPlaceLayerGroups.includes(group)) {
                    places.layerGroup.addTo(this.map);
                }
                else {
                    places.layerGroup.remove();
                }
            }
        },
    },
};
</script>

<style>
/* set width of drop-down-lists of selectable places */
.v-autocomplete__content {
    max-width: 307px;
}
</style>

<style scoped>

</style>
