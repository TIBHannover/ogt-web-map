<template>
    <div>
        <!-- location - https://www.wikidata.org/wiki/Property:P276
             significant place - https://www.wikidata.org/wiki/Property:P7153   -->
        <template v-if="selectedPlace.locations.length > 0 || selectedPlace.significantPlaces.length > 0">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Ort</v-list-item-title>
                    <v-list-item-subtitle>
                        <ul>
                            <li v-for="location in selectedPlace.locations">
                                {{ location }}
                            </li>
                            <li v-for="significantPlace in selectedPlace.significantPlaces">
                                {{ significantPlace }}
                            </li>
                        </ul>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- start point - https://www.wikidata.org/wiki/Property:P1427
             destination point - https://www.wikidata.org/wiki/Property:P1444   -->
        <template v-if="selectedPlace.startPoints.length > 0 || selectedPlace.destinationPoints.length > 0">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Start-/Zielort</v-list-item-title>
                    <v-list-item-subtitle>
                        <div v-if="selectedPlace.startPoints.length > 0" class="my-2">
                            von
                            <ul>
                                <li v-for="startPoint in selectedPlace.startPoints">
                                    {{ startPoint }}
                                </li>
                            </ul>
                        </div>
                        <div v-if="selectedPlace.destinationPoints.length > 0" class="my-2">
                            nach
                            <ul>
                                <li v-for="destinationPoint in selectedPlace.destinationPoints">
                                    {{ destinationPoint }}
                                </li>
                            </ul>
                        </div>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- start time - https://www.wikidata.org/wiki/Property:P580
             end time - https://www.wikidata.org/wiki/Property:P582     -->
        <template v-if="selectedPlace.startDate.value || selectedPlace.endDate.value">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Laufzeit</v-list-item-title>
                    <v-list-item-subtitle>
                        <template v-if="selectedPlace.startDate.value">
                            von {{ selectedPlace.startDate.locale }}
                        </template>
                        <template v-if="selectedPlace.endDate.value">
                            bis {{ selectedPlace.endDate.locale }}
                        </template>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>
        <!-- point in time - https://www.wikidata.org/wiki/Property:P585 -->
        <template v-else-if="selectedPlace.pointInTime.value">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Zeitpunkt</v-list-item-title>
                    <v-list-item-subtitle>
                        {{ selectedPlace.pointInTime.locale }}
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- perpetrators - https://www.wikidata.org/wiki/Property:P8031 -->
        <template v-if="selectedPlace.perpetrators.length > 0">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Täter</v-list-item-title>
                    <v-list-item-subtitle>
                        <ul class="hyphens-auto white-space-normal" lang="de">
                            <li v-for="perpetrator in selectedPlace.perpetrators">
                                <a v-if="perpetrator.hasPersonData"
                                   @click.stop="$emit('showPerson', perpetrator.id)"
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

        <!-- coordinate location - https://www.wikidata.org/wiki/Property:P625 -->
        <v-list-item dense>
            <v-list-item-content>
                <v-list-item-title>Koordinaten</v-list-item-title>
                <v-list-item-subtitle>
                    <ul>
                        <li>
                            {{ selectedPlace.addresses.selected.latLng.lat }},
                            {{ selectedPlace.addresses.selected.latLng.lng }}
                        </li>
                    </ul>
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

        <!-- number of survivors - https://www.wikidata.org/wiki/Property:P1561 -->
        <template v-if="selectedPlace.numberOfSurvivors.length > 0">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Überlebende</v-list-item-title>
                    <v-list-item-subtitle>
                        <ul>
                            <li v-for="numberOfSurvivors in selectedPlace.numberOfSurvivors">
                                {{ numberOfSurvivors }}
                            </li>
                        </ul>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- number of deaths - https://www.wikidata.org/wiki/Property:P1120 -->
        <template v-if="selectedPlace.numberOfDeaths.length > 0">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Getötete</v-list-item-title>
                    <v-list-item-subtitle>
                        <ul>
                            <li v-for="numberOfDeaths in selectedPlace.numberOfDeaths">
                                {{ numberOfDeaths }}
                            </li>
                        </ul>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- number of arrests - https://www.wikidata.org/wiki/Property:P5582 -->
        <template v-if="selectedPlace.numberOfArrests.length > 0">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Festnahmen</v-list-item-title>
                    <v-list-item-subtitle>
                        <ul>
                            <li v-for="numberOfArrests in selectedPlace.numberOfArrests">
                                {{ numberOfArrests }}
                            </li>
                        </ul>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <prisoner-count
            v-if="selectedPlace.prisonerCounts.length > 0"
            :selectedPlace="selectedPlace"
        ></prisoner-count>

        <sources
            v-if="selectedPlace.sources.length > 0"
            :selectedPlace="selectedPlace"
        ></sources>
    </div>
</template>

<script>
import PrisonerCount from './PrisonerCount';
import Sources from './Sources';

export default {
    name: 'Event',
    components: {PrisonerCount, Sources},
    props: ['selectedPlace'],
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