<template>
    <div
        class="memorial-form-wrapper"
        :class="{ 'admin-view': props.isAdminView }"
    >
        <div class="step-container">
            <h2 class="step-title">
                Schritt 2: Erinnerungsort-Details eingeben
            </h2>
            <form class="forms" @submit.prevent>
                <!-- Titel/Kurzbeschreibung -->
                <BaseInfos
                    ref="baseInfosRef"
                    :title="title"
                    :description="description"
                    :entry-type="props.entryType"
                    :wikidata-results="wikidataResultsValue"
                    @update:title="title = $event"
                    @update:description="description = $event"
                    @fieldFocus="setActiveField"
                    @focusin="setActiveSection('title')"
                    @infoButtonClick="openSectionInfo"
                    @select-existing-entity="onSelectExistingEntity"
                    @validation="onBaseInfoValidation"
                />

                <!-- Erinnert an (Commemorates) -->
                <div class="container-section commemorates-section">
                    <div class="section-header">
                        <label class="section-label">Erinnert an: *</label>
                        <button
                            type="button"
                            class="info-icon"
                            @click="openSectionInfo('commemorates')"
                        >
                            <v-icon>mdi-information-variant</v-icon>
                        </button>
                    </div>
                    <WikidataSelector
                        v-model="commemoratesValue"
                        label="Erinnert an (z.B. ein Ereignis, eine Person oder Gruppe)"
                        dialog-title="Wählen Sie, woran dieser Ort erinnert"
                        search-label="Nach Ereignissen, Personen, etc. suchen..."
                        :search-results="commemoratesSearchResultsValue"
                        :is-loading="isCommmemoratesSearchLoading"
                        :search-performed="commemoratesSearchPerformed"
                        endpoint="commemorates"
                        property-id="P547"
                        @search="onSearchCommemorates"
                        @fieldFocus="setActiveField('commemorates')"
                    />
                    <ValidationError
                        :show="showValidationErrors && !isCommmemoratesValid"
                        message="Bitte geben Sie an, woran dieser Ort erinnert."
                        showIcon
                    />
                </div>

                <!-- Standort -->
                <LocationSelector
                    ref="locationSelectorRef"
                    :map-zoom="mapZoomValue"
                    :map-center="mapCenterValue"
                    :selected-location="selectedLocationValue"
                    :location-search="locationSearchValue"
                    :location-qualifier="locationQualifierValue"
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
                    @update:location-qualifier="locationQualifierValue = $event"
                    @search-location="onSearchLocation"
                    @handle-map-click="onHandleMapClick"
                    @fieldFocus="setActiveField"
                    @infoButtonClick="openSectionInfo"
                    @validation="onLocationValidation"
                />

                <!-- Betreiber (Operator) -->
                <div class="container-section operator-section">
                    <div class="section-header">
                        <label class="section-label">Betreiber:</label>
                        <button
                            type="button"
                            class="info-icon"
                            @click="openSectionInfo('operator')"
                        >
                            <v-icon>mdi-information-variant</v-icon>
                        </button>
                    </div>
                    <WikidataSelector
                        v-model="operatorValue"
                        label="Betreiber"
                        dialog-title="Betreiber des Erinnerungsorts auswählen"
                        search-label="Nach Organisationen suchen..."
                        :search-results="operatorSearchResultsValue"
                        :is-loading="isOperatorSearchLoading"
                        :search-performed="operatorSearchPerformed"
                        endpoint="organizations"
                        property-id="P137"
                        @search="onSearchOperator"
                        @fieldFocus="setActiveField('operator')"
                    />
                </div>

                <!-- Eröffnungsdatum (Opening Date) -->
                <div class="container-section opening-date-section">
                    <div class="section-header">
                        <label class="section-label">Eröffnungsdatum:</label>
                        <button
                            type="button"
                            class="info-icon"
                            @click="openSectionInfo('openingDate')"
                        >
                            <v-icon>mdi-information-variant</v-icon>
                        </button>
                    </div>
                    <TimeSelector
                        ref="timeSelectorRef"
                        :event-time-type="'point'"
                        :event-date="openingDateValue"
                        :event-date-qualifier="openingDateQualifierValue"
                        @update:eventDate="openingDateValue = $event"
                        @update:eventDateQualifier="
                            openingDateQualifierValue = $event
                        "
                        @fieldFocus="setActiveField"
                        @infoButtonClick="openSectionInfo"
                        :hideToggle="true"
                        :sectionLabel="'Eröffnungsdatum:'"
                        :pointLabel="'Eröffnungsdatum'"
                        :pointDateLabel="'Datum der Eröffnung'"
                        :propertyId="'P1619'"
                        :validationMessageRequired="'Bitte gib das Eröffnungsdatum an.'"
                        :validationMessageInvalid="'Das Eröffnungsdatum hat ein ungültiges Format.'"
                        @validation="onTimeValidation"
                    />
                </div>

                <!-- Adresse (Address) -->
                <div class="container-section address-section">
                    <div class="section-header">
                        <label class="section-label">Adresse:</label>
                        <button
                            type="button"
                            class="info-icon"
                            @click="openSectionInfo('address')"
                        >
                            <v-icon>mdi-information-variant</v-icon>
                        </button>
                    </div>
                    <TextField
                        v-model="addressValue"
                        label="Adresse"
                        placeholder="Straße, Hausnummer, PLZ, Ort"
                        property-id="P6375"
                        @fieldFocus="setActiveField('address')"
                    />
                </div>

                <!-- Offizielle Website (Official Website) -->
                <div class="container-section website-section">
                    <div class="section-header">
                        <label class="section-label">Offizielle Website:</label>
                        <button
                            type="button"
                            class="info-icon"
                            @click="openSectionInfo('website')"
                        >
                            <v-icon>mdi-information-variant</v-icon>
                        </button>
                    </div>
                    <TextField
                        v-model="websiteValue"
                        label="Offizielle Website"
                        placeholder="https://www.example.com"
                        property-id="P856"
                        type="url"
                        :rules="websiteRules"
                        @fieldFocus="setActiveField('website')"
                    />
                </div>

                <!-- Bild aus Wikimedia Commons -->
                <ImageSelector
                    :imageUrl="imageUrlValue"
                    :imageFilename="imageFilenameValue"
                    @update:imageUrl="imageUrlValue = $event"
                    @update:imageFilename="imageFilenameValue = $event"
                    @fieldFocus="setActiveField"
                    @infoButtonClick="openSectionInfo"
                />

                <!-- Quellenangaben -->
                <SourcesField
                    ref="sourcesFieldRef"
                    :selectedSources="selectedSourcesValue"
                    @update:selectedSources="selectedSourcesValue = $event"
                    @fieldFocus="setActiveField"
                    @infoButtonClick="openSectionInfo"
                    @validation="onSourcesValidation"
                    :required="true"
                />

                <div class="button-container" v-if="!props.isAdminView">
                    <button
                        class="button bg-secondary"
                        type="button"
                        @click="onGoToStep(1)"
                    >
                        Zurück
                    </button>
                    <button
                        class="button bg-primary"
                        type="button"
                        @click="onSubmit"
                    >
                        Absenden
                    </button>
                </div>
            </form>
        </div>

        <!-- Bild Dialog -->
        <WikimediaImageSelector
            v-if="imageDialogVisible"
            @select="selectImage"
            @close="closeImageDialog"
        />

        <!-- Quellen Dialog -->
        <SourcesSelector
            v-if="sourcesDialogVisible"
            @select-source="selectSource"
            @close="closeSourcesDialog"
        />
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import BaseInfos from "./fields/BaseInfos.vue";
import ValidationError from "../helpers/ValidationError.vue";
import LocationSelector from "./fields/LocationSelector.vue";
import WikidataSelector from "./fields/common/WikidataSelector.vue";
import TextField from "./fields/common/TextField.vue";
import TimeSelector from "./fields/TimeSelector.vue";
import ImageSelector from "./fields/ImageSelector.vue";
import SourcesField from "./fields/SourcesField.vue";
import SourcesSelector from "./fields/SourcesSelector.vue";
import WikimediaImageSelector from "./fields/WikimediaImageSelector.vue";
import * as L from "leaflet";
import axios from "axios";

