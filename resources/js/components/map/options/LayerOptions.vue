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
                'Dunkelgraue Symbole (grau gefüllt)',
                'Farbige Symbole v1 (hellgrau gefüllt)',
                'Farbige Symbole v2 (grau gefüllt)',
                'Farbige Symbole v2 (hellgrau gefüllt)',
                'Farbige Symbole v2 (weiß gefüllt)',
                'Farbige Symbole v3 (weiß gefüllt)',
            ],
            mapMarkerStyleSelected: 4,
            mapGreyscaleLabels: ['Graustufen deaktiviert', 'Graustufen aktiviert'],
            mapGreyscaleSelected: 1,
        };
    },
    methods: {
        /**
         * Switch between map marker styles.
         */
        switchMapMarkerStyle() {
            let mapMarkerSubPath = '';

            if (this.mapMarkerStyleSelected == 0) {
                mapMarkerSubPath = '/greyFilled/';
            }
            else if (this.mapMarkerStyleSelected == 1) {
                mapMarkerSubPath = '/coloredFilled/';
            }
            else if (this.mapMarkerStyleSelected == 2) {
                mapMarkerSubPath = '/coloredFilledGrey/';
            }
            else if (this.mapMarkerStyleSelected == 3) {
                mapMarkerSubPath = '/coloredFilledLightGrey/';
            }
            else if (this.mapMarkerStyleSelected == 4) {
                mapMarkerSubPath = '/coloredFilledWhite/';
            }
            else if (this.mapMarkerStyleSelected == 5) {
                mapMarkerSubPath = '/coloredFilledWhiteV3/';
            }

            const subPathRegex = /\/(greyFilled|coloredFilled|coloredFilledGrey|coloredFilledLightGrey|coloredFilledWhite|coloredFilledWhiteV3)\//g;

            // update marker icons and shadows within layer groups (required for enable/disable layer groups)
            for (const [groupName, placesData] of Object.entries(this.groupedPlaces)) {
                this.groupedPlaces[groupName].layerGroup.getLayers().forEach(marker => {
                    let markerIconOptions = marker.getIcon().options;
                    let iconUrl = markerIconOptions.iconUrl.replace(subPathRegex, mapMarkerSubPath);
                    markerIconOptions.iconUrl = iconUrl;
                    markerIconOptions.iconRetinaUrl = iconUrl;
                });
            }

            // update marker icons on map
            const leafletMarkerIcons = document.querySelectorAll('.leaflet-marker-icon');
            leafletMarkerIcons.forEach(leafletMarkerIcon => {
                leafletMarkerIcon.src = leafletMarkerIcon.src.replace(subPathRegex, mapMarkerSubPath);
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
