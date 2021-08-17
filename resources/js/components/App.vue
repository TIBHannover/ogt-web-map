<template>
    <v-app>
        <navigation-sidebar></navigation-sidebar>
        <v-main>
            <router-view></router-view>
        </v-main>
    </v-app>
</template>

<script>
import NavigationSidebar from './navigation/NavigationSidebar';

export default {
    name: 'App',
    components: {NavigationSidebar},
};
</script>

<style>
/* moved v-navigation-drawer over v-btn having z-index 4 */
.v-navigation-drawer--absolute {
    z-index: 5;
}
</style>
