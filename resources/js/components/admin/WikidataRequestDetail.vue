<template>
    <v-container>
        <v-row>
            <v-col>
                <v-btn
                    color="primary"
                    class="mb-4"
                    :to="'/admin/wikidata-requests'"
                >
                    <v-icon class="mr-2">mdi-arrow-left</v-icon> Zurück zur
                    Übersicht
                </v-btn>

                <h1
                    class="font-family-special-elite font-weight-bold py-4 text-h4"
                >
                    Wikidata-Eintrag Details
                </h1>
            </v-col>
        </v-row>

        <!-- Ladeindikator -->
        <div v-if="loading" class="d-flex justify-center my-8">
            <v-progress-circular indeterminate color="primary" size="64" />
        </div>

        <!-- Fehlermeldung -->
        <v-alert v-else-if="error" type="error" class="my-4">
            {{ error }}
        </v-alert>

        <!-- Detailansicht -->
        <template v-else-if="entry">
            <!-- Statusbanner -->
            <v-alert :type="getStatusType(entry.status)" class="mb-6">
                <div class="text-h6">
                    Status: {{ getStatusLabel(entry.status) }}
                </div>
                <div v-if="entry.status === 'approved' && entry.wikidata_id">
                    <v-btn
                        :href="getWikidataUrl(entry.wikidata_id)"
                        target="_blank"
                        color="white"
                        class="mt-2"
                    >
                        Wikidata-Eintrag öffnen
                        <v-icon class="ml-2">mdi-open-in-new</v-icon>
                    </v-btn>
                </div>
                <div v-if="entry.status === 'rejected'" class="mt-2">
                    <strong>Ablehnungsgrund:</strong>
                    {{ entry.rejection_reason }}
                </div>
                <div v-if="entry.status === 'error'" class="mt-2">
                    <strong>Fehlermeldung:</strong> {{ entry.error_message }}
                    <v-btn
                        color="warning"
                        class="ml-2"
                        @click="retryEntry(entry.id)"
                    >
                        Wiederholen
                    </v-btn>
                </div>
            </v-alert>

            <!-- Basisinformationen -->
            <v-card class="mb-6">
                <v-card-title class="text-h5 font-weight-bold">
                    {{ entry.title }}
                </v-card-title>
                <v-card-text>
                    <div class="mb-4">
                        <v-chip color="primary" class="mr-2">{{
                            getTypeName(entry.type)
                        }}</v-chip>
                        <v-chip
                            v-if="entry.created_at"
                            size="small"
                            class="mr-2"
                        >
                            Erstellt am: {{ formatDate(entry.created_at) }}
                        </v-chip>
                        <v-chip size="small">
                            Erstellt von:
                            {{ entry.submitted_by || "Unbekannt" }}
                        </v-chip>
                    </div>

                    <div class="text-body-1 mb-4">
                        <div class="text-subtitle-1 font-weight-bold">
                            Beschreibung:
                        </div>
                        {{ entry.description }}
                    </div>

                    <!-- Bild -->
                    <div class="mb-4" v-if="entry.image_url">
                        <div class="mb-2">
                            <div class="text-subtitle-1 font-weight-bold">
                                Bild:
                            </div>
                        </div>

                        <v-card variant="outlined" class="pa-2" max-width="400">
                            <div class="d-flex flex-column">
                                <v-img
                                    :src="entry.image_url"
                                    :alt="entry.image_filename"
                                    max-height="250px"
                                    contain
                                ></v-img>
                                <div class="pa-2 text-caption">
                                    {{ entry.image_filename }}
                                </div>
                            </div>
                        </v-card>
                    </div>

                    <v-divider class="my-4"></v-divider>

                    <!-- Koordinaten mit Karte -->
                    <div v-if="entry.coordinates" class="mb-4">
                        <div class="text-subtitle-1 font-weight-bold mb-1">
                            Koordinaten:
                        </div>
                        <v-card variant="outlined" class="mb-3">
                            <div class="map-container" style="margin: 0 auto">
                                <l-map
                                    ref="mapRef"
                                    :zoom="mapZoom"
                                    :center="mapCenter"
                                >
                                    <l-tile-layer
                                        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                                        :attribution="'&copy; <a href=\'https://www.openstreetmap.org/copyright\'>OpenStreetMap</a> contributors'"
                                    ></l-tile-layer>
                                    <l-marker
                                        :lat-lng="mapCenter"
                                        :icon="markerIcon"
                                    >
                                    </l-marker>
                                </l-map>
                            </div>
                            <div class="selected-coordinates mt-2 text-center">
                                <strong>Koordinaten:</strong>
                                {{ mapCenter[0].toFixed(6) }},
                                {{ mapCenter[1].toFixed(6) }}
                            </div>
                        </v-card>
                    </div>
                </v-card-text>
            </v-card>

            <!-- Typspezifische Informationen -->
            <v-card class="mb-6">
                <v-card-title class="text-h5 font-weight-bold">
                    Spezifische Informationen
                </v-card-title>
                <v-card-text>
                    <!-- Ereignis (Q6983405) -->
                    <div v-if="entry.type === 'Q6983405'" class="mb-4">
                        <div
                            class="mb-3"
                            v-if="entry.time_point || entry.time_period_start"
                        >
                            <div class="text-subtitle-1 font-weight-bold mb-1">
                                <span v-if="entry.time_point">Zeitpunkt:</span>
                                <span v-if="entry.time_period_start">
                                    Zeitraum:</span
                                >
                            </div>
                            <div v-if="entry.time_point">
                                {{ formatDateDay(entry.time_point) }}
                            </div>
                            <div v-else>
                                Von: {{ formatDate(entry.time_period_start) }}
                                <template v-if="entry.time_period_end">
                                    bis: {{ formatDate(entry.time_period_end) }}
                                </template>
                            </div>
                        </div>

                        <div class="mb-3" v-if="entry.cause">
                            <div class="text-subtitle-1 font-weight-bold mb-1">
                                Ursache:
                            </div>
                            <div>{{ entry.cause }}</div>
                        </div>

                        <div
                            class="mb-3"
                            v-if="
                                entry.victim_count ||
                                entry.survivor_count ||
                                entry.death_count ||
                                entry.arrest_count
                            "
                        >
                            <div class="text-subtitle-1 font-weight-bold mb-1">
                                Betroffene Personen:
                            </div>
                            <v-chip
                                v-if="entry.victim_count"
                                size="small"
                                class="mr-2 mb-1"
                                >Opfer: {{ entry.victim_count }}</v-chip
                            >
                            <v-chip
                                v-if="entry.survivor_count"
                                size="small"
                                class="mr-2 mb-1"
                                >Überlebende: {{ entry.survivor_count }}</v-chip
                            >
                            <v-chip
                                v-if="entry.death_count"
                                size="small"
                                class="mr-2 mb-1"
                                >Todesfälle: {{ entry.death_count }}</v-chip
                            >
                            <v-chip
                                v-if="entry.arrest_count"
                                size="small"
                                class="mr-2 mb-1"
                                >Verhaftungen: {{ entry.arrest_count }}</v-chip
                            >
                        </div>
                    </div>

                    <!-- Gedenkort (Q5003624) -->
                    <div v-else-if="entry.type === 'Q5003624'" class="mb-4">
                        <!-- Spezifische Felder für Gedenkort -->
                        <div class="mb-3" v-if="entry.time_point">
                            <div class="text-subtitle-1 font-weight-bold mb-1">
                                Eingeweiht am:
                            </div>
                            <div>{{ formatDate(entry.time_point) }}</div>
                        </div>
                    </div>

                    <!-- Staatspolizeistelle (Q108047567) oder Staatspolizeiaußenstelle (Q108047541) -->
                    <div
                        v-else-if="
                            entry.type === 'Q108047567' ||
                            entry.type === 'Q108047541'
                        "
                        class="mb-4"
                    >
                        <!-- Spezifische Felder für Staatspolizeistellen -->
                        <div
                            class="mb-3"
                            v-if="
                                entry.time_period_start || entry.time_period_end
                            "
                        >
                            <div class="text-subtitle-1 font-weight-bold mb-1">
                                Betriebszeitraum:
                            </div>
                            <div>
                                <template v-if="entry.time_period_start">
                                    Von:
                                    {{ formatDate(entry.time_period_start) }}
                                </template>
                                <template v-if="entry.time_period_end">
                                    bis: {{ formatDate(entry.time_period_end) }}
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Arbeitserziehungslager (Q277565) oder Gefängnis (Q40357) -->
                    <div
                        v-else-if="
                            entry.type === 'Q277565' || entry.type === 'Q40357'
                        "
                        class="mb-4"
                    >
                        <!-- Spezifische Felder für Lager/Gefängnis -->
                        <div
                            class="mb-3"
                            v-if="
                                entry.time_period_start || entry.time_period_end
                            "
                        >
                            <div class="text-subtitle-1 font-weight-bold mb-1">
                                Betriebszeitraum:
                            </div>
                            <div>
                                <template v-if="entry.time_period_start">
                                    Von:
                                    {{ formatDate(entry.time_period_start) }}
                                </template>
                                <template v-if="entry.time_period_end">
                                    bis: {{ formatDate(entry.time_period_end) }}
                                </template>
                            </div>
                        </div>

                        <div
                            class="mb-3"
                            v-if="
                                entry.victim_count ||
                                entry.survivor_count ||
                                entry.death_count
                            "
                        >
                            <div class="text-subtitle-1 font-weight-bold mb-1">
                                Insassen:
                            </div>
                            <v-chip
                                v-if="entry.victim_count"
                                size="small"
                                class="mr-2 mb-1"
                                >Gesamt: {{ entry.victim_count }}</v-chip
                            >
                            <v-chip
                                v-if="entry.survivor_count"
                                size="small"
                                class="mr-2 mb-1"
                                >Überlebende: {{ entry.survivor_count }}</v-chip
                            >
                            <v-chip
                                v-if="entry.death_count"
                                size="small"
                                class="mr-2 mb-1"
                                >Todesfälle: {{ entry.death_count }}</v-chip
                            >
                        </div>
                    </div>

                    <!-- Fallback für andere Typen -->
                    <div v-else>
                        <div
                            class="mb-3"
                            v-if="
                                entry.time_point ||
                                entry.time_period_start ||
                                entry.time_period_end
                            "
                        >
                            <div class="text-subtitle-1 font-weight-bold mb-1">
                                Zeitliche Informationen:
                            </div>
                            <div v-if="entry.time_point">
                                Zeitpunkt: {{ formatDateDay(entry.time_point) }}
                            </div>
                            <div v-if="entry.time_period_start">
                                Von: {{ formatDate(entry.time_period_start) }}
                            </div>
                            <div v-if="entry.time_period_end">
                                Bis: {{ formatDate(entry.time_period_end) }}
                            </div>
                        </div>
                    </div>

                    <!-- Personen (für alle Typen) -->
                    <v-divider class="my-4" v-if="hasPersons"></v-divider>

                    <div
                        v-if="entry.victims && entry.victims.length > 0"
                        class="mb-3"
                    >
                        <div class="text-subtitle-1 font-weight-bold mb-1">
                            Opfer:
                        </div>
                        <Persons :persons="entry.victims" />
                    </div>

                    <div
                        v-if="
                            entry.perpetrators && entry.perpetrators.length > 0
                        "
                        class="mb-3"
                    >
                        <div class="text-subtitle-1 font-weight-bold mb-1">
                            Täter:
                        </div>
                        <Persons :persons="entry.perpetrators" />
                    </div>
                </v-card-text>
            </v-card>

            <!-- Aktionen -->
            <v-card v-if="entry.status === 'pending'">
                <v-card-title class="text-h5 font-weight-bold">
                    Aktionen
                </v-card-title>
                <v-card-text>
                    <v-btn
                        color="success"
                        class="mr-2"
                        @click="openApproveDialog(entry)"
                    >
                        Genehmigen
                    </v-btn>
                    <v-btn color="error" @click="openRejectDialog(entry)">
                        Ablehnen
                    </v-btn>
                </v-card-text>
            </v-card>
        </template>

        <!-- Dialog für Genehmigung -->
        <v-dialog v-model="approveDialog.visible" max-width="600px">
            <v-card>
                <v-card-title>Eintrag genehmigen</v-card-title>
                <v-card-text>
                    <p>
                        Sind Sie sicher, dass Sie diesen Eintrag genehmigen
                        möchten?<br />
                        Der Eintrag wird in Wikidata erstellt.
                    </p>
                    <v-alert type="info" class="mt-3">
                        <strong>Titel:</strong> {{ approveDialog.entry?.title
                        }}<br />
                        <strong>Beschreibung:</strong>
                        {{ approveDialog.entry?.description }}<br />
                        <strong>Typ:</strong>
                        {{ getTypeName(approveDialog.entry?.type) }}
                    </v-alert>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey" @click="approveDialog.visible = false"
                        >Abbrechen</v-btn
                    >
                    <v-btn
                        color="success"
                        @click="approveEntry"
                        :loading="approveDialog.loading"
                    >
                        Genehmigen
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Dialog für Ablehnung -->
        <v-dialog v-model="rejectDialog.visible" max-width="600px">
            <v-card>
                <v-card-title>Eintrag ablehnen</v-card-title>
                <v-card-text>
                    <p>Bitte geben Sie einen Grund für die Ablehnung an:</p>
                    <v-alert type="info" class="mt-3 mb-3">
                        <strong>Titel:</strong> {{ rejectDialog.entry?.title
                        }}<br />
                        <strong>Beschreibung:</strong>
                        {{ rejectDialog.entry?.description }}<br />
                        <strong>Typ:</strong>
                        {{ getTypeName(rejectDialog.entry?.type) }}
                    </v-alert>
                    <v-textarea
                        v-model="rejectDialog.reason"
                        label="Ablehnungsgrund"
                        required
                        rows="3"
                        auto-grow
                    ></v-textarea>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey" @click="rejectDialog.visible = false"
                        >Abbrechen</v-btn
                    >
                    <v-btn
                        color="error"
                        @click="rejectEntry"
                        :loading="rejectDialog.loading"
                        :disabled="!rejectDialog.reason"
                    >
                        Ablehnen
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from "vue";
import { useRoute, useRouter } from "vue-router";
import axios from "axios";
import { LMap, LTileLayer, LMarker, LIcon } from "@vue-leaflet/vue-leaflet";
import "leaflet/dist/leaflet.css";
import * as L from "leaflet";
import Persons from "./fields/Persons.vue";

