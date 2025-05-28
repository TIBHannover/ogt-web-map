<template>
    <div class="event-form-wrapper">
        <div class="step-container">
            <h2 class="step-title">Schritt 2: Ereignis-Details eingeben</h2>
            <form class="forms">
                <!-- Titel/Kurzbeschreibung -->
                <BaseInfos
                    :title="title"
                    :description="description"
                    :wikidata-results="wikidataResultsValue"
                    @update:title="title = $event"
                    @update:description="description = $event"
                    @field-focus="setActiveField"
                    @focusin="setActiveSection('title')"
                    @info-button-click="openSectionInfo"
                    @select-existing-entity="onSelectExistingEntity"
                />

                <!-- Zeitpunkt/Zeitraum -->
                <TimeSelector
                    :event-time-type="eventTimeTypeValue"
                    :event-date="eventDateValue"
                    :event-start-date="eventStartDateValue"
                    :event-end-date="eventEndDateValue"
                    @update:event-time-type="eventTimeTypeValue = $event"
                    @update:event-date="eventDateValue = $event"
                    @update:event-start-date="eventStartDateValue = $event"
                    @update:event-end-date="eventEndDateValue = $event"
                    @field-focus="setActiveField"
                    @info-button-click="openSectionInfo"
                />

                <!-- Opfer-Eingabefeld -->
                <PersonsField
                    label="Geschädigte"
                    fieldId="victims"
                    :peopleSearchResults="peopleSearchResultsValue"
                    :selectedPersons="selectedVictimsValue"
                    :showNewPersonForm="showNewVictimFormValue"
                    :newPerson="newVictimLocal"
                    :isSearchLoading="isVictimSearchLoading"
                    :searchPerformed="victimSearchPerformed"
                    :showInfoButton="true"
                    @searchPeople="onSearchPeople"
                    @selectPerson="onSelectVictim"
                    @startNewPersonForm="onStartNewVictimForm"
                    @cancelNewPersonForm="onCancelNewVictimForm"
                    @update:newPerson="newVictimLocal = $event"
                    @addNewPerson="onAddNewVictim"
                    @removePerson="onRemoveVictim"
                    @focusin="setActiveField('victims')"
                    @infoButtonClick="openSectionInfo('victims')"
                />

                <!-- Täter-Eingabefeld -->
                <PersonsField
                    label="Täter*In"
                    fieldId="perpetrators"
                    :peopleSearchResults="perpetratorsSearchResultsValue"
                    :selectedPersons="selectedPerpetratorsValue"
                    :showNewPersonForm="showNewPerpetratorFormValue"
                    :newPerson="newPerpetratorLocal"
                    :isSearchLoading="isPerpetratorsSearchLoading"
                    :searchPerformed="perpetratorSearchPerformed"
                    :showInfoButton="true"
                    @searchPeople="onSearchPerpetrators"
                    @selectPerson="onSelectPerpetrator"
                    @startNewPersonForm="onStartNewPerpetratorForm"
                    @cancelNewPersonForm="onCancelNewPerpetratorForm"
                    @update:newPerson="newPerpetratorLocal = $event"
                    @addNewPerson="onAddNewPerpetrator"
                    @removePerson="onRemovePerpetrator"
                    @focusin="setActiveField('perpetrators')"
                    @infoButtonClick="openSectionInfo('perpetrators')"
                />

                <!-- Opferzahlen -->
                <VictimsCounts
                    :victim-count="victimCountValue"
                    :survivor-count="survivorCountValue"
                    :death-count="deathCountValue"
                    :arrest-count="arrestCountValue"
                    @update:victim-count="victimCountValue = $event"
                    @update:survivor-count="survivorCountValue = $event"
                    @update:death-count="deathCountValue = $event"
                    @update:arrest-count="arrestCountValue = $event"
                    @field-focus="setActiveField"
                    @info-button-click="openSectionInfo"
                />

                <!-- Ursache der Verfolgung -->
                <CauseSelector
                    :cause="causeValue"
                    :cause-search-results="causeSearchResultsValue"
                    :selected-causes="selectedCausesValue"
                    @search-cause="onSearchCause"
                    @select-cause="onSelectCause"
                    @remove-cause="onRemoveCause"
                    @field-focus="setActiveField"
                    @info-button-click="openSectionInfo"
                />

                <!-- Standort -->
                <LocationSelector
                    :map-zoom="mapZoomValue"
                    :map-center="mapCenterValue"
                    :selected-location="selectedLocationValue"
                    :location-search="locationSearchValue"
                    @update:map-zoom="
                        ($event) => emit('update:mapZoom', $event)
                    "
                    @update:map-center="
                        ($event) => emit('update:mapCenter', $event)
                    "
                    @update:selected-location="
                        ($event) => emit('update:selectedLocation', $event)
                    "
                    @update:location-search="locationSearchValue = $event"
                    @search-location="onSearchLocation"
                    @handle-map-click="onHandleMapClick"
                    @field-focus="setActiveField"
                    @info-button-click="openSectionInfo"
                />

                <!-- Bild aus Wikimedia Commons -->
                <ImageSelector
                    :image-url="imageUrlValue"
                    :image-filename="imageFilenameValue"
                    @update:image-url="imageUrlValue = $event"
                    @update:image-filename="imageFilenameValue = $event"
                    @field-focus="setActiveField"
                    @info-button-click="openSectionInfo"
                />

                <!-- Quellenangaben -->
                <SourcesField
                    :selected-sources="selectedSourcesValue"
                    @update:selected-sources="selectedSourcesValue = $event"
                    @field-focus="setActiveField"
                    @info-button-click="openSectionInfo"
                />
                <!-- Source items display is moved to SourcesField component -->

                <div class="button-container">
                    <button
                        class="button bg-secondary"
                        type="button"
                        @click="onGoToStep(1)"
                    >
                        Zurück
                    </button>
                    <button class="button" type="button" @click="onSubmit">
                        Eintrag anlegen
                    </button>
                </div>
                <div v-if="errorValue" class="bg-error">{{ errorValue }}</div>
            </form>
        </div>

        <!-- Info-Box (Desktop) -->
        <div class="info-box desktop-info" v-if="activeField && !isMobileView">
            <div class="info-box-header">
                <h3>{{ getInfoTitle(activeField) }}</h3>
            </div>
            <div class="info-box-content">
                <div v-html="getInfoContent(activeField)"></div>
            </div>
        </div>
    </div>

    <!-- Mobile Info-Overlay -->
    <div
        class="mobile-info-overlay"
        v-if="showMobileInfoOverlay && isMobileView"
        @click="closeMobileInfoOverlay"
    >
        <div class="overlay-content" @click.stop>
            <div class="overlay-header">
                <h3>{{ getInfoTitle(lastActiveField) }}</h3>
                <button class="close-overlay" @click="closeMobileInfoOverlay">
                    &times;
                </button>
            </div>
            <div class="overlay-body">
                <div v-html="getInfoContent(lastActiveField)"></div>
            </div>
        </div>
    </div>

    <!-- Dialog für Bildauswahl -->
    <v-dialog v-model="imageDialogVisible" max-width="900px">
        <wikimedia-image-selector
            @select="selectImage"
            @close="closeImageDialog"
        />
    </v-dialog>

    <!-- Dialog für Quellenauswahl -->
    <v-dialog v-model="sourcesDialogVisible" max-width="900px">
        <sources-selector @select="selectSource" @close="closeSourcesDialog" />
    </v-dialog>

    <!-- Lightbox für Bildanzeige -->
    <lightbox
        :is-open="lightboxOpen"
        :image-url="imageUrlValue || ''"
        :image-alt="imageFilenameValue || ''"
        :image-caption="imageFilenameValue || ''"
        @close="lightboxOpen = false"
    />
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from "vue";
import * as L from "leaflet";
import PersonsField from "./fields/Persons.vue";
import "leaflet/dist/leaflet.css";
import { LMap, LTileLayer, LMarker } from "@vue-leaflet/vue-leaflet";
import WikimediaImageSelector from "./fields/WikimediaImageSelector.vue";
import SourcesSelector from "./fields/SourcesSelector.vue";
import Lightbox from "../helpers/Lightbox.vue";
import BaseInfos from "./fields/BaseInfos.vue";
import TimeSelector from "./fields/TimeSelector.vue";
import VictimsCounts from "./fields/VictimsCounts.vue";
import CauseSelector from "./fields/CauseSelector.vue";
import LocationSelector from "./fields/LocationSelector.vue";
import ImageSelector from "./fields/ImageSelector.vue";
import SourcesField from "./fields/SourcesField.vue";

