<template>
    <div class="container-section sources-section">
        <div class="section-header">
            <label class="section-label">Quellenangaben: *</label>
            <button
                type="button"
                class="info-icon"
                @click="$emit('infoButtonClick', 'sources')"
            >
                <v-icon>mdi-information-variant</v-icon>
            </button>
        </div>
        <div class="sources-selection">
            <v-btn
                type="button"
                class="button bg-secondary"
                @click="openSourcesSelector"
                @focus="$emit('fieldFocus', 'sources')"
            >
                <v-icon class="me-1">mdi-plus</v-icon>
                Quellenangabe hinzufügen
            </v-btn>

            <ValidationError
                :show="showValidationErrors && !isValid"
                message="Bitte fügen Sie mindestens eine Quellenangabe hinzu."
                showIcon
            />

            <div
                v-if="selectedSources.length > 0"
                class="selected-sources mt-4"
            >
                <div
                    v-for="(source, index) in selectedSources"
                    :key="index"
                    class="source-item"
                >
                    <div class="source-type-icon">
                        <v-icon v-if="source.type === 'book'">mdi-book</v-icon>
                        <v-icon v-else-if="source.type === 'url'"
                            >mdi-link</v-icon
                        >
                        <v-icon v-else-if="source.type === 'archive'"
                            >mdi-archive</v-icon
                        >
                    </div>
                    <div class="source-content">
                        <div class="source-title">
                            {{ source.title }}
                        </div>
                        <div
                            v-if="source.description"
                            class="source-description"
                        >
                            {{ source.description }}
                        </div>
                        <div class="source-details">
                            <template v-if="source.type === 'book'">
                                <span v-if="source.pages"
                                    >S. {{ source.pages }}</span
                                >
                                <a
                                    v-if="source.id && source.id !== 'manual'"
                                    :href="`https://www.wikidata.org/wiki/${source.id}`"
                                    target="_blank"
                                    class="source-link"
                                >
                                    <v-icon size="x-small mr-1"
                                        >mdi-open-in-new</v-icon
                                    >
                                    Wikidata
                                </a>
                            </template>
                            <template v-else-if="source.type === 'url'">
                                <span v-if="source.accessDate">
                                    Zugriff am
                                    {{ formatDate(source.accessDate) }}
                                </span>
                                <a
                                    :href="source.url"
                                    target="_blank"
                                    class="source-link"
                                >
                                    <v-icon size="x-small"
                                        >mdi-open-in-new</v-icon
                                    >
                                    Link
                                </a>
                            </template>
                            <template v-else-if="source.type === 'archive'">
                                <span
                                    >Inv. Nr. {{ source.inventoryNumber }}</span
                                >
                                <a
                                    v-if="source.id && source.id !== 'manual'"
                                    :href="`https://www.wikidata.org/wiki/${source.id}`"
                                    target="_blank"
                                    class="source-link"
                                >
                                    <v-icon size="x-small"
                                        >mdi-open-in-new</v-icon
                                    >
                                    Wikidata
                                </a>
                            </template>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="remove-source"
                        @click="removeSource(index)"
                    >
                        <v-icon>mdi-close</v-icon>
                    </button>
                </div>
            </div>
        </div>

        <v-dialog v-model="sourcesDialogVisible" max-width="900px">
            <SourcesSelector
                @select="selectSource"
                @close="closeSourcesDialog"
            />
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";
import SourcesSelector from "./SourcesSelector.vue";
import ValidationError from "../../helpers/ValidationError.vue";
import { useValidation } from "../../../composables/useValidation";

const props = defineProps({
    selectedSources: {
        type: Array,
        default: () => [],
    },
    required: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits([
    "update:selectedSources",
    "fieldFocus",
    "infoButtonClick",
    "validation",
]);

const sourcesDialogVisible = ref(false);

// Validation function
const validateSources = () => {
    return props.selectedSources && props.selectedSources.length > 0;
};

// Use the validation composable
const { isValid, showValidationErrors, validate, showErrors } = useValidation(
    validateSources,
    {
        emit,
        watchCallback: () => [props.selectedSources],
    },
);

// Formatiert ein Datum im deutschen Format
const formatDate = (dateString) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    return date.toLocaleDateString("de-DE");
};

// Sources-Auswahllogik
const openSourcesSelector = () => {
    sourcesDialogVisible.value = true;
};

const closeSourcesDialog = () => {
    sourcesDialogVisible.value = false;
};

const selectSource = (data) => {
    const newSources = [...props.selectedSources, data.source];
    emit("update:selectedSources", newSources);
    sourcesDialogVisible.value = false;
};

const removeSource = (index) => {
    const newSources = [...props.selectedSources];
    newSources.splice(index, 1);
    emit("update:selectedSources", newSources);
};

// Export the validation status for parent components
defineExpose({
    isValid,
    validate,
    showValidationErrors: showErrors,
});
</script>

<!-- Global form styles -->
<style>
@import '../styles/form-styles.css';
</style>

<style scoped>
.container-section {
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

.button {
    padding: 12px 24px;
    height: auto !important;
    background-color: #1976d2;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.2s;
}

.button:hover {
    background-color: #1565c0;
}

.button:disabled {
    background-color: #bbdefb;
    cursor: not-allowed;
}

.button.bg-secondary {
    background-color: #757575;
}

.button.bg-secondary:hover {
    background-color: #616161;
}

.sources-selection {
    margin-top: 1rem;
}

.selected-sources {
    margin-top: 1rem;
}

.source-item {
    display: flex;
    align-items: flex-start;
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 1rem;
    margin-bottom: 0.8rem;
    position: relative;
}

.source-type-icon {
    margin-right: 1rem;
    color: #555;
}

.source-content {
    flex: 1;
}

.source-title {
    font-weight: bold;
    margin-bottom: 0.3rem;
}

.source-description {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.source-details {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
    color: #777;
}

.source-link {
    color: #005691;
    text-decoration: none;
    font-size: 0.8rem;
    display: inline-flex;
    align-items: center;
}

.remove-source {
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
    font-size: 1.2rem;
    padding: 0;
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
}
</style>
