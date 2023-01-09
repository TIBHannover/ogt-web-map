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

            <v-row>
                <v-col>
                    <v-btn-toggle v-model="selectedGlossaryIndex" class="flex-wrap" group>
                        <v-btn
                            v-for="indexLetter in this.glossaryIndex" :key="indexLetter"
                            class="button-toggle-border mb-3 mr-3"
                            fab
                            small
                            :value="indexLetter"
                        >
                            {{ indexLetter }}
                        </v-btn>
                    </v-btn-toggle>
                </v-col>
            </v-row>

            <v-row>
                <v-col>
                    <v-text-field
                        v-model="search"
                        clearable
                        color="black"
                        hint="Die Bezeichnungen und die Beschreibungen der Glossareinträge werden durchsucht."
                        label="Glossar durchsuchen..."
                        prepend-inner-icon="mdi-text-search-variant"
                    ></v-text-field>
                </v-col>
            </v-row>
        </div>
    </v-container>
</template>

<script>
import glossaryData from '../data/glossaryData';

const GLOSSARY_INDEX_ALL = 'A-Z';

export default {
    name: 'Glossary',
    props: ['isMenuDisplayed'],
    data() {
        return {
            freeClientWidth: document.documentElement.clientWidth,
            glossaryData: glossaryData,
            glossaryIndex: [GLOSSARY_INDEX_ALL],
            search: null,
            selectedGlossaryIndex: GLOSSARY_INDEX_ALL,
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
        this.setGlossaryIndex();
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
        /**
         *  Set first letter of glossary entries as index for the glossary.
         */
        setGlossaryIndex() {
            for (const [glossaryItemLabel, glossaryItemData] of Object.entries(this.glossaryData)) {
                let firstChar = glossaryItemLabel.charAt(0);

                if (! this.glossaryIndex.includes(firstChar)) {
                    this.glossaryIndex.push(firstChar);
                }
            }
        },
    },
};
</script>

<style scoped>
.button-toggle-border {
    border-color: rgba(0, 0, 0, 0.87) !important;
    border-radius: 50% !important;
    border-width: thin !important;
}

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