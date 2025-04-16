<template>
    <div>
        <v-btn
            position="fixed"
            location="bottom right"
            :rounded="$vuetify.display.smAndUp ? 'pill' : 'circle'"
            :size="$vuetify.display.smAndUp ? 'default' : '56px'"
            class="mb-5 mr-5 z-index-4"
            color="white"
            v-show="!isTimelineOpen"
            @click.stop="toggleTimeline"
        >
            <v-icon>mdi-timeline</v-icon>
            <span v-if="$vuetify.display.smAndUp" class="ml-2 text-none"
                >Zeitstrahl Filter</span
            >
        </v-btn>

        <div
            class="timeline-toolbar position-fixed z-index-10 bottom-0 left-0 w-100 d-flex flex-column align-center border-t-thin"
            v-show="isTimelineOpen"
            :class="{ isOpen: isTimelineOpen }"
        >
            <v-btn
                @click.stop="toggleTimeline"
                icon
                class="close-button position-absolute right-0 top-0 mr-5 mt-5"
            >
                <v-icon>mdi-close</v-icon>
            </v-btn>
            <div class="timeline-toolbar__content d-flex">
                <div
                    class="timeline-header d-flex w-100 justify-start justify-sm-space-between flex-row align-start align-sm-center px-6 pt-5 pt-sm-6 ga-6 ga-sm-0"
                >
                    <div class="d-flex align-center">
                        <v-btn
                            :color="
                                isTimelineFilterEnabled ? 'primary' : 'default'
                            "
                            :variant="
                                isTimelineFilterEnabled
                                    ? 'elevated'
                                    : 'outlined'
                            "
                            rounded="pill"
                            @click="toggleTimelineFilter"
                            class="timeline-toggle-btn"
                        >
                            <v-icon
                                :icon="
                                    isTimelineFilterEnabled
                                        ? 'mdi-toggle-switch'
                                        : 'mdi-toggle-switch-off'
                                "
                                class="mr-2"
                            ></v-icon>
                            <span
                                v-if="$vuetify.display.smAndUp"
                                class="text-none"
                            >
                                {{
                                    isTimelineFilterEnabled
                                        ? "Zeitstrahl deaktivieren"
                                        : "Zeitstrahl aktivieren"
                                }}
                            </span>
                        </v-btn>
                    </div>
                    <div class="d-flex align-center">
                        <span v-if="$vuetify.display.mdAndUp" class="mr-2"
                            >Filtermodus:</span
                        >
                        <v-btn-toggle
                            v-model="timelineModeToggle"
                            mandatory
                            :disabled="!isTimelineFilterEnabled"
                            density="comfortable"
                            color="primary"
                            rounded="pill"
                            class="border"
                        >
                            <v-btn
                                value="range"
                                @click="setRangeMode"
                                :disabled="!isTimelineFilterEnabled"
                            >
                                <v-icon>mdi-filter-multiple</v-icon>
                                <span class="d-none d-sm-inline ml-1 text-none"
                                    >Zeitraum</span
                                >
                            </v-btn>
                            <v-btn
                                value="single"
                                @click="setSingleMode"
                                :disabled="!isTimelineFilterEnabled"
                            >
                                <v-icon>mdi-filter</v-icon>
                                <span class="d-none d-sm-inline ml-1 text-none"
                                    >Zeitpunkt</span
                                >
                            </v-btn>
                        </v-btn-toggle>
                    </div>
                </div>
                <div
                    class="map-timeline"
                    id="timeline"
                    :class="{
                        singleMode: timelineModeSingleHandle,
                        disabled: !isTimelineFilterEnabled,
                    }"
                >
                    <div>
                        <div
                            class="map-timeline-handle left"
                            @mousedown="moveLeftHandle"
                            v-show="!timelineModeSingleHandle"
                        >
                            <span></span>
                        </div>

                        <div
                            class="map-timeline-handle right"
                            @mousedown="handleRightMousedown"
                            :class="{
                                'single-handle': timelineModeSingleHandle,
                            }"
                        >
                            <span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch, toRaw } from "vue";

