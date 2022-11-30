<template>
    <div>
        <!-- place info sidebar -->
        <v-navigation-drawer
            v-model="showPlaceInfoSidebar"
            absolute
            class="z-index-5"
            color="white"
            hide-overlay
            mobile-breakpoint="750"
            width="375px"
        >
            <!-- place info header, link to wikidata item, close sidebar button -->
            <v-list-item>
                <v-list-item-content>
                    <v-list-item-title class="text-h6 text-sm-h5 white-space-normal font-family-courier font-weight-bold">
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
                       contain
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

            <!-- detailed item description -->
            <template v-if="selectedPlace.id in itemDescriptions">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-expansion-panels v-model="itemDescriptionPanel" focusable>
                            <v-expansion-panel class="grey lighten-3" style="max-width: 99%">
                                <v-expansion-panel-header>
                                    <v-list-item-title>Langbeschreibung</v-list-item-title>
                                </v-expansion-panel-header>
                                <v-expansion-panel-content class="mt-4">
                                    <v-list-item-subtitle
                                        class="hyphens-auto white-space-normal"
                                        lang="de"
                                    >
                                        <p v-for="textPart in itemDescriptions[selectedPlace.id].textParts">
                                            {{ textPart }}
                                        </p>
                                    </v-list-item-subtitle>
                                </v-expansion-panel-content>
                            </v-expansion-panel>
                        </v-expansion-panels>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <event
                v-if="selectedPlace.groupName == 'events'"
                :selectedPlace="selectedPlace"
                @showPerson="$emit('showPerson', $event)"
                @switchLocation="$emit('switchLocation', $event)"
                @undoZoomIntoPlace="$emit('undoZoomIntoPlace')"
                @zoomIntoPlace="$emit('zoomIntoPlace')"
            ></event>

            <gestapo-place
                v-else-if="gestapoPlaceGroups.includes(selectedPlace.groupName)"
                :selectedPlace="selectedPlace"
                @showPerson="$emit('showPerson', $event)"
                @switchLocation="$emit('switchLocation', $event)"
                @undoZoomIntoPlace="$emit('undoZoomIntoPlace')"
                @zoomIntoPlace="$emit('zoomIntoPlace')"
            ></gestapo-place>

            <memorial-place
                v-else-if="selectedPlace.groupName == 'memorials'"
                :selectedPlace="selectedPlace"
                @switchLocation="$emit('switchLocation', $event)"
                @undoZoomIntoPlace="$emit('undoZoomIntoPlace')"
                @zoomIntoPlace="$emit('zoomIntoPlace')"
            ></memorial-place>

            <person
                v-else-if="['perpetrators', 'victims'].includes(selectedPlace.groupName)"
                :selectedPerson="selectedPlace"
                @switchLocation="$emit('switchLocation', $event)"
            ></person>
        </v-navigation-drawer>
    </div>
</template>

<script>
import Event from './info/Event';
import GestapoPlace from './info/GestapoPlace';
import MemorialPlace from './info/MemorialPlace';
import Person from './info/Person';

export default {
    name: 'PlaceInfoSidebar',
    components: {Event, GestapoPlace, MemorialPlace, Person},
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
            itemDescriptionPanel: [],
            itemDescriptions: this.$ogtGlobals.texts.itemDescriptions,
        };
    },
    methods: {
        /**
         * Close opened item description panel.
         */
        closePanels: function () {
            this.itemDescriptionPanel = [];
        },
    },
    watch: {
        /**
         * When the displayed map location item is changed, then...
         * - close opened panels
         *
         * @param newSelectedPlaceId Wikidata item id
         * @param oldSelectedPlaceId Wikidata item id
         */
        'selectedPlace.id': function (newSelectedPlaceId, oldSelectedPlaceId) {
            this.closePanels();
        },
    },
};
</script>

<style scoped>
.hyphens-auto {
    hyphens: auto;
}

/* to enable linebreaks for long labels */
.white-space-normal {
    white-space: normal;
}

/* to move the place info sidebar to the foreground to cover the navigation menu button (z-index 4) */
.z-index-5 {
    z-index: 5;
}

.font-family-courier {
    font-family: Courier !important;
}
</style>
