<template>
    <v-container>
        <v-row>
            <v-col cols="12">
                <h1 class="text-h4 mb-4">Erinnerungskalender</h1>
            </v-col>
        </v-row>

        <!-- Featured Tile Section -->
        <v-row v-if="latestTile">
            <v-col cols="12">
                <v-card class="mb-6">
                    <v-card-title class="text-h5">
                        Aktuelle Erinnerungskachel
                    </v-card-title>
                    <v-card-subtitle class="pb-0">
                        {{ formatDate(latestTile.date) }} -
                        {{ latestTile.label }}
                    </v-card-subtitle>
                    <v-card-text>
                        <div class="d-flex flex-column flex-md-row">
                            <div
                                class="pdf-preview mr-md-4 mb-4 mb-md-0"
                                style="max-width: 300px"
                            >
                                <v-img
                                    v-if="latestTile.thumbnailUrl"
                                    :src="latestTile.thumbnailUrl"
                                    max-height="400"
                                    cover
                                    class="bg-grey-lighten-2"
                                    @click="openPdf(latestTile.file)"
                                    @error="handleImageError(latestTile)"
                                ></v-img>
                                <v-sheet
                                    v-else
                                    height="400"
                                    color="grey-lighten-2"
                                    class="d-flex align-center justify-center"
                                    @click="openPdf(latestTile.file)"
                                >
                                    <v-icon size="64">mdi-file-pdf-box</v-icon>
                                </v-sheet>
                            </div>
                            <div>
                                <p class="text-body-1">
                                    {{ latestTile.description }}
                                </p>
                                <v-btn
                                    color="primary"
                                    class="mt-2"
                                    @click="openPdf(latestTile.file)"
                                >
                                    PDF öffnen
                                </v-btn>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <!-- Calendar Section -->
        <v-row>
            <v-col cols="12">
                <v-card>
                    <v-toolbar flat density="compact">
                        <v-btn
                            icon="mdi-chevron-left"
                            variant="text"
                            @click="prevMonth"
                        ></v-btn>
                        <v-toolbar-title>{{ title }}</v-toolbar-title>
                        <v-btn
                            icon="mdi-chevron-right"
                            variant="text"
                            @click="nextMonth"
                        ></v-btn>
                        <v-spacer></v-spacer>
                        <v-tooltip text="Heute">
                            <template v-slot:activator="{ props }">
                                <v-btn
                                    icon="mdi-calendar-today"
                                    variant="text"
                                    class="ml-2"
                                    @click="setToday"
                                    v-bind="props"
                                ></v-btn>
                            </template>
                        </v-tooltip>
                    </v-toolbar>

                    <!-- Calendar Grid -->
                    <div class="calendar-grid pa-2">
                        <!-- Weekday Headers -->
                        <div class="calendar-header">
                            <div
                                v-for="day in weekdays"
                                :key="day"
                                class="weekday-header"
                            >
                                {{ day }}
                            </div>
                        </div>

                        <!-- Calendar Days -->
                        <div class="calendar-body">
                            <div
                                v-for="(day, index) in calendarDays"
                                :key="index"
                                class="calendar-day"
                                :class="{
                                    'outside-month': !day.currentMonth,
                                    today: day.isToday,
                                    'has-events': day.events.length > 0,
                                }"
                                @click="handleDayClick(day)"
                            >
                                <div class="day-number">{{ day.day }}</div>
                                <div
                                    v-if="day.events.length > 0"
                                    class="event-indicator"
                                >
                                    <div
                                        v-for="event in day.events.slice(0, 2)"
                                        :key="event.id"
                                        class="event-dot"
                                    >
                                        {{ event.name.substring(0, 15)
                                        }}{{
                                            event.name.length > 15 ? "..." : ""
                                        }}
                                    </div>
                                    <div
                                        v-if="day.events.length > 2"
                                        class="more-events"
                                    >
                                        +{{ day.events.length - 2 }} mehr
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <!-- Event Details Dialog -->
        <v-dialog v-model="showEventDialog" max-width="600">
            <v-card v-if="selectedEvent">
                <v-card-title class="text-h5">
                    {{ selectedEvent.name }}
                </v-card-title>
                <v-card-subtitle>
                    {{ formatDate(selectedEvent.start) }}
                </v-card-subtitle>
                <v-card-text>
                    <div class="d-flex flex-column">
                        <div class="pdf-preview mb-4" style="max-width: 100%">
                            <v-img
                                v-if="selectedEvent.thumbnailUrl"
                                :src="selectedEvent.thumbnailUrl"
                                max-height="300"
                                cover
                                class="bg-grey-lighten-2"
                                @error="handleEventImageError(selectedEvent)"
                            ></v-img>
                            <v-sheet
                                v-else
                                height="200"
                                color="grey-lighten-2"
                                class="d-flex align-center justify-center"
                            >
                                <v-icon size="64">mdi-file-pdf-box</v-icon>
                            </v-sheet>
                        </div>
                        <p>{{ selectedEvent.description }}</p>
                        <v-btn
                            color="primary"
                            class="mt-2"
                            @click="openPdf(selectedEvent.file)"
                        >
                            PDF öffnen
                        </v-btn>
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn
                        variant="text"
                        color="secondary"
                        @click="showEventDialog = false"
                    >
                        Schließen
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Date Events Dialog -->
        <v-dialog v-model="showDateDialog" max-width="500">
            <v-card v-if="dateEvents.length > 0">
                <v-card-title class="text-h5">
                    Ereignisse am {{ formatDate(selectedDateObject) }}
                </v-card-title>
                <v-card-text>
                    <v-list lines="two">
                        <v-list-item
                            v-for="event in dateEvents"
                            :key="event.id"
                            @click="selectEvent(event)"
                        >
                            <v-list-item-title>{{
                                event.name
                            }}</v-list-item-title>
                            <v-list-item-subtitle
                                >{{ event.description.substring(0, 50)
                                }}{{
                                    event.description.length > 50 ? "..." : ""
                                }}</v-list-item-subtitle
                            >
                        </v-list-item>
                    </v-list>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn
                        variant="text"
                        color="secondary"
                        @click="showDateDialog = false"
                    >
                        Schließen
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Error Dialog -->
        <v-dialog v-model="showErrorDialog" max-width="500">
            <v-card>
                <v-card-title class="text-h5 bg-error text-white">
                    Fehler
                </v-card-title>
                <v-card-text class="pt-4">
                    {{ errorMessage }}
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn
                        variant="text"
                        color="primary"
                        @click="showErrorDialog = false"
                    >
                        Schließen
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<script>
import { reminderTiles } from "../data/reminderTiles";
import {
    format,
    parseISO,
    isValid,
    getDate,
    getMonth,
    getYear,
    addMonths,
    subMonths,
    startOfMonth,
    endOfMonth,
    eachDayOfInterval,
    isSameMonth,
    isToday,
} from "date-fns";
import { de } from "date-fns/locale";
import { getPdfUrl } from "../utils/pdfUtils";