// Props von der übergeordneten Komponente
const props = defineProps({
    formsTitle: {
        type: String,
        default: null,
    },
    formsDescription: {
        type: String,
        default: null,
    },
    wikidataResults: {
        type: Array,
        default: () => [],
    },
    peopleSearchResults: {
        type: Array,
        default: () => [],
    },
    selectedVictims: {
        type: Array,
        default: () => [],
    },
    showNewVictimForm: {
        type: Boolean,
        default: false,
    },
    newVictim: {
        type: Object,
        default: () => ({
            firstName: "",
            lastName: "",
            birthDate: null,
            deathDate: null,
            description: "",
        }),
    },
    perpetratorsSearchResults: {
        type: Array,
        default: () => [],
    },
    selectedPerpetrators: {
        type: Array,
        default: () => [],
    },
    showNewPerpetratorForm: {
        type: Boolean,
        default: false,
    },
    newPerpetrator: {
        type: Object,
        default: () => ({
            firstName: "",
            lastName: "",
            birthDate: null,
            deathDate: null,
            description: "",
        }),
    },
    eventTimeType: {
        type: String,
        default: "point", // 'point' oder 'range'
    },
    eventDate: {
        type: String,
        default: null,
    },
    eventStartDate: {
        type: String,
        default: null,
    },
    eventEndDate: {
        type: String,
        default: null,
    },
    victimCount: {
        type: Number,
        default: 0,
    },
    survivorCount: {
        type: Number,
        default: 0,
    },
    deathCount: {
        type: Number,
        default: 0,
    },
    arrestCount: {
        type: Number,
        default: 0,
    },
    causeSearchResults: {
        type: Array,
        default: () => [],
    },
    selectedCauses: {
        type: Array,
        default: () => [],
    },
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
    error: {
        type: String,
        default: null,
    },
    imageUrl: {
        type: String,
        default: null,
    },
    imageFilename: {
        type: String,
        default: null,
    },
    selectedSources: {
        type: Array,
        default: () => [],
    },
});

