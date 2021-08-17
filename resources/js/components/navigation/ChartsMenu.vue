<template>
    <v-list rounded>
        <v-list-item @click.stop="$emit('setActiveMenu', 'mainMenu')">
            <v-list-item-icon>
                <v-icon>mdi-backburger</v-icon>
            </v-list-item-icon>
            <v-list-item-content>
                <v-list-item-title>zurück ins Hauptmenü</v-list-item-title>
            </v-list-item-content>
        </v-list-item>
        <v-divider></v-divider>
        <v-subheader class="text-uppercase">Datenvisualisierung</v-subheader>
        <v-list-item
            exact
            link
            v-for="menuItem in menuItems"
            :key="menuItem.title"
            :to="menuItem.path"
        >
            <v-list-item-icon>
                <v-icon>{{ menuItem.icon }}</v-icon>
            </v-list-item-icon>
            <v-list-item-content>
                <v-list-item-title>{{ menuItem.title }}</v-list-item-title>
            </v-list-item-content>
        </v-list-item>
        <v-divider></v-divider>
    </v-list>
</template>

<script>
export default {
    name: 'ChartsMenu',
    data() {
        return {
            menuItems: [
                {
                    title: 'Überblick',
                    icon: 'mdi-chart-areaspline',
                    path: '/charts',
                },
                {
                    title: 'Ortsverzeichnis',
                    icon: 'mdi-database-search-outline',
                    path: '/charts/database',
                },
                {
                    title: 'Fotos',
                    icon: 'mdi-image-multiple',
                    path: '/charts/photos',
                },
            ],
        };
    },
};
</script>

<style scoped>

</style>
