<template>
    <div class="container-section location-section">
        <div class="section-header d-flex justify-content-between align-items-center mb-3">
            <label class="section-label">Standort</label>
            
            <div v-if="showEditButton && !editMode" class="edit-mode-button-container">
                <button 
                    type="button" 
                    class="btn btn-sm btn-primary"
                    @click="startEditMode"
                >
                    <i class="bi bi-geo-alt me-1"></i> Ändere Koordinaten
                </button>
            </div>
        </div>

        <!-- View Mode - shows existing coordinates -->
        <div v-if="hasCoordinates && !editMode" class="view-mode">
            <div class="map-container mb-3">
                <l-map
                    ref="viewMapRef"
                    :zoom="8"
                    :center="[parsedCoordinates.lat, parsedCoordinates.lng]"
                    @ready="onViewMapReady"
                    style="height: 400px; width: 100%"
                    :options="{ zoomControl: true, dragging: true, scrollWheelZoom: false }"
                >
                    <l-tile-layer
                        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                        layer-type="base"
                        name="OpenStreetMap"
                    ></l-tile-layer>
                    <l-marker
                        :lat-lng="[parsedCoordinates.lat, parsedCoordinates.lng]"
                        :icon="markerIcon"
                    ></l-marker>
                </l-map>
            </div>

            <div class="coordinates-display mb-3">
                <strong>Aktuelle Koordinaten:</strong>
                {{ parsedCoordinates.lat.toFixed(6) }}, {{ parsedCoordinates.lng.toFixed(6) }}
            </div>

            <!-- Qualifizierer is always editable -->
            <div class="mb-3">
                <label for="coordinatesQualifier">Koordinaten-Qualifizierer:</label>
                <select 
                    id="coordinatesQualifier" 
                    v-model="qualifierValue" 
                    class="form-select mt-2"
                    @change="updateQualifier"
                >
                    <option value="">Genau</option>
                    <option value="Q5727902">Circa</option>
                    <option value="Q28863779">Koordinaten approximiert</option>
                    <option value="Q47116015">In der Nähe von</option>
                    <option value="Q47116028">Innerhalb von</option>
                    <option value="Q60070514">Ungefähr</option>
                </select>
            </div>
        </div>

        <!-- Edit Mode - allows setting new coordinates -->
        <div v-if="editMode || !hasCoordinates" class="edit-mode">
            <div class="alert alert-info mb-3" v-if="hasCoordinates">
                <i class="bi bi-info-circle me-2"></i>
                Sie bearbeiten die Koordinaten. Klicken Sie auf die Karte, um einen neuen Standort zu wu00e4hlen.
            </div>

            <div class="map-search mb-3">
                <div class="input-group">
                    <input
                        v-model="searchQuery"
                        type="text"
                        class="form-control"
                        placeholder="Adresse oder Ort suchen..."
                        @keydown.enter.prevent="searchLocation"
                    />
                    <button 
                        type="button" 
                        class="btn btn-primary"
                        @click="searchLocation"
                    >
                        <i class="bi bi-search me-1"></i> Suchen
                    </button>
                </div>
            </div>

            <div class="map-container mb-3">
                <l-map
                    ref="editMapRef"
                    :zoom="8"
                    :center="mapCenter"
                    @click="onMapClick"
                    @ready="onEditMapReady"
                    style="height: 400px; width: 100%"
                >
                    <l-tile-layer
                        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                        layer-type="base"
                        name="OpenStreetMap"
                    ></l-tile-layer>
                    <l-marker
                        v-if="editCoordinates"
                        :lat-lng="[editCoordinates.lat, editCoordinates.lng]"
                        :icon="markerIcon"
                    ></l-marker>
                </l-map>
            </div>

            <div class="coordinates-display mb-3" v-if="editCoordinates">
                <strong>Neue Koordinaten:</strong>
                {{ editCoordinates.lat.toFixed(6) }}, {{ editCoordinates.lng.toFixed(6) }}
            </div>

            <!-- Qualifizierer in edit mode too -->
            <div class="mb-3">
                <label for="editCoordinatesQualifier">Koordinaten-Qualifizierer:</label>
                <select 
                    id="editCoordinatesQualifier" 
                    v-model="qualifierValue" 
                    class="form-select mt-2"
                    @change="updateQualifier"
                >
                    <option value="">Genau</option>
                    <option value="Q5727902">Circa</option>
                    <option value="Q28863779">Koordinaten approximiert</option>
                    <option value="Q47116015">In der Nähe von</option>
                    <option value="Q47116028">Innerhalb von</option>
                    <option value="Q60070514">Ungefähr</option>
                </select>
            </div>

            <div class="d-flex">
                <button 
                    v-if="hasCoordinates"
                    type="button" 
                    class="btn btn-secondary me-2"
                    @click="cancelEdit"
                >
                    <i class="bi bi-x-circle me-1"></i> Abbrechen
                </button>
                <button 
                    type="button" 
                    class="btn btn-success"
                    @click="saveCoordinates"
                    :disabled="!editCoordinates"
                >
                    <i class="bi bi-check-circle me-1"></i> Koordinaten u00fcbernehmen
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { LMap, LTileLayer, LMarker } from "@vue-leaflet/vue-leaflet";
import "leaflet/dist/leaflet.css";
import * as L from "leaflet";
import axios from "axios";

