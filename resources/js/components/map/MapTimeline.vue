<template>
    <div class="map-timeline">
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
    const year = Math.round((YEAR_RANGE[1] - YEAR_RANGE[0]) * normalPosition + YEAR_RANGE[0]);
    element.querySelector("span").textContent = year;
    return {
        position: normalPosition,
        year: year
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
                STATE.left = setHandle(document.querySelector(".map-timeline-handle.left"), calcPosition(e.clientX), 0, STATE.right.position - 0.25);
                this.$parent.setYearRange(STATE.left.year, STATE.right.year);
                //this.setYearRange(STATE.left.year, STATE.right.year);
            };
            document.addEventListener("mousemove", move);
            document.addEventListener("mouseup", () => {
                document.removeEventListener("mousemove", move);
                
                afterMove();
            });
        },
        moveRightHandle(e) {
            beforeMove(e);
            const move = e => {
                STATE.right = setHandle(document.querySelector(".map-timeline-handle.right"), calcPosition(e.clientX), STATE.left.position + 0.25, 1);
                this.$parent.setYearRange(STATE.left.year, STATE.right.year);
                //this.setYearRange(STATE.left.year, STATE.right.year);
            };
            document.addEventListener("mousemove", move);
            document.addEventListener("mouseup", () => {
                document.removeEventListener("mousemove", move);
                
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
        setTimeout(() => {
            STATE.left = setHandle(document.querySelector(".map-timeline-handle.left"), 0);
            STATE.right = setHandle(document.querySelector(".map-timeline-handle.right"), 1);
        }, 0);
    },
};
</script>

<style scoped>
.map-timeline {
    --s: 450px;
    --w: 3.75em;
    --h: 40px;

    position: fixed;
    left: 50%;
    bottom: 0;
    padding: 0 calc(0.5 * var(--w));
    margin: 80px;
    margin-left: calc(-0.5 * var(--s));
    width: var(--s);
    height: var(--h);
    border-radius: calc(0.5 * var(--h));
    z-index: 3;
    overflow: hidden;
    box-shadow: 0 0 10px -5px rgba(0, 0, 0.2);
    background: #fff;
    background-image: radial-gradient(circle at calc(0.25 * var(--h)) calc(0.5 * var(--h)), #d0d0d0 calc(0.075 * var(--h)), transparent 0);
    background-position: calc(0.1 * var(--s) * 0.25) 0;
    background-size: calc(0.05 * var(--s)) var(--h);
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
