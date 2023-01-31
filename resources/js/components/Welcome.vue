<template>
    <v-container
        class="grey lighten-3 justify-center"
        :class="{ background: showBackgroundImage, 'xs-bg-x-shifted': $vuetify.breakpoint.xs && ([2, 4, 5].includes(selectedLayoutId)) }"
        fill-height
        fluid
        :style="{
            backgroundImage: showBackgroundImage ?
                'url(' + backgroundImageUrl + '), url(' + backgroundImageUrlFallback1 + '), url(' + backgroundImageUrlFallback2 + ')' : 'none'
        }"
    >
        <!-- button to switch between alternative page layouts -->
        <v-btn
            v-if="this.$ogtGlobals.isTestingEnv"
            absolute
            class="text-transform-none"
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
                        :src="this.$ogtGlobals.proxyPath + '/images/static/bannerDe.jpg'"
                    ></v-img>
                </router-link>
            </v-col>
        </v-row>

        <!-- for layout with buttons -->
        <div
            :class="{'mt-300': ([2, 3].includes(selectedLayoutId))}"
            v-show="showMenuButtons"
        >
            <p class="text-h1 white--text" v-show="showHeaderText && (selectedLayoutId == 4)">
                <span class="text-h4 white--text text-sm-h1">Gestapo.Terror.Orte</span>
                <br>
                <span class="text-h5 white--text text-sm-h3 ml-1">in Niedersachsen 1933 – 1945</span>
                <br>
            </p>
            <div v-if="showHeaderText && (selectedLayoutId == 5)" class="white--text">
                <div class="font-family-special-elite mb-2 text-h4 text-sm-h3 text-md-h2 text-lg-h1">
                    Gestapo.Terror.Orte
                </div>
                <div class="mb-7 ml-1 text-h5 text-md-h4 text-lg-h3">
                    in Niedersachsen 1933 – 1945
                </div>
            </div>
            <template v-for="menuButton in menuButtons">
                <router-link :to="menuButton.routeTo" class="text-decoration-none">
                    <v-btn
                        class="my-3"
                        :class="{
                            'background-color-grey border-none justify-start ml-1 mr-5 text-h6 text-transform-none':  selectedLayoutId == 5,
                            'mx-10':  selectedLayoutId != 5,
                        }"
                        color="white"
                        :elevation="selectedLayoutId == 5 ? '24' : 'false'"
                        :min-width="selectedLayoutId == 5 ? '200px' : 'false'"
                        outlined
                        rounded
                        x-large
                    >
                        <v-icon v-if="selectedLayoutId == 5" left>
                            mdi-arrow-right-thick
                        </v-icon>
                        {{ menuButton.label }}
                    </v-btn>
                </router-link>
            </template>
        </div>
    </v-container>
</template>

<script>
const OGT_TIB_URL = 'https://service.tib.eu/ogt';

export default {
    name: 'Welcome',
    data() {
        return {
            backgroundImageUrl: this.$ogtGlobals.proxyPath + '/images/static/startPageBackground.jpg',
            backgroundImageUrlFallback1: OGT_TIB_URL + '/images/static/startPageBackground.jpg',
            backgroundImageUrlFallback2: this.$ogtGlobals.proxyPath + '/images/solid-grey.svg',
            // A: banner and grey background
            // B: background image, stretched to width (100%) => grey background at top and bottom on small displays
            // C: background image, cover, menu buttons => background image text lost on small devices
            // D: background image, stretched to height & width (100%), show menu buttons delayed => background image looks distorted
            // E: background image, cover, menu buttons, title as text => style (e.g. font type, spacing) must be adjusted
            // F: like E, but headline in Special Elite, buttons with icons, same width, left-aligned, background color, elevated, lowercase letters and without border
            layoutLabels: ['A', 'B', 'C', 'D', 'E', 'F'],
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
                    label: 'Mitforschen',
                    routeTo: '/collaboration',
                },
            ],
            menuButtonsShowTimeoutId: null,
            selectedLayoutId: 5,
            showBackgroundImage: true,
            showBanner: false,
            showHeaderText: false,
            showMenuButtons: false,
        };
    },
    mounted() {
        this.selectedLayoutId -= 1;
        this.switchPageLayout();
    },
    methods: {
        switchPageLayout() {
            clearTimeout(this.menuButtonsShowTimeoutId);
            let backgroundSize = '100% auto';
            this.selectedLayoutId = (this.selectedLayoutId + 1) % this.layoutLabels.length;
            this.backgroundImageUrl = this.$ogtGlobals.proxyPath + '/images/static/startPageBackgroundWithHeadline.png';
            this.backgroundImageUrlFallback1 = OGT_TIB_URL + '/images/static/startPageBackgroundWithHeadline.png';

            if (this.selectedLayoutId == 0) {
                this.showBackgroundImage = false;
                this.showBanner = true;
                this.showHeaderText = false;
                this.showMenuButtons = false;
            }
            else if (this.selectedLayoutId == 1) {
                this.showBackgroundImage = true;
                this.showBanner = false;
                this.showHeaderText = false;
                this.showMenuButtons = false;
            }
            else if (this.selectedLayoutId == 2) {
                this.showBackgroundImage = true;
                this.showBanner = false;
                this.showHeaderText = false;
                this.showMenuButtons = true;
                backgroundSize = 'cover';
            }
            else if (this.selectedLayoutId == 3) {
                this.showBackgroundImage = true;
                this.showBanner = false;
                this.showHeaderText = false;
                this.showMenuButtons = false;
                backgroundSize = '100% 100%';
                this.setMenuButtonsShowTimeout();
            }
            else if (this.selectedLayoutId == 4) {
                this.backgroundImageUrl = this.$ogtGlobals.proxyPath + '/images/static/startPageBackground.jpg';
                this.backgroundImageUrlFallback1 = OGT_TIB_URL + '/images/static/startPageBackground.jpg';
                this.showBackgroundImage = true;
                this.showBanner = false;
                this.showHeaderText = true;
                this.showMenuButtons = true;
                backgroundSize = 'cover';
            }
            else if (this.selectedLayoutId == 5) {
                this.backgroundImageUrl = this.$ogtGlobals.proxyPath + '/images/static/startPageBackground.jpg';
                this.backgroundImageUrlFallback1 = OGT_TIB_URL + '/images/static/startPageBackground.jpg';
                this.showBackgroundImage = true;
                this.showBanner = false;
                this.showHeaderText = true;
                this.showMenuButtons = true;
                backgroundSize = 'cover';
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

.background-color-grey {
    background-color: rgba(94, 94, 94, 0.35);
}

.border-none {
    border: none;
}

.font-family-special-elite {
    font-family: "Special Elite" !important;
}

.mt-300 {
    margin-top: 300px;
}

/* to lowercase text within Vuetify buttons */
.text-transform-none {
    text-transform: none;
}

/* shift center of background image for extra small window sizes (< 600px) */
.xs-bg-x-shifted {
    background-position-x: 75%;
}
</style>
