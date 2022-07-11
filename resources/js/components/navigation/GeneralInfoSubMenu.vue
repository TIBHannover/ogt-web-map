<template>
    <div>
        <v-list
            rounded
            v-show="activeMenu === 'generalInfoSubMenu' || activeMenu === 'imprintView' || activeMenu === 'dataProtectionView'"
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
            <v-list-item-group
                active-class="selectedItemBorder"
                mandatory
                v-model="selectedPolicy"
            >
                <v-list-item
                    v-for="(menuItem, i) in menuItems"
                    :key="i"
                    @click.stop="$emit('setActiveMenu', menuItem.toMenu)"
                >
                    <v-list-item-icon>
                        <v-icon>{{ menuItem.icon }}</v-icon>
                    </v-list-item-icon>
                    <v-list-item-content>
                        <v-list-item-title>{{ menuItem.title }}</v-list-item-title>
                    </v-list-item-content>
                </v-list-item>
            </v-list-item-group>
            <br>
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
            selectedPolicy: 0,
            menuItems: [
                {
                    title: 'Impressum',
                    icon: 'mdi-fingerprint',
                    toMenu: 'imprintView',
                },
                {
                    title: 'Datenschutz',
                    icon: 'mdi-database-lock-outline',
                    toMenu: 'dataProtectionView',
                },
            ],
        };
    },
};
</script>

<style scoped>
.selectedItemBorder {
    /* grey darken-4 */
    border: 2px dashed #212121;
}
</style>
