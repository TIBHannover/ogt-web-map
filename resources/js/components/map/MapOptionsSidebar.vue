<template>
    <div>
        <!-- button to open map options sidebar, #FFFBE6 Corn Silk background-color -->
        <v-btn
            absolute
            class="mt-5"
            @click.stop="isMapOptionsDisplayed = !isMapOptionsDisplayed"
            color="#FFFBE6"
            fab
            right
            v-show="!isMapOptionsDisplayed"
        >
            <v-icon>mdi-tune</v-icon>
        </v-btn>

        <!-- map options sidebar -->
        <v-navigation-drawer
            absolute
            color="bgCornSilk"
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
                color="green lighten-1"
                fixed-tabs
                icons-and-text
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

            <v-tabs-items
                class="transparent"
                v-model="activeTab"
            >
                <!-- layers options -->
                <v-tab-item>
                    <v-subheader class="text-uppercase">#Layers options tab</v-subheader>
                </v-tab-item>

                <!-- place groups options -->
                <v-tab-item>
                    <v-subheader class="text-uppercase">#Categories options tab</v-subheader>
                </v-tab-item>

                <!-- time period options -->
                <v-tab-item>
                    <date-range></date-range>
                </v-tab-item>

                <!-- place select options -->
                <v-tab-item>
                    <places-selection
                        :groupedPlaces="groupedPlaces"
                        :map="map">
                    </places-selection>
                </v-tab-item>
            </v-tabs-items>
        </v-navigation-drawer>
    </div>
</template>

<script>
import DateRange from './options/DateRange';
import PlacesSelection from './options/PlacesSelection';

export default {
    name: 'MapOptionsSidebar',
    components: {DateRange, PlacesSelection},
    props: ['groupedPlaces', 'map'],
    data() {
        return {
            activeTab: 0,
            isMapOptionsDisplayed: false,
            tabs: [
                {
                    name: 'Layers',
                    icon: 'mdi-layers',
                },
                {
                    name: 'Kategorien',
                    icon: 'mdi-select-group',
                },
                {
                    name: 'Zeitraum',
                    icon: 'mdi-map-clock',
                },
                {
                    name: 'Liste',
                    icon: 'mdi-view-list',
                },
            ],
        };
    },
};
</script>

<style scoped>
.bgCornSilk {
    background-color: #FFFBE6;
}

/* reduced font size, so sidebar has space for four icon+text v-tabs */
.v-tab {
    font-size: 9px;
}
</style>
