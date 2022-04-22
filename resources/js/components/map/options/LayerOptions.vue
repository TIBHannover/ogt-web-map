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
            let mapMarkerWidth = '48px';
            let mapMarkerHeight = '53px';
            let mapMarkerMarginLeft = '-24px';
            let mapMarkerMarginTop = '-52px';
            let mapMarkerShadowWidth = '76px';
            let mapMarkerShadowHeight = '52px';

            if (this.mapMarkerStyleSelected == 1) {
                mapMarkerSubPath = '/coloredTransparent/';
            }
            else if (this.mapMarkerStyleSelected == 2) {
                mapMarkerSubPath = '/default/';
                mapMarkerFileType = '.png';
                mapMarkerWidth = '25px';
                mapMarkerHeight = '41px';
                mapMarkerMarginLeft = '-12px';
                mapMarkerMarginTop = '-41px';
                mapMarkerShadowWidth = '41px';
                mapMarkerShadowHeight = '41px';
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

            leafletMarkerIcons.forEach(leafletMarkerIcon => {
                leafletMarkerIcon.src = leafletMarkerIcon.src.replace(subPathRegex, mapMarkerSubPath);
                leafletMarkerIcon.src = leafletMarkerIcon.src.replace(/\.(svg|png)$/g, mapMarkerFileType);
                leafletMarkerIcon.style.height = mapMarkerHeight;
                leafletMarkerIcon.style.width = mapMarkerWidth;
                leafletMarkerIcon.style.marginLeft = mapMarkerMarginLeft;
                leafletMarkerIcon.style.marginTop = mapMarkerMarginTop;
            });

            const leafletMarkerShadows = document.querySelectorAll('.leaflet-marker-shadow');
            leafletMarkerShadows.forEach(leafletMarkerShadow => {
                leafletMarkerShadow.style.height = mapMarkerShadowHeight;
                leafletMarkerShadow.style.width = mapMarkerShadowWidth;
                leafletMarkerShadow.style.marginLeft = mapMarkerMarginLeft;
                leafletMarkerShadow.style.marginTop = mapMarkerMarginTop;
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
