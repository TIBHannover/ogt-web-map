<template>
    <div>
        <!-- addresses with associated coordinates and time periods, as well as option to zoom in/out of location. -->
        <v-list-item dense>
            <v-list-item-content>
                <v-list-item-title>Standort(e)</v-list-item-title>
                <v-list-item-subtitle>
                    <ul>
                        <li class="hyphens-auto white-space-normal" lang="de">
                            {{
                                selectedPlace.addresses.selected.label ?
                                    selectedPlace.addresses.selected.label : UNKNOWN_ADDRESS
                            }}
                        </li>
                        <ul>
                            <li v-if="selectedPlace.addresses.selected.startDate ||
                                          selectedPlace.addresses.selected.endDate"
                            >
                                <template v-if="selectedPlace.addresses.selected.startDate">
                                    von {{ selectedPlace.addresses.selected.startDate.locale }}
                                </template>
                                <template v-if="selectedPlace.addresses.selected.endDate">
                                    bis {{ selectedPlace.addresses.selected.endDate.locale }}
                                </template>
                            </li>
                            <li v-if="selectedPlace.addresses.selected.latLng && ! selectedPlace.addresses.selected.isUncertain">
                                {{ selectedPlace.addresses.selected.latLng.lat }},
                                {{ selectedPlace.addresses.selected.latLng.lng }}
                            </li>
                            <li v-else>
                                {{ UNKNOWN_COORDINATES }}
                            </li>
                        </ul>
                    </ul>
                    <div v-if="selectedPlace.addresses.additional.length > 0" class="mt-3">
                        Weitere Standorte
                        <ul v-for="additionalAddress in selectedPlace.addresses.additional" class="mb-3">
                            <li class="hyphens-auto white-space-normal" lang="de">
                                <a v-if="additionalAddress.latLng"
                                   @click.stop="$emit('switchLocation', {
                                           locationId: selectedPlace.id,
                                           latLng: additionalAddress.latLng,
                                       })"
                                   href="#"
                                >
                                    {{ additionalAddress.label ? additionalAddress.label : UNKNOWN_ADDRESS }}
                                </a>
                                <template v-else>
                                    {{ additionalAddress.label ? additionalAddress.label : UNKNOWN_ADDRESS }}
                                </template>
                            </li>
                            <ul>
                                <li v-if="additionalAddress.startDate || additionalAddress.endDate">
                                    <template v-if="additionalAddress.startDate">
                                        von {{ additionalAddress.startDate.locale }}
                                    </template>
                                    <template v-if="additionalAddress.endDate">
                                        bis {{ additionalAddress.endDate.locale }}
                                    </template>
                                </li>
                                <li v-if="additionalAddress.latLng && ! additionalAddress.isUncertain">
                                    {{ additionalAddress.latLng.lat }}, {{ additionalAddress.latLng.lng }}
                                </li>
                                <li v-else>
                                    {{ UNKNOWN_COORDINATES }}
                                </li>
                            </ul>
                        </ul>
                    </div>
                </v-list-item-subtitle>
            </v-list-item-content>
            <v-list-item-action class="flex-direction-row ml-0 my-0">
                <v-btn @click.stop="$emit('zoomIntoPlace')" icon>
                    <v-icon>mdi-magnify-plus</v-icon>
                </v-btn>
                <v-btn @click.stop="$emit('undoZoomIntoPlace')" icon>
                    <v-icon>mdi-undo-variant</v-icon>
                </v-btn>
            </v-list-item-action>
        </v-list-item>
        <v-divider></v-divider>
    </div>
</template>

<script>
export default {
    name: 'AddressInfo',
    props: ['selectedPlace'],
    created() {
        this.UNKNOWN_ADDRESS = 'Die Adresse ist unbekannt.';
        this.UNKNOWN_COORDINATES = 'Der genaue Standort ist unbekannt.';
    },
};
</script>

<style scoped>
/* move to top right */
.flex-direction-row {
    align-self: flex-start;
    flex-direction: row;
    padding-top: 7px;
}

.hyphens-auto {
    hyphens: auto;
}

/* to enable linebreaks for long labels */
.white-space-normal {
    white-space: normal;
}
</style>