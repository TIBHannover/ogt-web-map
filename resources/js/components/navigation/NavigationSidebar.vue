<template>
    <div>
        <!-- button to open navigation sidebar -->
        <v-btn
            position="fixed"
            location="top left"
            rounded="circle"
            size="56px"
            class="mt-5 ml-5 z-index-4"
            color="white"
            v-show="!isMenuDisplayed"
            @click.stop="toggleMenu"
        >
            <v-icon>mdi-menu</v-icon>
        </v-btn>

        <!-- navigation sidebar -->
        <v-navigation-drawer
            v-model="isMenuDisplayed"
            :scrim="false"
            mobile-breakpoint="sm"
            width="375"
            max-width="375"
            :disable-route-watcher="display.smAndDown"
            :class="{ isOpen: isMenuDisplayed }"
            class="mainMenu"
        >
            <!-- navigation header -->
            <v-list-item class="w-100 position-relative py-5">
                <v-list-item-title
                    class="font-family-special-elite font-weight-bold text-h6 text-sm-h5"
                >
                    Gestapo.Terror.Orte
                </v-list-item-title>
                <v-list-item-subtitle
                    >in Niedersachsen 1933–1945</v-list-item-subtitle
                >
                <v-list-item-action
                    class="position-absolute right-0 top-0 mr-5 mt-5"
                >
                    <v-btn
                        id="closeNavigationSidebar"
                        @click.stop="closeMenu"
                        icon
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
<script setup>
import { ref, watch } from "vue";
import { useDisplay } from "vuetify";
import LanguageConfig from "./LanguageConfig";
import LegalTexts from "./LegalTexts";
import MainMenu from "./MainMenu";
const display = ref(useDisplay());

const activeMenu = ref("mainMenu");
const isMenuDisplayed = ref(false);

function toggleMenu() {
    isMenuDisplayed.value = !isMenuDisplayed.value;
}
function closeMenu() {
    isMenuDisplayed.value = false;
}
</script>
<style scoped>
.font-family-special-elite {
    font-family: "Special Elite" !important;
}
.z-index-4 {
    z-index: 4;
}
</style>