// Emit-Ereignisse, die an die Elternkomponente gesendet werden
const emit = defineEmits([
    "update:formsTitle",
    "update:formsDescription",
    "update:wikidataResults",
    "update:peopleSearchResults",
    "update:selectedVictims",
    "update:showNewVictimForm",
    "update:newVictim",
    "update:perpetratorsSearchResults",
    "update:selectedPerpetrators",
    "update:showNewPerpetratorForm",
    "update:newPerpetrator",
    "update:eventTimeType",
    "update:eventDate",
    "update:eventStartDate",
    "update:eventEndDate",
    "update:victimCount",
    "update:survivorCount",
    "update:deathCount",
    "update:arrestCount",
    "update:cause",
    "update:causeSearchResults",
    "update:selectedCause",
    "update:mapZoom",
    "update:mapCenter",
    "update:selectedLocation",
    "update:locationSearch",
    "update:error",
    "update:imageUrl",
    "update:imageFilename",
    "update:selectedSources",
    "searchWikidataEntities",
    "selectExistingEntity",
    "searchPeople",
    "selectVictim",
    "startNewVictimForm",
    "cancelNewVictimForm",
    "addNewVictim",
    "removeVictim",
    "startAddingAnotherVictim",
    "searchPerpetrators",
    "selectPerpetrator",
    "startNewPerpetratorForm",
    "cancelNewPerpetratorForm",
    "addNewPerpetrator",
    "removePerpetrator",
    "startAddingAnotherPerpetrator",
    "searchCause",
    "selectCause",
    "removeCause",
    "searchLocation",
    "handleMapClick",
    "goToStep",
    "submit",
]);

// Lokale Zustandsverwaltung durch Computed Properties
const title = computed({
    get: () => props.formsTitle,
    set: (value) => emit("update:formsTitle", value),
});

const description = computed({
    get: () => props.formsDescription,
    set: (value) => emit("update:formsDescription", value),
});

// Zugriff auf übergebene Werte durch Computed Properties (nur lesen)
const wikidataResultsValue = computed(() => props.wikidataResults);
const peopleSearchResultsValue = computed(() => props.peopleSearchResults);
const selectedVictimsValue = computed(() => props.selectedVictims);
const showNewVictimFormValue = computed(() => props.showNewVictimForm);

// Täter computed properties
const perpetratorsSearchResultsValue = computed(
    () => props.perpetratorsSearchResults,
);
const selectedPerpetratorsValue = computed(() => props.selectedPerpetrators);
const showNewPerpetratorFormValue = computed(
    () => props.showNewPerpetratorForm,
);

// Zeitpunkt/Zeitraum computed properties
const eventTimeTypeValue = computed({
    get: () => props.eventTimeType,
    set: (value) => emit("update:eventTimeType", value),
});

const eventDateValue = computed({
    get: () => props.eventDate,
    set: (value) => emit("update:eventDate", value),
});

const eventStartDateValue = computed({
    get: () => props.eventStartDate,
    set: (value) => emit("update:eventStartDate", value),
});

const eventEndDateValue = computed({
    get: () => props.eventEndDate,
    set: (value) => emit("update:eventEndDate", value),
});

// Opferzahlen computed properties
const victimCountValue = computed({
    get: () => props.victimCount,
    set: (value) => emit("update:victimCount", value),
});

const survivorCountValue = computed({
    get: () => props.survivorCount,
    set: (value) => emit("update:survivorCount", value),
});

const deathCountValue = computed({
    get: () => props.deathCount,
    set: (value) => emit("update:deathCount", value),
});

const arrestCountValue = computed({
    get: () => props.arrestCount,
    set: (value) => emit("update:arrestCount", value),
});

// Ursache computed properties
const causeValue = computed({
    get: () => props.cause,
    set: (value) => emit("update:cause", value),
});

const causeSearchResultsValue = computed(() => props.causeSearchResults);

const selectedCausesValue = computed({
    get: () => props.selectedCauses,
    set: (value) => emit("update:selectedCauses", value),
});

const mapZoomValue = computed(() => props.mapZoom);
const mapCenterValue = computed(() => props.mapCenter);
const selectedLocationValue = computed(() => props.selectedLocation);
const locationSearchValue = computed({
    get: () => props.locationSearch,
    set: (value) => emit("update:locationSearch", value),
});
const errorValue = computed(() => props.error);

// Bild-Variablen
const imageUrlValue = computed({
    get: () => props.imageUrl,
    set: (value) => emit("update:imageUrl", value),
});

const imageFilenameValue = computed({
    get: () => props.imageFilename,
    set: (value) => emit("update:imageFilename", value),
});

const selectedSourcesValue = computed({
    get: () => props.selectedSources,
    set: (value) => emit("update:selectedSources", value),
});

// Bild-Dialog
const imageDialogVisible = ref(false);
const lightboxOpen = ref(false);

// Sources-Dialog
const sourcesDialogVisible = ref(false);

// Ladezustände und leere Ergebnisse
const isVictimSearchLoading = ref(false);
const isPerpetratorsSearchLoading = ref(false);
const victimSearchPerformed = ref(false);
const perpetratorSearchPerformed = ref(false);

// Lokale Kopie des newVictim Objekts
const newVictimLocal = ref({
    firstName: "",
    lastName: "",
    birthDate: null,
    deathDate: null,
    description: "",
});

// Lokale Kopie des newPerpetrator Objekts
const newPerpetratorLocal = ref({
    firstName: "",
    lastName: "",
    birthDate: null,
    deathDate: null,
    description: "",
});

