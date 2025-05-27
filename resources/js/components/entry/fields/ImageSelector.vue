<template>
    <div class="container-section image-section">
        <div class="section-header">
            <label class="section-label">Bild:</label>
            <button
                type="button"
                class="info-icon"
                @click="$emit('infoButtonClick', 'image')"
            >
                <v-icon>mdi-information-variant</v-icon>
            </button>
        </div>
        <div class="image-selection">
            <v-button
                type="button"
                class="button bg-secondary"
                @click="openImageSelector"
                @focus="$emit('fieldFocus', 'image')"
            >
                <v-icon class="me-1">mdi-plus</v-icon>
                {{
                    imageUrl &&
                    typeof imageUrl === "string" &&
                    imageUrl.trim() !== ""
                        ? "Bild ändern"
                        : "Bild aus Wikimedia Commons auswählen"
                }}
            </v-button>

            <div
                v-if="
                    imageUrl &&
                    typeof imageUrl === 'string' &&
                    imageUrl.trim() !== ''
                "
                class="selected-image"
            >
                <img
                    :src="imageUrl"
                    :alt="imageFilename || ''"
                    class="preview-image"
                    @click="openLightbox"
                    style="cursor: pointer"
                />
                <div class="click-hint">Klicken zum Vergrößern</div>
                <div class="image-details">
                    <span class="image-filename">{{ imageFilename }}</span>
                    <button
                        type="button"
                        class="remove-image"
                        @click="removeImage"
                    >
                        x
                    </button>
                </div>
            </div>
        </div>

        <!-- Dialog für Bildauswahl -->
        <v-dialog v-model="imageDialogVisible" max-width="900px">
            <WikimediaImageSelector
                @select="selectImage"
                @close="closeImageDialog"
            />
        </v-dialog>

        <!-- Lightbox für Bildanzeige -->
        <lightbox
            :is-open="lightboxOpen"
            :image-url="imageUrl || ''"
            :image-alt="imageFilename || ''"
            :image-caption="imageFilename || ''"
            @close="lightboxOpen = false"
        />
    </div>
</template>

<script setup>
import { ref, defineProps, defineEmits } from "vue";
import Lightbox from "../../helpers/Lightbox.vue";
import WikimediaImageSelector from "./WikimediaImageSelector.vue";

const props = defineProps({
    imageUrl: {
        type: String,
        default: null,
    },
    imageFilename: {
        type: String,
        default: null,
    },
});

const emit = defineEmits([
    "update:imageUrl",
    "update:imageFilename",
    "fieldFocus",
    "infoButtonClick",
]);

const imageDialogVisible = ref(false);
const lightboxOpen = ref(false);

const openImageSelector = () => {
    imageDialogVisible.value = true;
};

const closeImageDialog = () => {
    imageDialogVisible.value = false;
};

const selectImage = (image) => {
    emit("update:imageUrl", image.url);
    emit("update:imageFilename", image.filename);
    imageDialogVisible.value = false;
};

const removeImage = () => {
    emit("update:imageUrl", null);
    emit("update:imageFilename", null);
};

const openLightbox = () => {
    if (
        props.imageUrl &&
        typeof props.imageUrl === "string" &&
        props.imageUrl.trim() !== ""
    ) {
        lightboxOpen.value = true;
    }
};
</script>

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

.image-selection {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
}

.button {
    padding: 12px 24px;
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

.selected-image {
    width: 100%;
    max-width: 400px;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
    background-color: white;
}

.preview-image {
    width: 100%;
    height: auto;
    max-height: 200px;
    object-fit: contain;
    transition:
        transform 0.2s,
        filter 0.2s;
}

.preview-image:hover {
    transform: scale(1.03);
    filter: brightness(1.1);
}

.image-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    background-color: #f5f5f5;
    border-top: 1px solid #ddd;
}

.click-hint {
    text-align: center;
    font-size: 0.8rem;
    color: #555;
    padding: 0.2rem 0;
    background-color: #f9f9f9;
    border-top: 1px dashed #eee;
}

.image-filename {
    font-size: 0.9rem;
    color: #666;
    max-width: 320px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.remove-image {
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
    font-size: 1.2rem;
    padding: 0 0.5rem;
}
</style>