const route = useRoute();
const router = useRouter();
const requestId = computed(() => route.params.id);
const mapRef = ref(null);

// Karten-Einstellungen
const mapZoom = ref(15);
const mapCenter = computed(() => {
    if (!entry.value?.coordinates) return [0, 0];

    // Format der Koordinaten ist "lat,lon"
    const coords = entry.value.coordinates.split(",");
    if (coords.length === 2) {
        const lat = parseFloat(coords[0].trim());
        const lng = parseFloat(coords[1].trim());
        return [lat, lng];
    }
    return [0, 0];
});

// Benutzerdefiniertes Marker-Icon erstellen
const createCustomIcon = (type) => {
    return L.icon({
        iconUrl: `/images/leaflet/markerIcons/coloredFilledWhite/${type}.svg`,
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowUrl: "/images/leaflet/marker-shadow.png",
        shadowSize: [41, 41],
    });
};

// Icon für den Marker
const markerIcon = computed(() => {
    if (entry.value?.type === "Q6983405") return createCustomIcon("events");
    if (entry.value?.type === "Q5003624") return createCustomIcon("memorials");
    if (entry.value?.type === "Q108047567")
        return createCustomIcon("statePoliceOffices");
    if (entry.value?.type === "Q108047541")
        return createCustomIcon("fieldOffices");
    if (entry.value?.type === "Q277565")
        return createCustomIcon("laborEducationCamps");
    if (entry.value?.type === "Q40357") return createCustomIcon("prisons");
    return createCustomIcon("events"); // Standard-Icon
});

