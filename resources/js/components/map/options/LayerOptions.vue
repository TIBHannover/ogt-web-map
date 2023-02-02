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
                color="grey darken-4"
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
                color="grey darken-4"
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
                color="grey darken-4"
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
                'Dunkelgraue Symbole (transparent)',
                'Farbige Symbole v1 (transparent)',
                'Leaflet Standard Kartenmarker',
                'Dunkelgraue Symbole (grau gefüllt)',
                'Farbige Symbole v1 (hellgrau gefüllt)',
                'Farbige Symbole v2 (grau gefüllt)',
                'Farbige Symbole v2 (hellgrau gefüllt)',
                'Farbige Symbole v2 (weiß gefüllt)',
                'Farbige Symbole v3 (weiß gefüllt)',
            ],
            mapMarkerStyleSelected: 7,
            mapGreyscaleLabels: ['Graustufen deaktiviert', 'Graustufen aktiviert'],
            mapGreyscaleSelected: 1,
        };
    },
    methods: {
        /**
         * Switch between map marker styles.
         */
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
            else if (this.mapMarkerStyleSelected == 5) {
                mapMarkerSubPath = '/coloredFilledGrey/';
            }
            else if (this.mapMarkerStyleSelected == 6) {
                mapMarkerSubPath = '/coloredFilledLightGrey/';
            }
            else if (this.mapMarkerStyleSelected == 7) {
                mapMarkerSubPath = '/coloredFilledWhite/';
            }
            else if (this.mapMarkerStyleSelected == 8) {
                mapMarkerSubPath = '/coloredFilledWhiteV3/';
            }
            else {
                // default case
            }

            const subPathRegex = /\/(greyTransparent|coloredTransparent|default|greyFilled|coloredFilled|coloredFilledGrey|coloredFilledLightGrey|coloredFilledWhite|coloredFilledWhiteV3)\//g;
            const imageFileTypeRegex = /\.(svg|png)$/g;
            const anyNonDigitRegex = /\D/g;

            // update marker icons and shadows within layer groups (required for enable/disable layer groups)
            for (const [groupName, placesData] of Object.entries(this.groupedPlaces)) {
                this.groupedPlaces[groupName].layerGroup.getLayers().forEach(marker => {
                    let markerIconOptions = marker.getIcon().options;
                    let iconUrl = markerIconOptions.iconUrl.replace(subPathRegex, mapMarkerSubPath);
                    iconUrl = iconUrl.replace(imageFileTypeRegex, mapMarkerFileType);
                    markerIconOptions.iconUrl = iconUrl;
                    markerIconOptions.iconRetinaUrl = iconUrl;

                    markerIconOptions.iconSize = [
                        mapMarkerWidth.replace(anyNonDigitRegex, ''),
                        mapMarkerHeight.replace(anyNonDigitRegex, ''),
                    ];

                    markerIconOptions.iconAnchor = [
                        mapMarkerMarginLeft.replace(anyNonDigitRegex, ''),
                        mapMarkerMarginTop.replace(anyNonDigitRegex, ''),
                    ];

                    markerIconOptions.shadowSize = [
                        mapMarkerShadowWidth.replace(anyNonDigitRegex, ''),
                        mapMarkerShadowHeight.replace(anyNonDigitRegex, ''),
                    ];
                });
            }

            // update marker icons on map
            const leafletMarkerIcons = document.querySelectorAll('.leaflet-marker-icon');
            leafletMarkerIcons.forEach(leafletMarkerIcon => {
                leafletMarkerIcon.src = leafletMarkerIcon.src.replace(subPathRegex, mapMarkerSubPath);
                leafletMarkerIcon.src = leafletMarkerIcon.src.replace(imageFileTypeRegex, mapMarkerFileType);
                leafletMarkerIcon.style.height = mapMarkerHeight;
                leafletMarkerIcon.style.width = mapMarkerWidth;
                leafletMarkerIcon.style.marginLeft = mapMarkerMarginLeft;
                leafletMarkerIcon.style.marginTop = mapMarkerMarginTop;
            });

            // update marker shadows on map
            const leafletMarkerShadows = document.querySelectorAll('.leaflet-marker-shadow');
            leafletMarkerShadows.forEach(leafletMarkerShadow => {
                leafletMarkerShadow.style.height = mapMarkerShadowHeight;
                leafletMarkerShadow.style.width = mapMarkerShadowWidth;
                leafletMarkerShadow.style.marginLeft = mapMarkerMarginLeft;
                leafletMarkerShadow.style.marginTop = mapMarkerMarginTop;
            });
        },
        /**
         * Enable/disable map greyscale filter.
         */
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
