<template>
    <div class="lightbox-overlay" v-if="isOpen && imageUrl" @click="close">
        <div class="lightbox-content" @click.stop>
            <div class="lightbox-image-container">
                <img
                    :src="imageUrl"
                    :alt="imageAlt"
                    class="lightbox-image"
                    :style="{ transform: `scale(${zoomLevel})` }"
                />
            </div>
            <div class="lightbox-controls">
                <button
                    class="control-button"
                    @click="zoomOut"
                    :disabled="zoomLevel <= 1"
                >
                    <span>&#8722;</span>
                    <!-- Minus sign -->
                </button>
                <button class="control-button" @click="resetZoom">
                    <span>&#8634;</span>
                    <!-- Reset symbol -->
                </button>
                <button
                    class="control-button"
                    @click="zoomIn"
                    :disabled="zoomLevel >= 3"
                >
                    <span>&#43;</span>
                    <!-- Plus sign -->
                </button>
            </div>
            <div class="lightbox-caption" v-if="imageCaption">
                {{ imageCaption }}
            </div>
            <button class="lightbox-close" @click="close">×</button>
        </div>
    </div>
</template>

<script setup>
import { defineProps, defineEmits, ref } from "vue";

const props = defineProps({
    isOpen: {
        type: Boolean,
        required: true,
    },
    imageUrl: {
        type: [String, null],
        default: "",
    },
    imageAlt: {
        type: [String, null],
        default: "",
    },
    imageCaption: {
        type: [String, null],
        default: "",
    },
});

const emit = defineEmits(["close"]);
const zoomLevel = ref(1);

const close = () => {
    zoomLevel.value = 1; // Reset zoom when closing
    emit("close");
};

const zoomIn = () => {
    if (zoomLevel.value < 3) {
        zoomLevel.value += 0.25;
    }
};

const zoomOut = () => {
    if (zoomLevel.value > 1) {
        zoomLevel.value -= 0.25;
    }
};

const resetZoom = () => {
    zoomLevel.value = 1;
};
</script>

<style scoped>
.lightbox-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.9);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.lightbox-content {
    position: relative;
    max-width: 90%;
    max-height: 90%;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.lightbox-image-container {
    overflow: hidden;
    border: 2px solid white;
    background-color: rgba(0, 0, 0, 0.3);
    display: flex;
    justify-content: center;
    align-items: center;
}

.lightbox-image {
    max-width: 100%;
    max-height: 70vh;
    object-fit: contain;
    transition: transform 0.3s ease-out;
}

.lightbox-controls {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-top: 1rem;
}

.control-button {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.4);
    cursor: pointer;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 1.5rem;
    transition: background-color 0.2s;
}

.control-button:hover:not(:disabled) {
    background-color: rgba(255, 255, 255, 0.3);
}

.control-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.lightbox-caption {
    color: white;
    text-align: center;
    padding: 1rem;
    max-width: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    border-radius: 5px;
    margin-top: 1rem;
}

.lightbox-close {
    position: absolute;
    top: -30px;
    right: -30px;
    font-size: 2rem;
    color: white;
    background: none;
    border: none;
    cursor: pointer;
    width: 40px;
    height: 40px;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 50%;
}

.lightbox-close:hover {
    background-color: rgba(255, 255, 255, 0.2);
}
</style>
