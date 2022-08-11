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
                        {{ selectedPlaceInfo.label }}
                    </v-list-item-title>
                    <v-list-item-subtitle>{{ selectedPlaceInfo.layerName }}</v-list-item-subtitle>
                </v-list-item-content>
                <v-list-item-action>
                    <v-btn @click.stop="$emit('hidePlaceInfoSidebar')" icon>
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                    <a :href="'https://www.wikidata.org/wiki/' + selectedPlaceInfo.id" target="_blank">
                        <v-img max-width="45" :src="this.$ogtGlobals.proxyPath + '/images/wikidata-logo.svg'"></v-img>
                    </a>
                </v-list-item-action>
            </v-list-item>

            <v-divider></v-divider>

            <!-- location main image and image legend -->
            <div v-show="selectedPlaceInfo.mainImageUrl">
                <v-img :alt="selectedPlaceInfo.mainImageLegend"
                       max-height="250"
                       :src="selectedPlaceInfo.mainImageUrl"
                       :title="selectedPlaceInfo.mainImageLegend"
                ></v-img>
                <v-divider></v-divider>
            </div>

            <!-- Wikidata item brief description -->
            <template v-if="selectedPlaceInfo.description">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Kurzbeschreibung</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto text-justify white-space-normal" lang="de">
                            {{ selectedPlaceInfo.description }}
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- inception - https://www.wikidata.org/wiki/Property:P571 -->
            <!-- dissolved, abolished or demolished date - https://www.wikidata.org/wiki/Property:P576 -->
            <template v-if="selectedPlaceInfo.inceptionDate.value || selectedPlaceInfo.dissolvedDate.value">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Laufzeit</v-list-item-title>
                        <v-list-item-subtitle>
                            <template v-if="selectedPlaceInfo.inceptionDate.value">
                                von {{ selectedPlaceInfo.inceptionDate.locale }}
                            </template>
                            <template v-if="selectedPlaceInfo.dissolvedDate.value">
                                bis {{ selectedPlaceInfo.dissolvedDate.locale }}
                            </template>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- addresses with associated coordinates and time periods, as well as option to zoom in/out of location. -->
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Adresse(n)</v-list-item-title>
                    <v-list-item-subtitle>
                        <ul>
                            <li class="hyphens-auto white-space-normal" lang="de">
                                {{
                                    selectedPlaceInfo.addresses.selected.label ?
                                        selectedPlaceInfo.addresses.selected.label : 'Anschrift N/A'
                                }}
                            </li>
                            <ul>
                                <li v-if="selectedPlaceInfo.addresses.selected.startDate ||
                                          selectedPlaceInfo.addresses.selected.endDate"
                                >
                                    <template v-if="selectedPlaceInfo.addresses.selected.startDate">
                                        von {{ selectedPlaceInfo.addresses.selected.startDate.locale }}
                                    </template>
                                    <template v-if="selectedPlaceInfo.addresses.selected.endDate">
                                        bis {{ selectedPlaceInfo.addresses.selected.endDate.locale }}
                                    </template>
                                </li>
                                <li>
                                    {{ selectedPlaceInfo.addresses.selected.latLng.lat }},
                                    {{ selectedPlaceInfo.addresses.selected.latLng.lng }}
                                </li>
                            </ul>
                        </ul>
                        <div v-if="selectedPlaceInfo.addresses.additional.length > 0" class="mt-3">
                            Weitere Standorte
                            <ul v-for="additionalAddress in selectedPlaceInfo.addresses.additional" class="mb-3">
                                <li class="hyphens-auto white-space-normal" lang="de">
                                    <a v-if="additionalAddress.latLng"
                                       @click.stop="$emit('switchLocation', {
                                           locationId: selectedPlaceInfo.id,
                                           latLng: additionalAddress.latLng,
                                       })"
                                       href="#"
                                    >
                                        {{ additionalAddress.label ? additionalAddress.label : 'Anschrift N/A' }}
                                    </a>
                                    <template v-else>
                                        {{ additionalAddress.label ? additionalAddress.label : 'Anschrift N/A' }}
                                    </template>
                                </li>
                                <ul>
                                    <li v-if="additionalAddress.startDate || additionalAddress.endDate">
                                        <template v-if="additionalAddress.startDate">
                                            von {{ additionalAddress.startDate.locale }}
                                        </template>
                                        <template v-if="additionalAddress.endDate">
                                            bis {{ additionalAddress.endDate.locale }}
                                        </template>
                                    </li>
                                    <li v-if="additionalAddress.latLng">
                                        {{ additionalAddress.latLng.lat }}, {{ additionalAddress.latLng.lng }}
                                    </li>
                                    <li v-else>
                                        Koordinaten N/A
                                    </li>
                                </ul>
                            </ul>
                        </div>
                    </v-list-item-subtitle>
                </v-list-item-content>
                <v-list-item-action class="flex-direction-row ml-0 my-0">
                    <v-btn @click.stop="$emit('zoomIntoPlace')" icon>
                        <v-icon>mdi-magnify-plus</v-icon>
                    </v-btn>
                    <v-btn @click.stop="$emit('undoZoomIntoPlace')" icon>
                        <v-icon>mdi-undo-variant</v-icon>
                    </v-btn>
                </v-list-item-action>
            </v-list-item>
            <v-divider></v-divider>

            <!-- place is described by source - https://www.wikidata.org/wiki/Property:P1343 -->
            <template v-if="selectedPlaceInfo.sources.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Nachweise</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                            <ul>
                                <li v-for="source in selectedPlaceInfo.sources">
                                    {{ source.label }}<span v-if="source.pages">, S. {{ source.pages }}</span>
                                </li>
                            </ul>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>
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
/* move to top right */
.flex-direction-row {
    align-self: flex-start;
    flex-direction: row;
    padding-top: 7px;
}

.hyphens-auto {
    hyphens: auto;
}

/* to enable linebreaks for long labels */
.white-space-normal {
    white-space: normal;
}
</style>
