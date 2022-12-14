<template>
    <v-container id="glossaryContainer" :class="{'pl-375': isMenuDisplayed}">
        <div :class="{'pl-4': freeClientWidth == 0 && isMenuDisplayed}">
            <v-row :class="{'ml-15': freeClientWidth < 75 && ! isMenuDisplayed}">
                <v-col>
                    <h1 class="font-family-courier font-weight-bold hyphens-auto text-h4 text-md-h3 py-4" lang="de">
                        Glossar
                    </h1>
                </v-col>
            </v-row>
        </div>
    </v-container>
</template>

<script>
export default {
    name: 'Glossary',
    props: ['isMenuDisplayed'],
    data() {
        return {
            freeClientWidth: document.documentElement.clientWidth,
        };
    },
    created() {
        window.addEventListener('resize', this.setFreeClientWidth);
    },
    destroyed() {
        window.removeEventListener('resize', this.setFreeClientWidth);
    },
    mounted() {
        this.setFreeClientWidth();
    },
    methods: {
        /**
         * Determine width of free space between glossary content and client border.
         *
         * @param event
         */
        setFreeClientWidth(event) {
            const glossaryContainer = document.getElementById('glossaryContainer');

            this.freeClientWidth = (document.documentElement.clientWidth - glossaryContainer.clientWidth) / 2;
        },
    },
};
</script>

<style scoped>
.font-family-courier {
    font-family: Courier !important;
}

.hyphens-auto {
    hyphens: auto;
}

.pl-375 {
    padding-left: 375px;
}
</style>