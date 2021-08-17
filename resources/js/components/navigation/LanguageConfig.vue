<template>
    <v-list rounded>
        <v-subheader class="text-uppercase">Sprache</v-subheader>
        <v-list-item-group
            active-class="selectedItemBorder"
            mandatory
            v-model="selectedLanguage"
        >
            <v-list-item
                v-for="(language, i) in languages"
                :key="i"
                @click.stop="$emit('setActiveMenu', 'mainMenu')"
            >
                <v-list-item-content class="ml-10">
                    <v-list-item-title>{{ language.title }}</v-list-item-title>
                </v-list-item-content>
            </v-list-item>
        </v-list-item-group>
        <v-divider></v-divider>
    </v-list>
</template>

<script>
export default {
    name: 'LanguageConfig',
    data() {
        return {
            selectedLanguage: /^de/.test(navigator.language) ? 0 : 1,
            languages: [
                {
                    title: 'Deutsch',
                },
                {
                    title: 'Englisch',
                },
            ],
        };
    },
};
</script>

<style scoped>
.selectedItemBorder {
    border: 2px dashed #c8e6c9;
}
</style>

