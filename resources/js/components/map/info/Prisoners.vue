<template>
    <div v-if="selectedPlace.prisonerCounts.length > 0 || selectedPlace.prisoners.length > 0">
        <!-- prisoner count - https://www.wikidata.org/wiki/Property:P5630
             and sourcing circumstances - https://www.wikidata.org/wiki/Property:P1480
             and prisoner names based on their place of detention - https://www.wikidata.org/wiki/Property:P2632
        -->
        <v-list-item dense>
            <v-list-item-content>
                <v-list-item-title>Inhaftierte</v-list-item-title>
                <v-list-item-subtitle>
                    <ul v-if="selectedPlace.prisonerCounts.length > 0">
                        <li v-for="prisonerCount in selectedPlace.prisonerCounts">
                            <span v-if="prisonerCount.sourcingCircumstance">
                                {{ prisonerCount.sourcingCircumstance }}
                            </span>
                            {{ prisonerCount.value }}
                        </li>
                    </ul>
                    <div v-if="selectedPlace.prisoners.length > 0" class="mt-2">
                        Namen
                        <ul v-for="prisoner in selectedPlace.prisoners">
                            <li>
                                <a @click.stop="$emit('showPerson', {
                                       id: prisoner.id,
                                       group: 'victims',
                                   })"
                                   href="#"
                                >
                                    {{ prisoner.name }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </v-list-item-subtitle>
            </v-list-item-content>
        </v-list-item>
        <v-divider></v-divider>
    </div>
</template>

<script>
export default {
    name: 'Prisoners',
    props: ['selectedPlace'],
};
</script>

<style scoped>

</style>