// Zustandsvariablen
const loading = ref(false);
const error = ref(null);
const entry = ref(null);
const notification = ref({ type: "info", message: "" });

// Dialoge
const approveDialog = ref({
    visible: false,
    entry: null,
    loading: false,
});

const rejectDialog = ref({
    visible: false,
    entry: null,
    reason: "",
    loading: false,
});

// Entity-Typen Mapping
const entityTypes = {
    Q6983405: "Ereignis",
    Q5003624: "Erinnerungsort",
    Q108047567: "Staatspolizeistelle",
    Q108047541: "Staatspolizeiaußenstelle",
    Q277565: "Arbeitserziehungslager",
    Q40357: "Gefängnis",
};

// Computed Properties
const hasPersons = computed(() => {
    return (
        (entry.value?.victims && entry.value.victims.length > 0) ||
        (entry.value?.perpetrators && entry.value.perpetrators.length > 0)
    );
});

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
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
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
};

// Reinitialisiert die Karte und sorgt fu00fcr korrekte Darstellung
const reinitializeMap = () => {
    // CSS-Fixes anwenden
    applyLeafletCSS();

    // Map neu initialisieren
    if (mapRef.value && mapRef.value.leafletObject) {
        setTimeout(() => {
            mapRef.value.leafletObject.invalidateSize(true);

            // Neu zentrieren
            const [lat, lng] = mapCenter.value;
            mapRef.value.leafletObject.setView([lat, lng], mapZoom.value);

            // Erzwinge ein Update der Tile-Layer
            mapRef.value.leafletObject.eachLayer(function (layer) {
                if (layer instanceof L.TileLayer) {
                    layer.redraw();
                }
            });
        }, 300);
    }
};

