<template>
    <div>
        <!-- place of detention (+ start/end date) - https://www.wikidata.org/wiki/Property:P2632 -->
        <template v-if="selectedPerson.detentions.length > 0">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Haftort(e)</v-list-item-title>
                    <v-list-item-subtitle>
                        <ul>
                            <li v-for="detention in selectedPerson.detentions" class="mb-2">
                                <a v-if="detention.hasLocationMarker"
                                   @click.stop="$emit('switchLocation', { locationId: detention.id })"
                                   href="#"
                                >
                                    {{ detention.name }}
                                </a>
                                <template v-else>
                                    {{ detention.name }}
                                </template>
                                <ul v-if="detention.startDate || detention.endDate">
                                    <li>
                                        <template v-if="detention.startDate">
                                            von {{ detention.startDate.locale }}
                                        </template>
                                        <template v-if="detention.endDate">
                                            bis {{ detention.endDate.locale }}
                                        </template>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
        </template>
    </div>
</template>

<script>
export default {
    name: 'Victim',
    props: ['selectedPerson'],
};
</script>

<style scoped>

</style>