// Beobachte Änderungen im Props und aktualisiere die lokale Kopie
watch(
    () => props.newVictim,
    (newValue) => {
        newVictimLocal.value = { ...newValue };
    },
    { immediate: true, deep: true },
);

// Beobachte Änderungen im Props für newPerpetrator
watch(
    () => props.newPerpetrator,
    (newValue) => {
        newPerpetratorLocal.value = { ...newValue };
    },
    { immediate: true, deep: true },
);

// Info-Box Funktionalität
const activeField = ref(null);
const activeSection = ref(null);
const showMobileInfoOverlay = ref(false);
const isMobileView = ref(false);
const lastActiveField = ref(null);

// Funktion zum Setzen des aktiven Feldes
const setActiveField = (fieldName) => {
    activeField.value = fieldName;
    lastActiveField.value = fieldName; // Speichern des Feldnamens für mobile Anzeige
};

// Funktion zum U00dcberpru00fcfen der Bildschirmgro00dfe
const checkScreenSize = () => {
    isMobileView.value = window.innerWidth < 1200;
};

// Funktion zum Öffnen des Info-Bereichs (Desktop) oder Overlays (Mobil) für eine Sektion
const openSectionInfo = (sectionName) => {
    lastActiveField.value = sectionName;
    activeField.value = sectionName; // Set activeField for desktop view
    showMobileInfoOverlay.value = isMobileView.value; // Only show overlay on mobile
};

// Funktion zum Schlieu00dfen des mobilen Info-Overlays
const closeMobileInfoOverlay = () => {
    showMobileInfoOverlay.value = false;
};

// Bildschirmgru00f6u00dfe beim Laden und bei Gro00dfena00e4nderungüberpru00fcfen
onMounted(() => {
    checkScreenSize();
    window.addEventListener("resize", checkScreenSize);
});

// Funktion zum Setzen der aktiven Sektion
const setActiveSection = (sectionName) => {
    activeSection.value = sectionName;
};

// Info-Box-Titel basierend auf dem aktiven Bereich
const getInfoTitle = (fieldName) => {
    const sectionTitles = {
        title: "Basis-Informationen",
        time: "Zeitliche Informationen",
        "victim-count": "Geschädigte-Zahlen",
        cause: "Ursache der Verfolgung",
        location: "Standort",
        image: "Bild",
        victims: "Geschädigte Personen",
        perpetrators: "Täter*Innen",
    };

    if (sectionTitles[fieldName]) {
        return sectionTitles[fieldName];
    } else {
        return "Information";
    }
};

// Info-Box-Inhalt basierend auf dem aktiven Feld oder Sektion
const getInfoContent = (fieldName) => {
    // Zuordnung von Feldern zu Sektionen
    const fieldToSection = {
        title: "title",
        description: "title",
        "event-date": "time",
        "event-start-date": "time",
        "event-end-date": "time",
        "victim-count": "counts",
        "survivor-count": "counts",
        "death-count": "counts",
        "arrest-count": "counts",
    };

    // Wenn das Feld einer Sektion zugeordnet ist, diese verwenden
    const sectionName = fieldToSection[fieldName] || fieldName;

    return getSectionInfoContent(sectionName);
};

