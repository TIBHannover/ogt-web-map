<template>
    <v-list rounded>
        <v-list-subheader class="text-uppercase">Sprache</v-list-subheader>
        <v-list-item
            active-class="selectedItemBorder"
            mandatory
            v-model="selectedLanguage"
        >
            <v-list-item
                v-for="(language, i) in languages"
                :key="i"
                @click.stop="$emit('setActiveMenu', 'mainMenu')"
            >
                <v-list-item class="ml-10">
                    <v-list-item-title>{{ language.title }}</v-list-item-title>
                </v-list-item>
            </v-list-item>
        </v-list-item>
        <v-divider></v-divider>
    </v-list>
</template>

<script>
export default {
    name: "LanguageConfig",
    data() {
        return {
            selectedLanguage: /^de/.test(navigator.language) ? 0 : 1,
            languages: [
                {
                    title: "Deutsch",
                },
                {
                    title: "Englisch",
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