const emit = defineEmits(["updateTimeLine", "timelineFilterEnabledChange"]);
import NavigationHelpers from "../helpers/NavigationHelpers";

const props = defineProps({
    groupedPlaces: Object,
    map: Object,
});

defineExpose({
    toggleTimeline,
});

const YEAR_RANGE = [1933, new Date().getFullYear()];
const MONTH_STEP_END_YEAR = 1945;
const STATE = {
    left: null,
    right: null,
};
// old handle gap - 0.155,
const MIN_HANDLE_GAP = 0.155;

const { isTimelineOpen } = NavigationHelpers();
const timelineModeSingleHandle = ref(false);
const isTimelineFilterEnabled = ref(false);

const timelineModeToggle = ref("range");
let timelineOpenedInt = 0;

watch(timelineModeSingleHandle, (newValue) => {
    timelineModeToggle.value = newValue ? "single" : "range";
    resetHandlePositions();
});

watch(isTimelineFilterEnabled, (newValue) => {
    emit("timelineFilterEnabledChange", newValue);

    if (newValue) {
        // Wenn aktiviert, aktualisiere mit aktuellen Handle-Positionen
        if (STATE.left && STATE.right) {
            updateTimeLine(STATE.left, STATE.right);
        }
    } else {
        // Wenn deaktiviert, setze auf Unendlich (deaktiviere Filter)
        emit(
            "updateTimeLine",
            { year: -Infinity, month: -Infinity },
            { year: Infinity, month: Infinity },
        );
    }
});

const toggleTimeline = () => {
    if (!isTimelineOpen.value) {
        if (timelineOpenedInt === 0) {
            isTimelineFilterEnabled.value = true;
            timelineOpenedInt++;
        }

        if (STATE.left && STATE.right) {
            setTimeout(() => {
                updateTimeLine(STATE.left, STATE.right);
            }, 50);
        }
    }

    isTimelineOpen.value = !isTimelineOpen.value;
};

const handleModeChange = () => {
    closeMapPopups();
    setTimeout(() => {
        updateTimeLine(STATE.left, STATE.right);
    }, 50);
};

const handleTimelineEnableChange = () => {
    closeMapPopups();
};

watch(timelineModeToggle, (newValue) => {
    timelineModeSingleHandle.value = newValue === "single";
});

const setRangeMode = () => {
    timelineModeSingleHandle.value = false;
    handleModeChange();
};

const setSingleMode = () => {
    timelineModeSingleHandle.value = true;
    handleModeChange();
};

const toggleTimelineFilter = () => {
    isTimelineFilterEnabled.value = !isTimelineFilterEnabled.value;
    handleTimelineEnableChange();
};

const updateTimeLine = (left, right) => {
    if (!isTimelineFilterEnabled.value) {
        emit(
            "updateTimeLine",
            {
                year: -Infinity,
                month: -Infinity,
            },
            {
                year: Infinity,
                month: Infinity,
            },
        );
        return;
    }

    if (timelineModeSingleHandle.value) {
        emit("updateTimeLine", right, right);
    } else {
        emit("updateTimeLine", left, right);
    }
};

function beforeMove(e) {
    document.body.style.cursor = "grabbing";
    document.querySelector("main").style.pointerEvents = "none";
}

function afterMove() {
    document.body.style.cursor = "default";
    document.querySelector("main").style.pointerEvents = "auto";
}

function calcPosition(globalX) {
    const timeline = document.querySelector(".map-timeline");
    return (
        Math.max(
            0,
            Math.min(timeline.clientWidth, globalX - timeline.offsetLeft),
        ) / timeline.clientWidth
    );
}

