<template>
    <v-container>
        <v-row
            :style="{
                'padding-left':
                    freeClientWidth < 75 && !isMenuDisplayed
                        ? computedPadding
                        : 0,
            }"
        >
            <v-col>
                <h1
                    class="font-family-special-elite font-weight-bold hyphens-auto py-4 text-h4 text-md-h3"
                    lang="de"
                    :class="{ 'text-center': ogtGlobals.isMobile }"
                >
                    Erinnerungskalender
                </h1>
                <p>Einleitungstext hier. Lorem ipsum dolor.</p>
            </v-col>
        </v-row>

        <!-- Featured Tile Section -->
        <v-row v-if="latestTile">
            <v-card-title class="text-h5 mb-1 pb-0">
                Aktuelle Erinnerungskachel
            </v-card-title>
            <v-col cols="12">
                <v-card class="mb-6">
                    <v-card-text>
                        <div class="d-flex flex-column flex-md-row">
                            <div class="thumb mr-md-4 mb-4 mb-md-0">
                                <div
                                    class="mr-md-4 mb-4 mb-md-0 square-container"
                                    @click="openPdf(latestTile.file)"
                                >
                                    <template v-if="latestTile.thumbnailUrl">
                                        <v-img
                                            :src="latestTile.thumbnailUrl"
                                            height="400"
                                            width="400"
                                            max-width="100%"
                                            cover
                                            class="square-image bg-grey-lighten-2"
                                            @error="
                                                handleImageError(latestTile)
                                            "
                                        ></v-img>
                                    </template>
                                    <v-sheet
                                        v-else
                                        height="400"
                                        width="400"
                                        max-width="100%"
                                        color="grey-lighten-2"
                                        class="square-placeholder d-flex align-center justify-center"
                                    >
                                        <v-icon size="64"
                                            >mdi-file-pdf-box</v-icon
                                        >
                                    </v-sheet>
                                </div>
                            </div>
                            <div>
                                <h5 class="text-h5 text-title pl-0">
                                    {{ latestTile.label }}
                                </h5>
                                <p class="text-subtitle pb-0 pl-0">
                                    {{ formatDate(latestTile.date) }}
                                </p>
                                <p class="text-body-1">
                                    {{ latestTile.description }}
                                </p>
                                <v-btn
                                    color="primary"
                                    class="mt-2 download-button"
                                    @click="openPdf(latestTile.file)"
                                >
                                    <v-icon start> mdi-download </v-icon>
                                    Kachel öffnen
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
                <v-card class="bg-white">
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

        <v-row>
            <v-col cols="12">
                <v-card-title class="text-h5 mb-1 pb-0 pl-0"
                    >Anleitung Erinnerungskacheln</v-card-title
                >
                <p>
                    Gemeinsam an die Orte, Ereignisse und Geschädigten des
                    Gestapo-Terrors erinnern – diese Anleitung zeigt dir, wie du
                    Schritt für Schritt deine eigenen Erinnerungskacheln zu den
                    auf der interaktiven Landkarte verzeichneten Anlässen
                    erstellen kannst!
                </p>
                <v-btn
                    class="background-color-grey border-none justify-start ml-1 mr-5 my-3 mb-6 text-h6 py-3 text-transform-none"
                    color="black"
                    background="grey"
                    elevation="24"
                    min-width="200px"
                    variant="outlined"
                    style="height: auto"
                    rounded
                    size="x-large"
                    href="./resources/tiles/Anleitung_Erinnerungskacheln_compressed.pdf"
                    target="_blank"
                >
                    <v-icon start class="ml-3"> mdi-download </v-icon>
                    <span class="whitespace-normal"
                        >Download "Anleitung Erinnerungskacheln"
                    </span>
                </v-btn>
            </v-col>
        </v-row>

        <!-- Event Details Dialog -->
        <v-dialog v-model="showEventDialog" max-width="600">
            <v-card v-if="selectedEvent">
                <div class="px-4 py-4 pb-0">
                    <h5 class="text-h5 text-title">
                        {{ selectedEvent.name }}
                    </h5>
                    <p class="text-subtitle">
                        {{ formatDate(selectedEvent.start) }}
                    </p>
                </div>
                <v-card-text>
                    <div class="d-flex flex-column">
                        <div class="thumb mb-4 square-container">
                            <v-img
                                v-if="selectedEvent.thumbnailUrl"
                                :src="selectedEvent.thumbnailUrl"
                                height="400"
                                width="400"
                                max-width="100%"
                                cover
                                class="square-image bg-grey-lighten-2"
                                @error="handleEventImageError(selectedEvent)"
                            ></v-img>
                            <v-sheet
                                v-else
                                height="400"
                                width="400"
                                max-width="100%"
                                color="grey-lighten-2"
                                class="d-flex align-center justify-center square-placeholder"
                            >
                                <v-icon size="64">mdi-file-pdf-box</v-icon>
                            </v-sheet>
                        </div>
                        <p>{{ selectedEvent.description }}</p>
                        <v-btn
                            color="primary"
                            class="mt-2 download-button"
                            @click="openPdf(selectedEvent.file)"
                        >
                            <v-icon start> mdi-download </v-icon>
                            Kachel öffnen
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

