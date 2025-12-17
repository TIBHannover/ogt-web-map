<template>
    <div class="qualifier-selector">
        <select
            :id="id"
            :value="modelValue"
            @change="$emit('update:modelValue', $event.target.value)"
            class="qualifier-select"
        >
            <option value="">Genau</option>
            <option value="Q10">Circa</option>
            <option value="Q1790780">Weniger als</option>
            <option value="Q1790843">Mehr als</option>
        </select>
        <div class="select-arrow"></div>
    </div>
</template>

<script setup>
defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    id: {
        type: String,
        required: true,
    },
});

defineEmits(["update:modelValue"]);
</script>

<style scoped>
.qualifier-selector {
    width: 100%;
    margin-top: 0.25rem;
    position: relative;
}

.qualifier-select {
    width: 100%;
    padding: 0.5rem 2rem 0.5rem 0.75rem;
    background-color: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    cursor: pointer;
}

.qualifier-select:focus {
    outline: none;
    border-color: #1976d2;
    box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.25);
}

.select-arrow {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 0;
    height: 0;
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-top: 6px solid #555;
    pointer-events: none;
}

.qualifier-select:hover {
    border-color: #1976d2;
}

.qualifier-select:hover + .select-arrow {
    border-top-color: #1976d2;
}
</style>
