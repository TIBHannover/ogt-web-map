<template>
    <v-container
        class="grey lighten-3 justify-center"
        fill-height
        fluid
    >
        <!-- button to switch between alternative page layouts -->
        <v-btn
            absolute
            @click.stop="switchPageLayout()"
            color="white"
            right
            rounded
            top
        >
            Alternatives Layout "{{ layoutLabels[nextLayoutId] }}" anzeigen
        </v-btn>

        <!-- banner layout -->
        <v-row v-show="showBanner">
            <v-col>
                <router-link to="/map">
                    <v-img
                        class="mx-auto"
                        max-width="1150"
                        :src="this.$ogtGlobals.proxyPath + '/images/de/banner.jpg'">
                    </v-img>
                </router-link>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
export default {
    name: 'Welcome',
    data() {
        return {
            layoutLabels: ['Banner', 'Hintergrundbild'],
            nextLayoutId: 1,
            showBanner: true,
            showBackgroundImage: false,
        };
    },
    methods: {
        switchPageLayout() {
            if (this.nextLayoutId == 0) {
                this.showBanner = true;
                this.showBackgroundImage = false;
                this.nextLayoutId = 1;
            }
            else if (this.nextLayoutId == 1) {
                this.showBanner = false;
                this.showBackgroundImage = true;
                this.nextLayoutId = 0;
            }
        },
    },
};
</script>

<style scoped>

</style>
