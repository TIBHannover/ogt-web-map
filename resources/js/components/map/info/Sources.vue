<template>
    <div>
        <!-- place is described by source (label and pages) - https://www.wikidata.org/wiki/Property:P1343 -->
        <!-- place is described by source by url (label and url) - https://www.wikidata.org/wiki/Property:P1343 -->
        <v-list-item density="compact">
            <v-list-item-title>Nachweise</v-list-item-title>
            <v-list-item-subtitle
                class="hyphens-auto white-space-normal"
                lang="de"
            >
                <ul v-if="selectedPlace.sources.length > 0">
                    <li v-for="source in selectedPlace.sources">
                        {{ source.label
                        }}<span v-if="source.pages"
                            >, S. {{ source.pages }}</span
                        >
                        <span v-if="source.inventoryNumber"
                            >, Inventarnummer:
                            {{ source.inventoryNumber }}</span
                        >
                    </li>
                </ul>
                <ul v-if="selectedPlace.urlSources.length > 0">
                    <li v-for="urlSource in selectedPlace.urlSources">
                        <a :href="urlSource.url" target="_blank" class="d-flex">
                            <span>
                                <v-icon start size="small">
                                    mdi-open-in-new
                                </v-icon>
                            </span>
                            <span>
                                {{ urlSource.url }}
                            </span>
                        </a>
                    </li>
                </ul>
            </v-list-item-subtitle>
        </v-list-item>
        <v-divider></v-divider>
    </div>
</template>

<script>
export default {
    name: "Sources",
    props: ["selectedPlace"],
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
