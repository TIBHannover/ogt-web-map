<template>
    <div class="time-section container-section">
        <div class="section-header">
            <label class="section-label">Zeitpunkt / Zeitraum</label>
            <button
                type="button"
                class="info-icon"
                @click="$emit('infoButtonClick', 'time')"
            >
                <v-icon>mdi-information-variant</v-icon>
            </button>
        </div>
        <div class="time-type-selection">
            <v-btn-toggle
                v-model="localEventTimeType"
                mandatory
                density="comfortable"
                color="primary"
                class="time-toggle"
            >
                <v-btn value="point">
                    <v-icon start>mdi-clock</v-icon>
                    Zeitpunkt
                </v-btn>
                <v-btn value="range">
                    <v-icon start>mdi-calendar-range</v-icon>
                    Zeitraum
                </v-btn>
            </v-btn-toggle>
        </div>

        <!-- Zeitpunkt-Eingabe -->
        <div
            v-if="localEventTimeType === 'point'"
            class="form-group date-field-container"
        >
            <label for="">Datum des Ereignisses</label>
            <v-text-field
                v-model="eventDate"
                type="date"
                variant="outlined"
                density="comfortable"
                class="date-field"
                bg-color="white"
                :rules="[(v) => !!v || 'Datum ist erforderlich']"
                required
                @focus="$emit('fieldFocus', 'time')"
                @blur="onFieldBlur"
                @update:model-value="$emit('update:eventDate', $event)"
            ></v-text-field>
        </div>

        <!-- Zeitraum-Eingabe -->
        <div v-if="localEventTimeType === 'range'" class="time-range-inputs">
            <div class="form-group">
                <label>Beginn des Ereignisses</label>
                <v-text-field
                    v-model="eventStartDate"
                    type="date"
                    variant="outlined"
                    density="comfortable"
                    class="date-field"
                    bg-color="white"
                    :rules="[(v) => !!v || 'Beginn-Datum ist erforderlich']"
                    required
                    @focus="$emit('fieldFocus', 'time')"
                    @blur="onFieldBlur"
                    @update:model-value="$emit('update:eventStartDate', $event)"
                ></v-text-field>
            </div>
            <div class="form-group">
                <label>Ende des Ereignisses</label>
                <v-text-field
                    v-model="eventEndDate"
                    type="date"
                    variant="outlined"
                    density="comfortable"
                    class="date-field"
                    bg-color="white"
                    :rules="[(v) => !!v || 'End-Datum ist erforderlich']"
                    required
                    @focus="$emit('fieldFocus', 'time')"
                    @blur="onFieldBlur"
                    @update:model-value="$emit('update:eventEndDate', $event)"
                ></v-text-field>
            </div>
        </div>
        <ValidationError
            v-if="localEventTimeType === 'point'"
            :show="showValidationErrors && !isPointValid"
            message="Bitte geben Sie ein Datum für das Ereignis ein."
            showIcon
        />

        <ValidationError
            v-if="localEventTimeType === 'range'"
            :show="showValidationErrors && !isRangeValid"
            message="Bitte geben Sie sowohl Beginn- als auch Enddatum für den Zeitraum ein."
            showIcon
        />
    </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import ValidationError from "../../helpers/ValidationError.vue";
import { useValidation } from "../../../composables/useValidation";

