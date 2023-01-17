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
                    <v-btn-toggle v-model="selectedGlossaryIndex" class="flex-wrap" group mandatory>
                        <v-btn
                            v-for="indexLetter in this.glossaryIndex"
                            :key="indexLetter"
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
                        v-model="searchTerm"
                        clearable
                        color="black"
                        hint="Die Bezeichnungen und die Beschreibungen der Glossareinträge werden durchsucht."
                        label="Glossar durchsuchen..."
                        prepend-inner-icon="mdi-text-search-variant"
                    ></v-text-field>
                </v-col>
            </v-row>

            <v-row>
                <v-col>
                    <v-expansion-panels v-model="openedGlossaryPanel" focusable>
                        <v-expansion-panel
                            v-for="(glossaryItem, glossaryItemLabel) in this.glossaryData"
                            :key="glossaryItem.id"
                            v-show="filterGlossaryItems(glossaryItemLabel, glossaryItem.descriptions)"
                            class="hyphens-auto"
                            lang="de"
                        >
                            <v-expansion-panel-header :id="glossaryItem.id" class="font-weight-bold">
                                {{ glossaryItemLabel }}
                            </v-expansion-panel-header>

                            <v-expansion-panel-content>
                                <template v-for="description in glossaryItem.descriptions">
                                    <ul v-if="Array.isArray(description)" class="noBullets mb-4">
                                        <li v-for="enumerationText in description">
                                            {{ enumerationText }}
                                        </li>
                                    </ul>
                                    <p v-else @click="showLinkedGlossaryItem" v-html="description"></p>
                                </template>

                                <h5 v-if="glossaryItem.sources.length > 0" class="font-weight-bold">
                                    Quellen:
                                </h5>

                                <p v-for="source in glossaryItem.sources" class="text-body-2" v-html="source"></p>
                            </v-expansion-panel-content>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </v-col>
            </v-row>
        </div>
        <br><br><br><br><br>
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
            glossaryData: structuredClone(glossaryData),
            glossaryIndex: [GLOSSARY_INDEX_ALL],
            openedGlossaryPanel: null,
            searchTerm: null,
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
        this.linkGlossaryItems();
        this.showGlossaryItemByUrlHash();
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
        /**
         * Filter glossary items by selected glossary index and search term.
         *
         * @param {string} glossaryItemLabel
         * @param {array} glossaryItemDescriptions
         * @returns {boolean}
         */
        filterGlossaryItems(glossaryItemLabel, glossaryItemDescriptions) {
            let glossaryItemDescription = glossaryItemDescriptions.join();

            if ([GLOSSARY_INDEX_ALL, glossaryItemLabel.charAt(0)].includes(this.selectedGlossaryIndex)
                &&
                (
                    this.searchTerm === null ||
                    glossaryItemLabel.match(RegExp(this.searchTerm, 'i')) ||
                    glossaryItemDescription.match(RegExp(this.searchTerm, 'i'))
                )
            ) {
                return true;
            }

            return false;
        },
        /**
         * Link glossary items within glossary texts.
         */
        linkGlossaryItems() {
            let searchTerms = [];

            // create regex search terms for glossary item labels
            for (const [glossaryItemLabel, glossaryItemData] of Object.entries(this.glossaryData)) {

                let termRegex = glossaryItemLabel.replace('s ', '[ens]{0,3} ').replace(/[ne]?$/, '[ens]{0,3}');

                searchTerms.push({
                    'label': glossaryItemLabel,
                    'regex': termRegex,
                });
            }

            for (let [glossaryItemLabel, glossaryItemData] of Object.entries(this.glossaryData)) {
                glossaryItemData.descriptions.forEach((description, descriptionIndex) => {

                    // skip case enumeration description
                    if (Array.isArray(description)) {
                        return;
                    }

                    searchTerms.forEach((term, termIndex) => {

                        if (term.label == glossaryItemLabel) {
                            return;
                        }

                        let regex = new RegExp(`(^| |\\.|\\()(${term.regex})( |,|\\.|$|\\))`, 'gi');
                        let replacement = `$1<a href='javascript:' data-glossary-index='${termIndex}' data-search-term='${term.label}'>$2</a>$3`;

                        description = description.replace(regex, replacement);
                    });

                    glossaryItemData.descriptions[descriptionIndex] = description;
                });

                glossaryItemData.sources.forEach((source, sourceIndex) => {
                    glossaryItemData.sources[sourceIndex] = source.replace(/(^https:.[^ ]*)/i, '<a href=\'$1\' target=\'_blank\'>$1</a>');
                });
            }
        },
        /**
         * Show glossary entry linked in URL hash.
         */
        showGlossaryItemByUrlHash() {
            const urlHash = this.$route.hash;

            if (urlHash == '') {
                return;
            }

            const expansionPanelHeader = document.getElementById(urlHash.substring(1));

            if (expansionPanelHeader) {
                expansionPanelHeader.click();
            }
        },
        /**
         * Show glossary entry linked in a glossary text.
         *
         * @param event
         */
        showLinkedGlossaryItem(event) {
            if (event.target.hasAttribute('data-glossary-index')) {
                this.selectedGlossaryIndex = GLOSSARY_INDEX_ALL;
                this.searchTerm = event.target.getAttribute('data-search-term');
                this.openedGlossaryPanel = parseInt(event.target.getAttribute('data-glossary-index'));
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

.noBullets {
    list-style-type: none;
}

.pl-375 {
    padding-left: 375px;
}
</style>