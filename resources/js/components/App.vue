<template>
    <v-app>
        <navigation-sidebar></navigation-sidebar>
        <v-main>
            <router-view></router-view>
        </v-main>
        <div></div>
        <v-footer fixed padless width="100%" class="grey">
            <v-card class="flex grey" flat>
                <v-card-text class="pa-0 text-subtitle-2 font-weight-bold text--darken-4 text-center">
                    DIESE WEBSITE IST NOCH IM AUFBAU /<br>THIS WEBSITE IS STILL UNDER CONSTRUCTION
                </v-card-text>
            </v-card>
        </v-footer>
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
