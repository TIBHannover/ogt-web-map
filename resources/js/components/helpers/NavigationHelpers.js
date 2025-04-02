import { ref, watch, inject } from "vue";

const isTimelineOpen = ref(false);
const isMapOptionsOpen = ref(false);
const isPlaceInfoOpen = ref(false);

watch(isTimelineOpen, (newValue) => {
    if (newValue === true) {
        isMapOptionsOpen.value = false;
    }
});

watch(isMapOptionsOpen, (newValue) => {
    if (newValue === true) {
        isTimelineOpen.value = false;
    }
});

watch(isPlaceInfoOpen, (newValue) => {
    if (newValue === true) {
        isTimelineOpen.value = false;
    }
});

export default function NavigationHelpers() {
    const isMenuDisplayed = inject("isMenuDisplayed");

    watch(isMenuDisplayed, (newValue) => {
        if (newValue === true) {
            isTimelineOpen.value = false;
            // isMapOptionsOpen.value = false;
        }
    });

    const openTimeline = () => {
        isMapOptionsOpen.value = false;
        isPlaceInfoOpen.value = false;
        if (isMenuDisplayed.value) {
            isMenuDisplayed.value = false;
        }
        isTimelineOpen.value = true;
    };

    const closeTimeline = () => {
        isTimelineOpen.value = false;
    };

    const toggleTimeline = () => {
        if (isTimelineOpen.value) {
            closeTimeline();
        } else {
            openTimeline();
        }
    };

    const openMapOptions = () => {
        isMapOptionsOpen.value = true;
        // Close other navigations
        isTimelineOpen.value = false;
        isPlaceInfoOpen.value = false;
    };

    const closeMapOptions = () => {
        isMapOptionsOpen.value = false;
    };

    const toggleMapOptions = () => {
        if (isMapOptionsOpen.value) {
            closeMapOptions();
        } else {
            openMapOptions();
        }
    };

    const openPlaceInfo = () => {
        isTimelineOpen.value = false;
        isPlaceInfoOpen.value = true;
        // isMapOptionsOpen.value = false;
    };

    const closePlaceInfo = () => {
        isPlaceInfoOpen.value = false;
    };

    return {
        isTimelineOpen,
        isMapOptionsOpen,
        isPlaceInfoOpen,
        isMenuDisplayed,
        openTimeline,
        closeTimeline,
        toggleTimeline,
        openMapOptions,
        closeMapOptions,
        toggleMapOptions,
        openPlaceInfo,
        closePlaceInfo,
    };
}
