<template>
    <div>
        <!-- location - https://www.wikidata.org/wiki/Property:P276
             significant place - https://www.wikidata.org/wiki/Property:P7153   -->
        <template
            v-if="
                selectedPlace.locations.length > 0 ||
                selectedPlace.significantPlaces.length > 0
            "
        >
            <v-list-item density="compact">
                <v-list-item-title>Ort(e)</v-list-item-title>
                <v-list-item-subtitle>
                    <ul>
                        <li v-for="location in props.selectedPlace.locations">
                            {{ location }}
                        </li>
                        <li
                            v-for="significantPlace in props.selectedPlace
                                .significantPlaces"
                        >
                            {{ significantPlace }}
                        </li>
                    </ul>
                </v-list-item-subtitle>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- start point - https://www.wikidata.org/wiki/Property:P1427
             destination point - https://www.wikidata.org/wiki/Property:P1444   -->
        <template
            v-if="
                props.selectedPlace.startPoints.length > 0 ||
                props.selectedPlace.destinationPoints.length > 0
            "
        >
            <v-list-item density="compact">
                <v-list-item-title>Start-/Zielort</v-list-item-title>
                <v-list-item-subtitle>
                    <div
                        v-if="props.selectedPlace.startPoints.length > 0"
                        class="my-2"
                    >
                        von
                        <ul>
                            <li
                                v-for="startPoint in props.selectedPlace
                                    .startPoints"
                            >
                                {{ startPoint }}
                            </li>
                        </ul>
                    </div>
                    <div
                        v-if="props.selectedPlace.destinationPoints.length > 0"
                        class="my-2"
                    >
                        nach
                        <ul>
                            <li
                                v-for="destinationPoint in props.selectedPlace
                                    .destinationPoints"
                            >
                                {{ destinationPoint }}
                            </li>
                        </ul>
                    </div>
                </v-list-item-subtitle>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- coordinate location - https://www.wikidata.org/wiki/Property:P625 -->
        <v-list-item density="compact">
            <v-row no-gutters>
                <v-col cols="8">
                    <v-list-item-title
                        >Koordinaten Ausgangsort</v-list-item-title
                    >
                    <v-list-item-subtitle>
                        <ul>
                            <li>
                                {{
                                    props.selectedPlace.addresses.selected
                                        .latLng.lat
                                }},
                                {{
                                    props.selectedPlace.addresses.selected
                                        .latLng.lng
                                }}
                            </li>
                        </ul>
                    </v-list-item-subtitle>
                </v-col>
                <v-col cols="4" class="align-right">
                    <v-list-item-action
                        class="flex-direction-row ml-0 my-0 ga-2"
                    >
                        <v-btn
                            @click.stop="$emit('zoomIntoPlace')"
                            variant="outlined"
                            size="x-small"
                            icon
                        >
                            <v-icon>mdi-magnify-plus</v-icon>
                        </v-btn>
                        <v-btn
                            @click.stop="$emit('undoZoomIntoPlace')"
                            variant="outlined"
                            size="x-small"
                            icon
                        >
                            <v-icon>mdi-undo-variant</v-icon>
                        </v-btn>
                    </v-list-item-action>
                </v-col>
            </v-row>
        </v-list-item>
        <v-divider></v-divider>

        <!-- start time - https://www.wikidata.org/wiki/Property:P580
             end time - https://www.wikidata.org/wiki/Property:P582     -->
        <template
            v-if="
                props.selectedPlace.startDate.value ||
                props.selectedPlace.endDate.value
            "
        >
            <v-list-item density="compact">
                <v-list-item-title>Laufzeit</v-list-item-title>
                <v-list-item-subtitle>
                    <template v-if="props.selectedPlace.startDate.value">
                        von {{ props.selectedPlace.startDate.locale }}
                    </template>
                    <template v-if="props.selectedPlace.endDate.value">
                        bis {{ props.selectedPlace.endDate.locale }}
                    </template>
                </v-list-item-subtitle>
            </v-list-item>
            <v-divider></v-divider>
        </template>
        <!-- point in time - https://www.wikidata.org/wiki/Property:P585 -->
        <template v-else-if="props.selectedPlace.pointInTime.value">
            <v-list-item density="compact">
                <v-list-item-title>Zeitpunkt</v-list-item-title>
                <v-list-item-subtitle>
                    {{ props.selectedPlace.pointInTime.locale }}
                </v-list-item-subtitle>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- perpetrators - https://www.wikidata.org/wiki/Property:P8031 -->
        <template v-if="props.selectedPlace.perpetrators.length > 0">
            <v-list-item density="compact" class="position-relative">
                <div
                    id="information-institutions-highlight"
                    class="information-institutions-highlight tutorial-rectangle-highlight"
                    style="display: none"
                ></div>
                <v-list-item-title>Täter*innen</v-list-item-title>
                <v-list-item-subtitle>
                    <ul class="hyphens-auto white-space-normal" lang="de">
                        <li
                            v-for="perpetrator in props.selectedPlace
                                .perpetrators"
                        >
                            <div
                                v-if="
                                    perpetrator.hasPersonData ||
                                    perpetrator.hasLocationMarker
                                "
                            >
                                <h4 v-if="perpetrator.hasPersonData">
                                    Person(en)
                                </h4>
                                <a
                                    v-if="perpetrator.hasPersonData"
                                    @click.stop="
                                        $emit('showPerson', {
                                            id: perpetrator.id,
                                            group: 'perpetrators',
                                        })
                                    "
                                    href="#"
                                >
                                    {{ perpetrator.label }}
                                </a>
                                <h4 v-if="perpetrator.hasLocationMarker">
                                    Institution(en)
                                </h4>
                                <a
                                    v-if="perpetrator.hasLocationMarker"
                                    @click.stop="
                                        $emit('switchLocation', {
                                            locationId: perpetrator.id,
                                        })
                                    "
                                    href="#"
                                >
                                    {{ perpetrator.label }}
                                </a>
                            </div>
                            <template v-else>
                                {{ perpetrator.label }}
                            </template>
                        </li>
                    </ul>
                </v-list-item-subtitle>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- victims - https://www.wikidata.org/wiki/Property:P8032
             number of casualties - https://www.wikidata.org/wiki/Property:P1590
        -->
        <template
            v-if="
                props.selectedPlace.victims.length > 0 ||
                props.selectedPlace.numberOfCasualties.length > 0
            "
        >
            <v-list-item density="compact">
                <v-list-item-title>Geschädigte</v-list-item-title>
                <v-list-item-subtitle>
                    <div
                        v-if="props.selectedPlace.numberOfCasualties.length > 0"
                        class="my-2"
                    >
                        Anzahl
                        <ul>
                            <li
                                v-for="casualty in props.selectedPlace
                                    .numberOfCasualties"
                            >
                                {{ casualty.sourcingCircumstance }}
                                {{ casualty.value }}
                                <template
                                    v-if="
                                        casualty.pointInTime &&
                                        casualty.pointInTime.locale
                                    "
                                >
                                    ({{ casualty.pointInTime.locale }})
                                </template>
                            </li>
                        </ul>
                    </div>
                    <div
                        v-if="props.selectedPlace.victims.length > 0"
                        class="my-2"
                    >
                        Namen
                        <ul>
                            <li v-for="victim in props.selectedPlace.victims">
                                <a
                                    v-if="victim.hasPersonData"
                                    @click.stop="
                                        $emit('showPerson', {
                                            id: victim.id,
                                            group: 'victims',
                                        })
                                    "
                                    href="#"
                                >
                                    {{ victim.name }}
                                </a>
                                <template v-else>
                                    {{ victim.name }}
                                </template>
                            </li>
                        </ul>
                    </div>
                </v-list-item-subtitle>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- targets - https://www.wikidata.org/wiki/Property:P533 (previous) -->
        <!-- targets - https://www.wikidata.org/wiki/Property:P828 -->
        <template v-if="props.selectedPlace.targets.length > 0">
            <v-list-item density="compact">
                <v-list-item-title>Ziel des Angriffs</v-list-item-title>
                <v-list-item-subtitle>
                    <ul>
                        <li v-for="target in props.selectedPlace.targets">
                            {{ target }}
                        </li>
                    </ul>
                </v-list-item-subtitle>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- number of survivors - https://www.wikidata.org/wiki/Property:P1561 -->
        <template v-if="props.selectedPlace.numberOfSurvivors.length > 0">
            <v-list-item density="compact">
                <v-list-item-title>Überlebende</v-list-item-title>
                <v-list-item-subtitle>
                    <ul>
                        <li
                            v-for="survivor in props.selectedPlace
                                .numberOfSurvivors"
                        >
                            {{ survivor.sourcingCircumstance }}
                            {{ survivor.value }}
                            <template
                                v-if="
                                    survivor.pointInTime &&
                                    survivor.pointInTime.locale
                                "
                            >
                                ({{ survivor.pointInTime.locale }})
                            </template>
                        </li>
                    </ul>
                </v-list-item-subtitle>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- number of deaths - https://www.wikidata.org/wiki/Property:P1120 -->
        <template v-if="props.selectedPlace.numberOfDeaths.length > 0">
            <v-list-item density="compact">
                <v-list-item-title>Ermordete</v-list-item-title>
                <v-list-item-subtitle>
                    <ul>
                        <li v-for="death in props.selectedPlace.numberOfDeaths">
                            {{ death.sourcingCircumstance }} {{ death.value }}
                            <template
                                v-if="
                                    death.pointInTime &&
                                    death.pointInTime.locale
                                "
                            >
                                ({{ death.pointInTime.locale }})
                            </template>
                        </li>
                    </ul>
                </v-list-item-subtitle>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <!-- number of arrests - https://www.wikidata.org/wiki/Property:P5582 -->
        <template v-if="props.selectedPlace.numberOfArrests.length > 0">
            <v-list-item density="compact">
                <v-list-item-title>Festnahmen</v-list-item-title>
                <v-list-item-subtitle>
                    <ul>
                        <li
                            v-for="arrest in props.selectedPlace
                                .numberOfArrests"
                        >
                            {{ arrest.sourcingCircumstance }} {{ arrest.value }}
                            <template
                                v-if="
                                    arrest.pointInTime &&
                                    arrest.pointInTime.locale
                                "
                            >
                                ({{ arrest.pointInTime.locale }})
                            </template>
                        </li>
                    </ul>
                </v-list-item-subtitle>
            </v-list-item>
            <v-divider></v-divider>
        </template>

        <prisoners
            :selectedPlace="props.selectedPlace"
            @showPerson="$emit('showPerson', $event)"
        ></prisoners>

        <sources
            v-if="
                props.selectedPlace.sources.length > 0 ||
                props.selectedPlace.urlSources.length > 0
            "
            :selectedPlace="props.selectedPlace"
        ></sources>
    </div>
