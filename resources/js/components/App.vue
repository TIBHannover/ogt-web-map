<template>
    <v-app>
        <navigation-sidebar></navigation-sidebar>
        <v-main class="grey lighten-3">
            <router-view></router-view>
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
};
</script>

<style>
</style>