// Konsolidierte Hilfetexte pro Sektion
const getSectionInfoContent = (sectionName) => {
    const sectionContents = {
        title: `<p>In diesem Bereich geben Sie die grundlegenden Informationen zum Ereignis ein.</p>
               <p><strong>Titel:</strong> Geben Sie einen präzisen und aussagekräftigen Titel ein, der Art des Ereignisses, Zeit/Ort und ggf. beteiligte Gruppen enthält.</p>
               <p><strong>Beschreibung:</strong> Beschreiben Sie das Ereignis kurz und informativ mit den wichtigsten Fakten, beteiligten Personen und historischem Kontext.</p>
               <p>Beispiele für gute Titel: "Novemberpogrom 1938 in Osnabrück", "Deportation der jüdischen Bevölkerung aus Hamburg (1941)"</p>`,

        time: `<p>Hier erfassen Sie die zeitlichen Informationen zum Ereignis.</p>
               <p>Sie können zwischen einem einzelnen Zeitpunkt oder einem Zeitraum wählen:</p>
               <ul>
                 <li><strong>Zeitpunkt:</strong> Für Ereignisse, die an einem bestimmten Datum stattfanden</li>
                 <li><strong>Zeitraum:</strong> Für länger andauernde Ereignisse mit Start- und Enddatum</li>
               </ul>
               <p>Falls das genaue Datum nicht bekannt ist, wählen Sie einen bestmöglichen Schätzwert und vermerken Sie die Unsicherheit in der Beschreibung.</p>`,

        counts: `<p>In diesem Bereich können Sie die Opferzahlen des Ereignisses dokumentieren.</p>
                <p>Sie können folgende Informationen erführen (sofern bekannt):</p>
                <ul>
                  <li><strong>Gesamtzahl der Opfer:</strong> Alle betroffenen Personen</li>
                  <li><strong>Anzahl der Überlebenden:</strong> Personen, die das Ereignis überlebt haben</li>
                  <li><strong>Anzahl der Todesopfer:</strong> Personen, die durch das Ereignis ums Leben kamen</li>
                  <li><strong>Anzahl der Festnahmen:</strong> Personen, die im Zuge des Ereignisses festgenommen wurden</li>
                </ul>
                <p>Verwenden Sie nur gesicherte Zahlen aus verlässlichen Quellen. Bei Unsicherheit ist ein Bereich (z.B. "etwa 100-150 Opfer") in der Beschreibung besser als eine exakte Zahl.</p>`,

        cause: `<p>Hier dokumentieren Sie die Ursache(n) der Verfolgung.</p>
               <p>Wählen Sie die Hauptursache(n) aus oder fügen Sie neue hinzu. Typische Kategorien sind:</p>
               <ul>
                 <li>Religiöse Verfolgung</li>
                 <li>Rassistische Verfolgung</li>
                 <li>Politische Verfolgung</li>
                 <li>Soziale Verfolgung</li>
               </ul>
               <p>Eine präzise Dokumentation der Verfolgungsursachen ist wichtig für das Verständnis historischer Zusammenhänge.</p>`,

        location: `<p>Hier markieren Sie den genauen Ort des Ereignisses auf der Karte.</p>
                  <p>Sie können:</p>
                  <ul>
                    <li>Direkt auf die Karte klicken, um einen Standort zu markieren</li>
                    <li>Nach einem Ort suchen, um ihn schneller zu finden</li>
                    <li>Die Karte zoomen und verschieben, um den genauen Standort zu lokalisieren</li>
                  </ul>
                  <p>Präzise Ortsangaben sind wichtig für die historische Dokumentation und ermöglichen eine genaue geografische Einordnung.</p>`,

        image: `<p>Hier können Sie ein passendes Bild zum Ereignis hinzufügen.</p>
               <p>Geeignete Bilder sind:</p>
               <ul>
                 <li>Historische Fotos vom Ereignis</li>
                 <li>Bilder des Ortes</li>
                 <li>Dokumente zum Ereignis</li>
                 <li>Denkmäler oder Gedenktafeln</li>
               </ul>
               <p>Verwenden Sie nur Bilder, die Sie rechtlich nutzen dürfen, und achten Sie auf korrekte Quellenangaben.</p>`,

        sources: `<p>Hier können Sie Quellenangaben zum Ereignis hinzufügen.</p>
                <p>Sie können verschiedene Arten von Quellen angeben:</p>
                <ul>
                  <li><strong>Bücher:</strong> Wissenschaftliche Werke, Monographien, Sammelbände etc.</li>
                  <li><strong>URLs:</strong> Vertrauenswürdige Webseiten, Online-Archive, Datenbanken</li>
                  <li><strong>Archive:</strong> Archivmaterial mit Inventarnummer</li>
                </ul>
                <p>Bei Buchquellen können Sie nach bereits in Wikidata erfassten Werken suchen oder neue Werke manuell hinzufügen. Geben Sie möglichst genaue Seitenangaben an.</p>
                <p>Bei URLs ist das Zugriffsdatum wichtig, um die zeitliche Verfügbarkeit der Information zu dokumentieren.</p>
                <p>Bei Archivquellen geben Sie das entsprechende Archiv und die Inventarnummer an.</p>`,

        victims: `<p>Hier fügen Sie die Geschädigten oder Opfer des Ereignisses hinzu.</p>
                <p>Sie können nach bereits bekannten Personen suchen oder neue Personen manuell hinzufügen.</p>
                <p>Bei neuen Personen achten Sie bitte auf korrekte Namensschreibweisen, Lebensdaten und eine kurze Beschreibung.</p>
                <p>Dies hilft bei der Verknüpfung mit anderen Ereignissen und der Nachverfolgung von Schicksalen.</p>`,

        perpetrators: `<p>Hier dokumentieren Sie die für das Ereignis verantwortlichen Personen oder Gruppen.</p>
                     <p>Beachten Sie dabei die juristische und historische Genauigkeit, unterscheiden Sie zwischen direkten Tätern und Befehlsgebern und dokumentieren Sie auch institutionelle Verantwortlichkeiten.</p>
                     <p>Eine präzise Dokumentation der Täter ist ein wichtiger Teil der historischen Aufarbeitung.</p>`,
    };

    return (
        sectionContents[sectionName] ||
        "<p>Wählen Sie einen Bereich aus, um Hilfe zu erhalten.</p>"
    );
};

