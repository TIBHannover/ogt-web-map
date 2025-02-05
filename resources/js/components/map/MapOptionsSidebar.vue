<template>
    <div>
        <!-- button to open map options sidebar -->
        <v-btn
            position="fixed"
            location="top right"
            rounded="circle"
            size="56px"
            class="mt-5 mr-5 z-index-4"
            color="white"
            v-show="!isMapOptionsDisplayed"
            @click.stop="toggleMapOptions"
        >
            <v-icon>mdi-tune</v-icon>
        </v-btn>

        <!-- map options sidebar -->
        <v-navigation-drawer
            v-model="isMapOptionsDisplayed"
            class="z-index-4"
            :scrim="false"
            mobile-breakpoint="sm"
            location="right"
            width="375"
            max-width="375"
        >
            <!-- map options header -->
            <v-list-item>
                <v-list-item-title
                    class="font-family-special-elite font-weight-bold text-h6 text-sm-h5"
                >
                    Kartenoptionen
                </v-list-item-title>
                <v-list-item-subtitle
                    >passe die Ansicht individuell an</v-list-item-subtitle
                >

                <v-list-item-action>
                    <v-btn @click.stop="toggleMapOptions" icon>
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-list-item-action>
            </v-list-item>

            <v-divider></v-divider>

            <!-- map options tabs -->
            <v-tabs
                bg-color="transparent"
                color="grey-darken-4"
                fixed-tabs
                stacked
                v-model="activeTab"
            >
                <v-tab
                    class="font-weight-bold"
                    :key="index"
                    v-for="(tab, index) in tabs"
                >
                    {{ tab.name }}
                    <v-icon>{{ tab.icon }}</v-icon>
                </v-tab>
            </v-tabs>

            <v-divider></v-divider>

            <v-tab class="bg-transparent" v-model="activeTab">
                <!-- layers options -->
                <!-- <v-tab-item v-if="this.$ogtGlobals.isTestingEnv">
                    <layer-options
                        :groupedPlaces="groupedPlaces"
                    ></layer-options>
                </v-tab-item> -->

                <!-- time period options -->
                <!-- <v-tab-item v-if="this.$ogtGlobals.isTestingEnv">
                    <date-range></date-range>
                </v-tab-item> -->

                <!-- place select options -->
                <v-tab>
                    <places-selection
                        :groupedPlaces="groupedPlaces"
                        :map="map"
                        :mapMarkerIconsPath="mapMarkerIconsPath"
                        @update-layer-groups="updateLayerGroups"
                    ></places-selection>
                </v-tab>
            </v-tab>
        </v-navigation-drawer>
    </div>
</template>

<script setup>
import { ref } from "vue";
import DateRange from "./options/DateRange";
import LayerOptions from "./options/LayerOptions";
import PlacesSelection from "./options/PlacesSelection";

const props = defineProps({
    groupedPlaces: {
        type: Object,
        required: true,
    },
    map: {
        type: Object,
        required: true,
    },
    mapMarkerIconsPath: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(["update-layer-groups"]);

const activeTab = ref(0);
const isMapOptionsDisplayed = ref(true);
const tabs = ref([
    {
        name: "Auswahl",
        icon: "mdi-view-list",
        show: true,
    },
]);

const toggleMapOptions = () => {
    isMapOptionsDisplayed.value = !isMapOptionsDisplayed.value;
};

const updateLayerGroups = (value) => {
    console.log("Mapoptionssudebar", value);
    emit("update-layer-groups", value);
};
</script>

<style scoped>
.font-family-special-elite {
    font-family: "Special Elite" !important;
}

/* reduced font size, so sidebar has space for four icon+text v-tabs */
.v-tab {
    font-size: 9px;
}

/* to move the map options sidebar to the foreground to cover the under construction footer (z-index 3) */
.z-index-4 {
    z-index: 4;
}
</style>
