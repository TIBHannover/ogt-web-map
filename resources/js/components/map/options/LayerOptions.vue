<template>
    <v-container fluid>
        <!-- template content only, not yet functional -->
        <v-radio-group
            v-model="layerSelected"
            dense
            disabled
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
        <v-radio-group
            v-model="mapMarkerStyleSelected"
            @change="switchMapMarkerStyle()"
            dense
            label="Wähle einen Kartenmarker Style aus:"
            mandatory
            prepend-icon="mdi-map-marker"
        >
            <v-radio
                v-for="(mapMarkerStyleLabel, index) in mapMarkerStyleLabels"
                color="green lighten-1"
                :key="index"
                :label="mapMarkerStyleLabel"
                :value="index"
            ></v-radio>
        </v-radio-group>
    </v-container>
</template>

<script>
export default {
    name: 'LayerOptions',
    props: ['groupedPlaces'],
    data() {
        return {
            isMapGreyscale: true,
            layerLabels: ['OpenStreetMap', 'Niedersachsen 1933–1945'],
            layerSelected: 0,
            mapMarkerStyleSelected: 0,
            mapMarkerStyleLabels: ['Graue Symbole', 'Farbige Symbole', 'Leaflet Standard Kartenmarker'],
        };
    },
    methods:{
        toggleMapGreyscale() {
            const leafletTilePanes = document.querySelectorAll('.leaflet-tile-pane');

            let filter = 'grayscale(0)';

            if (this.isMapGreyscale) {
                filter = 'grayscale(1)';
            }

            leafletTilePanes.forEach(leafletTilePane => {
                leafletTilePane.style.filter = filter;
            });
        },
        switchMapMarkerStyle() {
            let mapMarkerSubPath = '/gray/';
            let mapMarkerFileType = '.svg';
            let mapMarkerHeight = '53px';

            if (this.mapMarkerStyleSelected == 1) {
                mapMarkerSubPath = '/colored/';
            }
            else if (this.mapMarkerStyleSelected == 2) {
                mapMarkerSubPath = '/default/';
                mapMarkerFileType = '.png';
                mapMarkerHeight = '41px';
            }
            else {
                // default case
            }

            const leafletMarkerIcons = document.querySelectorAll('.leaflet-marker-icon');

            leafletMarkerIcons.forEach(leafletTilePane => {
                leafletTilePane.src = leafletTilePane.src.replace(/\/(gray|colored|default)\//g, mapMarkerSubPath);
                leafletTilePane.src = leafletTilePane.src.replace(/\.(svg|png)$/g, mapMarkerFileType);
                leafletTilePane.style.height = mapMarkerHeight;
            });
        },
    }
};
</script>

<style scoped>

</style>
