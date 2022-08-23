<template>
    <div>
        <!-- perpetrators - https://www.wikidata.org/wiki/Property:P8031 -->
        <template v-if="selectedPlace.perpetrators.length > 0">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Täter</v-list-item-title>
                    <v-list-item-subtitle>
                        <ul class="hyphens-auto white-space-normal" lang="de">
                            <li v-for="perpetrator in selectedPlace.perpetrators">
                                <a v-if="perpetrator.hasPersonData"
                                   href="#"
                                >
                                    {{ perpetrator.label }}
                                </a>
                                <a v-else-if="perpetrator.hasLocationMarker"
                                   @click.stop="$emit('switchLocation', {
                                       locationId: perpetrator.id,
                                   })"
                                   href="#"
                                >
                                    {{ perpetrator.label }}
                                </a>
                                <template v-else>
                                    {{ perpetrator.label }}
                                </template>
                            </li>
                        </ul>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- victims - https://www.wikidata.org/wiki/Property:P8032
             number of casualties - https://www.wikidata.org/wiki/Property:P1590
        -->
        <template v-if="selectedPlace.victims.length > 0 || selectedPlace.numberOfCasualties.length > 0">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Geschädigte</v-list-item-title>
                    <v-list-item-subtitle>
                        <div v-if="selectedPlace.numberOfCasualties.length > 0" class="my-2">
                            Anzahl
                            <ul>
                                <li v-for="numberOfCasualties in selectedPlace.numberOfCasualties">
                                    {{ numberOfCasualties }}
                                </li>
                            </ul>
                        </div>
                        <div v-if="selectedPlace.victims.length > 0" class="my-2">
                            Namen
                            <ul>
                                <li v-for="victim in selectedPlace.victims">
                                    <a v-if="victim.hasPersonData" href="#">
                                        {{ victim.label }}
                                    </a>
                                    <template v-else>
                                        {{ victim.label }}
                                    </template>
                                </li>
                            </ul>
                        </div>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- targets - https://www.wikidata.org/wiki/Property:P533 -->
        <template v-if="selectedPlace.targets.length > 0">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Ziel des Angriffs</v-list-item-title>
                    <v-list-item-subtitle>
                        <ul>
                            <li v-for="target in selectedPlace.targets">
                                {{ target }}
                            </li>
                        </ul>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <sources v-if="selectedPlace.sources.length > 0"
                 :selectedPlace="selectedPlace">
        </sources>
    </div>
</template>

<script>
import Sources from './Sources';

export default {
    name: 'Event',
    components: {Sources},
    props: ['selectedPlace'],
};
</script>

<style scoped>
/* to enable linebreaks for long labels */
.white-space-normal {
    white-space: normal;
}
</style>