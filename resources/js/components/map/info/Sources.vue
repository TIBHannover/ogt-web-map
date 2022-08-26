<template>
    <div>
        <!-- place is described by source (label and pages) - https://www.wikidata.org/wiki/Property:P1343 -->
        <v-list-item dense>
            <v-list-item-content>
                <v-list-item-title>Nachweise</v-list-item-title>
                <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                    <ul>
                        <li v-for="source in selectedPlace.sources">
                            {{ source.label }}<span v-if="source.pages">, S. {{ source.pages }}</span>
                        </li>
                    </ul>
                </v-list-item-subtitle>
            </v-list-item-content>
        </v-list-item>
        <v-divider></v-divider>
    </div>
</template>

<script>
export default {
    name: 'Sources',
    props: ['selectedPlace'],
};
</script>

<style scoped>
.hyphens-auto {
    hyphens: auto;
}

/* to enable linebreaks for long labels */
.white-space-normal {
    white-space: normal;
}
</style>