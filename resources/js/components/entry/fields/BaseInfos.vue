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
            <v-text-field
                :model-value="title"
                @update:model-value="
                    (value) => {
                        console.log('Direct title input:', value);
                        $emit('update:title', value);
                        // Validate after direct input
                        setTimeout(() => validate(), 10);
                    }
                "
                name="title"
                maxlength="250"
                placeholder="z.B. Novemberpogrom 1938 in Osnabrück"
                @focus="openTitleOverlay"
                @focus.once="$emit('fieldFocus', 'title')"
                append-inner-icon="mdi-magnify"
                @click:append-inner="openTitleOverlay"
                variant="outlined"
                density="comfortable"
                hide-details
                single-line
                color="primary"
                bg-color="white"
                class="title-search-field"
            ></v-text-field>
        </div>

        <ValidationError
            :show="showValidationErrors && !isTitleValid"
            message="Bitte gebe einen Titel ein."
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
        <!-- Add a hidden validator to ensure we capture changes -->
        <input type="hidden" :value="title" @change="validate" />

        <!-- Anzeige der Suchergebnisse -->
        <div v-if="wikidataResults.length > 0" class="search-results">
            <div class="search-info-box">
                <div class="info-icon-wrapper">
                    <v-icon>mdi-information-outline</v-icon>
                </div>
                <p>Möglicherweise existiert dieser Eintrag bereits:</p>
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
            @input="handleDescriptionInput"
            name="description"
            maxlength="250"
            placeholder="z.B. vom NS-Regime gelenkte Gewaltmaßnahmen gegen Juden in Osnabrück."
            @focus="$emit('fieldFocus', 'title')"
        ></textarea>

        <ValidationError
            :show="showValidationErrors && !isDescriptionValid"
            message="Bitte gebe eine Kurzbeschreibung ein."
            showIcon
        />
        <ValidationError
            :show="descriptionTooLong"
            message="Die Beschreibung darf maximal 250 Zeichen enthalten."
            showIcon
        />
        <div
            class="character-counter"
            :class="{ 'text-danger': descriptionTooLong }"
        >
            {{ props.description ? props.description.length : 0 }}/250
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
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
    // Ensure validation is triggered after overlay closes
    setTimeout(() => {
        validate();
        emit("validation", isTitleValid.value && isDescriptionValid.value);
    }, 50);
};

// Handle title update from overlay
const updateTitleFromOverlay = (newTitle) => {
    console.log("BaseInfos: Title updated from overlay:", newTitle);
    // Do not mutate props directly
    emit("update:title", newTitle);
    // We don't close the overlay here - it will be closed by the Save button
    // or by the create action in the TitleSearchOverlay

    // Delay needed to ensure updates are processed before any validation
    setTimeout(() => {
        validate();
    }, 50);
};

// Handle create from overlay
const createFromOverlay = (title) => {
    console.log("BaseInfos: Title created from overlay:", title);
    emit("update:title", title);
    // Close the overlay after creation
    closeTitleOverlay();

    // Force validation after creation to update parent component
    setTimeout(() => {
        validate();
        emit("validation", isTitleValid.value && isDescriptionValid.value);
    }, 50);
};

// Description length check
const descriptionTooLong = ref(false);

const handleDescriptionInput = (event) => {
    const value = event.target.value;
    descriptionTooLong.value = value.length > 250;

    if (descriptionTooLong.value) {
        // Truncate if too long
        event.target.value = value.substring(0, 250);
    }

    emit("update:description", event.target.value);
};

// Watch for external description changes
watch(
    () => props.description,
    (newValue) => {
        if (newValue && newValue.length > 250) {
            descriptionTooLong.value = true;
            // Notify parent to truncate
            emit("update:description", newValue.substring(0, 250));
        } else {
            descriptionTooLong.value = false;
        }
    },
);

// Computed properties for validation
const isTitleValid = computed(() => {
    return props.title && props.title.trim().length > 0;
});

const isDescriptionValid = computed(() => {
    return (
        props.description &&
        props.description.trim().length > 0 &&
        !descriptionTooLong.value
    );
});

// Validation function
const validateBaseInfos = () => {
    console.log("BaseInfos: validateBaseInfos called with title:", props.title);
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
        console.log("BaseInfos: Updating bindings with current values");
        // If there are internal updates needed, they can happen here
    },
});
</script>

<!-- Global form styles -->
<style scoped>
@import "../styles/form-styles.css";

.title-input-container {
    width: 100%;
    margin-bottom: 1rem;
}

.title-search-field {
    width: 100%;
}

:deep(.title-search-field .v-field__append-inner) {
    cursor: pointer;
}

:deep(.title-search-field .v-field__input) {
    padding: 8px 16px;
    min-height: 48px;
}

:deep(.title-search-field .v-icon) {
    color: #1976d2;
}

:deep(.title-search-field .v-field--focused) {
    border-color: #1976d2 !important;
}

:deep(.title-search-field .v-field) {
    border-radius: 4px;
    border: 1px solid #ccc;
    background-color: white;
}
</style>

<style scoped>
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

.character-counter {
    text-align: right;
    font-size: 0.85rem;
    color: #757575;
    margin-top: -0.5rem;
    margin-bottom: 0.5rem;
}

.character-counter.text-danger {
    color: #f44336;
    font-weight: bold;
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
</style>
