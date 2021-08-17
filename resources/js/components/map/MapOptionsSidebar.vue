<template>
    <div>
        <!-- button to open map options sidebar -->
        <v-btn
            absolute
            class="mt-5"
            color="primary"
            fab
            right
            v-show="!isMapOptionsDisplayed"
            @click.stop="isMapOptionsDisplayed = !isMapOptionsDisplayed"
        >
            <v-icon>mdi-tune</v-icon>
        </v-btn>

        <!-- map options sidebar -->
        <v-navigation-drawer
            absolute
            class="blue accent-1"
            hide-overlay
            mobile-breakpoint="750"
            right
            width="375px"
            v-model="isMapOptionsDisplayed"
        >
            <!-- map options header -->
            <v-list-item>
                <v-list-item-content>
                    <v-list-item-title class="text-h5">Karten Einstellungen</v-list-item-title>
                </v-list-item-content>
                <v-list-item-action>
                    <v-btn
                        icon
                        @click.stop="isMapOptionsDisplayed = !isMapOptionsDisplayed"
                    >
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-list-item-action>
            </v-list-item>
            <v-divider></v-divider>
        </v-navigation-drawer>
    </div>
</template>

<script>
export default {
    name: 'MapOptionsSidebar',
    data() {
        return {
            isMapOptionsDisplayed: false,
        };
    },
};
</script>

<style scoped>

</style>
