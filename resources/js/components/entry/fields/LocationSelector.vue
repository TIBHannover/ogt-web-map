<template>
    <div class="container-section location-section">
        <div class="section-header">
            <label class="section-label">Standort auswählen *</label>
            <button
                type="button"
                class="info-icon"
                @click="$emit('infoButtonClick', 'location')"
            >
                <v-icon>mdi-information-variant</v-icon>
            </button>
        </div>
        <div class="map-search">
            <input
                :value="locationSearch"
                @input="$emit('update:locationSearch', $event.target.value)"
                type="text"
                class="map-search-input"
                placeholder="Adresse oder Ort suchen..."
                @keydown.enter.prevent="$emit('searchLocation')"
                @focus="$emit('fieldFocus', 'location')"
            />
            <button type="button" @click="$emit('searchLocation')">
                Suchen
            </button>
        </div>

        <ValidationError
            :show="showValidationErrors && !isValid"
            message="Bitte wählen Sie einen Standort auf der Karte aus."
            showIcon
        />

        <div class="map-container">
            <l-map
                ref="mapRef"
                :zoom="mapZoom"
                :center="mapCenter"
                @click="onMapClick"
                @ready="onMapReady"
                style="height: 100%; width: 100%"
            >
                <l-tile-layer
                    url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                    layer-type="base"
                    name="OpenStreetMap"
                ></l-tile-layer>
                <l-marker
                    v-if="selectedLocation"
                    :lat-lng="selectedLocation"
                    :icon="markerIcon"
                    :z-index-offset="1000"
                ></l-marker>
            </l-map>
            <div class="selected-coordinates" v-if="selectedLocation">
                <strong>Ausgewählte Koordinaten:</strong>
                {{ selectedLocation.lat.toFixed(6) }},
                {{ selectedLocation.lng.toFixed(6) }}

                <div class="location-qualifier-container">
                    <label for="locationQualifier"
                        >Koordinaten-Qualifizierer:</label
                    >
                    <LocationQualifier
                        id="locationQualifier"
                        :model-value="locationQualifier"
                        @update:model-value="
                            $emit('update:locationQualifier', $event)
                        "
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import * as L from "leaflet";
import { LMap, LTileLayer, LMarker } from "@vue-leaflet/vue-leaflet";
import "leaflet/dist/leaflet.css";
import ValidationError from "../../helpers/ValidationError.vue";
import LocationQualifier from "./common/LocationQualifier.vue";
import { useValidation } from "../../../composables/useValidation";

const props = defineProps({
    mapZoom: {
        type: Number,
        default: 8,
    },
    mapCenter: {
        type: Array,
        default: () => [52.8, 9.0],
    },
    selectedLocation: {
        type: Object,
        default: null,
    },
    locationSearch: {
        type: String,
        default: "",
    },
    locationQualifier: {
        type: String,
        default: "",
    },
});

const emit = defineEmits([
    "update:mapZoom",
    "update:mapCenter",
    "update:selectedLocation",
    "update:locationSearch",
    "update:locationQualifier",
    "searchLocation",
    "handleMapClick",
    "fieldFocus",
    "infoButtonClick",
    "validation",
]);

// Map reference und Initialisierung
const mapRef = ref(null);
const leafletMap = ref(null); // Referenz zur Leaflet-Map-Instance

// Validation function
const validateLocation = () => {
    return !!props.selectedLocation;
};

// Use the validation composable
const { isValid, showValidationErrors, validate, showErrors } = useValidation(
    validateLocation,
    {
        emit,
        watchCallback: () => [props.selectedLocation],
    },
);

// Export validation methods
defineExpose({
    isValid,
    validate,
    showValidationErrors: showErrors,
});

// Benutzerdefiniertes Marker-Icon erstellen
const createCustomIcon = () => {
    return L.icon({
        iconUrl: `/images/leaflet/markerIcons/coloredFilledWhite/events.svg`,
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowUrl: "/images/leaflet/marker-shadow.png",
        shadowSize: [41, 41],
    });
};

// Icon für den Marker (verwende Q6983405 für Ereignisse)
const markerIcon = createCustomIcon();

// Wird aufgerufen, wenn die Leaflet-Karte bereit ist
const onMapReady = (map) => {
    leafletMap.value = map;
    applyLeafletCSS();

    setTimeout(() => {
        if (leafletMap.value) {
            leafletMap.value.invalidateSize();
            if (props.selectedLocation) {
                leafletMap.value.setView(
                    [props.selectedLocation.lat, props.selectedLocation.lng],
                    props.mapZoom,
                );
            } else {
                leafletMap.value.setView(props.mapCenter, props.mapZoom);
            }
        }
    }, 200);
};

// Function to apply critical Leaflet CSS fixes
const applyLeafletCSS = () => {
    const styleId = "leaflet-tile-fix";

    const existingStyle = document.getElementById(styleId);
    if (existingStyle) {
        existingStyle.remove();
    }

    const style = document.createElement("style");
    style.id = styleId;
    style.textContent = `
        /* Grundlegende Map-Fixes */
        .leaflet-container {
            height: 400px !important;
            width: 100% !important;
            z-index: 0 !important;
        }

        /* Container-Fixes */
        .leaflet-tile-container {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            height: 100% !important;
            transform-origin: 0 0 !important;
        }

        /* Tile-Fixes */
        .leaflet-tile {
            position: absolute !important;
            box-shadow: none !important;
            border: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Korrektur für Tile-Bilder */
        .leaflet-tile-container img {
            display: block !important;
            width: 256px !important;
            height: 256px !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            box-shadow: none !important;
        }

        /* Marker-Fixes */
        .leaflet-marker-pane {
            position: absolute;
            z-index: 100 !important;
        }

        .leaflet-marker-icon {
            position: absolute;
            z-index: 1000 !important;
        }
    `;
    document.head.appendChild(style);
};

const onMapClick = (e) => {
    emit("handleMapClick", e);

    // Zusätzlich, falls wir lokale Map-Aktualisierungen benötigen
    if (leafletMap.value) {
        setTimeout(() => {
            leafletMap.value.invalidateSize();
        }, 50);
    }
};

onMounted(() => {
    applyLeafletCSS();

    if (leafletMap.value) {
        leafletMap.value.invalidateSize();
    }

    // Fix für Leaflet-Icons in Vue
    // Dies ist notwendig, da Leaflet Standard-Icon-Pfade erwartet
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl:
            "https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon-2x.png",
        iconUrl: "https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon.png",
        shadowUrl:
            "https://unpkg.com/leaflet@1.7.1/dist/images/marker-shadow.png",
    });
});
</script>
<style>
@import "../styles/form-styles.css";
</style>
