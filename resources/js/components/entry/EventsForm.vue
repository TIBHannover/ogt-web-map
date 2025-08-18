<template>
    <div
        class="event-form-wrapper"
        :class="{ 'admin-view': props.isAdminView }"
    >
        <div class="step-container">
            <h2 class="step-title">Schritt 2: Ereignis-Details eingeben</h2>
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
                    @field-focus="setActiveField"
                    @focusin="setActiveSection('title')"
                    @info-button-click="openSectionInfo"
                    @select-existing-entity="onSelectExistingEntity"
                    @validation="onBaseInfoValidation"
                />

                <!-- Zeitpunkt/Zeitraum -->
                <TimeSelector
                    ref="timeSelectorRef"
                    :event-time-type="eventTimeTypeValue"
                    :event-date="eventDateValue"
                    :event-date-qualifier="eventDateQualifierValue"
                    :event-start-date="eventStartDateValue"
                    :event-start-date-qualifier="eventStartDateQualifierValue"
                    :event-end-date="eventEndDateValue"
                    :event-end-date-qualifier="eventEndDateQualifierValue"
                    @update:event-time-type="updateEventTimeType($event)"
                    @update:event-date="eventDateValue = $event"
                    @update:event-date-qualifier="
                        eventDateQualifierValue = $event
                    "
                    @update:event-start-date="eventStartDateValue = $event"
                    @update:event-start-date-qualifier="
                        eventStartDateQualifierValue = $event
                    "
                    @update:event-end-date="eventEndDateValue = $event"
                    @update:event-end-date-qualifier="
                        eventEndDateQualifierValue = $event
                    "
                    @field-focus="setActiveField"
                    @info-button-click="openSectionInfo"
                    @validation="onTimeValidation"
                    required="true"
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
                    @updatePerson="onUpdateVictim"
                    @removePerson="onRemoveVictim"
                    @focusin="setActiveField('victims')"
                    @infoButtonClick="openSectionInfo('victims')"
                />

                <!-- Täter-Eingabefeld -->
                <PersonsField
                    ref="perpetratorsRef"
                    label="Täter*In *"
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
                    @updatePerson="onUpdatePerpetrator"
                    @removePerson="onRemovePerpetrator"
                    @focusin="setActiveField('perpetrators')"
                    @infoButtonClick="openSectionInfo('perpetrators')"
                    @validation="onPerpetratorsValidation"
                />

                <!-- Opferzahlen -->
                <VictimsCounts
                    :victim-count="victimCountValue"
                    :survivor-count="survivorCountValue"
                    :death-count="deathCountValue"
                    :arrest-count="arrestCountValue"
                    :victim-count-qualifier="victimCountQualifierValue"
                    :survivor-count-qualifier="survivorCountQualifierValue"
                    :death-count-qualifier="deathCountQualifierValue"
                    :arrest-count-qualifier="arrestCountQualifierValue"
                    @update:victim-count="victimCountValue = $event"
                    @update:survivor-count="survivorCountValue = $event"
                    @update:death-count="deathCountValue = $event"
                    @update:arrest-count="arrestCountValue = $event"
                    @update:victim-count-qualifier="
                        victimCountQualifierValue = $event
                    "
                    @update:survivor-count-qualifier="
                        survivorCountQualifierValue = $event
                    "
                    @update:death-count-qualifier="
                        deathCountQualifierValue = $event
                    "
                    @update:arrest-count-qualifier="
                        arrestCountQualifierValue = $event
                    "
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
                <slot name="location-selector">
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
                        @update:location-qualifier="
                            locationQualifierValue = $event
                        "
                        @search-location="onSearchLocation"
                        @handle-map-click="onHandleMapClick"
                        @field-focus="setActiveField"
                        @info-button-click="openSectionInfo"
                        @validation="onLocationValidation"
                    />
                </slot>

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
                    ref="sourcesFieldRef"
                    :selected-sources="selectedSourcesValue"
                    @update:selected-sources="selectedSourcesValue = $event"
                    @field-focus="setActiveField"
                    @info-button-click="openSectionInfo"
                    @validation="onSourcesValidation"
                    required="true"
                />
                <!-- Source items display is moved to SourcesField component -->

                <div class="button-container" v-if="!props.isAdminView">
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
                <div v-if="!props.isAdminView" class="required-fields-note">
                    * Pflichtfelder müssen ausgefüllt werden
                </div>
                <div v-if="errorValue" class="bg-error">{{ errorValue }}</div>
            </form>
        </div>

        <!-- Info-Box (Desktop) -->
        <div
            class="info-box desktop-info"
            v-if="activeField && !isMobileView && !isAdminView"
        >
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
        v-if="showMobileInfoOverlay && (isMobileView || props.isAdminView)"
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
    isAdminView: {
        type: Boolean,
        default: false,
    },
    formsTitle: {
        type: String,
        default: null,
    },
    formsDescription: {
        type: String,
        default: null,
    },
    entryType: {
        type: String,
        default: "Q6983405", // Default to Ereignis type
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
            isOrganization: false,
            gender: null,
            nationality: null,
            place_of_birth: null,
            place_of_death: null,
            place_of_detention: null,
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
            isOrganization: false,
            gender: null,
            nationality: null,
            place_of_birth: null,
            place_of_death: null,
            place_of_detention: null,
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
    eventDateQualifier: {
        type: String,
        default: "",
    },
    eventStartDate: {
        type: String,
        default: null,
    },
    eventStartDateQualifier: {
        type: String,
        default: "",
    },
    eventEndDate: {
        type: String,
        default: null,
    },
    eventEndDateQualifier: {
        type: String,
        default: "",
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
    victimCountQualifier: {
        type: String,
        default: "",
    },
    survivorCountQualifier: {
        type: String,
        default: "",
    },
    deathCountQualifier: {
        type: String,
        default: "",
    },
    arrestCountQualifier: {
        type: String,
        default: "",
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
    locationQualifier: {
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
    "update:eventDateQualifier",
    "update:eventStartDate",
    "update:eventStartDateQualifier",
    "update:eventEndDate",
    "update:eventEndDateQualifier",
    "update:victimCount",
    "update:survivorCount",
    "update:deathCount",
    "update:arrestCount",
    "update:victimCountQualifier",
    "update:survivorCountQualifier",
    "update:deathCountQualifier",
    "update:arrestCountQualifier",
    "update:cause",
    "update:causeSearchResults",
    "update:selectedCause",
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
    "updatePerpetrator",
    "removePerpetrator",
    "updateVictim",
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
    set: (value) => {
        console.log("EventsForm: Updating title to:", value);
        emit("update:formsTitle", value);
    },
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

const eventDateQualifierValue = computed({
    get: () => props.eventDateQualifier,
    set: (value) => emit("update:eventDateQualifier", value),
});

const eventStartDateValue = computed({
    get: () => props.eventStartDate,
    set: (value) => emit("update:eventStartDate", value),
});

const eventStartDateQualifierValue = computed({
    get: () => props.eventStartDateQualifier,
    set: (value) => emit("update:eventStartDateQualifier", value),
});

const eventEndDateValue = computed({
    get: () => props.eventEndDate,
    set: (value) => emit("update:eventEndDate", value),
});

const eventEndDateQualifierValue = computed({
    get: () => props.eventEndDateQualifier,
    set: (value) => emit("update:eventEndDateQualifier", value),
});

// Opferzahlen computed properties
const victimCountValue = computed({
    get: () => props.victimCount,
    set: (value) => {
        emit("update:victimCount", value);
    },
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

// Qualifier computed properties
const victimCountQualifierValue = computed({
    get: () => {
        console.log(
            "Getting victim count qualifier:",
            props.victimCountQualifier,
        );
        return props.victimCountQualifier;
    },
    set: (value) => {
        console.log("Setting victim count qualifier to:", value);
        emit("update:victimCountQualifier", value);
    },
});

const survivorCountQualifierValue = computed({
    get: () => props.survivorCountQualifier,
    set: (value) => emit("update:survivorCountQualifier", value),
});

const deathCountQualifierValue = computed({
    get: () => props.deathCountQualifier,
    set: (value) => emit("update:deathCountQualifier", value),
});

const arrestCountQualifierValue = computed({
    get: () => props.arrestCountQualifier,
    set: (value) => emit("update:arrestCountQualifier", value),
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

const locationQualifierValue = computed({
    get: () => props.locationQualifier,
    set: (value) => emit("update:locationQualifier", value),
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
    isOrganization: false,
    gender: null,
    nationality: null,
    place_of_birth: null,
    place_of_death: null,
    place_of_detention: null,
});

// Lokale Kopie des newPerpetrator Objekts
const newPerpetratorLocal = ref({
    firstName: "",
    lastName: "",
    birthDate: null,
    deathDate: null,
    description: "",
    isOrganization: false,
    gender: null,
    nationality: null,
    place_of_birth: null,
    place_of_death: null,
    place_of_detention: null,
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

const setActiveField = (fieldName) => {
    activeField.value = fieldName;
    lastActiveField.value = fieldName;
};

const checkScreenSize = () => {
    isMobileView.value = window.innerWidth < 1200;
};

const openSectionInfo = (sectionName) => {
    lastActiveField.value = sectionName;
    activeField.value = sectionName;
    showMobileInfoOverlay.value = isMobileView.value || props.isAdminView;
};

const closeMobileInfoOverlay = () => {
    showMobileInfoOverlay.value = false;
};

onMounted(() => {
    checkScreenSize();
    window.addEventListener("resize", checkScreenSize);
});

const setActiveSection = (sectionName) => {
    activeSection.value = sectionName;
};

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
        sources: "Quellenangaben",
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
               <p><strong>Diese Angabe ist verpflichtend.</strong></p>
               <p>Sie können zwischen einem einzelnen Zeitpunkt oder einem Zeitraum wählen:</p>
               <ul>
                 <li><strong>Zeitpunkt:</strong> Für Ereignisse, die an einem bestimmten Datum stattfanden</li>
                 <li><strong>Zeitraum:</strong> Für länger andauernde Ereignisse mit Start- und Enddatum</li>
               </ul>
               <p>Falls das genaue Datum nicht bekannt ist, wählen Sie einen bestmöglichen Schätzwert und vermerken Sie die Unsicherheit in der Beschreibung.</p>`,

        counts: `<p>In diesem Bereich können Sie die Geschädigtenzahl des Ereignisses dokumentieren.</p>
                <p>Sie können folgende Informationen erführen (sofern bekannt):</p>
                <ul>
                  <li><strong>Gesamtzahl der Geschädigten:</strong> Alle betroffenen Personen</li>
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
                <p><strong>Diese Angabe ist verpflichtend:</strong> Jedes Ereignis muss mindestens eine Quelle enthalten.</p>
                <p>Sie können verschiedene Arten von Quellen angeben:</p>
                <ul class="mb-3">
                  <li><strong>Bücher:</strong> Wissenschaftliche Werke, Monographien, Sammelbände etc.</li>
                  <li><strong>URLs:</strong> Vertrauenswürdige Webseiten, Online-Archive, Datenbanken</li>
                  <li><strong>Archive:</strong> Archivmaterial mit Inventarnummer</li>
                </ul>
                <p>Bei Buchquellen können Sie nach bereits in Wikidata erfassten Werken suchen oder neue Werke manuell hinzufügen. Geben Sie möglichst genaue Seitenangaben an.</p>
                <p>Bei URLs ist das Zugriffsdatum wichtig, um die zeitliche Verfügbarkeit der Information zu dokumentieren.</p>
                <p>Bei Archivquellen geben Sie das entsprechende Archiv und die Signatur oder Inventarnummer an. (Die meisten Archivalien haben eine Signatur. Seltener werden Inventarnummern genutzt, meist z. B. bei Gegenständen und manchmal auch bei Fotos).</p>`,

        victims: `<p>Hier fügen Sie die Geschädigten des Ereignisses hinzu.</p>
                <p>Sie können nach bereits bekannten Personen suchen oder neue Personen manuell hinzufügen.</p>
                <p>Bei neuen Personen achten Sie bitte auf korrekte Namensschreibweisen, Lebensdaten und eine kurze Beschreibung.</p>
                <p>Dies hilft bei der Verknüpfung mit anderen Ereignissen und der Nachverfolgung von Biographien.</p>`,

        perpetrators: `<p>Hier dokumentieren Sie die für das Ereignis verantwortlichen Personen oder Gruppen.</p>
                     <p><strong>Diese Angabe ist verpflichtend:</strong> Jedes Ereignis muss mindestens einen Täter oder eine verantwortliche Organisation haben.</p>
                     <p>Beachten Sie dabei die juristische und historische Genauigkeit, unterscheiden Sie zwischen direkten Tätern und Befehlsgebern und dokumentieren Sie auch institutionelle Verantwortlichkeiten." zu "Dokumentieren Sie auch institutionelle Verantwortlichkeiten.</p>
                     <p>Zudem sind Befehlsgeber, organisatorisch Verantwortliche und direkt ausführende Täter gleichermaßen Täter.</p>
                     <p>Wenn Sie natürliche Personen anlegen, ist besonders auf eine historisch präzise und sorgfältige Arbeitsweise zu achten.</p>`,
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

// Update event time type and emit to parent
const updateEventTimeType = (type) => {
    // Update local value
    eventTimeTypeValue.value = type;

    // Clear the opposite time values based on type
    if (type === "point") {
        // Clear range values
        eventStartDateValue.value = null;
        eventStartDateQualifierValue.value = "";
        eventEndDateValue.value = null;
        eventEndDateQualifierValue.value = "";
    } else {
        // Clear point values
        eventDateValue.value = null;
        eventDateQualifierValue.value = "";
    }

    // Emit to parent
    emit("update:eventTimeType", type);
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
    console.log("EventsForm: Searching people with query:", query);
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
const onSelectVictim = (person) => {
    console.log("EventsForm: Selected victim:", person);
    emit("selectVictim", person);
};
const onStartNewVictimForm = () => {
    console.log("EventsForm: Starting new victim form");
    emit("startNewVictimForm");
};
const onCancelNewVictimForm = () => {
    console.log("EventsForm: Canceling new victim form");
    emit("cancelNewVictimForm");
};

const onAddNewVictim = () => {
    console.log(
        "EventsForm: Adding new victim with data:",
        newVictimLocal.value,
    );
    emit("update:newVictim", newVictimLocal.value);
    emit("addNewVictim");
};

const onUpdateVictim = (person) => {
    emit("updateVictim", person);
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

const onUpdatePerpetrator = (person) => {
    emit("updatePerpetrator", person);
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

const onSubmit = () => {
    // Trigger validation for all components
    if (timeSelectorRef.value) {
        timeSelectorRef.value.validate();
    }

    emit("submit");
};

// Refs for form components
const timeSelectorRef = ref(null);
const baseInfosRef = ref(null);
const perpetratorsRef = ref(null);
const sourcesFieldRef = ref(null);
const locationSelectorRef = ref(null);

// Validation states
const isTimeValid = ref(true);
const isBaseInfoValid = ref(true);
const isPerpetratorsValid = ref(true);
const isSourcesValid = ref(true);
const isLocationValid = ref(true);

const onTimeValidation = (isValid) => {
    isTimeValid.value = isValid;
};

const onBaseInfoValidation = (isValid) => {
    isBaseInfoValid.value = isValid;
};

const onPerpetratorsValidation = (isValid) => {
    isPerpetratorsValid.value = isValid;
};

const onSourcesValidation = (isValid) => {
    isSourcesValid.value = isValid;
};

const onLocationValidation = (isValid) => {
    isLocationValid.value = isValid;
};

// Validate form before submission
const validateForm = () => {
    let isValid = true;

    // Validate time fields
    if (timeSelectorRef.value) {
        if (!timeSelectorRef.value.validate()) {
            isValid = false;
        }
    }

    // Validate base info (title and description)
    if (baseInfosRef.value) {
        if (!baseInfosRef.value.validate()) {
            isValid = false;
        }
    }

    // Validate perpetrators
    if (perpetratorsRef.value) {
        if (!perpetratorsRef.value.validate()) {
            isValid = false;
        }
    }

    // Validate sources
    if (sourcesFieldRef.value) {
        if (!sourcesFieldRef.value.validate()) {
            isValid = false;
        }
    }

    // Validate location
    if (locationSelectorRef.value) {
        if (!locationSelectorRef.value.validate()) {
            isValid = false;
        }
    }

    return isValid;
};

// Method to get all form data in a properly formatted object
const getData = () => {
    // Validate the form first
    const isValid = validateForm();

    // Log validation results
    console.log("Form validation result:", isValid);

    // Log raw form values before processing
    console.log("Raw form values in getData():", {
        title: title.value,
        description: description.value,
        victims: selectedVictimsValue.value || [],
        perpetrators: selectedPerpetratorsValue.value || [],
    });

    // Prepare the data in the correct format for the database
    const formData = {
        // Basic information
        title: title.value,
        description: description.value,

        // Victims and perpetrators - already arrays, no need to stringify here
        victims: selectedVictimsValue.value || [],
        perpetrators: selectedPerpetratorsValue.value || [],

        // Time information
        time_point:
            eventTimeTypeValue.value === "point" ? eventDateValue.value : "",
        time_point_qualifier:
            eventTimeTypeValue.value === "point"
                ? eventDateQualifierValue.value
                : "",
        time_period_start:
            eventTimeTypeValue.value === "range"
                ? eventStartDateValue.value
                : "",
        time_period_start_qualifier:
            eventTimeTypeValue.value === "range"
                ? eventStartDateQualifierValue.value
                : "",
        time_period_end:
            eventTimeTypeValue.value === "range" ? eventEndDateValue.value : "",
        time_period_end_qualifier:
            eventTimeTypeValue.value === "range"
                ? eventEndDateQualifierValue.value
                : "",

        // Counts - convert to numbers (safe conversion handling)
        victim_count: Number(victimCountValue.value) || 0,
        survivor_count: Number(survivorCountValue.value) || 0,
        death_count: Number(deathCountValue.value) || 0,
        arrest_count: Number(arrestCountValue.value) || 0,

        // Qualifiers
        victim_count_qualifier: victimCountQualifierValue.value || "",
        survivor_count_qualifier: survivorCountQualifierValue.value || "",
        death_count_qualifier: deathCountQualifierValue.value || "",
        arrest_count_qualifier: arrestCountQualifierValue.value || "",

        // Causes - already an array, no need to stringify here
        selected_causes: selectedCausesValue.value || [],

        // Location
        coordinates: selectedLocationValue.value
            ? {
                  lat: parseFloat(selectedLocationValue.value.lat || 0),
                  lng: parseFloat(selectedLocationValue.value.lng || 0),
              }
            : null,
        coordinates_qualifier: locationQualifierValue.value || "",

        // Image
        image_url: imageUrlValue.value || "",
        image_filename: imageFilenameValue.value || "",

        // Sources - already an array, no need to stringify here
        sources: selectedSourcesValue.value || [],
    };

    // Convert any NaN values to 0 for numerical fields
    ["victim_count", "survivor_count", "death_count", "arrest_count"].forEach(
        (field) => {
            if (isNaN(formData[field])) {
                formData[field] = 0;
            }
        },
    );

    // Log victim counts to help with debugging
    console.log("Victim counts in getData:", {
        victim_count: formData.victim_count,
        survivor_count: formData.survivor_count,
        death_count: formData.death_count,
        arrest_count: formData.arrest_count,
        victim_count_qualifier: formData.victim_count_qualifier,
        survivor_count_qualifier: formData.survivor_count_qualifier,
        death_count_qualifier: formData.death_count_qualifier,
        arrest_count_qualifier: formData.arrest_count_qualifier,
    });

    console.log("EventsForm getData() returning:", formData);
    return formData;
};

// Method to check if data is valid
const isFormValid = () => {
    return validateForm();
};

// Export the validateForm method for parent component access
defineExpose({
    validateForm,
    getData,
    isFormValid,
    refreshFormData: () => {
        // This ensures all computed values reflect their current bindings
        // Force Vue to update computed properties by triggering reactivity
        nextTick(() => {
            // For key form components, explicitly force updates if available
            if (
                baseInfosRef.value &&
                typeof baseInfosRef.value.updateBindings === "function"
            ) {
                baseInfosRef.value.updateBindings();
            }

            if (
                timeSelectorRef.value &&
                typeof timeSelectorRef.value.updateBindings === "function"
            ) {
                timeSelectorRef.value.updateBindings();
            }

            // Log current values to verify data is current
            console.log("Refreshed form data with current values:", {
                title: title.value,
                description: description.value,
                eventTimeType: eventTimeTypeValue.value,
                victimCount: victimCountValue.value,
                perpetrators: selectedPerpetratorsValue.value?.length || 0,
                victims: selectedVictimsValue.value?.length || 0,
                causes: selectedCausesValue.value?.length || 0,
                sources: selectedSourcesValue.value?.length || 0,
            });
        });
    },
});

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

<!-- Global form styles -->
<style>
@import "./styles/form-styles.css";
</style>

<style scoped>
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
