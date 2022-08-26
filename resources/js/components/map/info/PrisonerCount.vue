<template>
    <div>
        <!-- prisoner count - https://www.wikidata.org/wiki/Property:P5630
             and sourcing circumstances - https://www.wikidata.org/wiki/Property:P1480
        -->
        <v-list-item dense>
            <v-list-item-content>
                <v-list-item-title>Inhaftierte</v-list-item-title>
                <v-list-item-subtitle>
                    <ul>
                        <li v-for="prisonerCount in selectedPlace.prisonerCounts">
                            <span v-if="prisonerCount.sourcingCircumstance">
                                {{ prisonerCount.sourcingCircumstance }}
                            </span>
                            {{ prisonerCount.value }}
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
    name: 'PrisonerCount',
    props: ['selectedPlace'],
};
</script>

<style scoped>

</style>