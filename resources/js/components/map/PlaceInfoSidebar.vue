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
                        {{ selectedPlace.label }}
                    </v-list-item-title>
                    <v-list-item-subtitle>{{ selectedPlace.layerName }}</v-list-item-subtitle>
                </v-list-item-content>
                <v-list-item-action>
                    <v-btn @click.stop="$emit('hidePlaceInfoSidebar')" icon>
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                    <a :href="'https://www.wikidata.org/wiki/' + selectedPlace.id" target="_blank">
                        <v-img max-width="45" :src="this.$ogtGlobals.proxyPath + '/images/wikidata-logo.svg'"></v-img>
                    </a>
                </v-list-item-action>
            </v-list-item>

            <v-divider></v-divider>

            <!-- location main image and image legend -->
            <div v-show="selectedPlace.mainImageUrl">
                <v-img :alt="selectedPlace.mainImageLegend"
                       max-height="250"
                       :src="selectedPlace.mainImageUrl"
                       :title="selectedPlace.mainImageLegend"
                ></v-img>
                <v-divider></v-divider>
            </div>

            <!-- Wikidata item brief description -->
            <template v-if="selectedPlace.description">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Kurzbeschreibung</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto text-justify white-space-normal" lang="de">
                            {{ selectedPlace.description }}
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- inception - https://www.wikidata.org/wiki/Property:P571 -->
            <!-- dissolved, abolished or demolished date - https://www.wikidata.org/wiki/Property:P576 -->
            <template v-if="selectedPlace.inceptionDate.value || selectedPlace.dissolvedDate.value">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Laufzeit</v-list-item-title>
                        <v-list-item-subtitle>
                            <template v-if="selectedPlace.inceptionDate.value">
                                von {{ selectedPlace.inceptionDate.locale }}
                            </template>
                            <template v-if="selectedPlace.dissolvedDate.value">
                                bis {{ selectedPlace.dissolvedDate.locale }}
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
                                    selectedPlace.addresses.selected.label ?
                                        selectedPlace.addresses.selected.label : 'Anschrift N/A'
                                }}
                            </li>
                            <ul>
                                <li v-if="selectedPlace.addresses.selected.startDate ||
                                          selectedPlace.addresses.selected.endDate"
                                >
                                    <template v-if="selectedPlace.addresses.selected.startDate">
                                        von {{ selectedPlace.addresses.selected.startDate.locale }}
                                    </template>
                                    <template v-if="selectedPlace.addresses.selected.endDate">
                                        bis {{ selectedPlace.addresses.selected.endDate.locale }}
                                    </template>
                                </li>
                                <li>
                                    {{ selectedPlace.addresses.selected.latLng.lat }},
                                    {{ selectedPlace.addresses.selected.latLng.lng }}
                                </li>
                            </ul>
                        </ul>
                        <div v-if="selectedPlace.addresses.additional.length > 0" class="mt-3">
                            Weitere Standorte
                            <ul v-for="additionalAddress in selectedPlace.addresses.additional" class="mb-3">
                                <li class="hyphens-auto white-space-normal" lang="de">
                                    <a v-if="additionalAddress.latLng"
                                       @click.stop="$emit('switchLocation', {
                                           locationId: selectedPlace.id,
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

            <!-- number of employees at a given time, sourcing circumstances and directors in respective periods -->
            <template v-if="selectedPlace.employeeCounts.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Personalstärke</v-list-item-title>
                        <v-list-item-subtitle>
                            <ul>
                                <li v-for="employeeCount in selectedPlace.employeeCounts">
                                    <template v-if="employeeCount.sourcingCircumstance">
                                        {{ employeeCount.sourcingCircumstance }}
                                    </template>
                                    {{ employeeCount.value }}
                                    <template v-if="employeeCount.pointInTime">
                                        ({{ employeeCount.pointInTime.locale }})
                                    </template>
                                </li>
                            </ul>
                            <div class="mt-3" v-if="selectedPlace.directors.length > 0">
                                Leitung
                                <ul v-for="director in selectedPlace.directors" class="mb-3">
                                    <li>
                                        {{ director.name }}
                                    </li>
                                    <ul v-if="director.startDate && director.endDate">
                                        <li class="hyphens-auto white-space-normal" lang="de">
                                            <template v-if="director.startDate && director.maxStartDate">
                                                von zwischen {{ director.startDate.locale }} und
                                                {{ director.maxStartDate.locale }}
                                            </template>
                                            <template v-else-if="director.startDate">
                                                von {{ director.startDate.locale }}
                                            </template>
                                            <template v-if="director.minEndDate && director.endDate">
                                                bis zwischen {{ director.minEndDate.locale }} und
                                                {{ director.endDate.locale }}
                                            </template>
                                            <template v-else-if="director.endDate">
                                                bis {{ director.endDate.locale }}
                                            </template>
                                        </li>
                                    </ul>
                                </ul>
                            </div>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- prisoner count and source circumstances -->
            <template v-if="selectedPlace.prisonerCounts.length > 0">
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
            </template>

            <!-- associated significant events - https://www.wikidata.org/wiki/Property:P793 -->
            <template v-if="selectedPlace.events.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Ereignisse</v-list-item-title>
                        <v-list-item-subtitle>
                            <ul class="hyphens-auto white-space-normal" lang="de">
                                <li v-for="event in selectedPlace.events">
                                    {{ event.label }}
                                </li>
                            </ul>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- parent organizations - https://www.wikidata.org/wiki/Property:P749 -->
            <template v-if="selectedPlace.parentOrganizations.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Übergeordnete Organisation</v-list-item-title>
                        <v-list-item-subtitle>
                            <ul>
                                <li v-for="parentOrganization in selectedPlace.parentOrganizations">
                                    <a v-if="parentOrganization.hasLocationMarker"
                                       @click.stop="$emit('switchLocation', {
                                           locationId: parentOrganization.id,
                                       })"
                                       href="#"
                                    >
                                        {{ parentOrganization.label }}
                                    </a>
                                    <template v-else>
                                        {{ parentOrganization.label }}
                                    </template>
                                </li>
                            </ul>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- child organizations - https://www.wikidata.org/wiki/Property:P355 -->
            <template v-if="selectedPlace.childOrganizations.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Nachgeordnete Organisation</v-list-item-title>
                        <v-list-item-subtitle>
                            <ul>
                                <li v-for="childOrganization in selectedPlace.childOrganizations">
                                    <a v-if="childOrganization.hasLocationMarker"
                                       @click.stop="$emit('switchLocation', {
                                           locationId: childOrganization.id,
                                       })"
                                       href="#"
                                    >
                                        {{ childOrganization.label }}
                                    </a>
                                    <template v-else>
                                        {{ childOrganization.label }}
                                    </template>
                                </li>
                            </ul>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- replaces - https://www.wikidata.org/wiki/Property:P1365 -->
            <template v-if="selectedPlace.predecessors.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Vorgängerorganisation (zeitlich)</v-list-item-title>
                        <v-list-item-subtitle>
                            <ul>
                                <li v-for="predecessor in selectedPlace.predecessors">
                                    <a v-if="predecessor.hasLocationMarker"
                                       @click.stop="$emit('switchLocation', {
                                           locationId: predecessor.id,
                                       })"
                                       href="#"
                                    >
                                        {{ predecessor.label }}
                                    </a>
                                    <div v-else>
                                        {{ predecessor.label }}
                                    </div>
                                </li>
                            </ul>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- replaced by - https://www.wikidata.org/wiki/Property:P1366 -->
            <template v-if="selectedPlace.successors.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Nachfolgeorganisation (zeitlich)</v-list-item-title>
                        <v-list-item-subtitle>
                            <ul>
                                <li v-for="successor in selectedPlace.successors">
                                    <a v-if="successor.hasLocationMarker"
                                       @click.stop="$emit('switchLocation', {
                                           locationId: successor.id,
                                       })"
                                       href="#"
                                    >
                                        {{ successor.label }}
                                    </a>
                                    <div v-else>
                                        {{ successor.label }}
                                    </div>
                                </li>
                            </ul>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- place is described by source - https://www.wikidata.org/wiki/Property:P1343 -->
            <template v-if="selectedPlace.sources.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Nachweise</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                            <ul>
                                <li v-for="source in selectedPlace.sources">
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
    props: ['selectedPlace', 'showPlaceInfoSidebar'],
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
