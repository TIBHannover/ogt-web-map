<template>
    <v-list rounded>
        <v-subheader class="text-uppercase">Hauptmenü</v-subheader>
        <v-list-item
            link
            v-for="menuItem in menuItems"
            v-if="menuItem.show"
            :key="menuItem.title"
            :to="menuItem.path"
            @click.stop="$emit('setActiveMenu', menuItem.toMenu)"
        >
            <v-list-item-icon>
                <v-icon>{{ menuItem.icon }}</v-icon>
            </v-list-item-icon>
            <v-list-item-content>
                <v-list-item-title>{{ menuItem.title }}</v-list-item-title>
            </v-list-item-content>
        </v-list-item>
    </v-list>
</template>

<script>
export default {
    name: 'MainMenu',
    data() {
        return {
            menuItems: [
                {
                    title: 'Startseite',
                    icon: 'mdi-home',
                    path: '/welcome',
                    show: true,
                    toMenu: 'mainMenu',
                },
                {
                    title: 'Projekt',
                    icon: 'mdi-image-text',
                    path: '/project',
                    show: true,
                    toMenu: 'mainMenu',
                },
                {
                    title: 'Glossar',
                    icon: 'mdi-order-alphabetical-ascending',
                    path: '/glossary',
                    show: true,
                    toMenu: 'mainMenu',
                },
                {
                    title: 'Kartenansicht',
                    icon: 'mdi-map-search-outline',
                    path: '/map',
                    show: true,
                    toMenu: 'mainMenu',
                },
                {
                    title: 'Datenvisualisierung',
                    icon: 'mdi-chart-areaspline',
                    path: '/charts',
                    show: true,
                    toMenu: 'mainMenu',
                },
                {
                    title: 'Mitforschen',
                    icon: 'mdi-at',
                    path: '/collaboration',
                    show: true,
                    toMenu: 'mainMenu',
                },
                {
                    title: 'Sprache',
                    icon: 'mdi-translate',
                    show: ! this.$ogtGlobals.isProductionEnv,
                    toMenu: 'languageConfig',
                },
                {
                    title: 'Impressum & Datenschutz',
                    icon: 'mdi-information-outline',
                    show: true,
                    toMenu: 'legalTexts',
                },
            ],
        };
    },
};
</script>

<style scoped>

</style>