// Props
const props = defineProps({
    formsTitle: {
        type: String,
        default: "",
    },
    formsDescription: {
        type: String,
        default: "",
    },
    wikidataResults: {
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
    locationQualifier: {
        type: String,
        default: "",
    },
    error: {
        type: String,
        default: "",
    },
    imageUrl: {
        type: String,
        default: "",
    },
    imageFilename: {
        type: String,
        default: "",
    },
    selectedSources: {
        type: Array,
        default: () => [],
    },
    entryType: {
        type: String,
        default: "Q5003624",
    },
    isAdminView: {
        type: Boolean,
        default: false,
    },
});

// Emits
const emit = defineEmits([
    "update:formsTitle",
    "update:formsDescription",
    "update:wikidataResults",
    "update:mapZoom",
    "update:mapCenter",
    "update:selectedLocation",
    "update:locationSearch",
    "update:locationQualifier",
    "update:error",
    "update:imageUrl",
    "update:imageFilename",
    "update:selectedSources",
    "searchWikidataEntities",
    "selectExistingEntity",
    "searchLocation",
    "handleMapClick",
    "goToStep",
    "submit",
]);

// Local reactive refs for two-way binding
const title = ref(props.formsTitle);
const description = ref(props.formsDescription);
const wikidataResultsValue = ref(props.wikidataResults);

// Coordinates, location
const mapZoomValue = ref(props.mapZoom);
const mapCenterValue = ref(props.mapCenter);
const selectedLocationValue = ref(props.selectedLocation);
const locationSearchValue = ref(props.locationSearch);
const locationQualifierValue = ref(props.locationQualifier);
const errorValue = ref(props.error);

// Image
const imageUrlValue = ref(props.imageUrl);
const imageFilenameValue = ref(props.imageFilename);

// Sources
const selectedSourcesValue = ref(props.selectedSources);

// Specific fields for MemorialForm
const commemoratesValue = ref(null);
const commemoratesSearchResultsValue = ref([]);
const isCommmemoratesSearchLoading = ref(false);
const commemoratesSearchPerformed = ref(false);

const operatorValue = ref(null);
const operatorSearchResultsValue = ref([]);
const isOperatorSearchLoading = ref(false);
const operatorSearchPerformed = ref(false);

// Optional fields
const openingDateValue = ref("");
const openingDateQualifierValue = ref("");

const addressValue = ref("");
const websiteValue = ref("");

// Dialog states
const imageDialogVisible = ref(false);
const sourcesDialogVisible = ref(false);

// Info sections
const activeField = ref(null);
const activeSection = ref(null);
const showMobileInfoOverlay = ref(false);
const isMobileView = ref(false);
const lastActiveField = ref(null);

// Set active field for info display
const setActiveField = (field) => {
    activeField.value = field;
    lastActiveField.value = field;
};

// Check screen size
const checkScreenSize = () => {
    isMobileView.value = window.innerWidth < 768;
};

const openSectionInfo = (section) => {
    activeSection.value = section;
    showMobileInfoOverlay.value = isMobileView.value;
    emit("infoButtonClick", section);
};

// Close the mobile overlay
// This function is used in mobile view to close info overlays
// eslint-disable-next-line no-unused-vars
const closeMobileInfoOverlay = () => {
    showMobileInfoOverlay.value = false;
};

// Section management
const setActiveSection = (section) => {
    activeSection.value = section;
};

// Information content for info popups - used when displaying info
// eslint-disable-next-line no-unused-vars
const getInfoTitle = (section) => {
    const infoTitles = {
        title: "Titel & Beschreibung",
        commemorates: "Erinnert an",
        location: "Standort",
        operator: "Betreiber",
        openingDate: "Eröffnungsdatum",
        address: "Adresse",
        website: "Offizielle Website",
        images: "Bilder",
        sources: "Quellenangaben",
    };
    return infoTitles[section] || "Information";
};
// Use getInfoTitle in openSectionInfo

// Info content for different sections - used when displaying section info
// This is used by the mobile info overlay
// eslint-disable-next-line no-unused-vars
const getSectionInfoContent = (section) => {
    switch (section) {
        case "title":
            return `
                <p>Bitte gebe einen <strong>präzisen Titel</strong> für den Erinnerungsort ein.
                Der Titel sollte eindeutig und klar sein.</p>
                <p>Die <strong>Kurzbeschreibung</strong> sollte die wichtigsten Informationen zum Erinnerungsort
                in wenigen Sätzen zusammenfassen.</p>
                <p>Beispiel:<br>
                <strong>Titel:</strong> KZ-Gedenkstätte Neuengamme<br>
                <strong>Beschreibung:</strong> Gedenkstätte auf dem Gelände des ehemaligen Konzentrationslagers Neuengamme in Hamburg.</p>
            `;
        case "commemorates":
            return `
                <p>Geben Sie an, woran dieser Erinnerungsort erinnert. Dies kann ein Ereignis, eine Person, eine Gruppe oder ein anderer historischer Aspekt sein.</p>
                <p>Beispiele:</p>
                <ul>
                    <li>Holocaust</li>
                    <li>Novemberpogrome 1938</li>
                    <li>Widerstandskämpfer</li>
                    <li>Zwangsarbeiter</li>
                </ul>
                <p>Diese Information ist <strong>verpflichtend</strong>, da sie den historischen Kontext des Erinnerungsortes definiert.</p>
            `;
        case "location":
            return `
                <p>Bitte gebe den <strong>genauen Standort</strong> des Erinnerungsortes an. Du kannst entweder:</p>
                <ul>
                    <li>Nach einer Adresse oder einem Ort suchen</li>
                    <li>Direkt auf die Karte klicken, um den Standort zu markieren</li>
                </ul>
                <p>Die Koordinaten werden automatisch erfasst und sind ein <strong>Pflichtfeld</strong>.</p>
                <p>Zusätzlich kannst du einen <strong>Qualifizierer</strong> auswählen, um die Genauigkeit der Koordinaten anzugeben.</p>
            `;
        case "operator":
            return `
                <p>Geben Sie die Organisation oder Institution an, die den Erinnerungsort betreibt oder verwaltet.</p>
                <p>Beispiele:</p>
                <ul>
                    <li>Stiftung niedersächsische Gedenkstätten</li>
                    <li>Gedenkstättenverein</li>
                    <li>Stadt oder Gemeinde</li>
                </ul>
                <p>Diese Information ist optional, hilft aber bei der Vernetzung der Daten.</p>
            `;
        case "openingDate":
            return `
                <p>Wann wurde dieser Erinnerungsort eröffnet oder eingeweiht?</p>
                <p>Diese Information hilft, die zeitliche Entwicklung der Erinnerungskultur zu dokumentieren.</p>
                <p>Falls das genaue Datum nicht bekannt ist, kann auch nur das Jahr oder der ungefähre Zeitraum angegeben werden.</p>
            `;
        case "address":
            return `
                <p>Geben Sie die vollständige Postadresse des Erinnerungsortes ein.</p>
                <p>Format: Straße, Hausnummer, PLZ, Ort</p>
                <p>Beispiel: Gedenkstättenweg 1, 12345 Musterstadt</p>
            `;
        case "website":
            return `
                <p>Geben Sie die offizielle Website des Erinnerungsortes an.</p>
                <p>Bitte geben Sie die vollständige URL mit http:// oder https:// an.</p>
                <p>Beispiel: https://www.gedenkstaette-beispiel.de</p>
                <p>Diese URL wird geprüft und muss ein gültiges Format haben.</p>
            `;
        case "images":
            return `
                <p>Du kannst ein Bild von Wikimedia Commons auswählen, das den Erinnerungsort zeigt.</p>
                <p>Klicke auf "Bild suchen", um nach passenden Bildern zu suchen. Bilder sollten:</p>
                <ul>
                    <li>Frei verwendbar sein (auf Wikimedia Commons)</li>
                    <li>Den Erinnerungsort klar darstellen</li>
                    <li>Gute Qualität haben</li>
                </ul>
            `;
        case "sources":
            return `
                <p>Bitte gib deine <strong>Quellen</strong> an. Diese sind wichtig, um die Zuverlässigkeit und Nachvollziehbarkeit deiner Angaben zu gewährleisten.</p>
                <p>Du kannst aus verschiedenen Quellentypen wählen:</p>
                <ul>
                    <li><strong>Literatur:</strong> Bücher, wissenschaftliche Arbeiten</li>
                    <li><strong>Webseiten:</strong> Offizielle Seiten, Archive, Forschungsportale</li>
                    <li><strong>Archivmaterial:</strong> Dokumente, Akten, historische Aufzeichnungen</li>
                </ul>
                <p>Mindestens <strong>eine Quelle</strong> ist erforderlich.</p>
            `;
        default:
            return `<p>Wähle eine Kategorie aus, um mehr Informationen zu erhalten.</p>`;
    }
};

// Custom icon for the map marker
const createCustomIcon = () => {
    return L.icon({
        iconUrl: `/images/leaflet/markerIcons/coloredFilledWhite/memorials.svg`,
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowUrl: "/images/leaflet/marker-shadow.png",
        shadowSize: [41, 41],
    });
};

// Create the marker icon - used for the map marker
// eslint-disable-next-line no-unused-vars
const markerIcon = createCustomIcon();
// This will be used when the marker is added to the map

// Form validation rules
const websiteRules = [
    (value) => {
        if (!value) return true; // Optional field
        const pattern = new RegExp(
            "^(https?://)?([\\da-z.-]+)\\.([a-z.]{2,6})([/\\w .-]*)*/?$",
        );
        return (
            pattern.test(value) ||
            "Bitte geben Sie eine gültige URL ein (z.B. https://www.example.com)"
        );
    },
];

// Map reference - will be used for map interactions
// eslint-disable-next-line no-unused-vars
const mapRef = ref(null); // Reference needed for LocationSelector
// eslint-disable-next-line no-unused-vars
const leafletMap = ref(null); // Will store the Leaflet map instance

// Event handlers
const onSearchCommemorates = async (query) => {
    if (!query || query.trim() === "") return;

    isCommmemoratesSearchLoading.value = true;
    commemoratesSearchPerformed.value = true;
    commemoratesSearchResultsValue.value = [];

    try {
        const response = await axios.get("/api/wikidata/search", {
            params: {
                query: query,
                type: "commemorates",
            },
        });

        if (response.data && Array.isArray(response.data)) {
            commemoratesSearchResultsValue.value = response.data;
        }
    } catch (error) {
        console.error("Error searching for commemorates:", error);
    } finally {
        isCommmemoratesSearchLoading.value = false;
    }
};

const onSearchOperator = async (query) => {
    if (!query || query.trim() === "") return;

    isOperatorSearchLoading.value = true;
    operatorSearchPerformed.value = true;
    operatorSearchResultsValue.value = [];

    try {
        const response = await axios.get("/api/wikidata/search", {
            params: {
                query: query,
                type: "organizations",
            },
        });

        if (response.data && Array.isArray(response.data)) {
            operatorSearchResultsValue.value = response.data;
        }
    } catch (error) {
        console.error("Error searching for operators:", error);
    } finally {
        isOperatorSearchLoading.value = false;
    }
};

const onSearchLocation = () => {
    emit("searchLocation");
};

const onHandleMapClick = (event) => {
    emit("handleMapClick", event);
};

const onGoToStep = (step) => {
    emit("goToStep", step);
};

const onSelectExistingEntity = (entity) => {
    emit("selectExistingEntity", entity);
};

const onSubmit = () => {
    if (validateForm()) {
        emit("submit", getData());
    }
};

// References for validation
const timeSelectorRef = ref(null);
const baseInfosRef = ref(null);
const sourcesFieldRef = ref(null);
const locationSelectorRef = ref(null);

// Validation states
const isTimeValid = ref(true);
const isBaseInfoValid = ref(true);
const isSourcesValid = ref(true);
const isLocationValid = ref(true);

// Validation handlers - used when form fields report their validation state
// These are called by the respective components during validation
const onTimeValidation = (status) => {
    // Opening date is optional, so always mark as valid
    isTimeValid.value = true;
};

const onBaseInfoValidation = (status) => {
    isBaseInfoValid.value = status;
};

const onSourcesValidation = (status) => {
    isSourcesValid.value = status;
};

const onLocationValidation = (status) => {
    isLocationValid.value = status;
};

// Is commemorates field valid
const isCommmemoratesValid = computed(() => {
    return !!commemoratesValue.value;
});

// Form validation
const showValidationErrors = ref(false);

const validateForm = () => {
    showValidationErrors.value = true;

    // Call validate methods on child components
    const baseInfoValid = baseInfosRef.value
        ? baseInfosRef.value.validate()
        : true;
    const sourcesValid = sourcesFieldRef.value
        ? sourcesFieldRef.value.validate()
        : true;
    const locationValid = locationSelectorRef.value
        ? locationSelectorRef.value.validate()
        : true;

    // Direct validation for specific fields
    const commemoratesValid = !!commemoratesValue.value;

    const isValid =
        baseInfoValid && sourcesValid && locationValid && commemoratesValid;

    if (!isValid) {
        const errors = [];
        if (!baseInfoValid) errors.push("Titel und Beschreibung");
        if (!commemoratesValid) errors.push("Erinnert an");
        if (!locationValid) errors.push("Standort");
        if (!sourcesValid) errors.push("Quellenangaben");

        errorValue.value = `Bitte fülle alle Pflichtfelder aus: ${errors.join(", ")}`;
        emit("update:error", errorValue.value);
    } else {
        errorValue.value = "";
        emit("update:error", "");
    }

    return isValid;
};

// Collect all data for submission
const getData = () => {
    // Prepare the data object to return
    const formData = {
        title: title.value,
        description: description.value,
        type: "Q5003624", // Memorial place type
        coordinates: selectedLocationValue.value
            ? {
                  latitude: selectedLocationValue.value.lat,
                  longitude: selectedLocationValue.value.lng,
              }
            : null,
        coordinates_qualifier: locationQualifierValue.value,

        // Memorial-specific fields
        commemorates: commemoratesValue.value, // P547
        operator: operatorValue.value, // P137
        opening_date: openingDateValue.value, // P1619
        opening_date_qualifier: openingDateQualifierValue.value,
        address: addressValue.value, // P6375
        website: websiteValue.value, // P856

        // Image and sources
        image: imageUrlValue.value
            ? {
                  url: imageUrlValue.value,
                  filename: imageFilenameValue.value,
              }
            : null,
        sources: selectedSourcesValue.value,
    };

    // Clean up any undefined/null values for arrays
    Object.keys(formData).forEach((key) => {
        if (Array.isArray(formData[key]) && formData[key].length === 0) {
            formData[key] = null;
        }
    });

    return formData;
};

// Computed property for form validity - used during form submission
// This is referenced in the validateForm function
// eslint-disable-next-line no-unused-vars
const isFormValid = computed(() => {
    return (
        isBaseInfoValid.value &&
        isSourcesValid.value &&
        isLocationValid.value &&
        isCommmemoratesValid.value
    );
});

// Image handling - used by the ImageSelector component
// This is called when the user clicks to add/change an image
// eslint-disable-next-line no-unused-vars
const openImageSelector = () => {
    imageDialogVisible.value = true;
};

const closeImageDialog = () => {
    imageDialogVisible.value = false;
};

const selectImage = (imageData) => {
    imageUrlValue.value = imageData.url;
    imageFilenameValue.value = imageData.filename;
    emit("update:imageUrl", imageData.url);
    emit("update:imageFilename", imageData.filename);
    closeImageDialog();
};

// Source handling - used by the SourcesSelector component
// This is called when the user clicks to add a source
// eslint-disable-next-line no-unused-vars
const openSourcesSelector = () => {
    sourcesDialogVisible.value = true;
};

const closeSourcesDialog = () => {
    sourcesDialogVisible.value = false;
};

const selectSource = (source) => {
    selectedSourcesValue.value = [...selectedSourcesValue.value, source];
    emit("update:selectedSources", selectedSourcesValue.value);
    closeSourcesDialog();
};

// Watch for prop changes
watch(
    () => props.formsTitle,
    (newVal) => {
        title.value = newVal;
    },
);

watch(
    () => props.formsDescription,
    (newVal) => {
        description.value = newVal;
    },
);

watch(
    () => props.wikidataResults,
    (newVal) => {
        wikidataResultsValue.value = newVal;
    },
);

watch(
    () => props.mapZoom,
    (newVal) => {
        mapZoomValue.value = newVal;
    },
);

watch(
    () => props.mapCenter,
    (newVal) => {
        mapCenterValue.value = newVal;
    },
);

watch(
    () => props.selectedLocation,
    (newVal) => {
        selectedLocationValue.value = newVal;
    },
);

watch(
    () => props.locationSearch,
    (newVal) => {
        locationSearchValue.value = newVal;
    },
);

watch(
    () => props.locationQualifier,
    (newVal) => {
        locationQualifierValue.value = newVal;
    },
);

watch(
    () => props.error,
    (newVal) => {
        errorValue.value = newVal;
    },
);

watch(
    () => props.imageUrl,
    (newVal) => {
        imageUrlValue.value = newVal;
    },
);

watch(
    () => props.imageFilename,
    (newVal) => {
        imageFilenameValue.value = newVal;
    },
);

watch(
    () => props.selectedSources,
    (newVal) => {
        selectedSourcesValue.value = newVal;
    },
);

// Watch for changes in selectedSourcesValue and emit to parent
watch(
    () => selectedSourcesValue.value,
    (newVal) => {
        emit("update:selectedSources", newVal);
    },
);

// Emit changes back to parent
watch(
    () => title.value,
    (newVal) => {
        emit("update:formsTitle", newVal);
    },
);

watch(
    () => description.value,
    (newVal) => {
        emit("update:formsDescription", newVal);
    },
);

// Component setup
onMounted(() => {
    checkScreenSize();
    window.addEventListener("resize", checkScreenSize);
});

// Expose methods to parent component
defineExpose({
    getData,
});
</script>

<style scoped>
@import "./styles/form-styles.css";

.container-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.section-label {
    font-weight: 600;
    font-size: 1.1rem;
    color: #333;
}

.info-icon {
    background: none;
    border: none;
    color: #1976d2;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.info-icon:hover {
    background-color: rgba(25, 118, 210, 0.1);
}

.button-container {
    display: flex;
    justify-content: space-between;
    margin-top: 2rem;
}

.button {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
}
</style>
