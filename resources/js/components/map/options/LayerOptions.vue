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
        <v-radio-group
            v-model="mapGreyscaleSelected"
            @change="toggleMapGreyscale()"
            dense
            label="De-/Aktiviere die Karten Graustufen aus:"
            mandatory
            prepend-icon="mdi-map"
        >
            <v-radio
                v-for="(mapGreyscaleLabel, index) in mapGreyscaleLabels"
                color="green lighten-1"
                :key="index"
                :label="mapGreyscaleLabel"
                :value="index"
            ></v-radio>
        </v-radio-group>

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
            layerLabels: ['OpenStreetMap', 'Niedersachsen 1933–1945'],
            layerSelected: 0,
            mapMarkerStyleLabels: [
                'Graue Symbole (transparent)',
                'Farbige Symbole (transparent)',
                'Leaflet Standard Kartenmarker',
                'Graue Symbole',
                'Farbige Symbole',
            ],
            mapMarkerStyleSelected: 3,
            mapGreyscaleLabels: ['Graustufen deaktiviert', 'Graustufen aktiviert'],
            mapGreyscaleSelected: 1,
        };
    },
    methods: {
        switchMapMarkerStyle() {
            let mapMarkerSubPath = '/greyTransparent/';
            let mapMarkerFileType = '.svg';
            let mapMarkerHeight = '53px';
            let mapMarkerWidth = '48px';
            let mapMarkerMarginLeft = '-24px';
            let mapMarkerMarginTop = '-52px';

            if (this.mapMarkerStyleSelected == 1) {
                mapMarkerSubPath = '/coloredTransparent/';
            }
            else if (this.mapMarkerStyleSelected == 2) {
                mapMarkerSubPath = '/default/';
                mapMarkerFileType = '.png';
                mapMarkerHeight = '41px';
                mapMarkerWidth = '25px';
                mapMarkerMarginLeft = '-12px';
                mapMarkerMarginTop = '-41px';
            }
            else if (this.mapMarkerStyleSelected == 3) {
                mapMarkerSubPath = '/greyFilled/';
            }
            else if (this.mapMarkerStyleSelected == 4) {
                mapMarkerSubPath = '/coloredFilled/';
            }
            else {
                // default case
            }

            const leafletMarkerIcons = document.querySelectorAll('.leaflet-marker-icon');
            const subPathRegex = /\/(greyTransparent|coloredTransparent|default|greyFilled|coloredFilled)\//g;

            leafletMarkerIcons.forEach(leafletTilePane => {
                leafletTilePane.src = leafletTilePane.src.replace(subPathRegex, mapMarkerSubPath);
                leafletTilePane.src = leafletTilePane.src.replace(/\.(svg|png)$/g, mapMarkerFileType);
                leafletTilePane.style.height = mapMarkerHeight;
                leafletTilePane.style.width = mapMarkerWidth;
                leafletTilePane.style.marginLeft = mapMarkerMarginLeft;
                leafletTilePane.style.marginTop = mapMarkerMarginTop;
            });
        },
        toggleMapGreyscale() {
            let filter = 'grayscale(1)';

            if (this.mapGreyscaleSelected == 0) {
                filter = 'grayscale(0)';
            }

            const leafletTilePanes = document.querySelectorAll('.leaflet-tile-pane');

            leafletTilePanes.forEach(leafletTilePane => {
                leafletTilePane.style.filter = filter;
            });
        },
    },
};
</script>

<style scoped>

</style>
