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
            <!-- Text field that opens the dialog -->
            <v-text-field
                v-model="eventDateDisplay"
                variant="outlined"
                density="comfortable"
                class="date-field"
                bg-color="white"
                placeholder="Jahr, Monat oder Tag wählen"
                readonly
                @click="openDateDialog('point')"
                aria-label="Datum des Ereignisses auswählen"
                @focus="$emit('fieldFocus', 'time')"
                @blur="onFieldBlur"
                ref="eventDatePicker"
            >
                <template v-slot:prepend-inner>
                    <v-icon color="primary" class="mr-2">mdi-calendar</v-icon>
                </template>
                <template v-slot:append>
                    <v-chip
                        v-if="eventDatePrecision === 'year'"
                        size="small"
                        color="primary"
                        variant="outlined"
                        class="precision-chip"
                    >
                        Jahr
                    </v-chip>
                    <v-chip
                        v-if="eventDatePrecision === 'month'"
                        size="small"
                        color="primary"
                        variant="outlined"
                        class="precision-chip"
                    >
                        Monat
                    </v-chip>
                    <v-chip
                        v-if="eventDatePrecision === 'day'"
                        size="small"
                        color="primary"
                        variant="outlined"
                        class="precision-chip"
                    >
                        Tag
                    </v-chip>
                </template>
            </v-text-field>

            <DateQualifier
                id="event-date-qualifier"
                :model-value="eventDateQualifier"
                @update:model-value="$emit('update:eventDateQualifier', $event)"
            />
        </div>

        <!-- Zeitraum-Eingabe -->
        <div v-if="localEventTimeType === 'range'" class="time-range-inputs">
            <div class="form-group">
                <label>Beginn des Ereignisses</label>
                <!-- Text field that opens the dialog for start date -->
                <v-text-field
                    v-model="eventStartDateDisplay"
                    variant="outlined"
                    density="comfortable"
                    class="date-field"
                    bg-color="white"
                    placeholder="Jahr, Monat oder Tag wählen"
                    readonly
                    @click="openDateDialog('start')"
                    aria-label="Beginn des Ereignisses auswählen"
                    @focus="$emit('fieldFocus', 'time')"
                    @blur="onFieldBlur"
                    ref="eventStartDatePicker"
                >
                    <template v-slot:prepend-inner>
                        <v-icon color="primary" class="mr-2"
                            >mdi-calendar</v-icon
                        >
                    </template>
                    <template v-slot:append>
                        <v-chip
                            v-if="eventStartDatePrecision === 'year'"
                            size="small"
                            color="primary"
                            variant="outlined"
                            class="precision-chip"
                        >
                            Jahr
                        </v-chip>
                        <v-chip
                            v-if="eventStartDatePrecision === 'month'"
                            size="small"
                            color="primary"
                            variant="outlined"
                            class="precision-chip"
                        >
                            Monat
                        </v-chip>
                        <v-chip
                            v-if="eventStartDatePrecision === 'day'"
                            size="small"
                            color="primary"
                            variant="outlined"
                            class="precision-chip"
                        >
                            Tag
                        </v-chip>
                    </template>
                </v-text-field>

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
                <!-- Text field that opens the dialog for end date -->
                <v-text-field
                    v-model="eventEndDateDisplay"
                    variant="outlined"
                    density="comfortable"
                    class="date-field"
                    bg-color="white"
                    placeholder="Jahr, Monat oder Tag wählen"
                    readonly
                    @click="openDateDialog('end')"
                    aria-label="Ende des Ereignisses auswählen"
                    @focus="$emit('fieldFocus', 'time')"
                    @blur="onFieldBlur"
                    ref="eventEndDatePicker"
                >
                    <template v-slot:prepend-inner>
                        <v-icon color="primary" class="mr-2"
                            >mdi-calendar</v-icon
                        >
                    </template>
                    <template v-slot:append>
                        <v-chip
                            v-if="eventEndDatePrecision === 'year'"
                            size="small"
                            color="primary"
                            variant="outlined"
                            class="precision-chip"
                        >
                            Jahr
                        </v-chip>
                        <v-chip
                            v-if="eventEndDatePrecision === 'month'"
                            size="small"
                            color="primary"
                            variant="outlined"
                            class="precision-chip"
                        >
                            Monat
                        </v-chip>
                        <v-chip
                            v-if="eventEndDatePrecision === 'day'"
                            size="small"
                            color="primary"
                            variant="outlined"
                            class="precision-chip"
                        >
                            Tag
                        </v-chip>
                    </template>
                </v-text-field>

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
            message="Bitte gebe ein Datum für das Ereignis ein."
            showIcon
        />

        <ValidationError
            v-if="localEventTimeType === 'range'"
            :show="showValidationErrors && !isRangeValid"
            message="Bitte gebe sowohl Beginn- als auch Enddatum für den Zeitraum ein."
            showIcon
        />
    </div>

    <!-- Date Selection Dialog -->
    <v-dialog
        v-model="dateDialog"
        max-width="600px"
        persistent
        aria-modal="true"
        :aria-labelledby="'date-dialog-title'"
        role="dialog"
    >
        <v-card>
            <v-card-title class="text-primary pb-0" id="date-dialog-title">
                {{ dialogTitle }}
            </v-card-title>
            <v-card-text>
                <!-- Year Selection -->
                <div v-if="dialogStep === 'year'">
                    <div class="year-container">
                        <div
                            v-for="period in historicalPeriods"
                            :key="period.label"
                            class="period-section"
                        >
                            <div class="period-header">
                                {{ period.label }}
                            </div>
                            <div class="date-selection-grid year-grid">
                                <v-btn
                                    v-for="year in period.years"
                                    :key="year"
                                    variant="outlined"
                                    class="ma-1"
                                    size="large"
                                    :color="
                                        selectedYear === year ? 'primary' : ''
                                    "
                                    @click="selectYear(year)"
                                    :aria-label="`Jahr ${year} wählen`"
                                    :aria-selected="selectedYear === year"
                                >
                                    {{ year }}
                                </v-btn>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Month Selection -->
                <div
                    v-if="dialogStep === 'month'"
                    class="date-selection-grid month-grid"
                >
                    <v-btn
                        v-for="(month, index) in monthNames"
                        :key="index"
                        variant="outlined"
                        class="ma-1"
                        size="large"
                        :color="selectedMonth === index + 1 ? 'primary' : ''"
                        @click="selectMonth(index + 1)"
                        :aria-label="`Monat ${month} wählen`"
                        :aria-selected="selectedMonth === index + 1"
                    >
                        {{ month }}
                    </v-btn>
                </div>

                <!-- Day Selection -->
                <div
                    v-if="dialogStep === 'day'"
                    class="date-selection-grid days-grid"
                >
                    <div
                        class="day-header"
                        v-for="dayName in [
                            'Mo',
                            'Di',
                            'Mi',
                            'Do',
                            'Fr',
                            'Sa',
                            'So',
                        ]"
                        :key="dayName"
                    >
                        {{ dayName }}
                    </div>
                    <div
                        v-for="spacer in getFirstDayOffset"
                        :key="'spacer-' + spacer"
                        class="day-spacer"
                    ></div>
                    <v-btn
                        v-for="day in daysInSelectedMonth"
                        :key="day"
                        variant="outlined"
                        class="ma-1"
                        size="small"
                        :color="selectedDay === day ? 'primary' : ''"
                        @click="selectDay(day)"
                        :aria-label="`Tag ${day} wählen`"
                        :aria-selected="selectedDay === day"
                    >
                        {{ day }}
                    </v-btn>
                </div>
            </v-card-text>
            <v-card-actions>
                <v-btn
                    color="grey-darken-1"
                    variant="text"
                    @click="cancelDateSelection"
                    aria-label="Datumsauswahl abbrechen"
                >
                    Abbrechen
                </v-btn>
                <v-btn
                    v-if="dialogStep !== 'year'"
                    color="primary"
                    variant="text"
                    @click="goBackDialogStep"
                    :aria-label="
                        dialogStep === 'month'
                            ? 'Zurück zur Jahresauswahl'
                            : 'Zurück zur Monatsauswahl'
                    "
                >
                    {{
                        dialogStep === "month"
                            ? "Zurück zur Jahresauswahl"
                            : "Zurück zur Monatsauswahl"
                    }}
                </v-btn>
                <v-spacer></v-spacer>
                <v-btn
                    v-if="dialogStep !== 'year'"
                    color="primary"
                    variant="flat"
                    @click="skipCurrentStep"
                    :aria-label="
                        dialogStep === 'month'
                            ? 'Nur Jahr wählen'
                            : 'Nur Monat wählen'
                    "
                >
                    {{
                        dialogStep === "month"
                            ? "Nur Jahr wählen"
                            : "Nur Monat wählen"
                    }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
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

// Dialog and date selection state
const dateDialog = ref(false);
const dialogStep = ref("year"); // 'year', 'month', 'day'
const activeField = ref(null); // 'point', 'start', 'end'
const selectedYear = ref(null);
const selectedMonth = ref(null);
const selectedDay = ref(null);
const tempFormattedDate = ref(null);
// Using ref to manage UI state

// Display values for text fields
const eventDateDisplay = computed(() => {
    // Get the current precision to ensure display matches actual precision
    const precision = eventDatePrecision.value;
    return formatDateForDisplay(eventDate.value, precision);
});
const eventStartDateDisplay = computed(() => {
    const precision = eventStartDatePrecision.value;
    return formatDateForDisplay(eventStartDate.value, precision);
});
const eventEndDateDisplay = computed(() => {
    const precision = eventEndDatePrecision.value;
    return formatDateForDisplay(eventEndDate.value, precision);
});

// Dialog title based on current step
const dialogTitle = computed(() => {
    const fieldName =
        activeField.value === "point"
            ? "Ereignis"
            : activeField.value === "start"
              ? "Beginn des Ereignisses"
              : "Ende des Ereignisses";

    if (dialogStep.value === "year") {
        return `Jahr für ${fieldName} wählen`;
    } else if (dialogStep.value === "month") {
        return `Monat für ${fieldName} wählen (${selectedYear.value})`;
    } else {
        return `Tag für ${fieldName} wählen (${monthNames[selectedMonth.value - 1]} ${selectedYear.value})`;
    }
});

// List of month names
const monthNames = [
    "Januar",
    "Februar",
    "März",
    "April",
    "Mai",
    "Juni",
    "Juli",
    "August",
    "September",
    "Oktober",
    "November",
    "Dezember",
];

// Define historical periods
const historicalPeriods = computed(() => {
    const currentYear = new Date().getFullYear();
    return [
        {
            label: "Kriegszeit (1930-1945)",
            years: Array.from({ length: 16 }, (_, i) => i + 1930),
            start: 1930,
            end: 1945,
        },
        {
            label: "Nachkriegszeit (1946-1995)",
            years: Array.from({ length: 50 }, (_, i) => i + 1946),
            start: 1946,
            end: 1995,
        },
        {
            label: "Neuzeit (1996-heute)",
            years: Array.from(
                { length: currentYear - 1996 + 6 },
                (_, i) => i + 1996,
            ),
            start: 1996,
            end: currentYear + 5,
        },
    ];
});

// Calculate days in selected month
const daysInSelectedMonth = computed(() => {
    if (!selectedYear.value || !selectedMonth.value) return [];

    const lastDay = new Date(
        selectedYear.value,
        selectedMonth.value,
        0,
    ).getDate();
    return Array.from({ length: lastDay }, (_, i) => i + 1);
});

// Calculate first day of month offset (for proper calendar grid display)
const getFirstDayOffset = computed(() => {
    if (!selectedYear.value || !selectedMonth.value) return 0;

    // Get the day of week for the first day of the month (0-6, Sunday is 0)
    let firstDay = new Date(
        selectedYear.value,
        selectedMonth.value - 1,
        1,
    ).getDay();

    // Adjust for Monday as first day of week (0-6, Monday is 0)
    firstDay = firstDay === 0 ? 6 : firstDay - 1;

    return firstDay;
});

// Format a date for display based on precision
const formatDateForDisplay = (date, precision) => {
    if (!date) return "";

    try {
        // Handle different input types
        let year, month, day;

        if (date instanceof Date) {
            year = date.getFullYear();
            month = date.getMonth() + 1;
            day = date.getDate();
        } else if (typeof date === "string") {
            // Parse YYYY-MM-DD format
            if (date.includes("-")) {
                const parts = date.split("-");
                year = parseInt(parts[0]);
                month = parts.length > 1 ? parseInt(parts[1]) : null;
                day = parts.length > 2 ? parseInt(parts[2]) : null;
            }
            // Handle just year
            else if (/^\d{4}$/.test(date)) {
                year = parseInt(date);
                month = null;
                day = null;
            }
        }

        if (!year) return "";

        // Always respect the specified precision, regardless of available data
        if (precision === "year") {
            return `${year}`;
        } else if (precision === "month") {
            if (!month) return `${year}`;
            return `${monthNames[month - 1]} ${year}`;
        } else {
            // day precision
            if (!month) return `${year}`;
            if (!day) return `${monthNames[month - 1]} ${year}`;
            return `${day}. ${monthNames[month - 1]} ${year}`;
        }
    } catch (error) {
        console.error("Error formatting date:", error);
        return "";
    }
};

// Open the date selection dialog
const openDateDialog = (field) => {
    activeField.value = field;
    dialogStep.value = "year";

    // Initialize with existing values if present
    if (field === "point" && eventDate.value) {
        const parts = eventDate.value.split("-");
        selectedYear.value = parts.length > 0 ? parseInt(parts[0]) : null;

        // Only set month and day if the precision allows it
        if (eventDatePrecision.value !== "year") {
            selectedMonth.value = parts.length > 1 ? parseInt(parts[1]) : null;

            if (eventDatePrecision.value === "day") {
                selectedDay.value =
                    parts.length > 2 ? parseInt(parts[2]) : null;
            } else {
                selectedDay.value = null;
            }
        } else {
            selectedMonth.value = null;
            selectedDay.value = null;
        }
    } else if (field === "start" && eventStartDate.value) {
        const parts = eventStartDate.value.split("-");
        selectedYear.value = parts.length > 0 ? parseInt(parts[0]) : null;

        // Only set month and day if the precision allows it
        if (eventStartDatePrecision.value !== "year") {
            selectedMonth.value = parts.length > 1 ? parseInt(parts[1]) : null;

            if (eventStartDatePrecision.value === "day") {
                selectedDay.value =
                    parts.length > 2 ? parseInt(parts[2]) : null;
            } else {
                selectedDay.value = null;
            }
        } else {
            selectedMonth.value = null;
            selectedDay.value = null;
        }
    } else if (field === "end" && eventEndDate.value) {
        const parts = eventEndDate.value.split("-");
        selectedYear.value = parts.length > 0 ? parseInt(parts[0]) : null;

        // Only set month and day if the precision allows it
        if (eventEndDatePrecision.value !== "year") {
            selectedMonth.value = parts.length > 1 ? parseInt(parts[1]) : null;

            if (eventEndDatePrecision.value === "day") {
                selectedDay.value =
                    parts.length > 2 ? parseInt(parts[2]) : null;
            } else {
                selectedDay.value = null;
            }
        } else {
            selectedMonth.value = null;
            selectedDay.value = null;
        }
    } else {
        selectedYear.value = null;
        selectedMonth.value = null;
        selectedDay.value = null;
    }

    // Set dialog step based on current precision
    if (field === "point") {
        if (eventDatePrecision.value === "month" && selectedYear.value) {
            dialogStep.value = "month";
        } else if (
            eventDatePrecision.value === "day" &&
            selectedYear.value &&
            selectedMonth.value
        ) {
            dialogStep.value = "day";
        }
    } else if (field === "start") {
        if (eventStartDatePrecision.value === "month" && selectedYear.value) {
            dialogStep.value = "month";
        } else if (
            eventStartDatePrecision.value === "day" &&
            selectedYear.value &&
            selectedMonth.value
        ) {
            dialogStep.value = "day";
        }
    } else if (field === "end") {
        if (eventEndDatePrecision.value === "month" && selectedYear.value) {
            dialogStep.value = "month";
        } else if (
            eventEndDatePrecision.value === "day" &&
            selectedYear.value &&
            selectedMonth.value
        ) {
            dialogStep.value = "day";
        }
    }

    dateDialog.value = true;
    emit("fieldFocus", "time");

    // Scroll to the selected year if it exists
    if (selectedYear.value) {
        setTimeout(() => {
            const yearBtn = document.querySelector(
                `.year-grid .v-btn[aria-selected="true"]`,
            );
            if (yearBtn) {
                yearBtn.scrollIntoView({ behavior: "smooth", block: "center" });
            }
        }, 100);
    }
};

// Handle year selection
const selectYear = (year) => {
    selectedYear.value = year;
    dialogStep.value = "month";
};

// Handle month selection
const selectMonth = (month) => {
    selectedMonth.value = month;
    dialogStep.value = "day";
};

// Handle day selection
const selectDay = (day) => {
    selectedDay.value = day;
    applyDateSelection();
};

// Skip the current step (keep just year or month)
const skipCurrentStep = () => {
    if (dialogStep.value === "month") {
        // User wants to keep just the year
        // Force year precision regardless of previously selected values
        selectedMonth.value = null;
        selectedDay.value = null;
        // Explicitly set precision to year
        if (activeField.value === "point") {
            eventDatePrecision.value = "year";
        } else if (activeField.value === "start") {
            eventStartDatePrecision.value = "year";
        } else if (activeField.value === "end") {
            eventEndDatePrecision.value = "year";
        }
        applyDateSelection();
    } else if (dialogStep.value === "day") {
        // User wants to keep just month and year
        // Force month precision
        selectedDay.value = null;
        // Explicitly set precision to month
        if (activeField.value === "point") {
            eventDatePrecision.value = "month";
        } else if (activeField.value === "start") {
            eventStartDatePrecision.value = "month";
        } else if (activeField.value === "end") {
            eventEndDatePrecision.value = "month";
        }
        applyDateSelection();
    }
};

// Go back to previous dialog step
const goBackDialogStep = () => {
    if (dialogStep.value === "month") {
        dialogStep.value = "year";
    } else if (dialogStep.value === "day") {
        dialogStep.value = "month";
    }
};

// Cancel date selection
const cancelDateSelection = () => {
    dateDialog.value = false;
};

// Apply the selected date
const applyDateSelection = () => {
    let formattedValue;
    let precision;

    // Determine precision based on available selections
    if (selectedYear.value && !selectedMonth.value) {
        // Only year is selected
        formattedValue = `${selectedYear.value}`;
        precision = "year";
    } else if (
        selectedYear.value &&
        selectedMonth.value &&
        !selectedDay.value
    ) {
        // Year and month are selected
        formattedValue = `${selectedYear.value}-${selectedMonth.value.toString().padStart(2, "0")}`;
        precision = "month";
    } else if (selectedYear.value && selectedMonth.value && selectedDay.value) {
        // Full date is selected
        formattedValue = `${selectedYear.value}-${selectedMonth.value.toString().padStart(2, "0")}-${selectedDay.value.toString().padStart(2, "0")}`;
        precision = "day";
    } else if (selectedYear.value) {
        // Fallback - at least year is selected
        formattedValue = `${selectedYear.value}`;
        precision = "year";
    }

    // Store temporarily to handle in computed display value
    tempFormattedDate.value = formattedValue;

    // Update the appropriate field
    if (formattedValue) {
        if (activeField.value === "point") {
            eventDate.value = formattedValue;
            eventDatePrecision.value = precision;
            emit("update:eventDate", formattedValue);
            emit("update:eventDatePrecision", precision);
        } else if (activeField.value === "start") {
            eventStartDate.value = formattedValue;
            eventStartDatePrecision.value = precision;
            emit("update:eventStartDate", formattedValue);
            emit("update:eventStartDatePrecision", precision);
        } else if (activeField.value === "end") {
            eventEndDate.value = formattedValue;
            eventEndDatePrecision.value = precision;
            emit("update:eventEndDate", formattedValue);
            emit("update:eventEndDatePrecision", precision);
        }
    }

    // Close the dialog
    dateDialog.value = false;
};

// Handle date input with automatic precision detection
// This function is used when direct input is given or external changes happen
// Exported for API compatibility and potential future direct input handling
const handleDateInput = (value, field) => {
    if (!value) {
        emit(`update:${field}`, null);
        emit(`update:${field}Precision`, "day");
        return;
    }

    let formattedValue;
    let precision;

    try {
        // Handle string input (for text input mode)
        if (typeof value === "string") {
            // Check if it's a year (4 digits only)
            if (/^\d{4}$/.test(value)) {
                formattedValue = value;
                precision = "year";
            }
            // Check if it's a month (YYYY-MM format)
            else if (/^\d{4}-\d{2}$/.test(value)) {
                formattedValue = value;
                precision = "month";
            }
            // Check if it's a day (YYYY-MM-DD format)
            else if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
                formattedValue = value;
                precision = "day";
            }
            // Check if it's a month (MM.YYYY format)
            else if (/^\d{1,2}\.\d{4}$/.test(value)) {
                const [month, year] = value.split(".");
                formattedValue = `${year}-${month.padStart(2, "0")}`;
                precision = "month";
            }
            // Check if it's a day (DD.MM.YYYY format)
            else if (/^\d{1,2}\.\d{1,2}\.\d{4}$/.test(value)) {
                const [day, month, year] = value.split(".");
                formattedValue = `${year}-${month.padStart(2, "0")}-${day.padStart(2, "0")}`;
                precision = "day";
            } else {
                // Keep original value for other formats
                formattedValue = value;
                precision = "day";
            }
        } else if (value instanceof Date) {
            // Handle Date objects
            if (isNaN(value.getTime())) {
                console.warn("Invalid date object received");
                emit(`update:${field}`, null);
                return;
            }

            const year = value.getFullYear();
            formattedValue = `${year}`;
            precision = "year";
        } else {
            // For any other type, pass through
            formattedValue = value;
            precision = "day";
        }

        // Emit the updated value and precision
        emit(`update:${field}`, formattedValue);
        emit(`update:${field}Precision`, precision);
    } catch (error) {
        console.error(`Error processing date input for ${field}:`, error);
        emit(`update:${field}`, null);
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

// Keep watches for precision changes for UI visualization purposes
watch(eventDatePrecision, (newValue) => {
    emit("update:eventDatePrecision", newValue);
});

watch(eventStartDatePrecision, (newValue) => {
    emit("update:eventStartDatePrecision", newValue);
});

watch(eventEndDatePrecision, (newValue) => {
    emit("update:eventEndDatePrecision", newValue);
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
        console.log("TimeSelector: Updating bindings with current values");
        // If there are internal updates needed, they can happen here
    },
    handleDateInput, // Export the handleDateInput function for external use
});
</script>

<style scoped>
.container-section {
    width: 100%;
    margin-bottom: 2rem;
    background-color: #f5f5f5;
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

.precision-chip {
    font-size: 0.7rem;
    height: 20px !important;
    margin-right: 4px;
}

/* Responsive Styles */
@media (max-width: 600px) {
    .time-range-inputs {
        flex-direction: column;
    }

    .time-range-inputs .form-group:first-child {
        margin-right: 0;
    }

    .time-toggle {
        width: 100%;
    }

    .date-field-container {
        max-width: 100%;
    }

    .container-section {
        padding: 1rem 0.5rem;
    }
}

/* Date selection dialog styling */
.date-selection-container {
    max-height: 400px;
    overflow-y: auto;
    margin: 16px 0;
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 0, 0, 0.3) transparent;
}

.date-selection-container::-webkit-scrollbar {
    width: 6px;
}

.date-selection-container::-webkit-scrollbar-track {
    background: transparent;
}

.date-selection-container::-webkit-scrollbar-thumb {
    background-color: rgba(0, 0, 0, 0.3);
    border-radius: 3px;
}

.year-container {
    max-height: 400px;
    overflow-y: auto;
    margin: 16px 0;
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 0, 0, 0.3) transparent;
    padding: 0 4px;
}

