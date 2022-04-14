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

        <v-divider class="my-2"></v-divider>

        <v-subheader>Debug Optionen</v-subheader>
        <v-list>
            <v-list-item>
                <v-list-item-content>
                    <v-switch
                        v-model="isMapGreyscale"
                        color="green lighten-1"
                        label="Graustufen de-/aktivieren"
                        @change="toggleMapGreyscale()"
                    ></v-switch>
                </v-list-item-content>
            </v-list-item>
        </v-list>
    </v-container>
</template>

<script>
export default {
    name: 'LayerOptions',
    data() {
        return {
            isMapGreyscale: true,
            layerLabels: ['OpenStreetMap', 'Niedersachsen 1933–1945'],
            layerSelected: 0,
        };
    },
    methods:{
        toggleMapGreyscale() {
            const leafletTileContainers = document.querySelectorAll('.leaflet-tile-container');

            let filter = 'grayscale(0)';

            if (this.isMapGreyscale) {
                filter = 'grayscale(1)';
            }

            leafletTileContainers.forEach(leafletTileContainer => {
                leafletTileContainer.style.filter = filter;
            });
        }
    }
};
</script>

<style scoped>

</style>