// Eintrag laden
const loadEntry = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await axios.get(
            `/api/wikidata/requests/${requestId.value}`,
        );

        if (response.data.success) {
            entry.value = response.data.request;

            // Karte aktualisieren wenn Koordinaten vorhanden sind
            if (entry.value?.coordinates) {
                await nextTick();
                // CSS-Fixes anwenden und Karte reinitialisieren
                reinitializeMap();
            }
            console.log(entry.value);
        } else {
            error.value =
                response.data.message || "Fehler beim Laden des Eintrags";
        }
    } catch (err) {
        console.error("Fehler beim Laden des Eintrags:", err);
        error.value =
            err.response?.data?.message ||
            "Ein unerwarteter Fehler ist aufgetreten";
    } finally {
        loading.value = false;
    }
};

// Eintrag genehmigen
const approveEntry = async () => {
    approveDialog.value.loading = true;
    try {
        await axios.post(
            `/api/wikidata/requests/${approveDialog.value.entry.id}/approve`,
        );

        notification.value = {
            type: "success",
            message:
                "Der Eintrag wurde erfolgreich genehmigt und wird in Wikidata erstellt.",
        };

        // Dialog schließen und Eintrag neu laden
        approveDialog.value.visible = false;
        loadEntry();
    } catch (error) {
        console.error("Fehler bei der Genehmigung:", error);
        notification.value = {
            type: "error",
            message: `Fehler bei der Genehmigung: ${error.response?.data?.message || error.message}`,
        };
    } finally {
        approveDialog.value.loading = false;
    }
};

