<template>
    <div>
        <!-- employer - https://www.wikidata.org/wiki/Property:P108 -->
        <template v-if="selectedPerson.employers.length > 0">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Einsatzort</v-list-item-title>
                    <v-list-item-subtitle>
                        <ul>
                            <li v-for="employer in selectedPerson.employers">
                                <a v-if="employer.hasLocationMarker"
                                   @click.stop="$emit('switchLocation', {
                                       locationId: employer.id,
                                   })"
                                   href="#"
                                >
                                    {{ employer.label }}
                                </a>
                                <template v-else>
                                    {{ employer.label }}
                                </template>
                            </li>
                        </ul>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>
    </div>
</template>

<script>
export default {
    name: 'Perpetrator',
    props: ['selectedPerson'],
};
</script>

<style scoped>

</style>