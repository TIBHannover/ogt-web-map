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
                        ) in glossaryDataRef"
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
                            <span v-if="glossaryItemLabel == 'Judenhaus'"
                                >"</span
                            >
                            {{ glossaryItemLabel }}
                            <span v-if="glossaryItemLabel == 'Judenhaus'"
                                >"</span
                            >
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
                                <p v-else v-html="description"></p>
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
    const glossaryTermsToLink = [];

    // collect all terms, which should be linked to another glossary entry
    Object.entries(glossaryDataRef.value).forEach(([glossaryLabel, glossaryItem], index) => {
        glossaryTermsToLink.push({
            term: glossaryLabel,
            termItemId: glossaryItem.id,
            termItemIndex: index,
            termLabelKey: glossaryLabel,
        });

        glossaryItem.aliases.forEach(alias => {
            glossaryTermsToLink.push({
                term: alias,
                termItemId: glossaryItem.id,
                termItemIndex: index,
                termLabelKey: glossaryLabel,
            });
        });
    });

    // search and link all terms in descriptions for each glossary entry and update text links to HTML links in sources
    for (const [glossaryLabelKey, glossaryItem] of Object.entries(glossaryDataRef.value)) {
        glossaryItem.descriptions = glossaryItem.descriptions.map(description => {
            if (Array.isArray(description)) return description; // skip description arrays

            let replacedDescription = description;

            for (const { term, termItemId, termItemIndex, termLabelKey } of glossaryTermsToLink) {
                // do not link glossary item to self
                if (glossaryLabelKey === termLabelKey) continue;

                // there must be no (german) letters, numbers, umlauts or symbols (>') before the term
                // there must be no (german) letters, numbers, umlauts or symbols (<') after the term
                const regex = new RegExp(`(?<![\\wÄÖÜäöüß>'])(${term})(?![\\wÄÖÜäöüß<'])`, "gi");

                replacedDescription = replacedDescription.replace(
                    regex,
                    `<a href="#${termItemId}" class="glossary-link" data-glossary-index='${termItemIndex}' data-search-term='${termLabelKey}'>$1</a>`
                );
            }

            return replacedDescription;
        });

        glossaryItem.sources = glossaryItem.sources.map((source) => {
            return source.replace(
                /(^https?:\/\/[^ ]*)/i,
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
    // Only proceed if we clicked on a glossary link
    if (
        event.target.classList &&
        event.target.classList.contains("glossary-link")
    ) {
        const targetId = event.target.getAttribute("href").substring(1);
        const targetPanel = document.getElementById(targetId);

        if (targetPanel) {
            selectedGlossaryIndex.value = GLOSSARY_INDEX_ALL;

            searchTerm.value = null;

            setTimeout(() => {
                targetPanel.click();
                // Scroll to the panel
                targetPanel.scrollIntoView({ behavior: "smooth" });
            }, 100);

            event.preventDefault();
        }
    }
};

// Lifecycle hooks
onMounted(() => {
    setFreeClientWidth();
    setGlossaryIndex();

    glossaryDataRef.value = structuredClone(glossaryData);

    linkGlossaryItems();

    showGlossaryItemByUrlHash();

    document.addEventListener("click", (event) => {
        if (
            event.target.classList &&
            event.target.classList.contains("glossary-link")
        ) {
            showLinkedGlossaryItem(event);
        }
    });
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

.glossary-link {
    color: #4657b5;
    text-decoration: underline;
    cursor: pointer;
}
</style>
