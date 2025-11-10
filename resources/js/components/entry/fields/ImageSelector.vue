<template>
    <div class="container-section image-section">
        <div class="section-header">
            <label class="section-label">Bild aus Wikimedia-Commons:</label>
            <button
                type="button"
                class="info-icon"
                @click="$emit('infoButtonClick', 'image')"
            >
                <v-icon>mdi-information-variant</v-icon>
            </button>
        </div>
        <div class="image-selection">
            <v-btn
                type="button"
                class="btn btn-primary btn-block"
                @click="openImageSelector"
                @focus="$emit('fieldFocus', 'image')"
            >
                <v-icon start class="me-1">mdi-camera</v-icon>
                {{
                    imageUrl &&
                    typeof imageUrl === "string" &&
                    imageUrl.trim() !== ""
                        ? "Bild ändern"
                        : "Bild auswählen"
                }}
            </v-btn>

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
