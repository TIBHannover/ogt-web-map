<template>
    <div>
        <!-- official website - https://www.wikidata.org/wiki/Property:P856 -->
        <template v-if="selectedPlace.website">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Offizielle Website</v-list-item-title>
                    <v-list-item-subtitle class="white-space-normal">
                        <a :href="selectedPlace.website" target="_blank">
                            {{ selectedPlace.website }}
                        </a>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- operators - https://www.wikidata.org/wiki/Property:P137 -->
        <template v-if="selectedPlace.operators.length > 0">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Träger</v-list-item-title>
                    <v-list-item-subtitle>
                        <ul class="hyphens-auto white-space-normal" lang="de">
                            <li v-for="operator in selectedPlace.operators">
                                {{ operator }}
                            </li>
                        </ul>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <address-info
            :selectedPlace="selectedPlace"
            @switchLocation="$emit('switchLocation', $event)"
            @undoZoomIntoPlace="$emit('undoZoomIntoPlace')"
            @zoomIntoPlace="$emit('zoomIntoPlace')"
        ></address-info>
    </div>
</template>

<script>
import AddressInfo from './AddressInfo';

export default {
    name: 'MemorialPlace',
    components: {AddressInfo},
    props: ['selectedPlace'],
};
</script>

<style scoped>
/* to enable linebreaks for long labels */
.white-space-normal {
    white-space: normal;
}
</style>