</template>

<script setup>
import { defineProps, defineEmits, onMounted, onBeforeUnmount } from "vue";
import Prisoners from "./Prisoners";
import Sources from "./Sources";

const props = defineProps({
    selectedPlace: {
        type: Object,
        required: true,
    },
});

const showTutorialHighlight = (highlightId) => {
    const highlight = document.getElementById(highlightId);
    if (highlight) {
        highlight.style.display = "block";
    }
};

const hideTutorialHighlight = (highlightId) => {
    const highlight = document.getElementById(highlightId);
    if (highlight) {
        highlight.style.display = "none";
    }
};

const showInformationInstitutionsHighlightHandler = () =>
    showTutorialHighlight("information-institutions-highlight");
const hideInformationInstitutionsHighlightHandler = () =>
    hideTutorialHighlight("information-institutions-highlight");

onMounted(() => {
    // Highlight events
    window.addEventListener(
        "show-information-institutions-highlight",
        showInformationInstitutionsHighlightHandler,
    );
    window.addEventListener(
        "hide-information-institutions-highlight",
        hideInformationInstitutionsHighlightHandler,
    );
});

onBeforeUnmount(() => {
    window.removeEventListener(
        "show-information-institutions-highlight",
        showInformationInstitutionsHighlightHandler,
    );
    window.removeEventListener(
        "hide-information-institutions-highlight",
        hideInformationInstitutionsHighlightHandler,
    );
});

