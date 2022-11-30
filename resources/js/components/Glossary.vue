<template>
    <v-container class="grey lighten-3">
        <v-row class="ml-15">
        <!--<v-row class="ml-15 ml-lg-auto">-->
        <!--<v-row>-->
            <v-col>
                <h1 class="text-h4 text-md-h3 py-4 font-family-courier font-weight-bold hyphens-auto" lang="de">
                    Glossar
                    <!-- und ein langer Titeltext zum Testen -->
                </h1>
            </v-col>
        </v-row>

        <v-row>
            <v-col>
                <v-btn
                    class="ma-2"
                    outlined
                    small
                    fab
                    :class="{'v-btn--active': selectedLetter == ''}"
                    @click.stop="selectedLetter = ''"
                >A-Z</v-btn>
                <v-btn
                    class="ma-2"
                    outlined
                    small
                    fab
                    :class="{'v-btn--active': selectedLetter == glossaryLetter}"
                    v-for="(glossaryItems, glossaryLetter) in this.glossaryData" :key="glossaryLetter"
                    @click.stop="selectedLetter = glossaryLetter"
                >{{ glossaryLetter }}</v-btn>
            </v-col>
        </v-row>

        <v-row>
            <v-col>
                <v-expansion-panels>
                    <template v-for="(glossaryItems, glossaryLetter) in this.glossaryData">
                        <template v-for="(glossaryItem, glossaryItemKey) in glossaryItems">
                            <v-expansion-panel class="hyphens-auto" lang="de"
                                v-if="selectedLetter == glossaryLetter || selectedLetter == ''"
                            >
                                <v-expansion-panel-header>
                                    {{ glossaryItem.title }}
                                </v-expansion-panel-header>
                                <v-expansion-panel-content>
                                    <p
                                       v-for="(paragraph, paragraphKey) in glossaryItem.paragraphs" :key="paragraphKey"
                                    >
                                        {{ paragraph }}
                                    </p>
                                    Quellen:
                                    <p v-for="(source, sourceKey) in glossaryItem.sources" :key="sourceKey">
                                        {{ source }}
                                    </p>
                                </v-expansion-panel-content>
                            </v-expansion-panel>
                        </template>
                    </template>
                </v-expansion-panels>
            </v-col>
        </v-row>


        <!--
             <v-row>

            <v-col class="hidden-sm-and-down" style="min-width: 375px; max-width: 375px"></v-col>
            <v-col class="hyphens-auto" lang="de">
                <v-expansion-panels focusable inset>
                    <v-expansion-panel class="grey lighten-3">
                        <v-expansion-panel class="grey lighten-3">
                            <v-expansion-panel-header class="text-h6 text-md-h5">
                                Schulische und außerschulische Jugendgruppen
                            </v-expansion-panel-header>
                            <v-expansion-panel-content>
                                <p>
                                    Zur Karte beitragen können Klassen der Stufen Sek I und Sek II im Rahmen des
                                    Politiks-, Ethiks-, Geschichts- oder Informatikunterrichts. Es bieten sich darüber
                                    hinaus auch Stufenübergreifende AG oder Projekttage an. Auch außerschulische
                                    Jugendgruppen steht eine Teilnahme offen.
                                </p>
                            </v-expansion-panel-content>
                        </v-expansion-panel>
                    </v-expansion-panel>
                </v-expansion-panels>

                <section id="coResearch" class="mt-3 mb-16">
                    <h1 class="text-h5 text-md-h4 mb-5">
                        Wie kann ich mitforschen?
                    </h1>
                    <v-expansion-panels focusable inset>
                        <v-expansion-panel class="grey lighten-3">
                        </v-expansion-panel>
                    </v-expansion-panels>
                </section>

                <p class="col-12 col-sm-6" v-for="(glossaryItems, glossaryLetter) in this.glossaryData" :key="glossaryLetter">
                {{ glossaryItems }}

                <v-card>
                    <v-card-title>
                        {{ chart.title }}
                    </v-card-title>
                    <v-card-subtitle class="hyphens-auto text-justify" lang="de">
                        {{ chart.paragraphs }}
                    </v-card-subtitle>
                </v-card>
                </p>

            </v-col>
        </v-row>
        -->
    </v-container>
</template>

<script>
import glossaryData from '../data/glossaryData';

console.log("glossaryData", glossaryData);

export default {
    name: 'Glossary',
    data() {
        return {
            glossaryData: glossaryData,
            selectedLetter: '',
        };
    },
};
</script>

<style scoped>
.hyphens-auto {
    hyphens: auto;
}

.font-family-courier {
    font-family: Courier !important;
}
</style>
