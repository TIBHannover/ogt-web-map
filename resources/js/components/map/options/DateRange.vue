<template>
    <v-container fluid>
        <v-text-field
            class="disablePointerEvents"
            id="dateRangeText"
            label="Aktuell ausgewählter Zeitraum"
            prepend-icon="mdi-calendar"
            readonly
            v-model="dateRangeText"
        ></v-text-field>
        <v-radio-group
            dense
            label="Wähle einen alternativen Zeitraum aus:"
            mandatory
            prepend-icon="mdi-form-select"
            v-model="selectedTimePeriod"
        >
            <v-radio
                v-for="(timePeriodOption, index) in timePeriodOptions"
                :key="index"
                :label="timePeriodOption"
                @click="setDateRangeBySelectedTimePeriod"
                color="green lighten-1"
            ></v-radio>
        </v-radio-group>
        <v-date-picker
            color="green lighten-1"
            :full-width="true"
            locale="de-de"
            max="1945-12-31"
            min="1933-01-01"
            :no-title="true"
            range
            v-model="dateRange"
            v-show="selectedTimePeriod === 5"
        ></v-date-picker>
    </v-container>
</template>

<script>
export default {
    name: 'DateRange',
    data() {
        return {
            isMapOptionsDisplayed: false,
            tabs: [
                {
                    name: 'Layers',
                    icon: 'mdi-layers',
                },
                {
                    name: 'Kategorien',
                    icon: 'mdi-select-group',
                },
                {
                    name: 'Zeitraum',
                    icon: 'mdi-map-clock',
                },
                {
                    name: 'Liste',
                    icon: 'mdi-view-list',
                },
            ],
            timePeriodOptions: ['Nicht eingeschränkt', '1933 ~ 1936', '1937 ~ 1939', '1939 ~ 1941', '1942 ~ 1945', 'Frei konfigurierbar'],
            selectedTimePeriod: 0,
            dateRange: ['1933-01-01', '1945-12-31'],
        };
    },
    computed: {
        dateRangeText() {
            let convertedDateRangeArray = [];

            this.dateRange.forEach((date) => {
                const [year, month, day] = date.split('-');
                convertedDateRangeArray.push(day + '.' + month + '.' + year);
            });

            return convertedDateRangeArray.join(' ~ ');
        },
    },
    methods: {
        setDateRangeBySelectedTimePeriod() {
            switch (this.selectedTimePeriod) {
                case 0:
                    this.dateRange = ['1933-01-01', '1945-12-31'];
                    break;
                case 1:
                    this.dateRange = ['1933-01-01', '1936-12-31'];
                    break;
                case 2:
                    this.dateRange = ['1937-01-01', '1939-12-31'];
                    break;
                case 3:
                    this.dateRange = ['1939-01-01', '1941-12-31'];
                    break;
                case 4:
                    this.dateRange = ['1942-01-01', '1945-12-31'];
                    break;
                case 5:
                default:
                    // no date-range update required
                    break;
            }
        },
    },
};
</script>

<style>
/* remove background color for date-picker #part1 */
.v-picker--date > .v-picker__body {
    background-color: transparent;
}

/* disable automatically style effects on input label click */
.disablePointerEvents, label[for='dateRangeText'].v-label {
    pointer-events: none;
}
</style>

<style scoped>
/* remove background color for date-picker #part2 */
.v-picker--date {
    background-color: transparent;
}
</style>
