<template>
    <v-list rounded>
        <v-list-subheader class="text-uppercase">Hauptmenü</v-list-subheader>
        <v-list-item
            link
            v-for="menuItem in filteredMenuItems"
            :key="menuItem.title"
            :to="menuItem.path"
            @click.stop="$emit('setActiveMenu', menuItem.toMenu)"
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

<script>
import { inject } from "vue";

export default {
    name: "MainMenu",
    data() {
        const ogtGlobals = inject("ogtGlobals");
        return {
            menuItems: [
                {
                    title: "Startseite",
                    icon: "mdi-home",
                    path: "/welcome",
                    show: true,
                    toMenu: "mainMenu",
                },
                {
                    title: "Projekt",
                    icon: "mdi-image-text",
                    path: "/project",
                    show: true,
                    toMenu: "mainMenu",
                },
                {
                    title: "Glossar",
                    icon: "mdi-order-alphabetical-ascending",
                    path: "/glossary",
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
                /* {
                    title: 'Eintrag anlegen',
                    icon: 'mdi-plus',
                    path: '/entry',
                    show: true,
                    toMenu: 'mainMenu',
                }, */
                {
                    title: "Literatur",
                    icon: "mdi-book-multiple",
                    path: "/bibliography",
                    show: true,
                    toMenu: "mainMenu",
                },
                {
                    title: "Mitforschen",
                    icon: "mdi-at",
                    path: "/collaboration",
                    show: true,
                    toMenu: "mainMenu",
                },
                {
                    title: "Werstätte",
                    icon: "mdi-pencil-outline",
                    path: "/workshops",
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
            ],
        };
    },
    computed: {
        filteredMenuItems() {
            return this.menuItems.filter((menuItem) => menuItem.show);
        },
    },
};
</script>

<style scoped></style>