.period-section {
    margin-bottom: 24px;
}

.year-container::-webkit-scrollbar {
    width: 6px;
}

.year-container::-webkit-scrollbar-track {
    background: transparent;
}

.year-container::-webkit-scrollbar-thumb {
    background-color: rgba(0, 0, 0, 0.3);
    border-radius: 3px;
}

.period-header {
    font-weight: 500;
    padding: 12px 8px;
    background-color: rgb(245, 245, 245);
    margin-bottom: 16px;
    border-radius: 4px;
    position: sticky;
    top: 0;
    z-index: 1;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.date-selection-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px;
    padding: 0 12px;
}

.month-grid {
    grid-template-columns: repeat(3, 1fr);
}

.year-grid {
    padding-right: 8px;
}

.days-grid {
    grid-template-columns: repeat(7, 1fr);
    grid-auto-rows: 40px;
    gap: 4px;
    padding: 0 8px;
}

.day-header {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 500;
    color: rgba(0, 0, 0, 0.6);
    height: 32px;
}

.day-spacer {
    min-height: 32px;
}

@media (max-width: 400px) {
    .date-selection-grid {
        grid-template-columns: repeat(3, 1fr);
    }

    .month-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .days-grid {
        grid-auto-rows: 36px;
        gap: 4px;
    }
}
</style>
