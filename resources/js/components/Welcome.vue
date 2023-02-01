<template>
    <v-container
        class="background grey lighten-3 justify-center"
        :class="{ 'xs-bg-x-shifted': $vuetify.breakpoint.xs }"
        fill-height
        fluid
        :style="{
            backgroundImage: 'url(' + backgroundImageUrl + '), url(' + backgroundImageUrlFallback1 + '), url(' + backgroundImageUrlFallback2 + ')'
        }"
    >
        <div>
            <div class="white--text">
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
                        class="background-color-grey border-none justify-start ml-1 mr-5 my-3 text-h6 text-transform-none"
                        color="white"
                        elevation="24"
                        min-width="200px"
                        outlined
                        rounded
                        x-large
                    >
                        <v-icon left>
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

export default {
    name: 'Welcome',
    data() {
        return {
            backgroundImageUrl: this.$ogtGlobals.proxyPath + '/images/static/startPageBackground.jpg',
            backgroundImageUrlFallback1: 'https://service.tib.eu/ogt/images/static/startPageBackground.jpg',
            backgroundImageUrlFallback2: this.$ogtGlobals.proxyPath + '/images/solid-grey.svg',
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
        };
    },
};
</script>

<style scoped>
.background {
    background-position: center center;
    background-repeat: no-repeat;
    background-size: cover;
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

/* to lowercase text within Vuetify buttons */
.text-transform-none {
    text-transform: none;
}

/* shift center of background image for extra small window sizes (< 600px) */
.xs-bg-x-shifted {
    background-position-x: 75%;
}
</style>
