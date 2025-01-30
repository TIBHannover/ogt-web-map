<template>
    <div>
        <v-list rounded>
            <v-list-item @click.stop="$emit('setActiveMenu', 'mainMenu')" rounded="pill" class="d-flex py-4 my-2 mx-2">
                <v-list-item-title>
                    <v-icon>mdi-backburger</v-icon>
                    <span class="ml-5">zurück ins Hauptmenü</span>
                </v-list-item-title>
            </v-list-item>
            <v-divider></v-divider>
            <v-list-subheader class="text-uppercase">Rechtliche Informationen</v-list-subheader>
            <v-list v-model="selectedLegalText" active-class="selectedItemBorder" mandatory>
                <v-list-item v-for="(menuItem, index) in menuItems" :key="index" :value="menuItem.id" @click="selectLegalText(menuItem.id)" rounded="pill" class="mx-2 my-2">
                    <v-list-item-title>
                        <v-icon>{{ menuItem.icon }}</v-icon>
                        <span class="ml-5">{{ menuItem.label }}</span>
                    </v-list-item-title>
                </v-list-item>
            </v-list>
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
    methods: {
        selectLegalText(id) {
            this.selectedLegalText = id;
        },
    },
};
</script>

<style scoped>
.selectedItemBorder {
    /* grey darken-4 */
    border: 2px dashed #212121;
}
</style>
