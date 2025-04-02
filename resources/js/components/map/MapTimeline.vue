<template>
    <div>
        <v-btn
            position="fixed"
            location="bottom right"
            rounded="circle"
            size="56px"
            class="mb-5 mr-5 z-index-4"
            color="white"
            v-show="!isTimelineOpen"
            @click.stop="toggleTimeline"
        >
            <v-icon>mdi-timeline</v-icon>
        </v-btn>

        <div
            class="timeline-toolbar position-fixed z-index-4 bottom-0 w-100 d-flex flex-column align-center border-t-thin"
            v-show="isTimelineOpen"
            :class="{ isOpen: isTimelineOpen }"
        >
            <div class="timeline-toolbar__content d-flex">
                <v-btn
                    @click.stop="toggleTimeline"
                    icon
                    class="close-button position-absolute right-0 top-0 mr-5 mt-5"
                >
                    <v-icon>mdi-close</v-icon>
                </v-btn>
                <div
                    class="timeline-header d-flex w-100 justify-space-between flex-row align-center"
                >
                    <span class="timeline-title">Zeitstrahl</span>
                    <v-switch
                        v-model="timelineModeSinglePin"
                        @click.stop="handleModeChange"
                        append-icon="mdi-filter"
                        prepend-icon="mdi-filter-multiple"
                        hint="Einzel- oder Zeitraumfilter"
                        persistent-hint
                        label="Modus"
                    ></v-switch>
                </div>
                <div
                    class="map-timeline"
                    id="timeline"
                    :class="{ singleMode: timelineModeSinglePin }"
                >
                    <div>
                        <div
                            class="map-timeline-handle left"
                            @mousedown="moveLeftHandle"
                            v-show="!timelineModeSinglePin"
                        >
                            <span></span>
                        </div>

                        <!-- Right handle (used as single point in single mode) -->
                        <div
                            class="map-timeline-handle right"
                            @mousedown="handleRightMousedown"
                            :class="{ 'single-handle': timelineModeSinglePin }"
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
import { ref, onMounted, watch } from "vue";

const emit = defineEmits(["updateTimeLine"]);
import NavigationHelpers from "../helpers/NavigationHelpers";

const props = defineProps({
    groupedPlaces: Object,
    map: Object,
});

const YEAR_RANGE = [1933, new Date().getFullYear()];
const MONTH_STEP_END_YEAR = 1945;
const STATE = {
    left: null,
    right: null,
};
// old handle gap - 0.155,
const MIN_HANDLE_GAP = 0.155;

const { isTimelineOpen, toggleTimeline } = NavigationHelpers();
const timelineModeSinglePin = ref(false);

// Watch for mode changes to update the UI and handlers
watch(timelineModeSinglePin, (newValue) => {
    // Apply UI changes for the mode transition
    if (newValue) {
        // Single mode: Set both handles to the same position
        // Use the right handle position for both
        if (STATE.left && STATE.right) {
            STATE.left = { ...STATE.right };
            updateHandlePositions();
        }
    }
});

const handleModeChange = () => {
    // Emit the correct values based on the current mode after a small delay
    // to ensure the UI has updated
    setTimeout(() => {
        updateTimeLine(STATE.left, STATE.right);
    }, 50);
};

const updateTimeLine = (left, right) => {
    if (timelineModeSinglePin.value) {
        // In single mode, we emit the same value for start and end
        emit("updateTimeLine", right, right);
    } else {
        // In range mode, we emit different values
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

// Update UI to reflect current state
function updateHandlePositions() {
    if (!STATE.left || !STATE.right) return;

    const leftHandle = document.querySelector(".map-timeline-handle.left");
    const rightHandle = document.querySelector(".map-timeline-handle.right");

    if (leftHandle) {
        setHandle(leftHandle, STATE.left.position);
    }

    if (rightHandle) {
        setHandle(rightHandle, STATE.right.position);
    }
}

const moveLeftHandle = (e) => {
    if (timelineModeSinglePin.value) return; // Shouldn't happen, but just in case

    beforeMove(e);
    const move = (e) => {
        STATE.left = setHandle(
            document.querySelector(".map-timeline-handle.left"),
            calcPosition(e.clientX),
            0,
            STATE.right.position - MIN_HANDLE_GAP,
        );
    };
    console.log("movehandle left");
    document.addEventListener("mousemove", move);
    document.addEventListener(
        "mouseup",
        () => {
            document.removeEventListener("mousemove", move);
            updateTimeLine(STATE.left, STATE.right);
            afterMove();
        },
        { once: true },
    );
};

const handleRightMousedown = (e) => {
    if (timelineModeSinglePin.value) {
        moveSingleHandle(e);
    } else {
        moveRightHandle(e);
    }
};

const moveRightHandle = (e) => {
    //if (timelineModeSinglePin.value) return; // Shouldn't happen, but just in case

    beforeMove(e);
    const move = (e) => {
        STATE.right = setHandle(
            document.querySelector(".map-timeline-handle.right"),
            calcPosition(e.clientX),
            STATE.left.position + MIN_HANDLE_GAP,
            1,
        );
    };
    console.log("movehandle right");
    document.addEventListener("mousemove", move);
    document.addEventListener(
        "mouseup",
        () => {
            document.removeEventListener("mousemove", move);
            updateTimeLine(STATE.left, STATE.right);
            afterMove();
        },
        { once: true },
    );
};

// New function for single handle mode
const moveSingleHandle = (e) => {
    beforeMove(e);
    const move = (e) => {
        // In single mode, both left and right handles have the same position
        const position = calcPosition(e.clientX);
        STATE.right = setHandle(
            document.querySelector(".map-timeline-handle.right"),
            position,
            0, // No minimum limit in single mode
            1,
        );

        // Update the left handle position (hidden but we keep it in sync)
        STATE.left = { ...STATE.right };
    };
    document.addEventListener("mousemove", move);
    document.addEventListener(
        "mouseup",
        () => {
            document.removeEventListener("mousemove", move);
            updateTimeLine(STATE.left, STATE.right);
            afterMove();
        },
        { once: true },
    );
};

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
});
</script>

<style scoped>
.timeline-title {
    font-weight: bold;
    font-family: "Special Elite";
}

.z-index-4 {
    z-index: 4;
}

.timeline-toolbar {
    height: 200px;
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
    width: 800px;
}

.map-timeline {
    --s: 750px;
    --w: 4.5em;
    --h: 40px;

    position: fixed;
    left: 50%;
    bottom: 0;
    padding: 0 calc(0.5 * var(--w));
    margin: 80px;
    margin-left: calc(-0.5 * var(--s));
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
    transition: all 0.3s ease;
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
</style>
