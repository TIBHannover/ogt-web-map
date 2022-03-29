<template>
    <v-list rounded>
        <v-subheader class="text-uppercase">Hauptmenü</v-subheader>
        <v-list-item
            link
            v-for="menuItem in menuItems"
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
                    toMenu: 'mainMenu',
                },
                {
                    title: 'Projekt',
                    icon: 'mdi-image-text',
                    path: '/project',
                    toMenu: 'mainMenu',
                },
                {
                    title: 'Kartenansicht',
                    icon: 'mdi-map-search-outline',
                    path: '/map',
                    toMenu: 'mainMenu',
                },
                {
                    title: 'Datenvisualisierung',
                    icon: 'mdi-chart-areaspline',
                    path: '/charts',
                    toMenu: 'chartsMenu',
                },
                {
                    title: 'Mitmachen',
                    icon: 'mdi-at',
                    toMenu: 'contributeView',
                },
                {
                    title: 'Sprache',
                    icon: 'mdi-translate',
                    toMenu: 'languageConfig',
                },
                {
                    title: 'Allgemeine Informationen',
                    icon: 'mdi-information-outline',
                    toMenu: 'generalInfoSubMenu',
                },
            ],
        };
    },
};
</script>

<style scoped>

</style>