defineEmits([
    "showPerson",
    "switchLocation",
    "zoomIntoPlace",
    "undoZoomIntoPlace",
]);
</script>

<style scoped>
/* move to top right */
.flex-direction-row {
    align-self: flex-start;
    flex-direction: row;
    padding-top: 7px;
}

.hyphens-auto {
    hyphens: auto;
}

/* to enable linebreaks for long labels */
.white-space-normal {
    white-space: normal;
}

.tutorial-rectangle-highlight {
    position: absolute;
    top: 0;
    height: 100%;
    border: 6px solid #ff0000;
    box-shadow: 0 0 30px 10px rgba(255, 0, 0, 0.7);
    background-color: transparent;
    pointer-events: none;
    z-index: 9999;
    border-radius: 8px;
    animation: rectangle-highlight-pulse 2s infinite;
}

.information-institutions-highlight {
    width: calc(100% + 20px);
    height: calc(100% + 20px);
    top: -10px;
    left: -10px;
}

@keyframes rectangle-highlight-pulse {
    0% {
        box-shadow: 0 0 20px 10px rgba(255, 0, 0, 0.7);
        border-color: #ff0000;
    }
    50% {
        box-shadow: 0 0 25px 15px rgba(255, 0, 0, 0.7);
        border-color: #ff5555;
    }
    100% {
        box-shadow: 0 0 20px 10px rgba(255, 0, 0, 0.7);
        border-color: #ff0000;
    }
}
</style>
