<template>
    <div>
        <v-list
            rounded
            v-show="activeMenu === 'generalInfoSubMenu'"
        >
            <v-list-item @click.stop="$emit('setActiveMenu', 'mainMenu')">
                <v-list-item-icon>
                    <v-icon>mdi-backburger</v-icon>
                </v-list-item-icon>
                <v-list-item-content>
                    <v-list-item-title>zurück ins Hauptmenü</v-list-item-title>
                </v-list-item-content>
            </v-list-item>
            <v-divider></v-divider>
            <v-subheader class="text-uppercase">Allgemeine Infos</v-subheader>
            <v-list-item
                v-for="menuItem in menuItems"
                :key="menuItem.title"
                @click.stop="$emit('setActiveMenu', menuItem.toMenu)"
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

        <imprint
            v-show="activeMenu === 'imprintView'"
            @setActiveMenu="$emit('setActiveMenu', $event)"
        ></imprint>

        <data-protection
            v-show="activeMenu === 'dataProtectionView'"
            @setActiveMenu="$emit('setActiveMenu', $event)"
        ></data-protection>
    </div>
</template>

<script>
import DataProtection from './DataProtection';
import Imprint from './Imprint';

export default {
    name: 'GeneralInfoSubMenu',
    components: {DataProtection, Imprint},
    props: ['activeMenu'],
    data() {
        return {
            menuItems: [
                {
                    title: 'Impressum',
                    icon: 'mdi-information-outline',
                    toMenu: 'imprintView',
                },
                {
                    title: 'Datenschutz',
                    icon: 'mdi-information-outline',
                    toMenu: 'dataProtectionView',
                },
            ],
        };
    },
};
</script>

<style scoped>

</style>
