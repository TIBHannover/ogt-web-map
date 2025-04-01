<template>
    <v-container id="container" class="mb-110">
        <v-row :style="{ 'padding-left': computedPadding }">
            <v-col>
                <h1
                    class="font-family-special-elite font-weight-bold hyphens-auto py-4 text-h4 text-md-h3"
                    lang="de"
                >
                    Glossar
                </h1>
            </v-col>
        </v-row>

        <v-row>
            <v-col>
                <v-btn-toggle
                    v-model="selectedGlossaryIndex"
                    class="flex-wrap"
                    group
                    mandatory
                >
                    <v-btn
                        v-for="indexLetter in glossaryIndex"
                        :key="indexLetter"
                        class="button-toggle-border mb-3 mr-3"
                        rounded="circle"
                        variant="outlined"
                        size="40"
                        fab
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
                        v-for="(
                            glossaryItem, glossaryItemLabel
                        ) in glossaryData"
                        :key="glossaryItem.id"
                        v-show="
                            filterGlossaryItems(
                                glossaryItemLabel,
                                glossaryItem.descriptions,
                            )
                        "
                        class="hyphens-auto"
                        lang="de"
                    >
                        <v-expansion-panel-title
                            :id="glossaryItem.id"
                            class="font-weight-bold"
                        >
                            {{ glossaryItemLabel }}
                        </v-expansion-panel-title>

                        <v-expansion-panel-text>
                            <template
                                v-for="description in glossaryItem.descriptions"
                            >
                                <ul
                                    v-if="Array.isArray(description)"
                                    class="noBullets mb-4"
                                >
                                    <li v-for="enumerationText in description">
                                        {{ enumerationText }}
                                    </li>
                                </ul>
                                <p
                                    v-else
                                    @click="showLinkedGlossaryItem"
                                    v-html="description"
                                ></p>
                            </template>

                            <h5
                                v-if="glossaryItem.sources.length > 0"
                                class="font-weight-bold"
                            >
                                Quellen:
                            </h5>

                            <p
                                v-for="source in glossaryItem.sources"
                                class="text-body-2"
                                v-html="source"
                            ></p>
                        </v-expansion-panel-text>
                    </v-expansion-panel>
                </v-expansion-panels>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup>
import { ref, onMounted, onUnmounted, inject, computed } from "vue";
import { useRoute } from "vue-router";
import glossaryData from "../data/glossaryData";
const isMenuDisplayed = inject("isMenuDisplayed");

// Constants
const GLOSSARY_INDEX_ALL = "A-Z";

// Reactive references
const freeClientWidth = ref(document.documentElement.clientWidth);
const glossaryDataRef = ref(structuredClone(glossaryData));
const glossaryIndex = ref([GLOSSARY_INDEX_ALL]);
const openedGlossaryPanel = ref(null);
const searchTerm = ref(null);
const selectedGlossaryIndex = ref(GLOSSARY_INDEX_ALL);

const route = useRoute();

const computedPadding = computed(() => {
    return freeClientWidth.value < 75 && !isMenuDisplayed.value
        ? `${75 - freeClientWidth.value}px`
        : "0";
});

const setFreeClientWidth = () => {
    const container = document.getElementById("container");
    freeClientWidth.value =
        (document.documentElement.clientWidth - container.clientWidth) / 2;
};

const setGlossaryIndex = () => {
    for (const [glossaryItemLabel] of Object.entries(glossaryDataRef.value)) {
        let firstChar = glossaryItemLabel.charAt(0);
        if (!glossaryIndex.value.includes(firstChar)) {
            glossaryIndex.value.push(firstChar);
        }
    }
};

const filterGlossaryItems = (glossaryItemLabel, glossaryItemDescriptions) => {
    let glossaryItemDescription = glossaryItemDescriptions.join();

    return (
        [GLOSSARY_INDEX_ALL, glossaryItemLabel.charAt(0)].includes(
            selectedGlossaryIndex.value,
        ) &&
        (searchTerm.value === null ||
            glossaryItemLabel.match(RegExp(searchTerm.value, "i")) ||
            glossaryItemDescription.match(RegExp(searchTerm.value, "i")))
    );
};

const linkGlossaryItems = () => {
    let searchTerms = [];

    // create regex search terms for glossary item labels
    for (const [glossaryItemLabel, glossaryItemData] of Object.entries(
        glossaryDataRef.value,
    )) {
        let termRegex =
            glossaryItemLabel.replace("s ", "[ens]{0,3} ") + "[ens]{0,3}";
        searchTerms.push({
            label: glossaryItemLabel,
            regex: termRegex,
        });
    }

    for (let [glossaryItemLabel, glossaryItemData] of Object.entries(
        glossaryDataRef.value,
    )) {
        glossaryItemData.descriptions.forEach(
            (description, descriptionIndex) => {
                if (Array.isArray(description)) return;

                searchTerms.forEach((term, termIndex) => {
                    if (term.label == glossaryItemLabel) return;

                    let regex = new RegExp(
                        `(^| |\\.|\\()(${term.regex})( |,|\\.|$|\\))`,
                        "gi",
                    );
                    let replacement = `$1<a href='javascript:' data-glossary-index='${termIndex}' data-search-term='${term.label}'>$2</a>$3`;

                    description = description.replace(regex, replacement);
                });

                glossaryItemData.descriptions[descriptionIndex] = description;
            },
        );

        glossaryItemData.sources.forEach((source, sourceIndex) => {
            glossaryItemData.sources[sourceIndex] = source.replace(
                /(^https:.[^ ]*)/i,
                "<a href='$1' target='_blank'>$1</a>",
            );
        });
    }
};

const showGlossaryItemByUrlHash = () => {
    const urlHash = route.hash;
    if (urlHash == "") return;

    const expansionPanelHeader = document.getElementById(urlHash.substring(1));
    if (expansionPanelHeader) {
        expansionPanelHeader.click();
    }
};

const showLinkedGlossaryItem = (event) => {
    if (event.target.hasAttribute("data-glossary-index")) {
        selectedGlossaryIndex.value = GLOSSARY_INDEX_ALL;
        searchTerm.value = event.target.getAttribute("data-search-term");
        openedGlossaryPanel.value = parseInt(
            event.target.getAttribute("data-glossary-index"),
        );
    }
};

// Lifecycle hooks
onMounted(() => {
    setFreeClientWidth();
    setGlossaryIndex();
    linkGlossaryItems();
    showGlossaryItemByUrlHash();
});

onUnmounted(() => {
    window.removeEventListener("resize", setFreeClientWidth);
});

// Event listeners
window.addEventListener("resize", setFreeClientWidth);
</script>

<style scoped>
.button-toggle-border {
    border-color: #000 !important;
    border-radius: 50% !important;
    border-width: 1px !important;
    border-start-end-radius: 1px;
    border-start-start-radius: 1px;
    border-radius: 100% !important;
    border-width: 1px !important;
    border-style: solid !important;
}

.font-family-special-elite {
    font-family: "Special Elite" !important;
}

.hyphens-auto {
    hyphens: auto;
}

/* space for footer to avoid that footer covers content */
.mb-110 {
    margin-bottom: 110px;
}

.noBullets {
    list-style-type: none;
}
</style>
