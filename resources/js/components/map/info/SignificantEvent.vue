<template>
    <div>
        <!-- associated significant events - https://www.wikidata.org/wiki/Property:P793 -->
        <v-list-item dense>
            <v-list-item-content>
                <v-list-item-title>Ereignisse</v-list-item-title>
                <v-list-item-subtitle>
                    <ul class="hyphens-auto white-space-normal" lang="de">
                        <li v-for="event in selectedPlace.significantEvents">
                            <a v-if="event.hasLocationMarker"
                               @click.stop="$emit('switchLocation', { locationId: event.id })"
                               href="#"
                            >
                                {{ event.label }}
                            </a>
                            <template v-else>
                                {{ event.label }}
                            </template>
                        </li>
                    </ul>
                </v-list-item-subtitle>
            </v-list-item-content>
        </v-list-item>
        <v-divider></v-divider>
    </div>
</template>

<script>
export default {
    name: 'SignificantEvent',
    props: ['selectedPlace'],
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
</style>