<template>
    <div class="main-section container-section" @focusin="$emit('focusin')">
        <div class="section-header">
            <label class="section-label">Basis Informationen:</label>
            <button
                type="button"
                class="info-icon"
                @click="$emit('infoButtonClick', 'title')"
            >
                <v-icon>mdi-information-variant</v-icon>
            </button>
        </div>

        <label for="title" class="">Titel *</label>
        <div class="title-input-container">
            <input
                :value="title"
                @input="$emit('update:title', $event.target.value)"
                name="title"
                type="text"
                maxlength="250"
                placeholder="z.B. Novemberpogrom 1938 in Osnabrück"
                @focus="openTitleOverlay"
                @focus.once="$emit('fieldFocus', 'title')"
            />
            <button 
                type="button" 
                class="search-button"
                @click="openTitleOverlay"
                title="Titel überprüfen">
                <v-icon>mdi-magnify</v-icon>
            </button>
        </div>

        <ValidationError
            :show="showValidationErrors && !isTitleValid"
            message="Bitte geben Sie einen Titel ein."
            showIcon
        />

        <!-- Title Search Overlay -->
        <TitleSearchOverlay
            v-if="showTitleOverlay"
            :initial-title="title"
            :entry-type="entryType"
            :title="'Titel überprüfen'"
            :context="'update'"
            @update:title="updateTitleFromOverlay"
            @close="closeTitleOverlay"
            @create="createFromOverlay"
        />

        <!-- Anzeige der Suchergebnisse -->
        <div v-if="wikidataResults.length > 0" class="search-results">
            <div class="search-info-box">
                <div class="info-icon-wrapper">
                    <v-icon>mdi-information-outline</v-icon>
                </div>
                <p>
                    Möglicherweise existiert dieser Eintrag bereits:
                </p>
            </div>
            <div
                v-for="result in wikidataResults"
                :key="result.id"
                class="search-result-item"
                @click="$emit('selectExistingEntity', result)"
            >
                <div class="result-title">{{ result.label }}</div>
                <div class="result-description" v-if="result.description">
                    {{ result.description }}
                </div>
                <div class="result-id">{{ result.id }}</div>
            </div>
        </div>

        <label for="description" class="mt-3 d-block">Kurzbeschreibung *</label>
        <textarea
            :value="description"
            @input="$emit('update:description', $event.target.value)"
            name="description"
            maxlength="750"
            placeholder="z.B. vom NS-Regime gelenkte Gewaltmaßnahmen gegen Juden in Osnabrück."
            @focus="$emit('fieldFocus', 'title')"
        ></textarea>

        <ValidationError
            :show="showValidationErrors && !isDescriptionValid"
            message="Bitte geben Sie eine Kurzbeschreibung ein."
            showIcon
        />
    </div>
</template>

<script setup>
import { ref, computed } from "vue";
import ValidationError from "../../helpers/ValidationError.vue";
import { useValidation } from "../../../composables/useValidation";
import TitleSearchOverlay from "./TitleSearchOverlay.vue";

const props = defineProps({
    stepTitle: {
        type: String,
        default: "Schritt 2: Ereignis-Details eingeben",
    },
    title: {
        type: String,
        default: "",
    },
    description: {
        type: String,
        default: "",
    },
    wikidataResults: {
        type: Array,
        default: () => [],
    },
    entryType: {
        type: String,
        required: true,
    },
});

const emit = defineEmits([
    "update:title",
    "update:description",
    "fieldFocus",
    "focusin",
    "infoButtonClick",
    "selectExistingEntity",
    "validation",
    "searchWikidataEntities",
]);

// Title search overlay state
const showTitleOverlay = ref(false);

// Open and close title search overlay
const openTitleOverlay = () => {
    showTitleOverlay.value = true;
};

const closeTitleOverlay = () => {
    showTitleOverlay.value = false;
};

// Handle title update from overlay
const updateTitleFromOverlay = (newTitle) => {
    emit("update:title", newTitle);
};

// Handle create from overlay
const createFromOverlay = (title) => {
    emit("update:title", title);
    closeTitleOverlay();
};

// Computed properties for validation
const isTitleValid = computed(() => {
    return props.title && props.title.trim().length > 0;
});

const isDescriptionValid = computed(() => {
    return props.description && props.description.trim().length > 0;
});

// Validation function
const validateBaseInfos = () => {
    return isTitleValid.value && isDescriptionValid.value;
};

// Use the validation composable
const { isValid, showValidationErrors, validate, showErrors } = useValidation(
    validateBaseInfos,
    {
        emit,
        watchCallback: () => [props.title, props.description],
    },
);

// Export the validation status for parent components
defineExpose({
    isValid,
    validate,
    showValidationErrors: showErrors,
    updateBindings: () => {
        // This method can be called by the parent to ensure form values are current
        console.log('BaseInfos: Updating bindings with current values');
        // If there are internal updates needed, they can happen here
    }
});
</script>

<style scoped>
.step-container {
    background-color: rgba(255, 255, 255, 0.9);
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.step-title {
    margin-bottom: 20px;
    font-size: 1.5rem;
    color: #333;
}

.main-section {
    width: 100%;
    margin-bottom: 1.5rem;
    background-color: #f5f5f5;
    padding: 1rem;
    border-radius: 4px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.section-label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.info-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background-color: #1976d2;
    color: white;
    border: none;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.info-icon:hover {
    background-color: #1565c0;
}

.title-input-container {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.title-input-container input {
    flex: 1;
}

.search-button {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 4px;
    background-color: #1976d2;
    color: white;
    border: none;
    cursor: pointer;
    transition: background-color 0.2s;
}

.search-button:hover {
    background-color: #1565c0;
}

input,
textarea {
    padding: 0.75rem 1rem;
    width: 100%;
    background-color: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}

input:focus,
textarea:focus {
    outline: none;
    border-color: #1976d2;
    box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.25);
}

textarea {
    min-height: 8.5rem;
    resize: none;
}

/* Search Info Box */
.search-info-box {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 0.5rem;
    padding: 0.75rem;
    background-color: #fff9c4; /* Light yellow background */
    border-radius: 4px;
    border-left: 4px solid #fbc02d; /* Darker yellow border */
}

.search-info-box .info-icon-wrapper {
    color: #f57f17; /* Amber color for icon */
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.search-info-box p {
    margin: 0;
    color: #5d4037;
    font-size: 0.95rem;
}

/* Wikidata search results */
.search-results {
    width: 100%;
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #ccc;
    border-radius: 4px;
    margin-top: -0.5rem;
    background-color: #fff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    z-index: 20;
    position: relative;
}

.search-info {
    padding: 10px 15px;
    font-size: 0.9rem;
    color: #666;
    border-bottom: 1px solid #eee;
    margin: 0;
}

.search-result-item {
    padding: 10px 12px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    transition: background-color 0.2s;
}

.search-result-item:hover {
    background-color: #f5f5f5;
}

.search-result-item:last-child {
    border-bottom: none;
}

.result-title {
    font-weight: bold;
    margin-bottom: 3px;
}

.result-description {
    font-size: 0.9rem;
    color: #555;
    margin-bottom: 3px;
}

.result-id {
    font-size: 0.8rem;
    color: #888;
}
</style>