<script setup>
import { ref, computed, onMounted, onUnmounted, inject } from "vue";
import { useDisplay } from "vuetify";
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

// Get display breakpoints and use them in the template
const { mdAndUp } = useDisplay();

// Global state for consistent access across components
const ogtGlobals = inject("ogtGlobals");

// State
const currentDate = ref(new Date());
const tiles = ref([]);
const events = ref([]);
const selectedEvent = ref(null);
const showEventDialog = ref(false);
const showDateDialog = ref(false);
const dateEvents = ref([]);
const selectedDateObject = ref(null);
const weekdays = ref(["Mo", "Di", "Mi", "Do", "Fr", "Sa", "So"]);
const showErrorDialog = ref(false);
const errorMessage = ref("");

// Props
const props = defineProps({
    isMenuDisplayed: Boolean,
});

// Reactive references
const freeClientWidth = ref(document.documentElement.clientWidth);

// Used in template for dynamic padding calculation
const computedPadding = computed(() => {
    return freeClientWidth.value < 75 && !props.isMenuDisplayed
        ? `${75 - freeClientWidth.value}px`
        : "0";
});

const setFreeClientWidth = () => {
    const container = document.getElementById("container");
    if (container) {
        freeClientWidth.value =
            (document.documentElement.clientWidth - container.clientWidth) / 2;
    }
};

// Lifecycle hooks
onMounted(() => {
    setFreeClientWidth();
    window.addEventListener("resize", setFreeClientWidth);
});

onUnmounted(() => {
    window.removeEventListener("resize", setFreeClientWidth);
});

// Computed properties
const title = computed(() => {
    // Format the current month and year for display
    return format(currentDate.value, "MMMM yyyy", { locale: de });
});

const latestTile = computed(() => {
    // Get the current or upcoming tile
    const now = new Date();

    // First try to find a tile for today's date
    const todayTile = tiles.value.find((tile) => {
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
    const upcomingTiles = [...tiles.value]
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
    const pastTiles = [...tiles.value]
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
});

const calendarDays = computed(() => {
    // Get the start of the current month
    const monthStart = startOfMonth(currentDate.value);
    // Get the end of the current month
    const monthEnd = endOfMonth(currentDate.value);

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
        const dayEvents = events.value.filter((event) => {
            const eventDate = new Date(event.start);
            return getDate(eventDate) === day && getMonth(eventDate) === month;
        });

        return {
            date,
            day,
            month,
            year,
            currentMonth: isSameMonth(date, currentDate.value),
            isToday: isToday(date),
            events: dayEvents,
        };
    });
});

// Methods
const handleEventImageError = (event) => {
    console.warn(`Event image loading error: ${event.name}`);
    console.warn(`Failed image path: ${event.thumbnailUrl}`);
    // Use a default image placeholder
    event.thumbnailUrl = "/images/pdf_placeholder.svg";
};

