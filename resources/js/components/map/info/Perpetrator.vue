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