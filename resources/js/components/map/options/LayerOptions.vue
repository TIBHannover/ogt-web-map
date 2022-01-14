<template>
    <v-container fluid>
        <!-- template content only, not yet functional -->
        <v-radio-group
            v-model="layerSelected"
            dense
            label="Wähle eine Kartenansicht aus:"
            mandatory
            prepend-icon="mdi-map"
            title="⚠ not yet functional ⚠"
        >
            <v-radio
                v-for="(layerLabel, index) in layerLabels"
                color="green lighten-1"
                :key="index"
                :label="layerLabel"
                :value="index"
            ></v-radio>
        </v-radio-group>
    </v-container>
</template>

<script>
export default {
    name: 'LayerOptions',
    data() {
        return {
            layerLabels: ['OpenStreetMap', 'Niedersachsen 1933–1945'],
            layerSelected: 0,
        };
    },
};
</script>

<style scoped>

</style>