// Benutzerdefiniertes Marker-Icon erstellen
const createCustomIcon = (type) => {
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
const markerIcon = createCustomIcon("Q6983405");

// Map reference und Initialisierung
const mapRef = ref(null);
const leafletMap = ref(null); // Referenz zur Leaflet-Map-Instance

// Wird aufgerufen, wenn die Leaflet-Karte bereit ist
const onMapReady = (map) => {
    leafletMap.value = map;

    setTimeout(() => {
        if (leafletMap.value) {
            leafletMap.value.invalidateSize();
            if (selectedLocationValue.value) {
                leafletMap.value.setView(
                    [
                        selectedLocationValue.value.lat,
                        selectedLocationValue.value.lng,
                    ],
                    mapZoomValue.value,
                );
            } else {
                leafletMap.value.setView(
                    mapCenterValue.value,
                    mapZoomValue.value,
                );
            }
        }
    }, 200);
};

// Computed für die Prüfung, ob ein neues Opfer hinzugefügt werden kann
const canAddNewVictim = computed(() => {
    return newVictimLocal.value.firstName && newVictimLocal.value.lastName;
});

// Computed für die Prüfung, ob ein neuer Täter hinzugefügt werden kann
const canAddNewPerpetrator = computed(() => {
    return (
        newPerpetratorLocal.value.firstName &&
        newPerpetratorLocal.value.lastName
    );
});

// Ereignishandler, die Emits an die Elternkomponente senden
const onTitleInput = () => emit("searchWikidataEntities");
const onSelectExistingEntity = (entity) => emit("selectExistingEntity", entity);
const onSearchPeople = (query) => {
    if (query && query.length >= 2) {
        // Kurze Verzögerung, damit das Ladeicon nicht bei jeder Eingabe kurz aufflackert
        setTimeout(() => {
            if (!peopleSearchResultsValue.value.length) {
                isVictimSearchLoading.value = true;
            }
        }, 300);
        victimSearchPerformed.value = true;
    } else {
        isVictimSearchLoading.value = false;
        victimSearchPerformed.value = false;
    }
    emit("searchPeople", query);
};
const onSelectVictim = (person) => emit("selectVictim", person);
const onStartNewVictimForm = () => emit("startNewVictimForm");
const onCancelNewVictimForm = () => emit("cancelNewVictimForm");

const onAddNewVictim = () => {
    emit("update:newVictim", newVictimLocal.value);
    emit("addNewVictim");
};

const onRemoveVictim = (id) => emit("removeVictim", id);
const onStartAddingAnotherVictim = () => emit("startAddingAnotherVictim");

// Täter (Perpetrator) Handler-Funktionen
const onSearchPerpetrators = (query) => {
    if (query && query.length >= 2) {
        // Kurze Verzögerung, damit das Ladeicon nicht bei jeder Eingabe kurz aufflackert
        setTimeout(() => {
            if (!perpetratorsSearchResultsValue.value.length) {
                isPerpetratorsSearchLoading.value = true;
            }
        }, 300);
        perpetratorSearchPerformed.value = true;
    } else {
        isPerpetratorsSearchLoading.value = false;
        perpetratorSearchPerformed.value = false;
    }
    emit("searchPerpetrators", query);
};
const onSelectPerpetrator = (person) => emit("selectPerpetrator", person);
const onStartNewPerpetratorForm = () => emit("startNewPerpetratorForm");
const onCancelNewPerpetratorForm = () => emit("cancelNewPerpetratorForm");

const onAddNewPerpetrator = () => {
    emit("update:newPerpetrator", newPerpetratorLocal.value);
    emit("addNewPerpetrator");
};

const onRemovePerpetrator = (id) => emit("removePerpetrator", id);
const onStartAddingAnotherPerpetrator = () =>
    emit("startAddingAnotherPerpetrator");

// Ursachen-Handler
const onSearchCause = (query) => emit("searchCause", query);
const onSelectCause = (cause) => emit("selectCause", cause);
const onRemoveCause = (causeId) => emit("removeCause", causeId);

const onSearchLocation = () => emit("searchLocation");
const onHandleMapClick = (e) => {
    // Lokaler Map-Click-Handler, der die Emit-Funktion aufruft
    emit("handleMapClick", e);

    // Zusätzlich, falls wir lokale Map-Aktualisierungen benötigen
    if (leafletMap.value) {
        setTimeout(() => {
            leafletMap.value.invalidateSize();
        }, 50);
    }
};
const onGoToStep = (step) => emit("goToStep", step);
const onSubmit = () => emit("submit");

// Ladet den Ladezustand zurücksetzen, wenn Ergebnisse da sind oder wenn nichts gefunden wurde
watch(
    () => peopleSearchResultsValue.value,
    () => {
        isVictimSearchLoading.value = false;
    },
);

watch(
    () => perpetratorsSearchResultsValue.value,
    () => {
        isPerpetratorsSearchLoading.value = false;
    },
);

// Bild-Auswahllogik
const openImageSelector = () => {
    imageDialogVisible.value = true;
};

const closeImageDialog = () => {
    imageDialogVisible.value = false;
};

const selectImage = (image) => {
    imageUrlValue.value = image.url;
    console.log("Selected image URL:", image.url);
    imageFilenameValue.value = image.filename;
    console.log("Selected image filename:", image.filename);
    imageDialogVisible.value = false;
};

const removeImage = () => {
    imageUrlValue.value = null;
    imageFilenameValue.value = null;
};

// Sources-Auswahllogik
const openSourcesSelector = () => {
    sourcesDialogVisible.value = true;
};

const closeSourcesDialog = () => {
    sourcesDialogVisible.value = false;
};

const selectSource = (data) => {
    const newSources = [...selectedSourcesValue.value, data.source];
    emit("update:selectedSources", newSources);
    sourcesDialogVisible.value = false;
};

const removeSource = (index) => {
    const newSources = [...selectedSourcesValue.value];
    newSources.splice(index, 1);
    emit("update:selectedSources", newSources);
};

// Formatiert ein Datum im deutschen Format
const formatDate = (dateString) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    return date.toLocaleDateString("de-DE");
};

const openLightbox = () => {
    if (
        imageUrlValue.value &&
        typeof imageUrlValue.value === "string" &&
        imageUrlValue.value.trim() !== ""
    ) {
        lightboxOpen.value = true;
    }
};
</script>

<style scoped>
.font-family-special-elite {
    font-family: "Special Elite" !important;
}

