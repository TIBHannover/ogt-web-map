<template>
    <v-container id="container" class="mb-110" :class="{'pl-375': isMenuDisplayed}">
        <div :class="{'pl-4': freeClientWidth == 0 && isMenuDisplayed}">
            <!-- header -->
            <!-- <v-row :class="{'ml-15': freeClientWidth < 75 && ! isMenuDisplayed}"> -->
            <v-row :style="{'padding-left': (freeClientWidth < 75 && ! isMenuDisplayed) ? ((1*(75-freeClientWidth)) + 'px') : ''}">
                <v-col>
                    <h1 class="font-family-special-elite font-weight-bold hyphens-auto py-4 text-h4 text-md-h3" lang="de">
                        Datenvisualisierung
                    </h1>
                </v-col>
            </v-row>

            <!-- charts overview -->
            <section v-if="showOverview">
                <v-row>
                    <v-col class="hyphens-auto text-justify" cols=12 lang="de">
                        <h2 class="mb-3 text-h6 text-md-h5">
                            Visualisierung von Daten zu Organisationen, Ereignissen, Erinnerungsorten, Tätern und
                            Geschädigten
                        </h2>
                        <p>
                            Die Daten auf dieser Seite werden direkt auf Wikidata abgefragt und so ständig aktualisiert. Je
                            mehr Daten vorhanden sind, desto vollständiger werden die Übersichten. Dieses Ziel kann nur
                            durch die Hilfe vieler erreicht werden. Informationen, wie mitgeforscht werden kann, finde sich
                            hier:
                            <router-link to="/collaboration">Mitforschen</router-link>
                        </p>
                    </v-col>
                </v-row>

                <!-- charts -->
                <v-row>
                    <v-col class="col-12 col-sm-6" v-for="(chart, chartName) in this.charts" :key="chartName">
                        <v-card>
                            <v-img :src="$ogtGlobals.proxyPath + chart.imageUrl"></v-img>
                            <v-card-title>
                                {{ chart.title }}
                            </v-card-title>
                            <v-card-subtitle class="hyphens-auto text-justify" lang="de">
                                {{ chart.subtitle }}
                            </v-card-subtitle>
                            <v-card-actions>
                                <v-btn @click.stop="showChart(chart)" outlined rounded>
                                    Anzeigen
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-col>
                </v-row>
            </section>

            <!-- chart visualization -->
            <section v-if="!showOverview">
                <v-row justify="space-between">
                    <v-col sm="5">
                        <v-btn @click.stop="showOverview = !showOverview" outlined rounded>
                            <v-icon left>
                                mdi-arrow-left
                            </v-icon>
                            Zurück zur Übersicht
                        </v-btn>
                    </v-col>
                    <v-col class="maxWidth100" sm="5">
                        <v-btn class="float-sm-right" :href="selectedChart.queryUrl" outlined rounded target="_blank">
                            Im Wikidata Query Service anzeigen
                            <v-icon right>
                                mdi-open-in-new
                            </v-icon>
                        </v-btn>
                    </v-col>
                </v-row>

                <h1 class="text-h5 text-md-h4 my-5">
                    {{ selectedChart.title }}
                </h1>
                <p class="hyphens-auto text-justify" lang="de">
                    {{ selectedChart.subtitle }}
                </p>

                <iframe referrerpolicy="origin"
                        sandbox="allow-scripts allow-same-origin allow-popups"
                        :src="selectedChart.queryUrl"
                ></iframe>
            </section>
        </div>
    </v-container>
</template>

<script>
import chartsData from '../data/chartsData';

export default {
    name: 'Charts',
    props: ['isMenuDisplayed'],
    data() {
        return {
            charts: chartsData,
            freeClientWidth: document.documentElement.clientWidth,
            selectedChart: null,
            showOverview: true,
        };
    },
    created() {
        window.addEventListener('resize', this.setFreeClientWidth);
    },
    destroyed() {
        window.removeEventListener('resize', this.setFreeClientWidth);
    },
    mounted() {
        this.setFreeClientWidth();
    },
    methods: {
        /**
         * Determine width of free space between content and client border.
         *
         * @param event
         */
        setFreeClientWidth(event) {
            const container = document.getElementById('container');

            this.freeClientWidth = (document.documentElement.clientWidth - container.clientWidth) / 2;
        },
        /**
         * Show selected chart and hide charts overview.
         *
         * @param {Object} chart
         */
        showChart(chart) {
            this.selectedChart = chart;
            this.showOverview = false;
        },
    },
};
</script>

<style scoped>
.font-family-special-elite {
    font-family: "Special Elite" !important;
}

.hyphens-auto {
    hyphens: auto;
}

iframe {
    border: none;
    height: 80vh;
    width: 100%;
}

/* workaround to avoid the button not being fully visible on small devices */
.maxWidth100 {
    max-width: 100%;
}

/* space for footer to avoid that footer covers content */
.mb-110 {
    margin-bottom: 110px;
}

.pl-375 {
    padding-left: 375px;
}
</style>