export default {
    name: "ReminderCalendar",

    data() {
        return {
            currentDate: new Date(),
            tiles: [],
            events: [],
            selectedEvent: null,
            showEventDialog: false,
            showDateDialog: false,
            dateEvents: [],
            selectedDateObject: null,
            weekdays: ["Mo", "Di", "Mi", "Do", "Fr", "Sa", "So"],
            showErrorDialog: false,
            errorMessage: "",
        };
    },

    computed: {
        title() {
            // Format the current month and year for display
            return format(this.currentDate, "MMMM yyyy", { locale: de });
        },

        latestTile() {
            // Get the current or upcoming tile
            const now = new Date();

            // First try to find a tile for today's date
            const todayTile = this.tiles.find((tile) => {
                const tileDate = parseISO(tile.date);
                return (
                    getDate(tileDate) === getDate(now) &&
                    getMonth(tileDate) === getMonth(now)
                );
            });

            if (todayTile) return todayTile;

            // If no tile for today, find the next upcoming tile based on month/day
            const nowMonth = getMonth(now);
            const nowDay = getDate(now);

            // Sort events that are coming up next in the year
            const upcomingTiles = [...this.tiles]
                .filter((tile) => {
                    const tileDate = parseISO(tile.date);
                    const tileMonth = getMonth(tileDate);
                    const tileDay = getDate(tileDate);

                    return (
                        tileMonth > nowMonth ||
                        (tileMonth === nowMonth && tileDay > nowDay)
                    );
                })
                .sort((a, b) => {
                    const dateA = parseISO(a.date);
                    const dateB = parseISO(b.date);

                    const monthA = getMonth(dateA);
                    const monthB = getMonth(dateB);

                    if (monthA !== monthB) return monthA - monthB;
                    return getDate(dateA) - getDate(dateB);
                });

            if (upcomingTiles.length > 0) return upcomingTiles[0];

            // If no upcoming tiles, return the most recent past tile
            const pastTiles = [...this.tiles]
                .filter((tile) => {
                    const tileDate = parseISO(tile.date);
                    const tileMonth = getMonth(tileDate);
                    const tileDay = getDate(tileDate);

                    return (
                        tileMonth < nowMonth ||
                        (tileMonth === nowMonth && tileDay <= nowDay)
                    );
                })
                .sort((a, b) => {
                    const dateA = parseISO(a.date);
                    const dateB = parseISO(b.date);

                    const monthA = getMonth(dateA);
                    const monthB = getMonth(dateB);

                    if (monthA !== monthB) return monthB - monthA;
                    return getDate(dateB) - getDate(dateA);
                });

            return pastTiles.length > 0 ? pastTiles[0] : null;
        },

        calendarDays() {
            // Get the start of the current month
            const monthStart = startOfMonth(this.currentDate);
            // Get the end of the current month
            const monthEnd = endOfMonth(this.currentDate);

            // Get all days in the month
            const days = eachDayOfInterval({
                start: monthStart,
                end: monthEnd,
            });

            // Determine the first day of the month (0 = Sunday, 1 = Monday, ..., 6 = Saturday)
            let firstDay = monthStart.getDay();
            // Convert to Monday-based (0 = Monday, ..., 6 = Sunday)
            firstDay = firstDay === 0 ? 6 : firstDay - 1;

            // Add days from previous month to fill the first week
            const prevMonthDays = [];
            if (firstDay > 0) {
                const prevMonth = subMonths(monthStart, 1);
                const prevMonthEnd = endOfMonth(prevMonth);

                for (let i = firstDay - 1; i >= 0; i--) {
                    const date = new Date(prevMonthEnd);
                    date.setDate(prevMonthEnd.getDate() - i);
                    prevMonthDays.push(date);
                }
            }

            // Add days from next month to complete the grid (6 rows x 7 days = 42 cells)
            const nextMonthDays = [];
            const totalDaysNeeded = 42 - days.length - prevMonthDays.length;

            if (totalDaysNeeded > 0) {
                const nextMonth = addMonths(monthStart, 1);
                const nextMonthStart = startOfMonth(nextMonth);

                for (let i = 0; i < totalDaysNeeded; i++) {
                    const date = new Date(nextMonthStart);
                    date.setDate(nextMonthStart.getDate() + i);
                    nextMonthDays.push(date);
                }
            }

            // Combine all days
            const allDays = [...prevMonthDays, ...days, ...nextMonthDays];

            // Format and add metadata to each day
            return allDays.map((date) => {
                const day = getDate(date);
                const month = getMonth(date);
                const year = getYear(date);

                // Find events for this day (regardless of year)
                const dayEvents = this.events.filter((event) => {
                    const eventDate = new Date(event.start);
                    return (
                        getDate(eventDate) === day &&
                        getMonth(eventDate) === month
                    );
                });

                return {
                    date,
                    day,
                    month,
                    year,
                    currentMonth: isSameMonth(date, this.currentDate),
                    isToday: isToday(date),
                    events: dayEvents,
                };
            });
        },
    },

    created() {
        // Load and format the reminder tiles data
        this.tiles = reminderTiles.map((tile) => {
            // Fix any date format issues (like the "1998-03-203" typo)
            let fixedDate = tile.date;
            if (tile.date && !isValid(parseISO(tile.date))) {
                // Try to extract a valid date from potentially malformed date strings
                const match = tile.date.match(/(\d{4})-(\d{2})-(\d{2})/);
                if (match) {
                    fixedDate = `${match[1]}-${match[2]}-${match[3]}`;
                }
            }

            // Ensure file path is properly formatted
            const filePath = tile.file ? tile.file.replace(/^\.\//g, "") : null;

            return {
                ...tile,
                date: fixedDate,
                file: filePath,
                // Use the provided thumbnail URL
                thumbnailUrl: tile.thumbnailUrl,
            };
        });

        // Create events for the calendar
        this.events = this.tiles.map((tile) => {
            const date = parseISO(tile.date);

            return {
                id: tile.id,
                name: tile.label,
                start: date,
                description: tile.description || "",
                file: tile.file,
                thumbnailUrl: tile.thumbnailUrl,
                // Store the month and day to find recurring events
                month: getMonth(date),
                day: getDate(date),
            };
        });
    },

    methods: {
        // Handle event image loading errors
        handleEventImageError(event) {
            console.warn(`Event image loading error: ${event.name}`);
            // Use a default image placeholder
            event.thumbnailUrl = "/images/pdf_placeholder.jpg";
        },
        // Navigation methods
        nextMonth() {
            this.currentDate = addMonths(this.currentDate, 1);
        },

        prevMonth() {
            this.currentDate = subMonths(this.currentDate, 1);
        },

        setToday() {
            this.currentDate = new Date();
        },

        // Handle clicking on a calendar day
        handleDayClick(day) {
            if (day.events.length === 0) return;

            this.selectedDateObject = day.date;
            this.dateEvents = day.events;

            if (day.events.length === 1) {
                // If only one event, show it directly
                this.selectEvent(day.events[0]);
            } else {
                // If multiple events, show the date dialog
                this.showDateDialog = true;
            }
        },

        // Select an event to display its details
        selectEvent(event) {
            this.selectedEvent = event;
            this.showEventDialog = true;
            this.showDateDialog = false; // Close date dialog if open
        },

        // Format a date for display
        formatDate(dateObj) {
            if (!dateObj) return "";

            const date =
                typeof dateObj === "string" ? parseISO(dateObj) : dateObj;
            if (!isValid(date)) return "";

            return format(date, "dd. MMMM yyyy", { locale: de });
        },

        // Open a PDF file
        openPdf(file) {
            if (!file) return;

            try {
                // Ensure we have the correct path format
                const fileName = file.includes("/")
                    ? file
                    : `resources/tiles/${file}`;

                // Skip checking and directly try to open the PDF
                const url = getPdfUrl(fileName);
                console.log(`Opening PDF: ${url}`);
                window.open(url, "_blank");
            } catch (error) {
                console.error("Error opening PDF:", error);
                this.errorMessage =
                    "Beim Öffnen der PDF-Datei ist ein Fehler aufgetreten.";
                this.showErrorDialog = true;
            }
        },

        // Handle image loading errors
        handleImageError(tile) {
            console.warn(`Image loading error for tile: ${tile.label}`);
            // Use a default image placeholder
            tile.thumbnailUrl = "/images/pdf_placeholder.jpg";

            // Update the corresponding event if needed
            const event = this.events.find((e) => e.id === tile.id);
            if (event) {
                event.thumbnailUrl = "/images/pdf_placeholder.jpg";
            }
        },

        // No need for thumbnail loading since we use static images
    },
};
</script>

<style scoped>
.pdf-preview {
    cursor: pointer;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
    transition: all 0.2s ease-in-out;
}

.pdf-preview:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Calendar Styles */
.calendar-grid {
    display: flex;
    flex-direction: column;
    width: 100%;
}

.calendar-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    text-align: center;
    font-weight: bold;
    margin-bottom: 8px;
    border-bottom: 1px solid #e0e0e0;
}

.weekday-header {
    padding: 8px;
}

.calendar-body {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    grid-auto-rows: minmax(100px, auto);
    gap: 2px;
}

.calendar-day {
    border: 1px solid #e0e0e0;
    padding: 8px;
    min-height: 100px;
    position: relative;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.calendar-day:hover {
    background-color: #f5f5f5;
}

.outside-month {
    color: #bdbdbd;
    background-color: #f9f9f9;
}

.today {
    background-color: #e8f5e9;
    font-weight: bold;
}

.has-events {
    font-weight: bold;
}

.day-number {
    font-size: 1.1rem;
    margin-bottom: 4px;
}

.event-indicator {
    margin-top: 4px;
}

.event-dot {
    margin: 2px 0;
    padding: 2px 4px;
    font-size: 0.75rem;
    background-color: #bbdefb;
    border-radius: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.more-events {
    font-size: 0.75rem;
    color: #757575;
    margin-top: 2px;
}
</style>