.forms-container {
    width: 100%;
}

.step-container {
    background-color: rgba(255, 255, 255, 0.9);
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.step-title {
    margin-bottom: 20px;
    font-size: 1.5rem;
    color: #333;
}

/* Layout für Formular mit Info-Box */
.event-form-wrapper {
    display: flex;
    flex-direction: row;
    gap: 2rem;
    width: 100%;
}

.step-container {
    background-color: rgba(255, 255, 255, 0.9);
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    width: 65%; /* Reduzierte Breite, um Platz für die Info-Box zu lassen */
}

.info-box {
    width: 30%;
    padding: 1.5rem;
    background-color: #f8f9fa;
    border-radius: 8px;
    position: sticky;
    top: 2rem;
    align-self: flex-start;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-top: 2.7rem; /* Align with first input field */
    max-height: calc(100vh - 4rem);
    overflow-y: auto;
}

.info-box-header {
    border-bottom: 1px solid #e9ecef;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
}

.info-box-header h3 {
    font-size: 1.2rem;
    color: #1976d2;
    margin: 0;
}

.info-box-content {
    font-size: 0.95rem;
    line-height: 1.5;
    color: #495057;
}

.info-box-content p {
    margin-bottom: 1rem;
}

.info-box-content ul {
    padding-left: 1.5rem;
    margin-bottom: 1rem;
}

.info-box-content li {
    margin-bottom: 0.5rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
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

/* Mobile Info Overlay */
.mobile-info-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: flex;
    justify-content: center;
    align-items: center;
}

.overlay-content {
    width: 90%;
    max-width: 500px;
    max-height: 80vh;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.overlay-header {
    padding: 1rem;
    background-color: #1976d2;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.overlay-header h3 {
    margin: 0;
    font-size: 1.2rem;
}

.overlay-body {
    padding: 1rem;
    overflow-y: auto;
    max-height: 60vh;
}

.close-overlay {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    margin: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.3s ease;
}

.close-overlay:hover {
    background-color: rgba(255, 255, 255, 0.2);
}

@media (max-width: 1200px) {
    .event-form-wrapper {
        flex-direction: column;
    }

    .step-container {
        width: 100%;
    }

    .info-box {
        display: none; /* Desktop-Infobox auf mobilen Geräten ausblenden */
    }
}

@media (min-width: 1200px) {
    .info-icon {
        display: none !important;
    }
}

.forms > *:not(:last-child) {
    margin-bottom: 1rem;
}

.forms input,
.forms textarea {
    padding: 0.75rem 1rem;
    width: 100%;
    background-color: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}

.forms input:focus,
.forms textarea:focus {
    outline: none;
    border-color: #1976d2;
    box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.25);
}

.forms textarea {
    min-height: 8.5rem;
    resize: none;
}

.forms fieldset {
    display: block;
    width: 100%;
    border-color: transparent;
}

.forms fieldset div {
    margin-top: 0.75rem;
}

.forms fieldset div {
    display: flex;
    flex-direction: row;
    align-items: center;
}

.forms fieldset div input {
    margin-right: 0.75rem;
    width: 1rem;
    height: 1rem;
    box-shadow: none;
}

.forms fieldset div label {
    flex: 1 0 0;
}

.button-container {
    display: flex;
    justify-content: space-between;
    width: 100%;
    margin-top: 2.5rem;
}

.button {
    padding: 12px 24px;
    background-color: #1976d2;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.2s;
}

.button:hover {
    background-color: #1565c0;
}

.button:disabled {
    background-color: #bbdefb;
    cursor: not-allowed;
}

.button.bg-secondary {
    background-color: #757575;
}

.button.bg-secondary:hover {
    background-color: #616161;
}

.bg-error {
    color: #d32f2f;
    margin-top: 1rem;
    font-weight: bold;
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

/* Wikidata search results */
.search-results {
    width: 100%;
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #ccc;
    border-radius: 4px;
    margin-top: -0.5rem;
    background-color: #fff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    z-index: 20;
    position: relative;
}

.search-info {
    padding: 10px 15px;
    font-size: 0.9rem;
    color: #666;
    border-bottom: 1px solid #eee;
    margin: 0;
}

.search-result-item {
    padding: 10px 12px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    transition: background-color 0.2s;
}

.search-result-item:hover {
    background-color: #f5f5f5;
}

.search-result-item:last-child {
    border-bottom: none;
}

.result-title {
    font-weight: bold;
    margin-bottom: 3px;
}

.result-description {
    font-size: 0.9rem;
    color: #555;
    margin-bottom: 3px;
}

.result-id {
    font-size: 0.8rem;
    color: #888;
}

/* Opfer/Victims Styling */
.victims-section {
    width: 100%;
    margin-bottom: 1.5rem;
}

.victims-search {
    margin-bottom: 0.5rem;
}

.search-loading {
    display: flex;
    align-items: center;
    margin-top: 0.5rem;
    color: #666;
}

.search-loading span {
    margin-left: 0.5rem;
}

.search-no-results {
    margin-top: 0.5rem;
    padding: 0.5rem;
    background-color: #f8f8f8;
    border-radius: 4px;
    color: #666;
    font-style: italic;
}

.form-group {
    margin-bottom: 1rem;
    width: 100%;
}

.form-group label {
    display: block;
    margin-bottom: 0.25rem;
}

.new-victim-form {
    background-color: #f5f5f5;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}

.new-victim-form h4 {
    margin-top: 0;
    margin-bottom: 1rem;
}

.button-group {
    display: flex;
    justify-content: space-between;
}

.selected-victims {
    margin-top: 1rem;
    padding: 0.5rem;
    background-color: #f5f5f5;
    border-radius: 4px;
}

.selected-victim {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    background-color: white;
    border-radius: 4px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.victim-name {
    font-weight: 500;
    margin-right: 0.5rem;
}

.victim-description {
    color: #666;
    font-size: 0.9rem;
    flex-grow: 1;
}

.remove-victim {
    background: none;
    border: none;
    color: #d32f2f;
    cursor: pointer;
    font-size: 1rem;
    font-weight: bold;
    padding: 0.25rem 0.5rem;
}

.result-metadata {
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 0.25rem;
}

.result-metadata span {
    margin-right: 0.75rem;
}

.new-person-option,
.add-another-victim,
.add-another-perpetrator {
    padding: 0.75rem;
    border-top: 1px solid #eee;
    text-align: center;
}

/* Zeitpunkt/Zeitraum Styling */
.container-section {
    width: 100%;
    margin-bottom: 1.5rem;
    background-color: #f5f5f5;
    padding: 1rem;
    border-radius: 4px;
}

label.section-label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.time-type-selection {
    display: flex;
    gap: 2rem;
    margin-bottom: 1rem;
}

.time-type-selection .form-group {
    display: flex;
    align-items: center;
    margin-bottom: 0;
}

.time-type-selection .form-group input {
    width: auto;
    margin-right: 0.5rem;
}

.time-range-inputs {
    display: flex;
    gap: 1rem;
    width: 100%;
}

.time-range-inputs .form-group {
    flex: 1;
}

/* Täter Styling */
.perpetrators-section {
    width: 100%;
    margin-bottom: 1.5rem;
}

.perpetrators-search {
    margin-bottom: 0.5rem;
}

.perpetrators-search .search-loading,
.perpetrators-search .search-no-results {
    /* Stile werden von den generischen Klassen geerbt */
}

.new-perpetrator-form {
    background-color: #f5f5f5;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}

.new-perpetrator-form h4 {
    margin-top: 0;
    margin-bottom: 1rem;
}

.selected-perpetrators {
    margin-top: 1rem;
    padding: 0.5rem;
    background-color: #f5f5f5;
    border-radius: 4px;
}

.selected-perpetrator {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    background-color: white;
    border-radius: 4px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.perpetrator-name {
    font-weight: 500;
    margin-right: 0.5rem;
}

.perpetrator-description {
    color: #666;
    font-size: 0.9rem;
    flex-grow: 1;
}

.remove-perpetrator {
    background: none;
    border: none;
    color: #d32f2f;
    cursor: pointer;
    font-size: 1rem;
    font-weight: bold;
    padding: 0.25rem 0.5rem;
}

.counts-section .form-group {
    margin-bottom: 0.75rem;
}

.cause-search {
    margin-bottom: 0.5rem;
}

.cause-search input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.selected-causes {
    margin-top: 0.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.cause-display {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    background-color: white;
    border-radius: 4px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    margin-bottom: 0.5rem;
    position: relative;
}

.cause-name {
    font-weight: 500;
    margin-right: 0.5rem;
}

.cause-description {
    color: #666;
    font-size: 0.9rem;
    flex-grow: 1;
}

.remove-cause {
    background: none;
    border: none;
    color: #d32f2f;
    cursor: pointer;
    font-size: 1rem;
    font-weight: bold;
    padding: 0.25rem 0.5rem;
}

/* Bildauswahl-Stile */
.image-section {
    margin-top: 1rem;
    margin-bottom: 1rem;
}

.image-selection {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
}

.selected-image {
    width: 100%;
    max-width: 400px;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
    background-color: white;
}

.preview-image {
    width: 100%;
    height: auto;
    max-height: 200px;
    object-fit: contain;
    transition:
        transform 0.2s,
        filter 0.2s;
}

.preview-image:hover {
    transform: scale(1.03);
    filter: brightness(1.1);
}

.image-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    background-color: #f5f5f5;
    border-top: 1px solid #ddd;
}

.click-hint {
    text-align: center;
    font-size: 0.8rem;
    color: #555;
    padding: 0.2rem 0;
    background-color: #f9f9f9;
    border-top: 1px dashed #eee;
}

.image-filename {
    font-size: 0.9rem;
    color: #666;
    max-width: 320px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.remove-image {
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
    font-size: 1.2rem;
    padding: 0 0.5rem;
}

/* Quellenangaben Stile */
.sources-section {
    margin-top: 1rem;
    margin-bottom: 1rem;
}

.selected-sources {
    margin-top: 1rem;
}

.source-item {
    display: flex;
    align-items: flex-start;
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 1rem;
    margin-bottom: 0.8rem;
    position: relative;
}

.source-type-icon {
    margin-right: 1rem;
    color: #555;
}

.source-content {
    flex: 1;
}

.source-title {
    font-weight: bold;
    margin-bottom: 0.3rem;
}

.source-description {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.source-details {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
    color: #777;
}

.source-link {
    color: #005691;
    text-decoration: none;
    font-size: 0.8rem;
    display: inline-flex;
    align-items: center;
}

.remove-source {
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
    font-size: 1.2rem;
    padding: 0;
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
}
</style>
