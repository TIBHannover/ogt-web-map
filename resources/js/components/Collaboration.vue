<template>
    <v-container class="grey lighten-3" fluid>

        <!-- header -->
        <v-row>
            <v-col>
                <h1 class="text-h4 text-md-h3 text-center py-4">
                    Mitforschen
                </h1>
            </v-col>
        </v-row>

        <v-row>
            <!-- space reserved for opened navigation menu -->
            <v-col class="hidden-sm-and-down" style="min-width: 375px; max-width: 375px"></v-col>

            <!-- table of contents sidebar -->
            <v-col class="col-12 col-sm-2 order-sm-last" style="min-width: 300px">
                <section class="sticky-top pt-3">
                    <h6 class="text-h6">Inhaltsübersicht</h6>
                    <div class="toc-border-left">

                    </div>
                    <v-divider></v-divider>
                </section>
            </v-col>

            <!-- scrollable content -->
            <v-col class="hyphens-auto" lang="de">

            </v-col>
        </v-row>
    </v-container>
</template>

<script>
import Email from './helpers/Email';

export default {
    name: 'Collaboration',
    components: {Email},
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
