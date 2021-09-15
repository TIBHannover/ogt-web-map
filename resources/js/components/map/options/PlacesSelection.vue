<template>
    <v-container fluid>
        <v-subheader>Erfasste Gestapo Terror Orte</v-subheader>
        <v-autocomplete
            v-for="(group, groupName) in groupedPlaces"
            v-model="selectedPlace"
            class="mx-4 my-2"
            color="green lighten-1"
            dense
            hide-details
            item-text="itemLabel.value"
            :items="group.places"
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
            this.map.flyTo([newSelectedPlace.lat.value, newSelectedPlace.lng.value], 18);
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
