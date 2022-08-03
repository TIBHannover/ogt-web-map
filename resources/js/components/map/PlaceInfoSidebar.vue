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
                    <a :href="selectedPlaceInfo.wikidataItemUrl" target="_blank">
                        <v-img max-width="45" :src="this.$ogtGlobals.proxyPath + '/images/wikidata-logo.svg'"></v-img>
                    </a>
                </v-list-item-action>
            </v-list-item>

            <v-divider></v-divider>

            <!-- place sample image -->
            <div v-show="selectedPlaceInfo.mainImageUrl">
                <v-img
                    :alt="selectedPlaceInfo.mainImageLegend"
                    max-height="250"
                    :src="selectedPlaceInfo.mainImageUrl"
                    :title="selectedPlaceInfo.mainImageLegend"
                ></v-img>
                <v-divider></v-divider>
            </div>

            <!-- Wikidata item brief description -->
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Kurzbeschreibung</v-list-item-title>
                    <v-list-item-subtitle class="hyphens-auto text-justify white-space-normal" lang="de">
                        {{ selectedPlaceInfo.description }}
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>

            <v-divider></v-divider>

            <!-- inception - https://www.wikidata.org/wiki/Property:P571 -->
            <!-- dissolved, abolished or demolished date - https://www.wikidata.org/wiki/Property:P576 -->
            <template v-if="selectedPlaceInfo.inceptionLocaleDate || selectedPlaceInfo.dissolvedLocaleDate">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Laufzeit</v-list-item-title>
                        <v-list-item-subtitle>
                            <template v-if="selectedPlaceInfo.inceptionLocaleDate">
                                {{ selectedPlaceInfo.inceptionLocaleDate }}
                            </template>
                            <template v-if="selectedPlaceInfo.dissolvedLocaleDate">
                                bis {{ selectedPlaceInfo.dissolvedLocaleDate }}
                            </template>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- located in the administrative territorial entity - https://www.wikidata.org/wiki/Property:P131 -->
            <template v-if="selectedPlaceInfo.administrativeUnits.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Zuständigkeit</v-list-item-title>
                        <v-list-item-subtitle>
                            <ul>
                                <template v-for="administrativeUnit in selectedPlaceInfo.administrativeUnits">
                                    <li>{{ administrativeUnit }}</li>
                                </template>
                            </ul>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- place coordinates and zoom-in-place icon -->
            <!--
            <template v-if="selectedPlaceInfo.latLng">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Koordinaten (Lat., Long.)</v-list-item-title>
                        <v-list-item-subtitle>
                            {{ selectedPlaceInfo.latLng.lat }}, {{ selectedPlaceInfo.latLng.lng }}
                            <template v-if="selectedPlaceInfo.latLngAlt.length">
                                <br>
                                <br>
                                weitere Standorte
                                <ul>
                                    <template v-for="coordinatesAlt in selectedPlaceInfo.latLngAlt">
                                        <li>{{ Object.values(coordinatesAlt).join(', ') }}</li>
                                    </template>
                                </ul>
                            </template>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                    <v-list-item-action class="flex-direction-row my-0">
                        <v-btn @click.stop="$emit('zoomIntoPlace')" icon>
                            <v-icon>mdi-magnify-plus</v-icon>
                        </v-btn>
                        <v-btn @click.stop="$emit('undoZoomIntoPlace')" icon>
                            <v-icon>mdi-undo-variant</v-icon>
                        </v-btn>
                    </v-list-item-action>
                </v-list-item>
                <v-divider></v-divider>
            </template>
            -->

            <!-- place coordinates and zoom-in-place icon -->
            <!--
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Koordinaten (Lat., Long.)</v-list-item-title>
                    <v-list-item-subtitle>
                        {{ selectedPlaceInfo.latLng.lat }}, {{ selectedPlaceInfo.latLng.lng }}
                    </v-list-item-subtitle>
                </v-list-item-content>
                <v-list-item-action class="flex-direction-row my-0">
                    <v-btn @click.stop="$emit('zoomIntoPlace')" icon>
                        <v-icon>mdi-magnify-plus</v-icon>
                    </v-btn>
                    <v-btn @click.stop="$emit('undoZoomIntoPlace')" icon>
                        <v-icon>mdi-undo-variant</v-icon>
                    </v-btn>
                </v-list-item-action>
            </v-list-item>

            <v-divider></v-divider>
            -->

            <!-- street address - https://www.wikidata.org/wiki/Property:P6375
                    start time - https://www.wikidata.org/wiki/Property:P580
                    end time - https://www.wikidata.org/wiki/Property:P582
            -->
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Adresse(n)</v-list-item-title>
                        <v-list-item-subtitle>
                            <!--<button type="button text-decoration-none">-->
                            <!--<a href="#23234342" class="text-decoration-none">-->
                            <div v-if="selectedPlaceInfo.selectedAddress"
                                 class="hyphens-auto white-space-normal"
                                 lang="de"
                            >
                                {{ selectedPlaceInfo.selectedAddress.address }}
                                <ul>
                                    <li v-if="selectedPlaceInfo.selectedAddress.startDateLocale ||
                                              selectedPlaceInfo.selectedAddress.endDateLocale"
                                    >
                                        <template v-if="selectedPlaceInfo.selectedAddress.startDateLocale">
                                            {{ selectedPlaceInfo.selectedAddress.startDateLocale }}
                                        </template>
                                        <template v-if="selectedPlaceInfo.selectedAddress.endDateLocale">
                                            bis {{ selectedPlaceInfo.selectedAddress.endDateLocale }}
                                        </template>
                                    </li>
                                    <li>
                                        {{ selectedPlaceInfo.selectedAddress.coordinate.join(', ') }}
                                    </li>
                                </ul>
                            </div>
                            <div v-else>
                                Anschrift N/A
                                <ul>
                                    <li>
                                        {{ selectedPlaceInfo.latLng.lat }}, {{ selectedPlaceInfo.latLng.lng }}
                                    </li>
                                </ul>
                            </div>
                            <!--</a>-->
                            <!--</button>-->
                            <!--<br>-->
                            <div class="mt-3" v-if="selectedPlaceInfo.additionalAddresses.length > 0">
                                Weitere Standorte
                                <ul v-for="additionalAddress in selectedPlaceInfo.additionalAddresses"
                                    class="mb-3 hyphens-auto white-space-normal"
                                    lang="de"
                                >
                                    <li v-if="additionalAddress.coordinate.length > 0">
                                        <a @click.stop="$emit('switchAddress', {
                                            coordinate: additionalAddress.coordinate,
                                            xxx: selectedPlaceInfo.markers[additionalAddress.coordinate.toString()],
                                           })"
                                           href="#"
                                        >
                                            {{ additionalAddress.address }}
                                        </a>
                                    </li>
                                    <li v-else>
                                        {{ additionalAddress.address }}
                                    </li>
                                    <ul v-if="additionalAddress.startDateLocale || additionalAddress.endDateLocale">
                                        <li>
                                            <template v-if="additionalAddress.startDateLocale">
                                                {{ additionalAddress.startDateLocale }}
                                            </template>
                                            <template v-if="additionalAddress.endDateLocale">
                                                bis {{ additionalAddress.endDateLocale }}
                                            </template>
                                        </li>
                                        <li v-if="additionalAddress.coordinate.length > 0">
                                            {{ additionalAddress.coordinate.join(', ') }}
                                        </li>
                                        <li v-else>
                                            Koordinaten N/A
                                        </li>
                                    </ul>

                                    <!--
                                    <li class="hyphens-auto white-space-normal" lang="de">
                                        {{ streetAddress.address }}
                                    </li>
                                    <ul v-if="streetAddress.startDateLocale || streetAddress.endDateLocale">
                                        <li>
                                            <template v-if="streetAddress.startDateLocale">
                                                {{ streetAddress.startDateLocale }}
                                            </template>
                                            <template v-if="streetAddress.endDateLocale">
                                                bis {{ streetAddress.endDateLocale }}
                                            </template>
                                        </li>
                                        <li v-if="streetAddress.coordinate != ''">
                                            {{ streetAddress.coordinate.join(', ') }}
                                        </li>
                                    </ul>
                                    -->
                                </ul>
                                <ul>
                                    <!--
                                    <template v-for="coordinatesAlt in selectedPlaceInfo.latLngAlt">
                                        <li>{{ Object.values(coordinatesAlt).join(', ') }}</li>
                                    </template>
                                    -->
                                </ul>
                            </div>
                        </v-list-item-subtitle>
                        <!--
                        <v-list-item-subtitle>
                            <ul v-for="streetAddress in selectedPlaceInfo.streetAddresses"
                                class="mb-3"
                                :class="{'selectedItemBorder': streetAddress.isSelected }"
                                @click.stop="$emit('switchAddress', streetAddress.coordinate)"
                            >
                                <li class="hyphens-auto white-space-normal" lang="de">
                                    {{ streetAddress.address }}
                                </li>
                                <ul v-if="streetAddress.startDateLocale || streetAddress.endDateLocale">
                                    <li>
                                        <template v-if="streetAddress.startDateLocale">
                                            {{ streetAddress.startDateLocale }}
                                        </template>
                                        <template v-if="streetAddress.endDateLocale">
                                            bis {{ streetAddress.endDateLocale }}
                                        </template>
                                    </li>
                                    <li v-if="streetAddress.coordinate != ''">
                                        {{ streetAddress.coordinate.join(', ') }}
                                    </li>
                                </ul>
                            </ul>
                        </v-list-item-subtitle>
                        -->
                    </v-list-item-content>
                    <v-list-item-action class="flex-direction-row my-0">
                        <v-btn @click.stop="$emit('zoomIntoPlace')" icon>
                            <v-icon>mdi-magnify-plus</v-icon>
                        </v-btn>
                        <v-btn @click.stop="$emit('undoZoomIntoPlace')" icon>
                            <v-icon>mdi-undo-variant</v-icon>
                        </v-btn>
                    </v-list-item-action>
                </v-list-item>
                <v-divider></v-divider>

            <!-- street address - https://www.wikidata.org/wiki/Property:P6375
                    start time - https://www.wikidata.org/wiki/Property:P580
                    end time - https://www.wikidata.org/wiki/Property:P582
            -->
            <!--
            <template v-if="selectedPlaceInfo.streetAddresses.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Adresse(n)</v-list-item-title>
                        <v-list-item-subtitle>
                            <ul v-for="streetAddress in selectedPlaceInfo.streetAddresses"
                                class="mb-3"
                                :class="{'selectedItemBorder': streetAddress.isSelected }"
                                @click.stop="$emit('switchAddress', streetAddress.coordinate)"
                            >
                                <li class="hyphens-auto white-space-normal" lang="de">
                                    {{ streetAddress.address }}
                                </li>
                                <ul v-if="streetAddress.startDateLocale || streetAddress.endDateLocale">
                                    <li>
                                        <template v-if="streetAddress.startDateLocale">
                                            {{ streetAddress.startDateLocale }}
                                        </template>
                                        <template v-if="streetAddress.endDateLocale">
                                            bis {{ streetAddress.endDateLocale }}
                                        </template>
                                    </li>
                                    <li v-if="streetAddress.coordinate != ''">
                                        {{ streetAddress.coordinate.join(', ') }}
                                    </li>
                                </ul>
                            </ul>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>
            -->

            <!-- employees - https://www.wikidata.org/wiki/Property:P1128 -->
            <template v-if="selectedPlaceInfo.employeeCounts.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Personalstärke</v-list-item-title>
                        <v-list-item-subtitle>
                            <ul>
                                <li v-for="employeeCount in selectedPlaceInfo.employeeCounts">
                                    <template v-if="employeeCount.sourcingCircumstance">
                                        {{ employeeCount.sourcingCircumstance }}.
                                    </template>
                                    {{ employeeCount.counted }}
                                    <template v-if="employeeCount.pointInLocaleTime">
                                        ({{ employeeCount.pointInLocaleTime }})
                                    </template>
                                </li>
                            </ul>
                            <div class="mt-3" v-if="selectedPlaceInfo.directors.length > 0">
                                Leitung
                                <ul v-for="director in selectedPlaceInfo.directors"
                                    class="mb-3"
                                >
                                    <li>
                                        {{ director.name }}
                                    </li>
                                    <ul v-if="director.latestStartDate[1] || director.latestDate[1]">
                                        <li class="hyphens-auto white-space-normal"
                                            lang="de"
                                        >
                                            <template v-if="director.latestStartDate[1]">
                                                von zwischen {{ director.earliestDate[1] }} und {{ director.latestStartDate[1] }}
                                            </template>
                                            <template v-else>
                                                {{ director.earliestDate[1] }}
                                            </template>
                                            <template v-if="director.earliestEndDate[1]">
                                                bis zwischen {{ director.earliestEndDate[1] }} und {{ director.latestDate[1] }}
                                            </template>
                                            <template v-else>
                                                bis {{ director.latestDate[1] }}
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

            <!-- prisoner count - https://www.wikidata.org/wiki/Property:P5630 -->
            <template v-if="selectedPlaceInfo.prisonerCounts.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Inhaftierte</v-list-item-title>
                        <v-list-item-subtitle>
                            <ul>
                                <li v-for="prisonerCount in selectedPlaceInfo.prisonerCounts">
                                    <span v-if="prisonerCount.sourcingCircumstance">
                                        {{ prisonerCount.sourcingCircumstance }}
                                    </span>
                                    {{ prisonerCount.counted }}
                                </li>
                            </ul>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- significant event - https://www.wikidata.org/wiki/Property:P793 -->
            <template v-if="selectedPlaceInfo.significantEvents.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Ereignisse</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                            <ul>
                                <li v-for="significantEvent in selectedPlaceInfo.significantEvents">
                                    {{ significantEvent.label }}
                                </li>
                            </ul>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- parent organization - https://www.wikidata.org/wiki/Property:P749 -->
            <template v-if="selectedPlaceInfo.parentOrganizations.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Übergeordnete Organisation</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                            <ul>
                                <li v-for="parentOrganization in selectedPlaceInfo.parentOrganizations">
                                    <a v-if="parentOrganization.hasMarker"
                                       @click.stop="$emit('switchMarker', parentOrganization.id)"
                                       href="#"
                                    >
                                        {{ parentOrganization.label }}
                                    </a>
                                    <div v-else>
                                        {{ parentOrganization.label }}
                                    </div>
                                </li>
                            </ul>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- subsidiary - https://www.wikidata.org/wiki/Property:P355 -->
            <template v-if="selectedPlaceInfo.subsidiaries.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Nachgeordnete Organisation</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                            <ul>
                                <li v-for="subsidiary in selectedPlaceInfo.subsidiaries">
                                    <a v-if="subsidiary.hasMarker"
                                       @click.stop="$emit('switchMarker', subsidiary.id)"
                                       href="#"
                                    >
                                        {{ subsidiary.label }}
                                    </a>
                                    <div v-else>
                                        {{ subsidiary.label }}
                                    </div>
                                </li>
                            </ul>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- replaces - https://www.wikidata.org/wiki/Property:P1365 -->
            <template v-if="selectedPlaceInfo.replaces.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Vorgängerorganisation (zeitlich)</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                            <ul>
                                <li v-for="replace in selectedPlaceInfo.replaces">
                                    <a v-if="replace.hasMarker"
                                       @click.stop="$emit('switchMarker', replace.id)"
                                       href="#"
                                    >
                                        {{ replace.label }}
                                    </a>
                                    <div v-else>
                                        {{ replace.label }}
                                    </div>
                                </li>
                            </ul>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- replacedBy - https://www.wikidata.org/wiki/Property:P1366 -->
            <template v-if="selectedPlaceInfo.replacedBys.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Nachfolgeorganisation (zeitlich)</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                            <ul>
                                <li v-for="replacedBy in selectedPlaceInfo.replacedBys">
                                    <a v-if="replacedBy.hasMarker"
                                       @click.stop="$emit('switchMarker', replacedBy.id)"
                                       href="#"
                                    >
                                        {{ replacedBy.label }}
                                    </a>
                                    <div v-else>
                                        {{ replacedBy.label }}
                                    </div>
                                </li>
                            </ul>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- place is described by source - https://www.wikidata.org/wiki/Property:P1343 -->
            <template v-if="selectedPlaceInfo.sources.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Nachweise</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                            <ul>
                                <li v-for="source in selectedPlaceInfo.sources">
                                    {{source.label}}
                                    <span v-if="source.pages">
                                        , S. {{ source.pages }}
                                    </span>
                                </li>
                            </ul>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- place is described by source - https://www.wikidata.org/wiki/Property:P1343 -->
            <!--
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Nachweise/Quellen</v-list-item-title>
                    <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                        <ul>
                            <template v-for="source in selectedPlaceInfo.sources">
                                <li>{{ source.label }}
                                    (<a :href="source.wikidataUrl" target="_blank">WD</a>)
                                    (<a :href="source.dnbUrl" target="_blank">DNB</a>)
                                </li>
                            </template>
                        </ul>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            -->

            <v-divider></v-divider>

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

/* to mark the address displayed on the map if several addresses are assigned to one location */
.selectedItemBorder {
    border: 2px dashed #212121; /* grey darken-4 */
    border-radius: 20px;
}


/*.test:hover {*/
/*    background: #212121;*/
/*    !*border: 2px dashed #212121;*! !* grey darken-4 *!*/
/*    border-radius: 20px;*/
/*    !*opacity: .04;*!*/
/*    !*background-color: currentColor;*!*/
/*}*/


</style>
