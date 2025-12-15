<template>
    <div>
        <!-- button to open map options sidebar -->
        <v-btn
            position="fixed"
            location="top right"
            :rounded="$vuetify.display.smAndUp ? 'pill' : 'circle'"
            :size="$vuetify.display.smAndUp ? 'default' : '56px'"
            class="map-options-button mt-5 mr-5 z-index-4"
            color="white"
            v-show="!isMapOptionsOpen"
            @click.stop="toggleMapOptions"
        >
            <v-icon>mdi-tune</v-icon>
            <span v-if="$vuetify.display.smAndUp" class="ml-2 text-none"
                >Kartenoptionen</span
            >
        </v-btn>

        <!-- map options sidebar -->
        <v-navigation-drawer
            v-model="isMapOptionsOpen"
            class="z-index-4 menuOptionsidebar"
            :scrim="false"
            :style="props.tutorialMode ? { 'pointer-events': 'none' } : {}"
            :location="isMobile ? 'bottom' : 'right'"
            :min-height="isMobile ? 'auto' : ''"
            :width="isMobile ? '100%' : '375'"
            :max-width="isMobile ? '100%' : '375'"
            :class="{
                'mobile-drawer': isMobile,
                'v-navigation-drawer--closed': !isMapOptionsOpen && isMobile,
            }"
        >
            <!-- map options header -->
            <v-list-item class="position-relative py-3">
                <v-list-item-title
                    class="font-family-special-elite font-weight-bold text-h6 text-sm-h5"
                >
                    Kartenoptionen
                </v-list-item-title>
                <v-list-item-subtitle
                    >passe die Ansicht individuell an</v-list-item-subtitle
                >
                <v-list-item-action
                    class="position-absolute right-0 top-0 mt-3 mr-5"
                >
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
                stacked
                hide-slider
                v-model="activeTab"
            >
                <v-tab
                    class="font-weight-bold mx-0 w-100"
                    :key="index"
                    v-for="(tab, index) in tabs"
                >
                    {{ tab.name }}
                    <v-icon>{{ tab.icon }}</v-icon>
                </v-tab>
            </v-tabs>

            <v-divider></v-divider>

            <!-- places selection options -->
            <places-selection
                v-model:groupedPlacesShownOnMap="proxyGroupedPlacesShownOnMap"
                :groupedPlaces="groupedPlaces"
                :map="map"
                :mapMarkerIconsPath="mapMarkerIconsPath"
            ></places-selection>
        </v-navigation-drawer>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from "vue";
import PlacesSelection from "./options/PlacesSelection";
import NavigationHelpers from "../helpers/NavigationHelpers";

const props = defineProps({
    groupedPlaces: {
        type: Object,
        required: true,
    },
    groupedPlacesShownOnMap: {
        type: Array,
        default: () => [],
    },
    map: {
        type: Object,
        required: true,
    },
    mapMarkerIconsPath: {
        type: String,
        required: true,
    },
    tutorialMode: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:groupedPlacesShownOnMap"]);

const proxyGroupedPlacesShownOnMap = computed({
    get: () => props.groupedPlacesShownOnMap,
    set: (value) => emit("update:groupedPlacesShownOnMap", value),
});

const { isMapOptionsOpen, toggleMapOptions, closeMapOptions } =
    NavigationHelpers();

const activeTab = ref(0);
const tabs = ref([
    {
        name: "Auswahl",
        icon: "mdi-view-list",
        show: true,
    },
]);

const windowWidth = ref(window.innerWidth);
const MOBILE_BREAKPOINT = 600; // Standard mobile breakpoint

// Computed property to determine if the device is mobile
const isMobile = computed(() => {
    return windowWidth.value < MOBILE_BREAKPOINT;
});

// Update window width on resize
const handleResize = () => {
    windowWidth.value = window.innerWidth;
};

onMounted(() => {
    window.addEventListener("resize", handleResize);
    // Zustand beim Start explizit zurücksetzen
    if (!isMapOptionsOpen.value) {
        closeMapOptions();
    }
});

onUnmounted(() => {
    window.removeEventListener("resize", handleResize);
});
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
.mobile-drawer {
    border-top-left-radius: 16px;
    border-top-right-radius: 16px;
    overflow-y: auto;
    box-shadow: 0px -2px 10px rgba(0, 0, 0, 0.1);
}
.v-navigation-drawer--closed {
    transform: translateY(100%) !important;
    visibility: hidden !important;
}
@media (max-width: 600px) {
    .menuOptionsidebar {
        max-height: 40vh !important;
    }
}
</style>