function setHandle(element, position, leftLimit = 0, rightLimit = 1) {
    const normalPosition = Math.max(leftLimit, Math.min(rightLimit, position));
    element.style.left = `${normalPosition * 100}%`;
    const monthSteps = (MONTH_STEP_END_YEAR - YEAR_RANGE[0]) * 12;
    const yearSteps = YEAR_RANGE[1] - MONTH_STEP_END_YEAR;
    const monthFraction =
        monthSteps / (monthSteps + YEAR_RANGE[1] - MONTH_STEP_END_YEAR);
    const isMonthStepRange = normalPosition < monthFraction;
    const year = isMonthStepRange
        ? Math.floor(
              (MONTH_STEP_END_YEAR - YEAR_RANGE[0]) *
                  (normalPosition / monthFraction) +
                  YEAR_RANGE[0],
          )
        : Math.round(
              yearSteps *
                  ((normalPosition - monthFraction) / (1 - monthFraction)) +
                  MONTH_STEP_END_YEAR,
          );
    const month = isMonthStepRange
        ? Math.floor(((normalPosition / monthFraction) * monthSteps) % 12)
        : -1;
    const monthNames = [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "Mai",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Okt",
        "Nov",
        "Dez",
    ];
    element.querySelector("span").textContent = ~month
        ? `${monthNames[month]} ${year.toString().slice(2)}`
        : `${year}`;
    return {
        position: normalPosition,
        month: month >= 0 ? month : 0,
        year,
    };
}

function resetHandlePositions() {
    if (!STATE.left || !STATE.right) return;

    closeMapPopups();
    const leftHandle = document.querySelector(".map-timeline-handle.left");
    const rightHandle = document.querySelector(".map-timeline-handle.right");

    if (timelineModeSingleHandle.value) {
        // Für Single-Handle-Modus: setze beide Handles auf 0.6 (Mitte)
        const position = 0.6;

        if (leftHandle) {
            STATE.left = setHandle(leftHandle, position);
        }

        if (rightHandle) {
            STATE.right = setHandle(rightHandle, position);
        }
    } else {
        // Für Dual-Handle-Modus: setze Handles auf 0 und 1
        if (leftHandle) {
            STATE.left = setHandle(leftHandle, 0);
        }

        if (rightHandle) {
            STATE.right = setHandle(rightHandle, 1);
        }
    }

    updateTimeLine(STATE.left, STATE.right);
}

const moveLeftHandle = (e) => {
    if (timelineModeSingleHandle.value) return; // Shouldn't happen, but just in case
    closeMapPopups();
    beforeMove(e);

    const move = (e) => {
        STATE.left = setHandle(
            document.querySelector(".map-timeline-handle.left"),
            calcPosition(getClientX(e)),
            0,
            STATE.right.position - MIN_HANDLE_GAP,
        );
    };

    const end = () => {
        document.removeEventListener("mousemove", move);
        document.removeEventListener("touchmove", move);
        document.removeEventListener("mouseup", end);
        document.removeEventListener("touchend", end);
        updateTimeLine(STATE.left, STATE.right);
        afterMove();
    };

    document.addEventListener("mousemove", move);
    document.addEventListener("touchmove", move, { passive: false });
    document.addEventListener("mouseup", end);
    document.addEventListener("touchend", end);

    // Prevent default for touch events to avoid scrolling
    if (e.touches) {
        e.preventDefault();
    }
};

const handleRightMousedown = (e) => {
    if (timelineModeSingleHandle.value) {
        moveSingleHandle(e);
    } else {
        moveRightHandle(e);
    }

    // Prevent default for touch events to avoid scrolling
    if (e.touches) {
        e.preventDefault();
    }
};

const moveRightHandle = (e) => {
    closeMapPopups();
    beforeMove(e);

    const move = (e) => {
        STATE.right = setHandle(
            document.querySelector(".map-timeline-handle.right"),
            calcPosition(getClientX(e)),
            STATE.left.position + MIN_HANDLE_GAP,
            1,
        );
    };

    const end = () => {
        document.removeEventListener("mousemove", move);
        document.removeEventListener("touchmove", move);
        document.removeEventListener("mouseup", end);
        document.removeEventListener("touchend", end);
        updateTimeLine(STATE.left, STATE.right);
        afterMove();
    };

    document.addEventListener("mousemove", move);
    document.addEventListener("touchmove", move, { passive: false });
    document.addEventListener("mouseup", end);
    document.addEventListener("touchend", end);

    // Prevent default for touch events to avoid scrolling
    if (e.touches) {
        e.preventDefault();
    }
};