const props = defineProps({
    eventTimeType: {
        type: String,
        default: "point", // 'point' oder 'range'
    },
    eventDate: {
        type: String,
        default: null,
    },
    eventStartDate: {
        type: String,
        default: null,
    },
    eventEndDate: {
        type: String,
        default: null,
    },
    required: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits([
    "update:eventTimeType",
    "update:eventDate",
    "update:eventStartDate",
    "update:eventEndDate",
    "fieldFocus",
    "infoButtonClick",
    "validation",
]);

// Local state for v-btn-toggle
const localEventTimeType = ref(props.eventTimeType);

// Local state for date fields
const eventDate = ref(props.eventDate);
const eventStartDate = ref(props.eventStartDate);
const eventEndDate = ref(props.eventEndDate);

// Computed helpers for validation
const isPointValid = computed(() => !!eventDate.value);
const isRangeValid = computed(
    () => !!eventStartDate.value && !!eventEndDate.value,
);

// Validation function
const validateFields = () => {
    if (localEventTimeType.value === "point") {
        return isPointValid.value;
    } else {
        // range mode
        return isRangeValid.value;
    }
};

// Use the validation composable
const { isValid, showValidationErrors, validate, showErrors } = useValidation(
    validateFields,
    {
        emit,
        watchCallback: () => [
            eventDate.value,
            eventStartDate.value,
            eventEndDate.value,
            localEventTimeType.value,
        ],
    },
);

// Watch for changes to local state and emit events
watch(localEventTimeType, (newValue) => {
    emit("update:eventTimeType", newValue);
    // Reset validation errors when switching between time types
    showValidationErrors.value = false;
});

watch(eventDate, (newValue) => {
    emit("update:eventDate", newValue);
});

watch(eventStartDate, (newValue) => {
    emit("update:eventStartDate", newValue);
});

watch(eventEndDate, (newValue) => {
    emit("update:eventEndDate", newValue);
});

// Watch for prop changes to update local state
watch(
    () => props.eventTimeType,
    (newValue) => {
        localEventTimeType.value = newValue;
    },
);

watch(
    () => props.eventDate,
    (newValue) => {
        eventDate.value = newValue;
    },
);

watch(
    () => props.eventStartDate,
    (newValue) => {
        eventStartDate.value = newValue;
    },
);

watch(
    () => props.eventEndDate,
    (newValue) => {
        eventEndDate.value = newValue;
    },
);

// Methods to handle field interaction
const onFieldBlur = () => {
    // Show validation errors if fields are left empty
    if (!isValid.value) {
        showValidationErrors.value = true;
    }
};

// Export the validation status for parent components
defineExpose({
    isValid,
    validate,
    showValidationErrors: showErrors,
});
</script>

<style scoped>
.container-section {
    width: 100%;
    margin-bottom: 2rem;
    background-color: var(--v-surface-variant);
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
}

.section-label {
    display: block;
    font-weight: 500;
    font-size: 1.1rem;
    margin-bottom: 0;
}

.section-label::after {
    content: "*";
    color: red;
    margin-left: 3px;
}

.time-type-selection {
    margin-bottom: 2rem;
}

.time-toggle {
    width: 100%;
    max-width: 400px;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
}

.time-toggle .v-btn {
    flex: 1;
    justify-content: center;
    height: 44px;
    font-weight: 500;
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

.form-group {
    margin-bottom: 1.5rem;
    width: 100%;
}

.date-field {
    width: 100%;
}

.date-field :deep(.v-field__input) {
    padding-top: 6px;
    min-height: 44px;
    font-size: 1rem;
}

.date-field :deep(.v-field__outline) {
    color: rgba(0, 0, 0, 0.12);
}

.date-field :deep(.v-field--focused .v-field__outline) {
    color: var(--v-primary-base, #1976d2);
}

.date-field :deep(input[type="date"]) {
    font-family: inherit;
    font-size: 1rem;
    color: rgba(0, 0, 0, 0.87);
}

/* Visual indicator for required fields */
.form-group label::after {
    content: "*";
    color: red;
    margin-left: 3px;
}

/* Spezielle Stylings für den Zeitraum */
.time-range-inputs {
    display: flex;
    gap: 1rem;
    width: 100%;
}

.time-range-inputs .form-group {
    flex: 1;
    margin-bottom: 0;
}
input.v-field__input {
    padding: 0.75rem 1rem;
    width: 100%;
    background-color: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}

/* Responsive Styles */
@media (max-width: 600px) {
    .time-range-inputs {
        flex-direction: column;
        gap: 1.5rem;
    }

    .time-range-inputs .form-group:first-child {
        margin-bottom: 0.5rem;
    }

    .time-toggle {
        max-width: 100%;
    }

    .date-field-container {
        max-width: 100%;
    }

    .container-section {
        padding: 1.25rem 1rem;
    }
}
</style>
