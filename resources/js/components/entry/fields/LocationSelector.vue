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
                placeholder="Adresse oder Ort suchen..."
                @keydown.enter.prevent="$emit('searchLocation')"
                @focus="$emit('fieldFocus', 'location')"
            />
            <button type="button" @click="$emit('searchLocation')">
                Suchen
            </button>
        </div>
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
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import * as L from "leaflet";
import { LMap, LTileLayer, LMarker } from "@vue-leaflet/vue-leaflet";
import "leaflet/dist/leaflet.css";

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
});

const emit = defineEmits([
    "update:mapZoom",
    "update:mapCenter",
    "update:selectedLocation",
    "update:locationSearch",
    "searchLocation",
    "handleMapClick",
    "fieldFocus",
    "infoButtonClick",
]);

// Map reference und Initialisierung
const mapRef = ref(null);
const leafletMap = ref(null); // Referenz zur Leaflet-Map-Instance

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

// Icon fu00fcr den Marker (verwende Q6983405 fu00fcr Ereignisse)
const markerIcon = createCustomIcon();

// Wird aufgerufen, wenn die Leaflet-Karte bereit ist
const onMapReady = (map) => {
    leafletMap.value = map;

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
    if (leafletMap.value) {
        leafletMap.value.invalidateSize();
    }
});
</script>

<style scoped>
.container-section {
    width: 100%;
    margin-bottom: 1.5rem;
    background-color: #f5f5f5;
    padding: 1rem;
    border-radius: 4px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.section-label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.info-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background-color: #1976d2;
    color: white;
    border: none;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.info-icon:hover {
    background-color: #1565c0;
}

.map-container {
    width: 800px;
    margin-bottom: 1rem;
    border: 1px solid #ccc;
    border-radius: 4px;
    overflow: visible; /* Geändert von hidden zu visible, damit Suchfeld sichtbar bleibt */
    position: relative;
    max-width: 100%;
}

.map-container .leaflet-container {
    width: 100% !important;
    height: 400px !important;
    background-color: #f8f8f8;
    overflow: hidden;
}

.map-container::after {
    content: "";
    display: block;
    clear: both;
}

.selected-coordinates {
    margin-top: 0.5rem;
    background-color: #f5f5f5;
    padding: 8px;
    border-radius: 4px;
    font-size: 0.9rem;
}

.map-search {
    margin: 0.5rem 0;
    display: flex;
    width: 100%;
    position: relative;
    z-index: 10;
}

.map-search input {
    flex-grow: 1;
    margin-right: 0.5rem;
    padding: 0.75rem 1rem;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}

.map-search input:focus {
    outline: none;
    border-color: #1976d2;
    box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.25);
}

.map-search button {
    padding: 0.5rem 1rem;
    background-color: #1976d2;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    min-width: 100px;
    transition: background-color 0.2s;
}

.map-search button:hover {
    background-color: #1565c0;
}

/* Leaflet map specific fixes */
.leaflet-pane {
    z-index: 1;
}

.leaflet-tile-pane {
    z-index: 0;
}

.leaflet-tile {
    margin: 0 !important;
    padding: 0 !important;
}

.leaflet-tile-container img {
    width: 256px !important;
    height: 256px !important;
    margin: 0 !important;
    padding: 0 !important;
    left: 0 !important;
    top: 0 !important;
    border: none !important;
}

.leaflet-control {
    z-index: 10;
}

.leaflet-top,
.leaflet-bottom {
    z-index: 10;
}
</style>