// Eintrag ablehnen
const rejectEntry = async () => {
    if (!rejectDialog.value.reason) {
        notification.value = {
            type: "error",
            message: "Bitte geben Sie einen Ablehnungsgrund an.",
        };
        return;
    }

    rejectDialog.value.loading = true;
    try {
        await axios.post(
            `/api/wikidata/requests/${rejectDialog.value.entry.id}/reject`,
            {
                rejection_reason: rejectDialog.value.reason,
            },
        );

        notification.value = {
            type: "success",
            message: "Der Eintrag wurde erfolgreich abgelehnt.",
        };

        // Dialog schließen und Eintrag neu laden
        rejectDialog.value.visible = false;
        rejectDialog.value.reason = "";
        loadEntry();
    } catch (error) {
        console.error("Fehler bei der Ablehnung:", error);
        notification.value = {
            type: "error",
            message: `Fehler bei der Ablehnung: ${error.response?.data?.message || error.message}`,
        };
    } finally {
        rejectDialog.value.loading = false;
    }
};

// Fehlerhaften Eintrag erneut versuchen
const retryEntry = async (id) => {
    try {
        const response = await axios.post(`/api/wikidata/requests/${id}/retry`);

        notification.value = {
            type: "success",
            message: "Der Eintrag wurde zur erneuten Verarbeitung eingereicht.",
        };

        loadEntry();
    } catch (error) {
        console.error("Fehler beim erneuten Versuch:", error);
        notification.value = {
            type: "error",
            message: `Fehler beim erneuten Versuch: ${error.response?.data?.message || error.message}`,
        };
    }
};

