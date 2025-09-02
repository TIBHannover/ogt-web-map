<template>
  <div class="gender-selector">
    <v-select
      v-model="selectedValue"
      :label="label"
      :items="genderOptions"
      item-title="label"
      item-value="id"
      :disabled="disabled"
      variant="outlined"
      clearable
      return-object
    >
      <template v-slot:append>
        <v-chip v-if="selectedValue" size="small" class="wikidata-id-chip" variant="outlined">
          {{ selectedValue.id }}
        </v-chip>
      </template>
    </v-select>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: [String, Object],
    default: null,
  },
  label: {
    type: String,
    default: 'Geschlecht',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue']);

// Predefined Wikidata gender options
const genderOptions = [
  { id: 'Q6581097', label: 'männlich' },
  { id: 'Q6581072', label: 'weiblich' },
  { id: 'Q1097630', label: 'Intersexualität' },
  { id: 'Q1052281', label: 'Transfrau' },
  { id: 'Q2449503', label: 'Transmann' },
  { id: 'Q48270', label: 'nichtbinär' },
];

// Computed property for the selected value
const selectedValue = computed({
  get: () => {
    if (!props.modelValue) return null;
    
    // If it's just an ID string, find the full object
    if (typeof props.modelValue === 'string') {
      return genderOptions.find(option => option.id === props.modelValue) || { id: props.modelValue, label: props.modelValue };
    }
    
    // If it's already an object, return it
    return props.modelValue;
  },
  set: (value) => {
    // When setting, just emit the ID string or null
    emit('update:modelValue', value ? value.id : null);
  }
});
</script>

<style scoped>
.wikidata-id-chip {
  font-size: 0.7rem;
  color: #666;
}
</style>