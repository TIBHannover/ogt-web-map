<template>
    <div>
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
            <v-subheader class="text-uppercase">Rechtliche Informationen</v-subheader>
            <v-list-item-group v-model="selectedLegalText" active-class="selectedItemBorder" mandatory>
                <v-list-item v-for="(menuItem, index) in menuItems" :key="index" :value="menuItem.id">
                    <v-list-item-icon>
                        <v-icon>{{ menuItem.icon }}</v-icon>
                    </v-list-item-icon>
                    <v-list-item-content>
                        <v-list-item-title>{{ menuItem.label }}</v-list-item-title>
                    </v-list-item-content>
                </v-list-item>
            </v-list-item-group>
        </v-list>
        <v-divider></v-divider>

        <data-protection v-show="selectedLegalText == 'dataProtection'"></data-protection>

        <imprint v-show="selectedLegalText == 'imprint'"></imprint>
    </div>
</template>

<script>
import DataProtection from './DataProtection';
import Imprint from './Imprint';

export default {
    name: 'LegalTexts',
    components: {DataProtection, Imprint},
    data() {
        return {
            menuItems: [
                {
                    icon: 'mdi-database-lock-outline',
                    id: 'dataProtection',
                    label: 'Datenschutz',
                },
                {
                    icon: 'mdi-fingerprint',
                    id: 'imprint',
                    label: 'Impressum',
                },
            ],
            selectedLegalText: 'dataProtection',
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
