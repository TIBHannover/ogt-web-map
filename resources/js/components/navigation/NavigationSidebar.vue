<template>
    <div>
        <!-- button to open navigation sidebar -->
        <v-btn
            fixed
            class="mt-5"
            color="white"
            fab
            left
            v-show="!isMenuDisplayed"
            @click.stop="isMenuDisplayed = !isMenuDisplayed"
        >
            <v-icon>mdi-menu</v-icon>
        </v-btn>

        <!-- navigation sidebar -->
        <v-navigation-drawer
            fixed
            class="grey lighten-3"
            hide-overlay
            mobile-breakpoint="750"
            width="375px"
            v-model="isMenuDisplayed"
            :disable-route-watcher="$vuetify.breakpoint.smAndUp"
        >
            <!-- navigation header -->
            <v-list-item>
                <v-list-item-content>
                    <v-list-item-title class="text-h6 text-sm-h5">Gestapo.Terror.Orte</v-list-item-title>
                    <v-list-item-subtitle>in Niedersachsen 1933–1945</v-list-item-subtitle>
                </v-list-item-content>
                <v-list-item-action>
                    <v-btn
                        icon
                        @click.stop="isMenuDisplayed = !isMenuDisplayed"
                    >
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-list-item-action>
            </v-list-item>
            <v-divider></v-divider>

            <!-- navigation content -->
            <main-menu
                v-show="activeMenu === 'mainMenu'"
                @setActiveMenu="activeMenu = $event"
            ></main-menu>

            <charts-menu
                v-show="activeMenu === 'chartsMenu'"
                @setActiveMenu="activeMenu = $event"
            ></charts-menu>

            <language-config
                v-show="activeMenu === 'languageConfig'"
                @setActiveMenu="activeMenu = $event"
            ></language-config>

            <legal-texts
                v-show="activeMenu === 'legalTexts'"
                @setActiveMenu="activeMenu = $event"
            ></legal-texts>
        </v-navigation-drawer>
    </div>
</template>

<script>

import ChartsMenu from './ChartsMenu';
import LanguageConfig from './LanguageConfig';
import LegalTexts from './LegalTexts';
import MainMenu from './MainMenu';

export default {
    name: 'NavigationSidebar',
    components: {ChartsMenu, LanguageConfig, LegalTexts, MainMenu},
    data() {
        return {
            activeMenu: 'mainMenu',
            isMenuDisplayed: false,
        };
    },
};
</script>

<style scoped>

</style>
