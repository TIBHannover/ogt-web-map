<template>
    <div>
        <!-- official website - https://www.wikidata.org/wiki/Property:P856 -->
        <template v-if="selectedPlace.website">
            <v-list-item density="compact">
                <v-list-item-title>Offizielle Website</v-list-item-title>
                <v-list-item-subtitle class="white-space-normal">
                    <a :href="selectedPlace.website" target="_blank">
                        {{ selectedPlace.website }}
                    </a>
                </v-list-item-subtitle>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- operators - https://www.wikidata.org/wiki/Property:P137 -->
        <template v-if="selectedPlace.operators.length > 0">
            <v-list-item density="compact">
                <v-list-item-title>Träger</v-list-item-title>
                <v-list-item-subtitle>
                    <ul class="hyphens-auto white-space-normal" lang="de">
                        <li v-for="operator in selectedPlace.operators">
                            {{ operator }}
                        </li>
                    </ul>
                </v-list-item-subtitle>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- has uses - https://www.wikidata.org/wiki/Property:P366 -->
        <template v-if="selectedPlace.hasUses.length > 0">
            <v-list-item density="compact">
                <v-list-item-title>Leistungen und Angebote</v-list-item-title>
                <v-list-item-subtitle>
                    <ul class="hyphens-auto white-space-normal" lang="de">
                        <li v-for="hasUse in selectedPlace.hasUses">
                            {{ hasUse }}
                        </li>
                    </ul>
                </v-list-item-subtitle>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- first activity date (from firstActivity with P700=Q1790956 qualifier) -->
        <template
            v-if="
                selectedPlace.firstActivity && selectedPlace.firstActivity.value
            "
        >
            <v-list-item density="compact">
                <v-list-item-title>Erste Aktivität</v-list-item-title>
                <v-list-item-subtitle>
                    {{ selectedPlace.firstActivity.locale }}
                </v-list-item-subtitle>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- opening date (from openingDate with P700=Q483988 qualifier) -->
        <template
            v-if="selectedPlace.openingDate && selectedPlace.openingDate.value"
        >
            <v-list-item density="compact">
                <v-list-item-title>Eröffnung</v-list-item-title>
                <v-list-item-subtitle>
                    {{ selectedPlace.openingDate.locale }}
                </v-list-item-subtitle>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- fallback to inceptionDate if no openingDate is available -->
        <template
            v-if="
                !(
                    selectedPlace.openingDate && selectedPlace.openingDate.value
                ) &&
                selectedPlace.inceptionDate &&
                selectedPlace.inceptionDate.value
            "
        >
            <v-list-item density="compact">
                <v-list-item-title>Eröffnung</v-list-item-title>
                <v-list-item-subtitle>
                    {{ selectedPlace.inceptionDate.locale }}
                </v-list-item-subtitle>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <address-info
            :selectedPlace="selectedPlace"
            @switchLocation="$emit('switchLocation', $event)"
            @undoZoomIntoPlace="$emit('undoZoomIntoPlace')"
            @zoomIntoPlace="$emit('zoomIntoPlace')"
        ></address-info>

        <!-- commemorates - https://www.wikidata.org/wiki/Property:P547 -->
        <v-list-item density="compact">
            <v-list-item-title>Erinnert an</v-list-item-title>
            <v-list-item-subtitle>
                <ul>
                    <li v-for="commemorate in selectedPlace.commemorates">
                        <a
                            v-if="commemorate.hasLocationMarker"
                            @click.stop="
                                $emit('switchLocation', {
                                    locationId: commemorate.id,
                                })
                            "
                            href="#"
                        >
                            {{ commemorate.label }}
                        </a>
                        <template v-else>
                            {{ commemorate.label }}
                        </template>
                    </li>
                </ul>
            </v-list-item-subtitle>
        </v-list-item>
        <v-divider></v-divider>
    </div>
</template>

<script>
import AddressInfo from "./AddressInfo";

export default {
    name: "MemorialPlace",
    components: { AddressInfo },
    props: ["selectedPlace"],
    data() {
        return {
            debug: true, // Set to false in production
        };
    },
    mounted() {
        console.log("MemorialPlace mounted", {
            place: this.selectedPlace,
            firstActivity: this.selectedPlace.firstActivity,
            openingDate: this.selectedPlace.openingDate,
            inceptionDate: this.selectedPlace.inceptionDate,
        });
    },
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
