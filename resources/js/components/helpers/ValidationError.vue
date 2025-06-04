<template>
    <div
        v-if="show"
        class="validation-error mt-3"
        :class="customClass"
        :style="customStyle"
    >
        <v-icon v-if="showIcon" class="error-icon" icon="mdi-alert-circle" />
        <slot>
            <span>{{ message }}</span>
        </slot>
    </div>
</template>

<script setup>
import { defineProps } from "vue";

defineProps({
    show: {
        type: Boolean,
        default: true,
    },
    message: {
        type: String,
        default: "Dieses Feld ist erforderlich.",
    },
    customClass: {
        type: String,
        default: "",
    },
    customStyle: {
        type: Object,
        default: () => ({}),
    },
    showIcon: {
        type: Boolean,
        default: true,
    },
});
</script>

<style scoped>
.validation-error {
    color: red;
    margin-bottom: 1rem;
    padding: 0.5rem;
    background-color: rgba(255, 0, 0, 0.05);
    border-radius: 4px;
    border-left: 3px solid red;
    display: flex;
    align-items: center;
}

.error-icon {
    margin-right: 8px;
    font-size: 16px;
    color: red;
}
</style>
