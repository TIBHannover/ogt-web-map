<template>
    <v-container>
        <!-- header -->
        <v-row class="ml-14 ml-sm-n3">
            <v-col>
                <h1 class="hyphens-auto py-4 text-center text-h4 text-md-h3" lang="de">
                    Datenvisualisierung
                </h1>
            </v-col>
        </v-row>

    </v-container>
</template>

<script>
import chartsData from '../data/chartsData';

export default {
    name: 'Charts',
    data() {
        return {
            charts: chartsData,
        };
    },
};
</script>

<style scoped>
.hyphens-auto {
    hyphens: auto;
}
</style>
