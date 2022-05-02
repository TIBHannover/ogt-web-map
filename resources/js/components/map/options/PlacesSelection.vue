<template>
    <v-container fluid>
        <v-checkbox
            v-for="(group, groupName) in groupedPlaces"
            v-model="checkedPlaceLayerGroups"
            class="mx-4"
            :color="group.color"
            dense
            hide-details
            :key="groupName + '-checkbox'"
            :label="group.layerName"
            :value="groupName"
        ></v-checkbox>

        <v-divider class="my-2"></v-divider>

        <v-subheader>Erfasste Gestapo Terror Orte</v-subheader>
        <v-autocomplete
            v-for="(group, groupName) in groupedPlaces"
            v-model="selectedPlace"
            v-show="checkedPlaceLayerGroups.includes(groupName)"
            class="mx-4 my-2"
            color="grey darken-4"
            dense
            hide-details
            item-text="placeLabelWithIndex"
            :items="group.placesByCoordinates"
            :key="groupName"
            :label="group.layerName"
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
                    this.groupedPlaces[group].layerGroup.addTo(this.map);
                } else {
                    this.groupedPlaces[group].layerGroup.remove();
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
