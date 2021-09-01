<template>
    <v-container fluid>
        <v-subheader>Liste der Gestapo Terror Orte</v-subheader>
        <v-autocomplete
            v-for="(placeGroup, index) in places"
            v-model="selectedPlace"
            class="mx-4"
            color="green lighten-1"
            dense
            item-text="itemLabel.value"
            :items="placeGroup"
            :key="index"
            :label="placeLayerGroups[index].layerName"
            outlined
            return-object
            rounded
        ></v-autocomplete>
    </v-container>
</template>

<script>
export default {
    name: 'PlacesSelection',
    props: ['map', 'places'],
    data() {
        return {
            selectedPlace: null,
            placeLayerGroups: {
                fieldOffices: {
                    layerName: 'Außendienststellen',
                },
                extPolicePrisonsAndLaborEducationCamps: {
                    layerName: 'Erweiterte Polizeigefängnisse/AELs',
                },
                prisons: {
                    layerName: 'Gefängnisse',
                },
                statePoliceHeadquarters: {
                    layerName: 'Staatspolizeileitstellen',
                },
                statePoliceOffices: {
                    layerName: 'Staatspolizeistellen',
                },
            },
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
