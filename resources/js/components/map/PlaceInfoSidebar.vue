<template>
    <div>
        <!-- place info sidebar -->
        <v-navigation-drawer
            v-model="showPlaceInfoSidebar"
            absolute
            color="bgCornSilk"
            hide-overlay
            mobile-breakpoint="750"
            width="375px"
        >
            <!-- place info header, link to wikidata item, close sidebar button -->
            <v-list-item>
                <v-list-item-content>
                    <v-list-item-title class="text-h6 text-sm-h5 white-space-normal">
                        {{ selectedPlaceInfo.label }}
                    </v-list-item-title>
                    <v-list-item-subtitle>{{ selectedPlaceInfo.layerName }}</v-list-item-subtitle>
                </v-list-item-content>
                <v-list-item-action>
                    <v-btn @click.stop="$emit('hidePlaceInfoSidebar')" icon>
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                    <a :href="selectedPlaceInfo.wikidataItem" target="_blank">
                        <v-img max-width="45" src="/images/wikidata-logo.svg"></v-img>
                    </a>
                </v-list-item-action>
            </v-list-item>

            <v-divider></v-divider>

            <!-- place sample image -->
            <div v-show="selectedPlaceInfo.imageUrl">
                <v-img max-height="250" :src="selectedPlaceInfo.imageUrl"></v-img>
                <v-divider></v-divider>
            </div>

            <!-- place coordinates and zoom-in-place icon -->
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Koordinaten (Lat., Long.)</v-list-item-title>
                    <v-list-item-subtitle>
                        {{ selectedPlaceInfo.latLng.lat }}, {{ selectedPlaceInfo.latLng.lng }}
                    </v-list-item-subtitle>
                </v-list-item-content>
                <v-list-item-action class="flex-direction-row my-0">
                    <v-btn @click.stop="$emit('zoomIntoPlace')" icon>
                        <v-icon>mdi-magnify-plus</v-icon>
                    </v-btn>
                    <v-btn @click.stop="$emit('undoZoomIntoPlace')" icon>
                        <v-icon>mdi-undo-variant</v-icon>
                    </v-btn>
                </v-list-item-action>
            </v-list-item>

            <v-divider></v-divider>

            <!-- Wikidata item brief description -->
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Kurzbeschreibung</v-list-item-title>
                    <v-list-item-subtitle class="hyphens-auto text-justify white-space-normal" lang="de">
                        {{ selectedPlaceInfo.description }}
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>

            <v-divider></v-divider>

            <!-- place is an instance of - https://www.wikidata.org/wiki/Property:P31 -->
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Instanz von</v-list-item-title>
                    <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                        {{ selectedPlaceInfo.instanceLabels }}
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>

            <v-divider></v-divider>

            <!-- place is described by source - https://www.wikidata.org/wiki/Property:P1343 -->
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Nachweise/Quellen</v-list-item-title>
                    <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                        <ul>
                            <template v-for="source in selectedPlaceInfo.sources">
                                <li>{{ source.label }}
                                    (<a :href="source.wikidataUrl" target="_blank">WD</a>)
                                    (<a :href="source.dnbUrl" target="_blank">DNB</a>)
                                </li>
                            </template>
                        </ul>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>

            <v-divider></v-divider>

        </v-navigation-drawer>
    </div>
</template>

<script>
export default {
    name: 'PlaceInfoSidebar',
    props: ['selectedPlaceInfo', 'showPlaceInfoSidebar'],
};
</script>

<style scoped>
.bgCornSilk {
    background-color: #FFFBE6;
}

.flex-direction-row {
    align-self: auto;
    flex-direction: row;
}

.hyphens-auto {
    hyphens: auto;
}

/* to enable linebreaks for long labels */
.white-space-normal {
    white-space: normal;
}
</style>
