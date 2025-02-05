<template>
    <div>
        <!-- place info sidebar -->
        <v-navigation-drawer
            :model-value="showPlaceInfoSidebar"
            @update:model-value="$emit('hidePlaceInfoSidebar')"
            absolute
            class="z-index-5"
            :scrim="false"
            mobile-breakpoint="sm"
            max-width="375"
            width="375"
        >
            <!-- place info header, link to wikidata item, close sidebar button -->
            <v-list-item>

                    <v-list-item-title
                        class="font-family-special-elite font-weight-bold hyphens-auto text-h6 text-sm-h5 white-space-normal"
                        lang="de"
                    >
                        {{ selectedPlace.label }}
                    </v-list-item-title>
                    <v-list-item-subtitle>{{
                        selectedPlace.layerName
                        .replace(/^Ereignisse$/, "Ereignis")
                        .replace(/^Täter\*innen$/, "Täter*in")
                    }}</v-list-item-subtitle>

                <v-list-item-action>
                    <v-btn @click="$emit('hidePlaceInfoSidebar')" icon>
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                    <a :href="`https://www.wikidata.org/wiki/${selectedPlace.id}`" target="_blank">
                        <v-img
                            :src="`${ogtGlobals?.proxyPath}/images/wikidata-logo.svg`"
                            max-width="45"
                        ></v-img>
                    </a>
                </v-list-item-action>
            </v-list-item>

            <v-divider></v-divider>

            <!-- location main image and image legend -->
            <div v-show="selectedPlace.mainImageUrl">
                <v-img :alt="selectedPlace.mainImageLegend"
                       contain
                       max-height="250"
                       :src="selectedPlace.mainImageUrl"
                       :title="selectedPlace.mainImageLegend"
                ></v-img>
                <v-divider></v-divider>
            </div>

            <!-- Wikidata item brief description -->
            <template v-if="selectedPlace.description">
                <v-list-item density="compact">

                        <v-list-item-title>Kurzbeschreibung</v-list-item-title>
                        <v-list-item-subtitle class="hyphens-auto text-justify white-space-normal" lang="de">
                            {{ selectedPlace.description }}
                        </v-list-item-subtitle>

                </v-list-item>
                <v-divider></v-divider>
            </template>

            <!-- detailed item description -->
            <template v-if="itemDescriptions && selectedPlace?.id && itemDescriptions[selectedPlace.id]">
                <v-list-item density="compact">

                        <v-expansion-panels v-model="itemDescriptionPanel" focusable>
                            <v-expansion-panel class="bg-grey-lighten-3" style="max-width: 99%">
                                <v-expansion-panel-title>
                                    <v-list-item-title>Langbeschreibung</v-list-item-title>
                                </v-expansion-panel-title>
                                <v-expansion-panel-text class="mt-4">
                                    <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                                        <template v-if="itemDescriptions && selectedPlace.id && itemDescriptions[selectedPlace.id]">
                                            <p v-for="(textPart, index) in itemDescriptions[selectedPlace.id]?.textParts" :key="index">
                                                {{ textPart }}
                                            </p>
                                        </template>
                                    </v-list-item-subtitle>
                                </v-expansion-panel-text>
                            </v-expansion-panel>
                        </v-expansion-panels>

                </v-list-item>
                <v-divider></v-divider>
            </template>

            <event
                v-if="selectedPlace.groupName == 'events'"
                :selectedPlace="selectedPlace"
                @showPerson="$emit('showPerson', $event)"
                @switchLocation="$emit('switchLocation', $event)"
                @undoZoomIntoPlace="$emit('undoZoomIntoPlace')"
                @zoomIntoPlace="$emit('zoomIntoPlace')"
            ></event>

            <gestapo-place
                v-else-if="gestapoPlaceGroups.includes(selectedPlace.groupName)"
                :selectedPlace="selectedPlace"
                @showPerson="$emit('showPerson', $event)"
                @switchLocation="$emit('switchLocation', $event)"
                @undoZoomIntoPlace="$emit('undoZoomIntoPlace')"
                @zoomIntoPlace="$emit('zoomIntoPlace')"
            ></gestapo-place>

            <memorial-place
                v-else-if="selectedPlace.groupName == 'memorials'"
                :selectedPlace="selectedPlace"
                @switchLocation="$emit('switchLocation', $event)"
                @undoZoomIntoPlace="$emit('undoZoomIntoPlace')"
                @zoomIntoPlace="$emit('zoomIntoPlace')"
            ></memorial-place>

            <person
                v-else-if="['perpetrators', 'victims'].includes(selectedPlace.groupName)"
                :selectedPerson="selectedPlace"
                @switchLocation="$emit('switchLocation', $event)"
            ></person>
        </v-navigation-drawer>
    </div>
</template>
<script setup>
    import { ref, computed, watch, inject } from 'vue';
    import Event from './info/Event';
    import GestapoPlace from './info/GestapoPlace';
    import MemorialPlace from './info/MemorialPlace';
    import Person from './info/Person';
    const ogtGlobals = inject('ogtGlobals');

    const emits = defineEmits(['hidePlaceInfoSidebar', 'showPerson', 'switchLocation', 'undoZoomIntoPlace', 'zoomIntoPlace']);

    const props = defineProps({
        selectedPlace: {
            type: Object,
            required: true,
            default: () => ({})
        },
        showPlaceInfoSidebar: {
            type: Boolean,
            required: true
        }
    });

    const itemDescriptionPanel = ref([]);
    const itemDescriptions = ref(window?.$ogtGlobals?.texts?.itemDescriptions || {});

    const gestapoPlaceGroups = [
        'extPolicePrisons',
        'fieldOffices',
        'laborEducationCamps',
        'prisons',
        'statePoliceHeadquarters',
        'statePoliceOffices',
    ];

    const drawerModel = computed({
        get: () => props.showPlaceInfoSidebar,
        set: (value) => emit('hidePlaceInfoSidebar', value)
    });

    const getComponent = computed(() => {
        const groupName = props.selectedPlace?.groupName;
        if (!groupName) return null;

        if (groupName === 'events') return Event;
        if (gestapoPlaceGroups.includes(groupName)) return GestapoPlace;
        if (groupName === 'memorials') return MemorialPlace;
        if (['perpetrators', 'victims'].includes(groupName)) return Person;
        return null;
    });

    const closePanels = () => {
        itemDescriptionPanel.value = [];
    };

    watch(() => props.selectedPlace?.id, (newId, oldId) => {
        if (newId !== oldId) {
        closePanels();
        }
    });

</script>

<style scoped>
.font-family-special-elite {
    font-family: "Special Elite" !important;
}

.hyphens-auto {
    hyphens: auto;
}

/* to enable linebreaks for long labels */
.white-space-normal {
    white-space: normal;
}

/* to move the place info sidebar to the foreground to cover the navigation menu button (z-index 4) */
.z-index-5 {
    z-index: 5;
}
</style>
