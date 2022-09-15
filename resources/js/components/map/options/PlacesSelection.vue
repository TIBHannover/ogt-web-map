<template>
    <v-container fluid>
        <v-checkbox
            v-for="groupName in groupedPlacesOrder"
            v-model="checkedPlaceLayerGroups"
            class="mx-4"
            :color="groupedPlaces[groupName].color"
            dense
            hide-details
            :key="groupName + '-checkbox'"
            :label="groupedPlaces[groupName].layerName"
            :value="groupName"
        ></v-checkbox>

        <v-divider class="my-2"></v-divider>

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
    props: ['groupedPlaces', 'map'],
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
                } else {
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
