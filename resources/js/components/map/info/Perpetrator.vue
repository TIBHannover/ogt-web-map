<template>
    <div>
        <!-- employer - https://www.wikidata.org/wiki/Property:P108 -->
        <template v-if="selectedPerson.employers.length > 0">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Einsatzort</v-list-item-title>
                    <v-list-item-subtitle>
                        <ul>
                            <li v-for="employer in selectedPerson.employers" class="mb-2">
                                <a v-if="employer.hasLocationMarker"
                                   @click.stop="$emit('switchLocation', {
                                       locationId: employer.id,
                                   })"
                                   href="#"
                                >
                                    {{ employer.name }}
                                </a>
                                <template v-else>
                                    {{ employer.name }}
                                </template>
                                <ul v-if="employer.startDate || employer.endDate">
                                    <li class="white-space-normal">
                                        <template v-if="employer.startDate && employer.maxStartDate">
                                            von zwischen {{ employer.startDate.locale }} und
                                            {{ employer.maxStartDate.locale }}
                                        </template>
                                        <template v-else-if="employer.startDate">
                                            von {{ employer.startDate.locale }}
                                        </template>
                                        <template v-if="employer.minEndDate && employer.endDate">
                                            bis zwischen {{ employer.minEndDate.locale }} und
                                            {{ employer.endDate.locale }}
                                        </template>
                                        <template v-else-if="employer.endDate">
                                            bis {{ employer.endDate.locale }}
                                        </template>
                                    </li>
                                </ul>
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
/* to enable linebreaks for long labels */
.white-space-normal {
    white-space: normal;
}
</style>