export default {
    components: {
        LMap,
        LTileLayer,
        LMarker
    },
    props: {
        coordinates: {
            type: [String, Object],
            default: null
        },
        coordinatesQualifier: {
            type: String,
            default: ""
        }
    },
    data() {
        console.log('Initializing AdminCoordinateEditor with qualifier:', this.coordinatesQualifier);
        return {
            editMode: false,
            searchQuery: "",
            editCoordinates: null,
            qualifierValue: this.coordinatesQualifier || "",
            viewMapRef: null,
            editMapRef: null,
            leafletViewMap: null,
            leafletEditMap: null,
            mapCenter: [52.5, 13.4], // Default to Berlin
            markerIcon: null,
        };
    },
    computed: {
        hasCoordinates() {
            return this.coordinates && this.parsedCoordinates;
        },
        showEditButton() {
            return this.hasCoordinates;
        },
        parsedCoordinates() {
            if (!this.coordinates) return null;
            
            console.log('Parsing coordinates from:', this.coordinates);
            try {
                // Handle string format
                if (typeof this.coordinates === "string") {
                    console.log('Coordinates are a string');
                    // Try parsing as JSON
                    if (this.coordinates.trim().startsWith("{")) {
                        const coords = JSON.parse(this.coordinates);
                        return {
                            lat: parseFloat(coords.lat || coords.latitude || 0),
                            lng: parseFloat(coords.lng || coords.longitude || 0)
                        };
                    } else if (this.coordinates.trim().startsWith("[")) {
                        const coords = JSON.parse(this.coordinates);
                        if (Array.isArray(coords) && coords.length >= 2) {
                            return {
                                lat: parseFloat(coords[0]),
                                lng: parseFloat(coords[1])
                            };
                        }
                    } else {
                        // Try comma-separated format
                        const parts = this.coordinates.split(",");
                        if (parts.length >= 2) {
                            console.log('Parsed as comma-separated parts:', parts);
                            const lat = parseFloat(parts[0].trim());
                            const lng = parseFloat(parts[1].trim());
                            
                            // Sanity check that the values are valid numbers
                            if (!isNaN(lat) && !isNaN(lng)) {
                                console.log('Successfully parsed lat/lng:', lat, lng);
                                return {
                                    lat: lat,
                                    lng: lng
                                };
                            } else {
                                console.warn('Parsed invalid numeric values:', parts);
                            }
                        }
                    }
                } else if (typeof this.coordinates === "object") {
                    console.log('Coordinates are an object');
                    // Already an object
                    return {
                        lat: parseFloat(this.coordinates.lat || this.coordinates.latitude || 0),
                        lng: parseFloat(this.coordinates.lng || this.coordinates.longitude || 0)
                    };
                }
            } catch (e) {
                console.error("Error parsing coordinates:", e);
            }
            
            return null;
        }
    },
    methods: {
        createMarkerIcon() {
            return L.icon({
                iconUrl: `/images/leaflet/markerIcons/coloredFilledWhite/events.svg`,
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowUrl: "/images/leaflet/marker-shadow.png",
                shadowSize: [41, 41],
            });
        },
        onViewMapReady(map) {
            this.leafletViewMap = map;
            this.applyLeafletCSS();
            
            setTimeout(() => {
                if (this.leafletViewMap) {
                    this.leafletViewMap.invalidateSize();
                    if (this.parsedCoordinates) {
                        this.leafletViewMap.setView(
                            [this.parsedCoordinates.lat, this.parsedCoordinates.lng],
                            8
                        );
                    }
                }
            }, 200);
        },
        onEditMapReady(map) {
            this.leafletEditMap = map;
            this.applyLeafletCSS();
            
            setTimeout(() => {
                if (this.leafletEditMap) {
                    this.leafletEditMap.invalidateSize();
                    if (this.parsedCoordinates) {
                        // Set map center to current coordinates
                        this.leafletEditMap.setView(
                            [this.parsedCoordinates.lat, this.parsedCoordinates.lng],
                            8
                        );
                        
                        // Initialize edit coordinates with current values
                        this.editCoordinates = { ...this.parsedCoordinates };
                        console.log('Initialized edit coordinates in map ready:', this.editCoordinates);
                    } else {
                        // Default view if no coordinates
                        this.leafletEditMap.setView(this.mapCenter, 8);
                    }
                }
            }, 200);
        },
        applyLeafletCSS() {
            const styleId = "leaflet-tile-fix";

            // Remove existing style if it exists
            const existingStyle = document.getElementById(styleId);
            if (existingStyle) {
                existingStyle.remove();
            }

            const style = document.createElement("style");
            style.id = styleId;
            style.textContent = `
                /* Grundlegende Map-Fixes */
                .leaflet-container {
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

                /* Korrektur fu00fcr Tile-Bilder */
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
        },
        onMapClick(e) {
            // Get the coordinates from the map click event
            const coords = {
                lat: e.latlng.lat,
                lng: e.latlng.lng
            };
            console.log('Map clicked, coordinates:', coords);
            
            // Update the local editCoordinates state
            this.handleCoordinateUpdate(coords);
        },
        searchLocation() {
            if (!this.searchQuery.trim()) return;
            
            // Use Nominatim for geocoding
            axios.get(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(this.searchQuery)}`)
                .then(response => {
                    if (response.data && response.data.length > 0) {
                        const location = response.data[0];
                        const coords = {
                            lat: parseFloat(location.lat),
                            lng: parseFloat(location.lon)
                        };
                        
                        // Update using our handleCoordinateUpdate method
                        this.handleCoordinateUpdate(coords);
                        
                        if (this.leafletEditMap) {
                            this.leafletEditMap.setView(
                                [coords.lat, coords.lng],
                                13
                            );
                        }
                        
                        console.log('Location searched, coordinates:', coords);
                    }
                })
                .catch(error => {
                    console.error('Error searching location:', error);
                });
        },
        cancelEdit() {
            this.editMode = false;
            this.editCoordinates = this.parsedCoordinates ? { ...this.parsedCoordinates } : null;
        },
        saveCoordinates() {
            if (this.editCoordinates) {
                console.log('Saving coordinates:', this.editCoordinates);
                
                // Format coordinates as string "lat, lng" for consistency
                const lat = parseFloat(this.editCoordinates.lat);
                const lng = parseFloat(this.editCoordinates.lng);
                const coordString = `${lat}, ${lng}`;
                
                // Emit the coordinates string to parent
                this.$emit('update:coordinates', coordString);
                console.log('Emitted coordinates to parent:', coordString);
                
                // Exit edit mode
                this.editMode = false;
            } else {
                console.warn('No coordinates to save');
            }
        },
        updateQualifier() {
            console.log('Updating qualifier to:', this.qualifierValue);
            this.$emit('update:coordinates-qualifier', this.qualifierValue);
        },
        
        // Converts a coordinate object to a string format "lat, lng"
        formatCoordinateString(coords) {
            if (!coords) return null;
            const lat = parseFloat(coords.lat || 0);
            const lng = parseFloat(coords.lng || 0);
            return `${lat}, ${lng}`;
        },
        
        // Main coordinate handling - keeps object format internally, standardizes format
        handleCoordinateUpdate(coords) {
            if (!coords) {
                console.log('handleCoordinateUpdate called with no coordinates');
                return;
            }
            
            // Make sure we have a valid object format for internal use
            let lat, lng;
            
            if (typeof coords === 'string') {
                // Parse string to object
                try {
                    const parts = coords.split(',');
                    if (parts.length >= 2) {
                        lat = parseFloat(parts[0].trim());
                        lng = parseFloat(parts[1].trim());
                    }
                } catch (e) {
                    console.error('Failed to parse string coordinates:', e);
                    return;
                }
            } else if (typeof coords === 'object') {
                // Already an object
                lat = parseFloat(coords.lat || 0);
                lng = parseFloat(coords.lng || 0);
            }
            
            // Validate coordinates
            if (isNaN(lat) || isNaN(lng)) {
                console.error('Invalid coordinates:', coords);
                return;
            }
            
            // Update internal state with object format
            this.editCoordinates = { lat, lng };
            console.log('Updated editCoordinates:', this.editCoordinates);
        },
        
        // Edit mode methods
        startEditMode() {
            console.log('Starting edit mode, coordinates:', this.coordinates);
            console.log('Parsed coordinates:', this.parsedCoordinates);
            
            // Make sure editCoordinates is initialized with the current parsed coordinates
            if (this.parsedCoordinates) {
                const lat = parseFloat(this.parsedCoordinates.lat);
                const lng = parseFloat(this.parsedCoordinates.lng);
                
                if (!isNaN(lat) && !isNaN(lng)) {
                    this.editCoordinates = {
                        lat: lat,
                        lng: lng
                    };
                    console.log('Initialized edit coordinates with:', this.editCoordinates);
                } else {
                    console.error('Invalid parsed coordinates, cannot enter edit mode');
                    return;
                }
            } else {
                console.warn('No parsed coordinates available, starting with empty edit');
                this.editCoordinates = null;
            }
            
            // Enter edit mode
            this.editMode = true;
            
            // Wait for the map to be ready before setting its view
            this.$nextTick(() => {
                setTimeout(() => {
                    if (this.leafletEditMap && this.editCoordinates) {
                        console.log('Setting edit map view to:', this.editCoordinates);
                        this.leafletEditMap.invalidateSize();
                        this.leafletEditMap.setView(
                            [this.editCoordinates.lat, this.editCoordinates.lng],
                            13
                        );
                    }
                }, 300);
            });
        },
    },
    watch: {
        coordinatesQualifier(newVal) {
            console.log('coordinatesQualifier changed to:', newVal);
            this.qualifierValue = newVal || '';
        },
        coordinates: {
            handler(newVal) {
                console.log('coordinates prop changed to:', newVal);
                console.log('Type of new coordinates value:', typeof newVal);
                if (newVal) {
                    // Wait for parsedCoordinates to update
                    this.$nextTick(() => {
                        if (this.parsedCoordinates) {
                            // Initialize edit coordinates for future use
                            this.editCoordinates = { ...this.parsedCoordinates };
                            console.log('Updated editCoordinates from coordinates watch:', this.editCoordinates);
                        } else {
                            console.warn('coordinates changed but parsedCoordinates is null');
                        }
                    });
                }
            },
            immediate: true
        }
    },
    mounted() {
        // Initialize marker icon
        this.markerIcon = this.createMarkerIcon();
        
        // Fix for Leaflet icons
        delete L.Icon.Default.prototype._getIconUrl;
        L.Icon.Default.mergeOptions({
            iconRetinaUrl: "https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon-2x.png",
            iconUrl: "https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon.png",
            shadowUrl: "https://unpkg.com/leaflet@1.7.1/dist/images/marker-shadow.png",
        });
        
        // Set initial edit coordinates if coordinates exist
        if (this.parsedCoordinates) {
            this.editCoordinates = { ...this.parsedCoordinates };
            console.log('Initialized editCoordinates with parsedCoordinates:', this.editCoordinates);
        }
    }
};
</script>

<style scoped>
.section-header {
    margin-bottom: 1rem;
}

.section-label {
    font-weight: bold;
    font-size: 1.1rem;
}

.coordinates-display {
    font-size: 0.95rem;
}

.map-container {
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
}
</style>