<template>
    <div>
        <!-- button to open map options sidebar -->
        <v-btn
            absolute
            class="mt-5"
            @click.stop="isMapOptionsDisplayed = !isMapOptionsDisplayed"
            color="white"
            fab
            right
            v-show="!isMapOptionsDisplayed"
        >
            <v-icon>mdi-tune</v-icon>
        </v-btn>

        <!-- map options sidebar -->
        <v-navigation-drawer
            absolute
            class="z-index-4"
            hide-overlay
            mobile-breakpoint="750"
            right
            width="375px"
            v-model="isMapOptionsDisplayed"
        >
            <!-- map options header -->
            <v-list-item>
                <v-list-item-content>
                    <v-list-item-title class="text-h6 text-sm-h5">Kartenoptionen</v-list-item-title>
                    <v-list-item-subtitle>passe die Ansicht individuell an</v-list-item-subtitle>
                </v-list-item-content>
                <v-list-item-action>
                    <v-btn
                        @click.stop="isMapOptionsDisplayed = !isMapOptionsDisplayed"
                        icon
                    >
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-list-item-action>
            </v-list-item>

            <v-divider></v-divider>

            <!-- map options tabs -->
            <v-tabs
                background-color="transparent"
                color="grey darken-4"
                fixed-tabs
                icons-and-text
                v-model="activeTab"
            >
                <v-tab
                    class="font-weight-bold"
                    :key="index"
                    v-for="(tab, index) in tabs"
                    v-if="tab.show"
                >
                    {{ tab.name }}
                    <v-icon>{{ tab.icon }}</v-icon>
                </v-tab>
            </v-tabs>

            <v-divider></v-divider>

            <v-tabs-items
                class="transparent"
                v-model="activeTab"
            >
                <!-- layers options -->
                <v-tab-item v-if="this.$ogtGlobals.isTestingEnv">
                    <layer-options
                        :groupedPlaces="groupedPlaces"
                    ></layer-options>
                </v-tab-item>

                <!-- time period options -->
                <v-tab-item v-if="this.$ogtGlobals.isTestingEnv">
                    <date-range></date-range>
                </v-tab-item>

                <!-- place select options -->
                <v-tab-item>
                    <places-selection
                        :groupedPlaces="groupedPlaces"
                        :map="map"
                        :mapMarkerIconsPath="mapMarkerIconsPath"
                    ></places-selection>
                </v-tab-item>
            </v-tabs-items>
        </v-navigation-drawer>
    </div>
</template>

<script>
import DateRange from './options/DateRange';
import LayerOptions from './options/LayerOptions';
import PlacesSelection from './options/PlacesSelection';

export default {
    name: 'MapOptionsSidebar',
    components: {DateRange, LayerOptions, PlacesSelection},
    props: ['groupedPlaces', 'map', 'mapMarkerIconsPath'],
    data() {
        return {
            activeTab: 0,
            isMapOptionsDisplayed: false,
            tabs: [
                {
                    name: 'Layers',
                    icon: 'mdi-layers',
                    show: this.$ogtGlobals.isTestingEnv,
                },
                {
                    name: 'Zeitraum',
                    icon: 'mdi-map-clock',
                    show: this.$ogtGlobals.isTestingEnv,
                },
                {
                    name: 'Auswahl',
                    icon: 'mdi-view-list',
                    show: true,
                },
            ],
        };
    },
};
</script>

<style scoped>
/* reduced font size, so sidebar has space for four icon+text v-tabs */
.v-tab {
    font-size: 9px;
}

/* to move the map options sidebar to the foreground to cover the under construction footer (z-index 3) */
.z-index-4 {
    z-index: 4;
}
</style>