const moveSingleHandle = (e) => {
    closeMapPopups();
    beforeMove(e);

    const move = (e) => {
        // In single mode, both left and right handles have the same position
        const position = calcPosition(getClientX(e));
        STATE.right = setHandle(
            document.querySelector(".map-timeline-handle.right"),
            position,
            0, // No minimum limit in single mode
            1,
        );
    };

    const end = () => {
        document.removeEventListener("mousemove", move);
        document.removeEventListener("touchmove", move);
        document.removeEventListener("mouseup", end);
        document.removeEventListener("touchend", end);
        STATE.left = { ...STATE.right };
        updateTimeLine(STATE.left, STATE.right);
        afterMove();
    };

    document.addEventListener("mousemove", move);
    document.addEventListener("touchmove", move, { passive: false });
    document.addEventListener("mouseup", end);
    document.addEventListener("touchend", end);

    // Prevent default for touch events to avoid scrolling
    if (e.touches) {
        e.preventDefault();
    }
};

function getClientX(e) {
    return e.touches ? e.touches[0].clientX : e.clientX;
}

onMounted(() => {
    const monthSteps = (MONTH_STEP_END_YEAR - YEAR_RANGE[0]) * 12;
    const yearSteps = YEAR_RANGE[1] - MONTH_STEP_END_YEAR;
    const monthFraction =
        monthSteps / (monthSteps + YEAR_RANGE[1] - MONTH_STEP_END_YEAR);
    const timeline = document.querySelector("#timeline");
    const monthsBackground = document.createElement("div");
    monthsBackground.classList.add("map-timeline-months");
    monthsBackground.style.width = `${monthFraction * 100}%`;
    timeline.appendChild(monthsBackground);

    const createStripe = (y) => {
        const stripe = document.createElement("div");
        stripe.classList.add("map-timeline-stripe");
        stripe.style.left = `${y * 100}%`;
        timeline.appendChild(stripe);
    };

    for (let i = 0; i < monthSteps + yearSteps; i++) {
        createStripe(i / (monthSteps + yearSteps));
    }

    setTimeout(() => {
        STATE.left = setHandle(
            document.querySelector(".map-timeline-handle.left"),
            0,
        );
        STATE.right = setHandle(
            document.querySelector(".map-timeline-handle.right"),
            1,
        );
    }, 0);

    setTimeout(() => {
        STATE.left = setHandle(
            document.querySelector(".map-timeline-handle.left"),
            0,
        );
        STATE.right = setHandle(
            document.querySelector(".map-timeline-handle.right"),
            1,
        );

        // Add touch event listeners to handles
        const leftHandle = document.querySelector(".map-timeline-handle.left");
        const rightHandle = document.querySelector(
            ".map-timeline-handle.right",
        );

        leftHandle.addEventListener("touchstart", moveLeftHandle, {
            passive: false,
        });
        rightHandle.addEventListener("touchstart", handleRightMousedown, {
            passive: false,
        });
    }, 0);
});

function closeMapPopups() {
    if (!props.map) return;

    try {
        const rawMap = toRaw(props.map);

        if (rawMap._popup) {
            try {
                if (rawMap._popup._container) {
                    rawMap._popup._container.remove();
                }
                rawMap._popup = null;
            } catch (e) {
                console.warn("Error manually removing popup:", e);
            }
        }

        if (typeof rawMap.closePopup === "function") {
            rawMap.closePopup();
        }
    } catch (error) {
        console.warn("Error closing map popups:", error);
    }
}
</script>

<style scoped>
.z-index-4 {
    z-index: 4;
}

.z-index-10 {
    z-index: 1005;
}

