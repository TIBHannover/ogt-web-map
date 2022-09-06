<template>
    <v-container class="grey lighten-3" fluid>

        <!-- header -->
        <v-row>
            <v-col>
                <h1 class="text-h4 text-md-h3 text-center py-4">
                    Projekt
                </h1>
            </v-col>
        </v-row>

        <v-row>
            <!-- table of contents sidebar -->
            <v-col class="col-12 col-sm-2 order-sm-last" style="min-width: 215px">
                <section class="sticky-top pt-3">
                    <h6 class="text-h6">Inhaltsübersicht</h6>
                    <div class="toc-border-left">
                        <v-list-item>
                            <v-list-item-content>
                                <v-list-item-subtitle>
                                    <a class="hyphens-auto white-space-normal" href="#researchObjectives" lang="de">
                                        Forschungsfragen & Projektziele
                                    </a>
                                </v-list-item-subtitle>
                            </v-list-item-content>
                        </v-list-item>
                        <v-list-item>
                            <v-list-item-content>
                                <v-list-item-subtitle>
                                    <a class="hyphens-auto white-space-normal" href="#teamAndPartners" lang="de">
                                        Team & Partner:innen
                                    </a>
                                </v-list-item-subtitle>
                            </v-list-item-content>
                        </v-list-item>
                        <v-list-item>
                            <v-list-item-content>
                                <v-list-item-subtitle>
                                    <a class="hyphens-auto white-space-normal"
                                       href="#presentationsAndPublications"
                                       lang="de"
                                    >
                                        Projektpräsentationen & Publikationen
                                    </a>
                                </v-list-item-subtitle>
                            </v-list-item-content>
                        </v-list-item>
                        <v-list-item>
                            <v-list-item-content>
                                <v-list-item-subtitle>
                                    <a class="hyphens-auto white-space-normal" href="#pressReports" lang="de">
                                        Presseberichte
                                    </a>
                                </v-list-item-subtitle>
                            </v-list-item-content>
                        </v-list-item>
                    </div>
                    <v-divider></v-divider>

                </section>
            </v-col>

            <!-- scrollable content -->
            <v-col>
                <section id="researchObjectives" class="mb-16">
                    <h1 class="text-h5 text-md-h4 mb-5">
                        Forschungsfragen & Projektziele
                    </h1>
                </section>
                <section id="teamAndPartners" class="mb-16">
                    <h1 class="text-h5 text-md-h4 mb-5">
                        Team & Partner:innen
                    </h1>
                </section>
                <section id="presentationsAndPublications" class="mb-16">
                    <h1 class="text-h5 text-md-h4 mb-5">
                        Projektpräsentationen & Publikationen
                    </h1>
                </section>
                <section id="pressReports" class="mb-16">
                    <h1 class="text-h5 text-md-h4 mb-5">
                        Presseberichte
                    </h1>
                </section>
            </v-col>
        </v-row>

    </v-container>
</template>

<script>
export default {
    name: 'Project',
};
</script>

<style scoped>
.hyphens-auto {
    hyphens: auto;
}

.sticky-top {
    position: sticky;
    top: 0;
}

.toc-border-left {
    border-color: #0000001f;
    border-left-style: solid;
    border-left-width: thin;
}

/* to enable linebreaks for long labels */
.white-space-normal {
    white-space: normal;
}
</style>