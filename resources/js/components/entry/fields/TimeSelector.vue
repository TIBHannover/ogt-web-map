<template>
    <div class="time-section container-section">
        <div class="section-header">
            <label class="section-label">Zeitpunkt / Zeitraum:</label>
            <button
                type="button"
                class="info-icon"
                @click="$emit('infoButtonClick', 'time')"
            >
                <v-icon>mdi-information-variant</v-icon>
            </button>
        </div>
        <div class="time-type-selection">
            <div class="form-group">
                <input
                    type="radio"
                    id="time-point"
                    name="event-time-type"
                    value="point"
                    :checked="eventTimeType === 'point'"
                    @change="$emit('update:eventTimeType', 'point')"
                />
                <label for="time-point">Zeitpunkt</label>
            </div>
            <div class="form-group">
                <input
                    type="radio"
                    id="time-range"
                    name="event-time-type"
                    value="range"
                    :checked="eventTimeType === 'range'"
                    @change="$emit('update:eventTimeType', 'range')"
                />
                <label for="time-range">Zeitraum</label>
            </div>
        </div>

        <!-- Zeitpunkt-Eingabe -->
        <div
            v-if="eventTimeType === 'point'"
            class="form-group"
        >
            <label for="event-date">Datum des Ereignisses</label>
            <input
                type="date"
                id="event-date"
                :value="eventDate"
                @input="$emit('update:eventDate', $event.target.value)"
                @focus="$emit('fieldFocus', 'time')"
            />
        </div>

        <!-- Zeitraum-Eingabe -->
        <div
            v-if="eventTimeType === 'range'"
            class="time-range-inputs"
        >
            <div class="form-group">
                <label for="event-start-date">Beginn des Ereignisses</label>
                <input
                    type="date"
                    id="event-start-date"
                    :value="eventStartDate"
                    @input="$emit('update:eventStartDate', $event.target.value)"
                    @focus="$emit('fieldFocus', 'time')"
                />
            </div>
            <div class="form-group">
                <label for="event-end-date">Ende des Ereignisses</label>
                <input
                    type="date"
                    id="event-end-date"
                    :value="eventEndDate"
                    @input="$emit('update:eventEndDate', $event.target.value)"
                    @focus="$emit('fieldFocus', 'time')"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    eventTimeType: {
        type: String,
        default: 'point' // 'point' oder 'range'
    },
    eventDate: {
        type: String,
        default: null
    },
    eventStartDate: {
        type: String,
        default: null
    },
    eventEndDate: {
        type: String,
        default: null
    }
});

defineEmits([
    'update:eventTimeType',
    'update:eventDate',
    'update:eventStartDate',
    'update:eventEndDate',
    'fieldFocus',
    'infoButtonClick'
]);
</script>

<style scoped>
.container-section {
    width: 100%;
    margin-bottom: 1.5rem;
    background-color: #f5f5f5;
    padding: 1rem;
    border-radius: 4px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.section-label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
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

.time-type-selection {
    display: flex;
    gap: 2rem;
    margin-bottom: 1rem;
}

.time-type-selection .form-group {
    display: flex;
    align-items: center;
    margin-bottom: 0;
}

.time-type-selection .form-group input {
    width: auto;
    margin-right: 0.5rem;
}

.form-group {
    margin-bottom: 1rem;
    width: 100%;
}

.form-group label {
    display: block;
    margin-bottom: 0.25rem;
}

.form-group input {
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

.form-group input:focus {
    outline: none;
    border-color: #1976d2;
    box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.25);
}

.time-range-inputs {
    display: flex;
    gap: 1rem;
    width: 100%;
}

.time-range-inputs .form-group {
    flex: 1;
}
</style>