.timeline-toolbar {
    height: 170px;
    background-color: rgba(255, 255, 255, 0.9);
    transform: translateY(230px);
    transition: all 0.3s ease;
    -webkit-box-shadow: 0px -5px 15px 5px rgba(0, 0, 0, 0.15);
    box-shadow: 0px -5px 15px 5px rgba(0, 0, 0, 0.15);

    &.isOpen {
        transform: translateY(0);
    }
}

.timeline-toolbar__content {
    position: relative;
    width: 800px;
    height: 188px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.map-timeline {
    --s: 750px;
    --w: 4.5em;
    --h: 40px;
    position: fixed;
    bottom: 0;
    padding: 0 calc(0.5 * var(--w));
    margin: 94px 0 36px 0;
    width: 95%;
    max-width: var(--s);
    height: var(--h);
    border-radius: calc(0.5 * var(--h));
    z-index: 3;
    overflow: hidden;
    box-shadow: 0 0 10px -5px rgba(0, 0, 0.2);
    background: #fff;
}
.map-timeline > div {
    position: relative;
    width: 100%;
    height: 100%;
}

.map-timeline-handle {
    position: absolute;
    cursor: grab;
    width: var(--w);
    height: 100%;
    transform: translateX(calc(-0.5 * var(--w)));
}
.map-timeline-handle:active {
    cursor: grabbing;
}
.map-timeline-handle::before {
    content: "";
    position: absolute;
    display: block;
    right: 0;
    width: 100vw;
    height: 100%;
    background-color: #000;
    opacity: 0.5;
    z-index: -1;
}
.map-timeline-handle.left::before {
    right: 0;
}
.map-timeline-handle.right::before {
    left: 0;
}

.map-timeline.singleMode .map-timeline-handle.right::before {
    display: none;
}

.map-timeline-handle.single-handle {
    border-radius: calc(0.5 * var(--h));
}
.map-timeline-handle span {
    display: block;
    width: 100%;
    height: 100%;
    background-color: var(--color-primary);
    color: #fff;
    width: 100%;
    height: 100%;
    text-align: center;
    line-height: var(--h);
    font-size: 0.9em;
    font-weight: bold;
    user-select: none;
}
.map-timeline-handle.left span {
    border-top-left-radius: calc(0.5 * var(--h));
    border-bottom-left-radius: calc(0.5 * var(--h));
}
.map-timeline-handle.right span {
    border-top-right-radius: calc(0.5 * var(--h));
    border-bottom-right-radius: calc(0.5 * var(--h));
}
.map-timeline.singleMode .map-timeline-handle.right span {
    border-radius: calc(0.5 * var(--h));
}
.map-timeline .map-timeline-stripe:nth-child(2) {
    display: none;
}

.v-btn--active,
.timeline-toggle-btn.v-btn--elevated {
    background-color: #af1414 !important;
}

.map-timeline.disabled {
    opacity: 0.5;
    pointer-events: none;
}

.v-btn-toggle {
    margin-bottom: 0;
}

.v-btn-toggle .v-btn {
    min-width: 36px;
}

@media (max-width: 1000px) {
    .timeline-toolbar.isOpen .close-button {
        margin-top: -63px !important;
    }
}

@media (max-width: 850px) {
    .timeline-toolbar__content {
        width: 100%;
    }
}

@media (max-width: 600px) {
    .timeline-toolbar.isOpen .close-button {
        margin-top: 15px !important;
        z-index: 1000;
    }
    .map-timeline {
        margin: 90px 0 33px 0;
    }
    .timeline-header {
        flex-direction: column;
        align-items: center;
    }

    .timeline-header > div:first-child {
        margin-bottom: 8px;
    }
}
</style>
<style>
.map-timeline-months {
    position: absolute;
    display: block;
    top: 0;
    left: 0;
    height: 100%;
    background-color: #eaeaea;
    z-index: -1;
}
.map-timeline-stripe {
    position: absolute;
    display: block;
    top: 0;
    width: 1px;
    height: 100%;
    background-color: #bababa;
    z-index: -1;
}
@media (max-width: 600px) {
    .map-timeline-stripe:nth-child(even) {
        display: none;
    }
}
</style>