// Dialog für Genehmigung öffnen
const openApproveDialog = (entry) => {
    approveDialog.value.entry = entry;
    approveDialog.value.visible = true;
};

// Dialog für Ablehnung öffnen
const openRejectDialog = (entry) => {
    rejectDialog.value.entry = entry;
    rejectDialog.value.visible = true;
    rejectDialog.value.reason = "";
};

// Hilfsfunktionen
const formatDate = (dateString) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    return new Intl.DateTimeFormat("de-DE", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
};

const formatDateDay = (dateString) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    return new Intl.DateTimeFormat("de-DE", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    }).format(date);
};

const getTypeName = (typeId) => {
    return entityTypes[typeId] || typeId;
};

const getWikidataUrl = (id) => {
    return `https://www.wikidata.org/wiki/${id}`;
};

const getStatusLabel = (status) => {
    switch (status) {
        case "pending":
            return "Ausstehend";
        case "approved":
            return "Genehmigt";
        case "rejected":
            return "Abgelehnt";
        case "error":
            return "Fehlerhaft";
        default:
            return status;
    }
};

const getStatusType = (status) => {
    switch (status) {
        case "pending":
            return "info";
        case "approved":
            return "success";
        case "rejected":
            return "error";
        case "error":
            return "warning";
        default:
            return "info";
    }
};

// Komponente initialisieren
onMounted(() => {
    // Apply Leaflet CSS fixes
    applyLeafletCSS();

    // Fix fu00fcr Leaflet-Icons in Vue
    // Dies ist notwendig, da Leaflet Standard-Icon-Pfade erwartet
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: "/images/leaflet/marker-icon-2x.png",
        iconUrl: "/images/leaflet/marker-icon.png",
        shadowUrl: "/images/leaflet/marker-shadow.png",
    });

    loadEntry();

    // Stelle sicher, dass die Karte korrekt gezeichnet wird
    setTimeout(() => {
        reinitializeMap();
    }, 500);
});
</script>

<style scoped>
.font-family-special-elite {
    font-family: "Special Elite" !important;
}

.selected-coordinates {
    padding: 8px;
    background-color: rgba(255, 255, 255, 0.8);
    border-radius: 4px;
    font-size: 14px;
    margin-top: 8px;
    text-align: center;
}

.map-container {
    position: relative;
    overflow: hidden;
    margin: 0 auto;
    height: 400px; /* Angepasste Höhe */
    width: 100%; /* Volle Breite */
}

/* Leaflet-Karte Styles */
:deep(.leaflet-container) {
    width: 100% !important;
    height: 100% !important;
    z-index: 1 !important;
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
}

:deep(.leaflet-control-container .leaflet-top) {
    z-index: 999;
}

:deep(.leaflet-control-container .leaflet-bottom) {
    z-index: 999;
}

:deep(.leaflet-tile-container img) {
    width: 256px !important;
    height: 256px !important;
}
</style>
