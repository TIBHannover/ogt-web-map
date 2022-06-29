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
                    <a :href="selectedPlaceInfo.wikidataItem" target="_blank">
                        <v-img max-width="45" :src="this.$ogtGlobals.proxyPath + '/images/wikidata-logo.svg'"></v-img>
                    </a>
                </v-list-item-action>
            </v-list-item>

            <v-divider></v-divider>

            <!-- place sample image -->
            <div v-show="selectedPlaceInfo.imageUrl">
                <v-img max-height="250" :src="selectedPlaceInfo.imageUrl"></v-img>
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

            <!-- place is an instance of - https://www.wikidata.org/wiki/Property:P31 -->
            <!--
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Instanz von</v-list-item-title>
                    <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                        {{ selectedPlaceInfo.instanceLabels }}
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>

            <v-divider></v-divider>
            -->

            <!-- official website - https://www.wikidata.org/wiki/Property:P856 -->
            <template v-if="selectedPlaceInfo.officialWebsite">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Offizielle Website</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                            <a :href="selectedPlaceInfo.officialWebsite" target="_blank">
                                {{ selectedPlaceInfo.officialWebsite }}
                            </a>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- inception - https://www.wikidata.org/wiki/Property:P571 -->
            <!-- dissolved, abolished or demolished date - https://www.wikidata.org/wiki/Property:P576 -->
            <template v-if="selectedPlaceInfo.inceptionDates.length > 0 || selectedPlaceInfo.dissolvedDates.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Laufzeit</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                            <template v-if="selectedPlaceInfo.inceptionDates.length > 0">
                                von
                                <ul v-for="inceptionDate in selectedPlaceInfo.inceptionDates">
                                    <li>{{ inceptionDate }}</li>
                                </ul>
                            </template>
                            <template v-if="selectedPlaceInfo.dissolvedDates.length > 0">
                                bis
                                <ul v-for="dissolvedDate in selectedPlaceInfo.dissolvedDates">
                                    <li>{{ dissolvedDate }}</li>
                                </ul>
                            </template>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- time a time period starts - https://www.wikidata.org/wiki/Property:P580 -->
            <template v-if="selectedPlaceInfo.startDate">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title v-if="selectedPlaceInfo.layerName == 'Erinnerungsorte'">
                            Erste Aktivität
                        </v-list-item-title>
                        <v-list-item-title v-else>Startzeitpunkt</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                            {{ selectedPlaceInfo.startDate }}
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- date of official opening - https://www.wikidata.org/wiki/Property:P1619 -->
            <template v-if="selectedPlaceInfo.dateOfOfficialOpening">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Eröffnungsdatum</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                           {{ selectedPlaceInfo.dateOfOfficialOpening }}
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- located in the administrative territorial entity - https://www.wikidata.org/wiki/Property:P131 -->
            <template v-if="selectedPlaceInfo.administrativeTerritorialEntitys.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Zuständigkeit</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                            <ul>
                                <template v-for="administrativeTerritorialEntity in selectedPlaceInfo.administrativeTerritorialEntitys">
                                    <li>{{ administrativeTerritorialEntity }}</li>
                                </template>
                            </ul>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- has use - https://www.wikidata.org/wiki/Property:P366 -->
            <template v-if="selectedPlaceInfo.hasUseData.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Leistungen und Angebote</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                            <ul>
                                <template v-for="hasUse in selectedPlaceInfo.hasUseData">
                                    <li>{{ hasUse }}</li>
                                </template>
                            </ul>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- place coordinates and zoom-in-place icon -->
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

            <!-- street address - https://www.wikidata.org/wiki/Property:P6375 -->
            <template v-if="selectedPlaceInfo.streetAddresses.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Adresse(n)</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                            <ul v-for="streetAddress in selectedPlaceInfo.streetAddresses">
                                <li>{{ streetAddress.address }}</li>
                                <ul v-if="streetAddress.startDate || streetAddress.endDate">
                                    <li>
                                        <template v-if="streetAddress.startDate">{{ streetAddress.startDate }}</template>
                                        <template v-if="streetAddress.endDate">bis {{ streetAddress.endDate }}</template>
                                    </li>
                                </ul>
                            </ul>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- commemorates - https://www.wikidata.org/wiki/Property:P547 -->
            <template v-if="selectedPlaceInfo.commemoratesData.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Erinnert an</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                            <ul>
                                <template v-for="commemorates in selectedPlaceInfo.commemoratesData">
                                    <li>{{ commemorates }}</li>
                                </template>
                            </ul>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- employees - https://www.wikidata.org/wiki/Property:P1128 -->
            <template v-if="selectedPlaceInfo.employeesData.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Personal</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                            <ul>
                                <template v-for="employees in selectedPlaceInfo.employeesData">
                                    <li>
                                        <template v-if="employees.sourcingCircumstances">
                                            {{ employees.sourcingCircumstances }}.
                                        </template>
                                        {{ employees.numberOfEmployees }}
                                        <template v-if="employees.pointInTime">
                                            ({{ employees.pointInTime }})
                                        </template>
                                    </li>
                                </template>
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
                                <template v-for="significantEvent in selectedPlaceInfo.significantEvents">
                                    <li>{{ significantEvent }}</li>
                                </template>
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
                                <template v-for="parentOrganization in selectedPlaceInfo.parentOrganizations">
                                    <li>{{ parentOrganization }}</li>
                                </template>
                            </ul>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- operator - https://www.wikidata.org/wiki/Property:P137 -->
            <template v-if="selectedPlaceInfo.operators.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Träger</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                            <ul>
                                <template v-for="operator in selectedPlaceInfo.operators">
                                    <li>{{ operator }}</li>
                                </template>
                            </ul>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- subsidiary - https://www.wikidata.org/wiki/Property:P355 -->
            <template v-if="selectedPlaceInfo.subsidiarys.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Nachgeordnete Organisation</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                            <ul>
                                <template v-for="subsidiary in selectedPlaceInfo.subsidiarys">
                                    <li>{{ subsidiary }}</li>
                                </template>
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
                                <template v-for="replacesItem in selectedPlaceInfo.replaces">
                                    <li>{{ replacesItem }}</li>
                                </template>
                            </ul>
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- replacedBy - https://www.wikidata.org/wiki/Property:P1366 -->
            <template v-if="selectedPlaceInfo.replacedBy.length > 0">
                <v-list-item dense>
                    <v-list-item-content>
                        <v-list-item-title>Nachfolgeorganisation (zeitlich)</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                            <ul>
                                <template v-for="replacedByItem in selectedPlaceInfo.replacedBy">
                                    <li>{{ replacedByItem }}</li>
                                </template>
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
                        <v-list-item-title>Nachweise/Quellen</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                            <ul>
                                <template v-for="source in selectedPlaceInfo.sources">
                                    <li>{{ source.label }}
                                        <template v-if="source.wikidataUrl">
                                            (<a :href="source.wikidataUrl" target="_blank">WD</a>)
                                        </template>
                                        <template v-if="source.dnbUrl">
                                            (<a :href="source.dnbUrl" target="_blank">DNB</a>)
                                        </template>
                                    </li>
                                </template>
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
</style>