const nextMonth = () => {
    currentDate.value = addMonths(currentDate.value, 1);
};

const prevMonth = () => {
    currentDate.value = subMonths(currentDate.value, 1);
};

const setToday = () => {
    currentDate.value = new Date();
};

const handleDayClick = (day) => {
    if (day.events.length === 0) return;

    selectedDateObject.value = day.date;
    dateEvents.value = day.events;

    if (day.events.length === 1) {
        // If only one event, show it directly
        selectEvent(day.events[0]);
    } else {
        // If multiple events, show the date dialog
        showDateDialog.value = true;
    }
};

const selectEvent = (event) => {
    selectedEvent.value = event;
    showEventDialog.value = true;
    showDateDialog.value = false; // Close date dialog if open
};

const formatDate = (dateObj) => {
    if (!dateObj) return "";

    const date = typeof dateObj === "string" ? parseISO(dateObj) : dateObj;
    if (!isValid(date)) return "";

    return format(date, "dd. MMMM yyyy", { locale: de });
};

const openPdf = (file) => {
    if (!file) return;

    try {
        // Ensure we have the correct path format
        const fileName = file.includes("/") ? file : `resources/tiles/${file}`;

        // Skip checking and directly try to open the PDF
        const url = getPdfUrl(fileName);
        window.open(url, "_blank");
    } catch (error) {
        console.error("Error opening PDF:", error);
        errorMessage.value =
            "Beim Öffnen der PDF-Datei ist ein Fehler aufgetreten.";
        showErrorDialog.value = true;
    }
};

const handleImageError = (tile) => {
    console.warn(`Image loading error for tile: ${tile.label}`);
    console.warn(`Failed image path: ${tile.thumbnailUrl}`);
    // Use a default image placeholder
    tile.thumbnailUrl = "/images/pdf_placeholder.svg";

    // Update the corresponding event if needed
    const event = events.value.find((e) => e.id === tile.id);
    if (event) {
        event.thumbnailUrl = "/images/pdf_placeholder.svg";
    }
};

// Lifecycle hook - equivalent to created()
onMounted(() => {
    // Load and format the reminder tiles data
    tiles.value = reminderTiles.map((tile) => {
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
        };
    });

    // Create events for the calendar
    events.value = tiles.value.map((tile) => {
        const date = parseISO(tile.date);

        const thumbnailUrl = tile.thumbnailUrl || null;

        return {
            id: tile.id,
            name: tile.label,
            start: date,
            description: tile.description || "",
            file: tile.file,
            thumbnailUrl: thumbnailUrl,
            // Store the month and day to find recurring events
            month: getMonth(date),
            day: getDate(date),
        };
    });
});
</script>

<style scoped>
.thumb {
    cursor: pointer;
    border-radius: 4px;
    transition: all 0.2s ease-in-out;
}

.thumb:hover,
.square-container:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    cursor: pointer;
}

.text-title {
    font-weight: 500;
}

.text-subtitle {
    color: #888;
    font-size: 0.875rem;
    font-weight: 400;
}

.square-container {
    width: 400px;
    height: 400px;
    max-width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0 auto;
    position: relative;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
    transition: all 0.2s ease-in-out;
}

.square-image {
    aspect-ratio: 1/1;
    object-fit: contain !important;
    border-radius: 4px;
}

.square-placeholder {
    aspect-ratio: 1/1;
    border-radius: 4px;
}

/* Responsive adjustments for mobile */
@media (max-width: 600px) {
    .square-container {
        width: 300px;
        height: 300px;
    }

    .square-image,
    .square-placeholder {
        height: 300px !important;
        width: 300px !important;
    }
}

@media (max-width: 400px) {
    .square-container {
        width: 250px;
        height: 250px;
    }

    .square-image,
    .square-placeholder {
        height: 250px !important;
        width: 250px !important;
    }
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

.download-button {
    display: inline-block;
    padding: 0.8rem 1.5rem;
    background-color: #3490dc;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-weight: bold;
    transition: background-color 0.3s;
    height: 3rem;
}

.download-button:hover {
    background-color: #2779bd;
}
</style>
