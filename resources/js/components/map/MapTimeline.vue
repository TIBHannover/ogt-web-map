<template>
    <div class="map-timeline" id="timeline">
        <div>
        <div class="map-timeline-handle left" @mousedown="moveLeftHandle">
            <span></span>
        </div>
        <div class="map-timeline-handle right" @mousedown="moveRightHandle">
            <span></span>
        </div>
        </div>
    </div>
</template>

<script>
const YEAR_RANGE = [ 1933, (new Date()).getFullYear() ];
const MONTH_STEP_END_YEAR = 1945;
const STATE = {
    left: null,
    right: null
};

function beforeMove(e) {
    document.body.style.cursor = "grabbing";
    document.querySelector("main").pointerEvents = "none";
}
function afterMove() {
    document.body.style.cursor = "default";
    document.querySelector("main").pointerEvents = "auto";
}
function calcPosition(globalX) {
    const timeline = document.querySelector(".map-timeline");
    return Math.max(0, Math.min(timeline.clientWidth, globalX - timeline.offsetLeft)) / timeline.clientWidth;
}
function setHandle(element, position, leftLimit = 0, rightLimit = 1) {
    const normalPosition = Math.max(leftLimit, Math.min(rightLimit, position));
    element.style.left = `${normalPosition * 100}%`;
    const monthSteps = (MONTH_STEP_END_YEAR - YEAR_RANGE[0]) * 12;
    const yearSteps = YEAR_RANGE[1] - MONTH_STEP_END_YEAR;
    const monthFraction = monthSteps / (monthSteps + YEAR_RANGE[1] - MONTH_STEP_END_YEAR);
    const isMonthStepRange = normalPosition < monthFraction;
    const year = isMonthStepRange
    ? Math.floor((MONTH_STEP_END_YEAR - YEAR_RANGE[0]) * (normalPosition / monthFraction) + YEAR_RANGE[0])
    : Math.round(yearSteps * ((normalPosition - monthFraction) / (1 - monthFraction)) + MONTH_STEP_END_YEAR);
    const month = isMonthStepRange
    ? Math.floor((normalPosition / monthFraction) * monthSteps % 12)
    : -1;
    const monthNames = [
        "Jan", "Feb", "Mar", "Apr", "Mai", "Jun", "Jul", "Aug", "Sep", "Okt", "Nov", "Dez"
    ];
    element.querySelector("span").textContent = ~month ? `${monthNames[month]} ${year.toString().slice(2)}` : `${year}`;
    return {
        position: normalPosition,
        month: month >= 0 ? month : 0,
        year
    };
}

export default {
    name: 'MapTimeline',
    props: ['groupedPlaces', 'map'],
    data() {
        return {};
    },
    methods: {
        moveLeftHandle(e) {
            beforeMove(e);
            const move = e => {
                STATE.left = setHandle(document.querySelector(".map-timeline-handle.left"), calcPosition(e.clientX), 0, STATE.right.position - 0.155);
                //this.setYearRange(STATE.left.year, STATE.right.year);
            };
            document.addEventListener("mousemove", move);
            document.addEventListener("mouseup", () => {
                document.removeEventListener("mousemove", move);
                
                this.$parent.applyFilters(STATE.left, STATE.right);

                afterMove();
            });
        },
        moveRightHandle(e) {
            beforeMove(e);
            const move = e => {
                STATE.right = setHandle(document.querySelector(".map-timeline-handle.right"), calcPosition(e.clientX), STATE.left.position + 0.155, 1);
                //this.setYearRange(STATE.left.year, STATE.right.year);
            };
            document.addEventListener("mousemove", move);
            document.addEventListener("mouseup", () => {
                document.removeEventListener("mousemove", move);
                
                this.$parent.applyFilters(STATE.left, STATE.right);

                afterMove();
            });
        },
        setYearRange(startYear, endYear) {
            /* for (const [group, places] of Object.entries(this.groupedPlaces)) {
                for (const place of places) {
                    const getDateProp = key => {
                        return place[key]?.value
                        || Object.values(place[key] ?? {})[0]?.value;
                    };
                    const date = getDateProp("inceptionDates")
                            ?? getDateProp("dissolvedDates")
                            ?? getDateProp("endTime")
                            ?? getDateProp("startTime");
                    const year = date ? new Date(date).getFullYear() : 0;
                    if(year && (year < this.minYear || year > this.maxYear)) {
                        places.remove();
                    }
                    else {
                        places.addTo(this.map);
                    }
                }
            } */
        }
    },
    mounted: () => {
        const monthSteps = (MONTH_STEP_END_YEAR - YEAR_RANGE[0]) * 12;
        const yearSteps = YEAR_RANGE[1] - MONTH_STEP_END_YEAR;
        const monthFraction = monthSteps / (monthSteps + YEAR_RANGE[1] - MONTH_STEP_END_YEAR);
        const timeline = document.querySelector("#timeline");
        const monthsBackground = document.createElement("div");
        monthsBackground.classList.add("map-timeline-months");
        monthsBackground.style.width = `${monthFraction * 100}%`;
        timeline.appendChild(monthsBackground);
        const createStripe = y => {
            const stripe = document.createElement("div");
            stripe.classList.add("map-timeline-stripe");
            stripe.style.left = `${y * 100}%`;
            timeline.appendChild(stripe);
        };
        for(let i = 0; i < (monthSteps + yearSteps); i++) {
            createStripe(i / (monthSteps + yearSteps));
        }
        setTimeout(() => {
            STATE.left = setHandle(document.querySelector(".map-timeline-handle.left"), 0);
            STATE.right = setHandle(document.querySelector(".map-timeline-handle.right"), 1);
        }, 0);
    },
};
</script>

<style scoped>
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