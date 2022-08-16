<template>
    <div>
        <!-- place info sidebar -->
        <v-navigation-drawer
            v-model="showPlaceInfoSidebar"
            absolute
            color="grey lighten-3"
            hide-overlay
            mobile-breakpoint="750"
            width="375px"
        >
            <!-- place info header, link to wikidata item, close sidebar button -->
            <v-list-item>
                <v-list-item-content>
                    <v-list-item-title class="text-h6 text-sm-h5 white-space-normal">
                        {{ selectedPlace.label }}
                    </v-list-item-title>
                    <v-list-item-subtitle>{{ selectedPlace.layerName }}</v-list-item-subtitle>
                </v-list-item-content>
                <v-list-item-action>
                    <v-btn @click.stop="$emit('hidePlaceInfoSidebar')" icon>
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                    <a :href="'https://www.wikidata.org/wiki/' + selectedPlace.id" target="_blank">
                        <v-img max-width="45" :src="this.$ogtGlobals.proxyPath + '/images/wikidata-logo.svg'"></v-img>
                    </a>
                </v-list-item-action>
            </v-list-item>

            <v-divider></v-divider>

            <!-- location main image and image legend -->
            <div v-show="selectedPlace.mainImageUrl">
                <v-img :alt="selectedPlace.mainImageLegend"
                       max-height="250"
                       :src="selectedPlace.mainImageUrl"
                       :title="selectedPlace.mainImageLegend"
                ></v-img>
                <v-divider></v-divider>
            </div>

            <!-- Wikidata item brief description -->
            <template v-if="selectedPlace.description">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Kurzbeschreibung</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto text-justify white-space-normal" lang="de">
                            {{ selectedPlace.description }}
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <gestapo-place
                v-if="gestapoPlaceGroups.includes(selectedPlace.groupName)"
                :selectedPlace="selectedPlace"
                @switchLocation="$emit('switchLocation', $event)"
                @undoZoomIntoPlace="$emit('undoZoomIntoPlace')"
                @zoomIntoPlace="$emit('zoomIntoPlace')"
            ></gestapo-place>
        </v-navigation-drawer>
    </div>
</template>

<script>
import GestapoPlace from './info/GestapoPlace';

export default {
    name: 'PlaceInfoSidebar',
    components: {GestapoPlace},
    props: ['selectedPlace', 'showPlaceInfoSidebar'],
    data() {
        return {
            gestapoPlaceGroups: [
                'extPolicePrisons',
                'fieldOffices',
                'laborEducationCamps',
                'prisons',
                'statePoliceHeadquarters',
                'statePoliceOffices',
            ],
        };
    },
};
</script>

<style scoped>
/* move to top right */
.hyphens-auto {
    hyphens: auto;
}

/* to enable linebreaks for long labels */
.white-space-normal {
    white-space: normal;
}
</style>
