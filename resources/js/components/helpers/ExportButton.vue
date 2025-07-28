<template>
    <v-btn
        @click="handleClick"
        :color="color"
        :variant="variant"
        :loading="isLoading"
        :class="{ 'loading-button': isLoading }"
    >
        <v-tooltip v-if="isLoading" activator="parent" location="top">
            Daten werden verarbeitet. Bitte warten Sie bis der Download
            abgeschlossen ist...
        </v-tooltip>
        <v-icon start>{{ icon }}</v-icon>
        <span v-if="isLoading">
            <v-icon class="loading-spinner mr-2">mdi-loading</v-icon>
            Daten werden verarbeitet...
        </span>
        <span v-else>{{ label }}</span>
    </v-btn>
</template>

<script setup>
import { defineProps, defineEmits } from "vue";

const props = defineProps({
    type: {
        type: String,
        required: true,
    },
    format: {
        type: String,
        required: true,
    },
    isLoading: {
        type: Boolean,
        default: false,
    },
    color: {
        type: String,
        default: "primary",
    },
    variant: {
        type: String,
        default: "",
    },
    icon: {
        type: String,
        default: "mdi-download",
    },
    label: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(["export"]);

const handleClick = () => {
    emit("export", { type: props.type, format: props.format });
};
</script>

<style scoped>
.loading-button {
    transition: all 0.3s;
}

.loading-spinner {
    animation: spin 2s linear infinite;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}
</style>
