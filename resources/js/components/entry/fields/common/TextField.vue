<template>
  <div class="text-field-selector">
    <v-text-field
      v-model="localValue"
      :label="label"
      :placeholder="placeholder"
      :rules="rules"
      :required="required"
      :hint="hint"
      :persistent-hint="persistentHint"
      :clearable="clearable"
      :disabled="disabled"
      :type="type"
    ></v-text-field>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  label: {
    type: String,
    required: true
  },
  placeholder: {
    type: String,
    default: ''
  },
  rules: {
    type: Array,
    default: () => []
  },
  required: {
    type: Boolean,
    default: false
  },
  hint: {
    type: String,
    default: ''
  },
  persistentHint: {
    type: Boolean,
    default: false
  },
  clearable: {
    type: Boolean,
    default: true
  },
  disabled: {
    type: Boolean,
    default: false
  },
  type: {
    type: String,
    default: 'text'
  }
});

const emit = defineEmits(['update:modelValue']);

const localValue = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
});

watch(() => props.modelValue, (newValue) => {
  if (newValue !== localValue.value) {
    localValue.value = newValue;
  }
});
</script>

<style scoped>
.text-field-selector {
  width: 100%;
  margin-bottom: 8px;
}
</style>