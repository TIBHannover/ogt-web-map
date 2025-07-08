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
            <div class="precision-selector">
                <v-btn-toggle
                    v-model="eventDatePrecision"
                    mandatory
                    density="comfortable"
                    color="primary"
                    class="precision-toggle"
                >
                    <v-btn value="day" size="small">
                        <v-icon size="small">mdi-calendar-day</v-icon>
                        Tag
                    </v-btn>
                    <v-btn value="month" size="small">
                        <v-icon size="small">mdi-calendar-month</v-icon>
                        Monat
                    </v-btn>
                    <v-btn value="year" size="small">
                        <v-icon size="small">mdi-calendar-year</v-icon>
                        Jahr
                    </v-btn>
                </v-btn-toggle>
            </div>
            
            <!-- Tag-Präzision -->
            <v-text-field
                v-if="eventDatePrecision === 'day'"
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
            
            <!-- Monat-Präzision -->
            <v-text-field
                v-if="eventDatePrecision === 'month'"
                v-model="eventDate"
                type="month"
                variant="outlined"
                density="comfortable"
                class="date-field"
                bg-color="white"
                :rules="[(v) => !!v || 'Monat ist erforderlich']"
                required
                @focus="$emit('fieldFocus', 'time')"
                @blur="onFieldBlur"
                @update:model-value="$emit('update:eventDate', $event)"
            ></v-text-field>
            
            <!-- Jahr-Präzision -->
            <v-text-field
                v-if="eventDatePrecision === 'year'"
                v-model="eventDate"
                type="number"
                variant="outlined"
                density="comfortable"
                class="date-field"
                bg-color="white"
                :rules="[
                    (v) => !!v || 'Jahr ist erforderlich',
                    (v) => (v && v.toString().length === 4) || 'Bitte geben Sie ein gültiges Jahr ein'
                ]"
                required
                min="1000"
                max="2100"
                placeholder="JJJJ"
                @focus="$emit('fieldFocus', 'time')"
                @blur="onFieldBlur"
                @update:model-value="handleYearInput($event, 'eventDate')"
            ></v-text-field>
            
            <DateQualifier
                id="event-date-qualifier"
                :model-value="eventDateQualifier"
                @update:model-value="
                    $emit('update:eventDateQualifier', $event)
                "
            />
        </div>

        <!-- Zeitraum-Eingabe -->
        <div v-if="localEventTimeType === 'range'" class="time-range-inputs">
            <div class="form-group">
                <label>Beginn des Ereignisses</label>
                <div class="precision-selector">
                    <v-btn-toggle
                        v-model="eventStartDatePrecision"
                        mandatory
                        density="comfortable"
                        color="primary"
                        class="precision-toggle"
                    >
                        <v-btn value="day" size="small">
                            <v-icon size="small">mdi-calendar-day</v-icon>
                            Tag
                        </v-btn>
                        <v-btn value="month" size="small">
                            <v-icon size="small">mdi-calendar-month</v-icon>
                            Monat
                        </v-btn>
                        <v-btn value="year" size="small">
                            <v-icon size="small">mdi-calendar-year</v-icon>
                            Jahr
                        </v-btn>
                    </v-btn-toggle>
                </div>
                
                <!-- Tag-Präzision -->
                <v-text-field
                    v-if="eventStartDatePrecision === 'day'"
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
                
                <!-- Monat-Präzision -->
                <v-text-field
                    v-if="eventStartDatePrecision === 'month'"
                    v-model="eventStartDate"
                    type="month"
                    variant="outlined"
                    density="comfortable"
                    class="date-field"
                    bg-color="white"
                    :rules="[(v) => !!v || 'Beginn-Monat ist erforderlich']"
                    required
                    @focus="$emit('fieldFocus', 'time')"
                    @blur="onFieldBlur"
                    @update:model-value="$emit('update:eventStartDate', $event)"
                ></v-text-field>
                
                <!-- Jahr-Präzision -->
                <v-text-field
                    v-if="eventStartDatePrecision === 'year'"
                    v-model="eventStartDate"
                    type="number"
                    variant="outlined"
                    density="comfortable"
                    class="date-field"
                    bg-color="white"
                    :rules="[
                        (v) => !!v || 'Beginn-Jahr ist erforderlich',
                        (v) => (v && v.toString().length === 4) || 'Bitte geben Sie ein gültiges Jahr ein'
                    ]"
                    required
                    min="1000"
                    max="2100"
                    placeholder="JJJJ"
                    @focus="$emit('fieldFocus', 'time')"
                    @blur="onFieldBlur"
                    @update:model-value="handleYearInput($event, 'eventStartDate')"
                ></v-text-field>
                
                <DateQualifier
                    id="event-start-date-qualifier"
                    :model-value="eventStartDateQualifier"
                    @update:model-value="
                        $emit('update:eventStartDateQualifier', $event)
                    "
                />
            </div>
            <div class="form-group">
                <label>Ende des Ereignisses</label>
                <div class="precision-selector">
                    <v-btn-toggle
                        v-model="eventEndDatePrecision"
                        mandatory
                        density="comfortable"
                        color="primary"
                        class="precision-toggle"
                    >
                        <v-btn value="day" size="small">
                            <v-icon size="small">mdi-calendar-day</v-icon>
                            Tag
                        </v-btn>
                        <v-btn value="month" size="small">
                            <v-icon size="small">mdi-calendar-month</v-icon>
                            Monat
                        </v-btn>
                        <v-btn value="year" size="small">
                            <v-icon size="small">mdi-calendar-year</v-icon>
                            Jahr
                        </v-btn>
                    </v-btn-toggle>
                </div>
                
                <!-- Tag-Präzision -->
                <v-text-field
                    v-if="eventEndDatePrecision === 'day'"
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
                
                <!-- Monat-Präzision -->
                <v-text-field
                    v-if="eventEndDatePrecision === 'month'"
                    v-model="eventEndDate"
                    type="month"
                    variant="outlined"
                    density="comfortable"
                    class="date-field"
                    bg-color="white"
                    :rules="[(v) => !!v || 'End-Monat ist erforderlich']"
                    required
                    @focus="$emit('fieldFocus', 'time')"
                    @blur="onFieldBlur"
                    @update:model-value="$emit('update:eventEndDate', $event)"
                ></v-text-field>
                
                <!-- Jahr-Präzision -->
                <v-text-field
                    v-if="eventEndDatePrecision === 'year'"
                    v-model="eventEndDate"
                    type="number"
                    variant="outlined"
                    density="comfortable"
                    class="date-field"
                    bg-color="white"
                    :rules="[
                        (v) => !!v || 'End-Jahr ist erforderlich',
                        (v) => (v && v.toString().length === 4) || 'Bitte geben Sie ein gültiges Jahr ein'
                    ]"
                    required
                    min="1000"
                    max="2100"
                    placeholder="JJJJ"
                    @focus="$emit('fieldFocus', 'time')"
                    @blur="onFieldBlur"
                    @update:model-value="handleYearInput($event, 'eventEndDate')"
                ></v-text-field>
                
                <DateQualifier
                    id="event-end-date-qualifier"
                    :model-value="eventEndDateQualifier"
                    @update:model-value="
                        $emit('update:eventEndDateQualifier', $event)
                    "
                />
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
import DateQualifier from "./common/DateQualifier.vue";
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
    eventDateQualifier: {
        type: String,
        default: "",
    },
    eventStartDateQualifier: {
        type: String,
        default: "",
    },
    eventEndDateQualifier: {
        type: String,
        default: "",
    },
    eventDatePrecision: {
        type: String,
        default: "day", // 'day', 'month', or 'year'
    },
    eventStartDatePrecision: {
        type: String,
        default: "day", // 'day', 'month', or 'year'
    },
    eventEndDatePrecision: {
        type: String,
        default: "day", // 'day', 'month', or 'year'
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
    "update:eventDateQualifier",
    "update:eventStartDateQualifier",
    "update:eventEndDateQualifier",
    "update:eventDatePrecision",
    "update:eventStartDatePrecision",
    "update:eventEndDatePrecision",
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
const eventDateQualifier = ref(props.eventDateQualifier);
const eventStartDateQualifier = ref(props.eventStartDateQualifier);
const eventEndDateQualifier = ref(props.eventEndDateQualifier);

// Local state for precision toggles
const eventDatePrecision = ref(props.eventDatePrecision);
const eventStartDatePrecision = ref(props.eventStartDatePrecision);
const eventEndDatePrecision = ref(props.eventEndDatePrecision);

// Handle year input (format as YYYY)
const handleYearInput = (value, field) => {
    if (value && value.toString().length === 4) {
        // Format as YYYY
        emit(`update:${field}`, value.toString());
    } else {
        emit(`update:${field}`, value ? value.toString() : null);
    }
};

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

watch(eventDateQualifier, (newValue) => {
    emit("update:eventDateQualifier", newValue);
});

watch(eventStartDateQualifier, (newValue) => {
    emit("update:eventStartDateQualifier", newValue);
});

watch(eventEndDateQualifier, (newValue) => {
    emit("update:eventEndDateQualifier", newValue);
});

// Watch for precision changes
watch(eventDatePrecision, (newValue) => {
    emit("update:eventDatePrecision", newValue);
    // Clear the date value when changing precision to prevent format issues
    if (newValue === "year" && eventDate.value && eventDate.value.includes("-")) {
        const year = eventDate.value.split("-")[0];
        eventDate.value = year;
        emit("update:eventDate", year);
    }
});

watch(eventStartDatePrecision, (newValue) => {
    emit("update:eventStartDatePrecision", newValue);
    // Clear the date value when changing precision to prevent format issues
    if (newValue === "year" && eventStartDate.value && eventStartDate.value.includes("-")) {
        const year = eventStartDate.value.split("-")[0];
        eventStartDate.value = year;
        emit("update:eventStartDate", year);
    }
});

watch(eventEndDatePrecision, (newValue) => {
    emit("update:eventEndDatePrecision", newValue);
    // Clear the date value when changing precision to prevent format issues
    if (newValue === "year" && eventEndDate.value && eventEndDate.value.includes("-")) {
        const year = eventEndDate.value.split("-")[0];
        eventEndDate.value = year;
        emit("update:eventEndDate", year);
    }
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

watch(
    () => props.eventDateQualifier,
    (newValue) => {
        eventDateQualifier.value = newValue;
    },
);

watch(
    () => props.eventStartDateQualifier,
    (newValue) => {
        eventStartDateQualifier.value = newValue;
    },
);

watch(
    () => props.eventEndDateQualifier,
    (newValue) => {
        eventEndDateQualifier.value = newValue;
    },
);

watch(
    () => props.eventDatePrecision,
    (newValue) => {
        eventDatePrecision.value = newValue;
    },
);

watch(
    () => props.eventStartDatePrecision,
    (newValue) => {
        eventStartDatePrecision.value = newValue;
    },
);

watch(
    () => props.eventEndDatePrecision,
    (newValue) => {
        eventEndDatePrecision.value = newValue;
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
    updateBindings: () => {
        // This method can be called by the parent to ensure form values are current
        console.log('TimeSelector: Updating bindings with current values');
        // If there are internal updates needed, they can happen here
    }
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

.date-field :deep(input[type="date"]), 
.date-field :deep(input[type="month"]), 
.date-field :deep(input[type="number"]) {
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

/* Precision toggle styling */
.precision-selector {
    margin-bottom: 0.75rem;
}

.precision-toggle {
    width: 100%;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.precision-toggle .v-btn {
    flex: 1;
    font-size: 0.8rem;
    height: 36px;
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