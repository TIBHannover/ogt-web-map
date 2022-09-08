<template>
    <v-list rounded>
        <v-list-item @click.stop="$emit('setActiveMenu', 'legalTexts')">
            <v-list-item-icon>
                <v-icon>mdi-backburger</v-icon>
            </v-list-item-icon>
            <v-list-item-content>
                <v-list-item-title>zurück zum vorherigen Menü</v-list-item-title>
            </v-list-item-content>
        </v-list-item>
        <v-divider></v-divider>
        <v-subheader class="text-uppercase">Impressum</v-subheader>
        <v-list-item>
            <v-list-item-content class="text-justify">
                Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor
                invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam
                et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est
                Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed
                diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam
                voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd
                gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.
            </v-list-item-content>
        </v-list-item>
    </v-list>
</template>

<script>
export default {
    name: 'Imprint',
};
</script>

<style scoped>

</style>
