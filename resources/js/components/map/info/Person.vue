<template>
    <div>
        <!-- given name - https://www.wikidata.org/wiki/Property:P735 -->
        <template v-if="selectedPerson.givenName">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Vorname</v-list-item-title>
                    <v-list-item-subtitle>
                        {{ selectedPerson.givenName }}
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- family name - https://www.wikidata.org/wiki/Property:P734 -->
        <template v-if="selectedPerson.familyName">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Nachname</v-list-item-title>
                    <v-list-item-subtitle>
                        {{ selectedPerson.familyName }}
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- sex or gender - https://www.wikidata.org/wiki/Property:P21 -->
        <template v-if="selectedPerson.gender">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Geschlecht</v-list-item-title>
                    <v-list-item-subtitle>
                        {{ selectedPerson.gender }}
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- date of birth - https://www.wikidata.org/wiki/Property:P569
             place of birth https://www.wikidata.org/wiki/Property:P19      -->
        <template v-if="selectedPerson.placeOfBirth || selectedPerson.dateOfBirth.value">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Geburtsdatum/-ort</v-list-item-title>
                    <v-list-item-subtitle>
                        <template v-if="selectedPerson.dateOfBirth.value">
                            {{ selectedPerson.dateOfBirth.locale }}
                        </template>
                        <template v-if="selectedPerson.placeOfBirth">
                            in {{ selectedPerson.placeOfBirth }}
                        </template>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- date of death - https://www.wikidata.org/wiki/Property:P570
             place of death - https://www.wikidata.org/wiki/Property:P20    -->
        <template v-if="selectedPerson.placeOfDeath || selectedPerson.dateOfDeath.value">
            <v-list-item dense>
                <v-list-item-content>
                    <v-list-item-title>Sterbedatum/-ort</v-list-item-title>
                    <v-list-item-subtitle>
                        <template v-if="selectedPerson.dateOfDeath.value">
                            {{ selectedPerson.dateOfDeath.locale }}
                        </template>
                        <template v-if="selectedPerson.placeOfDeath">
                            in {{ selectedPerson.placeOfDeath }}
                        </template>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <perpetrator
            v-if="selectedPerson.groupName == 'perpetrators'"
            :selectedPerson="selectedPerson"
            @switchLocation="$emit('switchLocation', $event)"
        ></perpetrator>
        <victim
            v-else-if="selectedPerson.groupName == 'victims'"
            :selectedPerson="selectedPerson"
            @switchLocation="$emit('switchLocation', $event)"
        ></victim>

        <significant-event
            v-if="selectedPerson.significantEvents.length > 0"
            :selectedPlace="selectedPerson"
            @switchLocation="$emit('switchLocation', $event)"
        ></significant-event>

        <sources
            v-if="selectedPerson.sources.length > 0"
            :selectedPlace="selectedPerson"
        ></sources>
    </div>
</template>

<script>
import Perpetrator from './Perpetrator';
import SignificantEvent from './SignificantEvent';
import Sources from './Sources';
import Victim from './Victim';

export default {
    name: 'Person',
    components: {Perpetrator, SignificantEvent, Sources, Victim},
    props: ['selectedPerson'],
};
</script>

<style scoped>

</style>