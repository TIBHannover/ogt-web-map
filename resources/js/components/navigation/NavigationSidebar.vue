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
            hide-overlay
            mobile-breakpoint="750"
            width="375px"
            v-model="isMenuDisplayed"
            :disable-route-watcher="$vuetify.breakpoint.smAndUp"
        >
            <!-- navigation header -->
            <v-list-item>
                <v-list-item-content>
                    <v-list-item-title class="font-family-special-elite font-weight-bold text-h6 text-sm-h5">
                        Gestapo.Terror.Orte
                    </v-list-item-title>
                    <v-list-item-subtitle>in Niedersachsen 1933–1945</v-list-item-subtitle>
                </v-list-item-content>
                <v-list-item-action>
                    <v-btn id="closeNavigationSidebar" @click.stop="isMenuDisplayed = false" icon>
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

import LanguageConfig from './LanguageConfig';
import LegalTexts from './LegalTexts';
import MainMenu from './MainMenu';

export default {
    name: 'NavigationSidebar',
    components: {LanguageConfig, LegalTexts, MainMenu},
    data() {
        return {
            activeMenu: 'mainMenu',
            isMenuDisplayed: false,
        };
    },
    watch: {
        isMenuDisplayed(newStatus, oldStatus) {
            this.$emit('isMenuDisplayed', newStatus);
        },
    },
};
</script>

<style scoped>
.font-family-special-elite {
    font-family: "Special Elite" !important;
}
</style>
