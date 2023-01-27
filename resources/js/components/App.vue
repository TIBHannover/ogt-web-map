<template>
    <v-app>
        <navigation-sidebar @isMenuDisplayed="setIsMenuDisplayed($event)"></navigation-sidebar>
        <v-main class="grey lighten-3">
            <router-view :isMenuDisplayed="isMenuDisplayed"></router-view>
        </v-main>
        <info-footer></info-footer>
    </v-app>
</template>

<script>
import InfoFooter from './InfoFooter';
import NavigationSidebar from './navigation/NavigationSidebar';

export default {
    name: 'App',
    components: {InfoFooter, NavigationSidebar},
    data() {
        return {
            isMenuDisplayed: false,
        };
    },
    methods: {
        setIsMenuDisplayed: function (isMenuDisplayed) {
            this.isMenuDisplayed = isMenuDisplayed;
        },
    },
};
</script>

<style>
</style>
