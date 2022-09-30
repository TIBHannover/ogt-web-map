<template>
    <v-container class="mb-110">

        <!-- header -->
        <v-row class="ml-14 ml-sm-n3">
            <v-col>
                <h1 class="hyphens-auto py-4 text-center text-h4 text-md-h3" lang="de">
                    Datenvisualisierung
                </h1>
            </v-col>
        </v-row>

        <!-- charts overview -->
        <section v-if="showOverview">
            <v-row>
                <v-col cols=12 class="hyphens-auto text-justify" lang="de">
                    <h2 class="text-h6 text-md-h5 mb-3">
                        Visualisierung von Daten zu Organisationen, Ereignissen, Erinnerungsorten, Tätern und
                        Geschädigten
                    </h2>
                    <p>
                        Die Daten auf dieser Seite werden direkt auf Wikidata abgefragt und so ständig aktualisiert. Je
                        mehr
                        Daten vorhanden sind, desto vollständiger werden die Übersichten. Dieses Ziel kann nur durch die
                        Hilfe vieler erreicht werden. Informationen, wie mitgeforscht werden kann, finde sich hier:
                        <router-link to="/collaboration">
                            Mitforschen
                        </router-link>
                    </p>
                </v-col>
            </v-row>

            <!-- charts -->
            <v-row>
                <v-col class="col-12 col-sm-6" v-for="(chart, chartName) in this.charts" :key="chartName">
                    <v-card>
                        <v-img :src="$ogtGlobals.proxyPath + chart.imageUrl"></v-img>
                        <v-card-title>{{ chart.title }}</v-card-title>
                        <v-card-subtitle>{{ chart.subtitle }}</v-card-subtitle>
                        <v-card-actions>
                            <v-btn @click.stop="showVisualization(chartName)" outlined rounded>
                                Anzeigen
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-col>
            </v-row>
        </section>
    </v-container>
</template>

<script>
import chartsData from '../data/chartsData';

export default {
    name: 'Charts',
    data() {
        return {
            charts: chartsData,
            displayedChart: '',
            showOverview: true,
        };
    },
    methods: {
        /**
         * Show visualization and hide charts overview.
         *
         * @param chartName
         */
        showVisualization(chartName) {
            this.displayedChart = chartName;
            this.showOverview = false;
        },
    },
};
</script>

<style scoped>
/* space for footer to avoid that footer covers content */
.mb-110 {
    margin-bottom: 110px;
}

.hyphens-auto {
    hyphens: auto;
}
</style>
