<template>
    <v-container fluid>
        <v-subheader>Liste der Gestapo Terror Orte</v-subheader>
        <v-autocomplete
            v-for="(group, groupName) in groupedPlaces"
            v-model="selectedPlace"
            class="mx-4"
            color="green lighten-1"
            dense
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
        selectedPlace(newSelectedPlace, oldSelectedPlace) {
            this.map.flyTo([newSelectedPlace.lat.value, newSelectedPlace.lng.value], 18);
        },
    },
};
</script>

<style>
.v-autocomplete__content {
    max-width: 307px;
}
</style>

<style scoped>

</style>
