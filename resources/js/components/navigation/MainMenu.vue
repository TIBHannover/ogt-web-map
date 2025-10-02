<template>
    <v-list rounded>
        <v-list-subheader class="text-uppercase">Hauptmenü</v-list-subheader>
        <v-list-item
            link
            v-for="menuItem in filteredMenuItems"
            :key="menuItem.title"
            :to="menuItem.path"
            @click.stop="emit('setActiveMenu', menuItem.toMenu)"
            rounded="pill"
            class="d-flex py-4 my-2 mx-2"
        >
            <v-list-item-title>
                <v-icon>{{ menuItem.icon }}</v-icon>
                <span class="ml-5">{{ menuItem.title }}</span>
            </v-list-item-title>
        </v-list-item>
    </v-list>
</template>

<script setup>
import { ref, computed, inject, provide } from "vue";
const emit = defineEmits(["setActiveMenu"]);
const ogtGlobals = inject("ogtGlobals");

const menuItems = ref([
    {
        title: "Startseite",
        icon: "mdi-home",
        path: "/starteite",
        show: true,
        toMenu: "mainMenu",
    },
    {
        title: "Projekt",
        icon: "mdi-image-text",
        path: "/projekt",
        show: true,
        toMenu: "mainMenu",
    },
    {
        title: "Glossar",
        icon: "mdi-order-alphabetical-ascending",
        path: "/glossar",
        show: true,
        toMenu: "mainMenu",
    },
    {
        title: "Kartenansicht",
        icon: "mdi-map-search-outline",
        path: "/map",
        show: true,
        toMenu: "mainMenu",
    },
    {
        title: "Datenvisualisierung",
        icon: "mdi-chart-areaspline",
        path: "/charts",
        show: true,
        toMenu: "mainMenu",
    },
    {
        title: "Erinnerungskalender",
        icon: "mdi-calendar",
        path: "/erinnerungskalender",
        show: true,
        toMenu: "mainMenu",
    },
    {
        title: "Touren",
        icon: "mdi-routes",
        path: "/touren",
        show: true,
        toMenu: "mainMenu",
    },
    {
        title: "Forschungsdaten",
        icon: "mdi-database",
        path: "/forschungsdaten",
        show: true,
        toMenu: "mainMenu",
    },
    {
        title: "Eintrag anlegen",
        icon: "mdi-plus",
        path: "/neuer-eintrag",
        show: true,
        toMenu: "mainMenu",
    },
    {
        title: "Mitforschen: Werkstätten",
        icon: "mdi-pencil-outline",
        path: "/werkstaette",
        show: true,
        toMenu: "mainMenu",
    },
    {
        title: "Materialien",
        icon: "mdi-note-multiple",
        path: "/materialien",
        show: true,
        toMenu: "mainMenu",
    },
    {
        title: "Literatur",
        icon: "mdi-book-multiple",
        path: "/literatur",
        show: true,
        toMenu: "mainMenu",
    },
    {
        title: "Sprache",
        icon: "mdi-translate",
        show: ogtGlobals.isTestingEnv,
        toMenu: "languageConfig",
    },
    {
        title: "Impressum & Datenschutz",
        icon: "mdi-information-outline",
        show: true,
        toMenu: "legalTexts",
    },
]);

const filteredMenuItems = computed(() => {
    return menuItems.value.filter((menuItem) => menuItem.show);
});
</script>

<style scoped></style>
