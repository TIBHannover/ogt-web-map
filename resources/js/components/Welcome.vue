<template>
    <v-container
        class="grey lighten-3 justify-center"
        :class="{ background: showBackgroundImage, 'xs-bg-x-shifted': $vuetify.breakpoint.xs && (selectedLayoutId == 2) }"
        fill-height
        fluid
        :style="{ backgroundImage: showBackgroundImage ? 'url(' + backgroundImageUrl + ')' : 'none' }"
    >
        <!-- button to switch between alternative page layouts -->
        <v-btn
            absolute
            class="noneTextTransform"
            @click.stop="switchPageLayout()"
            color="white"
            right
            rounded
            top
        >
            Alternatives Layout "{{ layoutLabels[(selectedLayoutId + 1) % layoutLabels.length] }}" anzeigen
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

        <!-- for layout with buttons -->
        <div class="mt-300" v-show="showMenuButtons">
            <template v-for="menuButton in menuButtons">
                <router-link :to="menuButton.routeTo">
                    <v-btn
                        class="mx-10 my-3"
                        color="white"
                        outlined
                        rounded
                        x-large
                    >
                        {{ menuButton.label }}
                    </v-btn>
                </router-link>
            </template>
        </div>
    </v-container>
</template>

<script>
export default {
    name: 'Welcome',
    data() {
        return {
            backgroundImageUrl: this.$ogtGlobals.proxyPath + '/images/de/backgroundWithText.png',
            // A: banner and grey background
            // B: background image, stretched to width (100%) => grey background at top and bottom on small displays
            // C: background image, cover, menu buttons => background image text lost on small devices
            // D: background image, stretched to height & width (100%), show menu buttons delayed => background image looks distorted
            layoutLabels: ['A', 'B', 'C', 'D'],
            menuButtons: [
                {
                    label: 'Projekt',
                    routeTo: '/project',
                },
                {
                    label: 'Kartenansicht',
                    routeTo: '/map',
                },
                {
                    label: 'Datenvisualisierung',
                    routeTo: '/charts',
                },
            ],
            menuButtonsShowTimeoutId: null,
            selectedLayoutId: 1,
            showBackgroundImage: true,
            showBanner: false,
            showMenuButtons: false,
        };
    },
    methods: {
        switchPageLayout() {
            clearTimeout(this.menuButtonsShowTimeoutId);
            let backgroundSize = '100% auto';
            this.selectedLayoutId = (this.selectedLayoutId + 1) % this.layoutLabels.length;

            if (this.selectedLayoutId == 0) {
                this.showBackgroundImage = false;
                this.showBanner = true;
                this.showMenuButtons = false;
            }
            else if (this.selectedLayoutId == 1) {
                this.showBackgroundImage = true;
                this.showBanner = false;
                this.showMenuButtons = false;
            }
            else if (this.selectedLayoutId == 2) {
                this.showBackgroundImage = true;
                this.showBanner = false;
                this.showMenuButtons = true;
                backgroundSize = 'cover';
            }
            else if (this.selectedLayoutId == 3) {
                this.showBackgroundImage = true;
                this.showBanner = false;
                this.showMenuButtons = false;
                backgroundSize = '100% 100%';
                this.setMenuButtonsShowTimeout();
            }

            const backgrounds = document.querySelectorAll('.background');

            backgrounds.forEach(background => {
                background.style.backgroundSize = backgroundSize;
            });
        },
        setMenuButtonsShowTimeout() {
            this.menuButtonsShowTimeoutId = setTimeout( () => {
                this.showMenuButtons = true;
            }, 3000);
        },
    },
};
</script>

<style scoped>
.background {
    background-position: center center;
    background-repeat: no-repeat;
    background-size: 100% auto;
}

.mt-300 {
    margin-top: 300px;
}

/* to lowercase text within Vuetify buttons */
.noneTextTransform {
    text-transform: none;
}

/* used move center of background image for extra small window size (< 600px) */
.xs-bg-x-shifted {
    background-position-x: -650px;
}
</style>
