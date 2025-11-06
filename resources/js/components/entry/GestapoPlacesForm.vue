<template>
    <div
        class="gestapo-places-form-wrapper form-wrapper"
        :class="{ 'admin-view': props.isAdminView }"
    >
        <div class="step-container">
            <h2 class="step-title">
                Schritt 2: Ort des Gestapoterrors eingeben
            </h2>
            <form class="forms" @submit.prevent>
                <!-- Typ des Gestapoterror-Orts (P31) - Radio Buttons als Boxen -->
                <div class="container-section gestapo-place-type-section">
                    <div class="section-header">
                        <label class="section-label required"
                            >Ort des Gestapoterrors: *</label
                        >
                        <button
                            type="button"
                            class="info-icon"
                            @click="openSectionInfo('gestapoPlaceType')"
                        >
                            <v-icon>mdi-information-variant</v-icon>
                        </button>
                    </div>
                    <div class="gestapo-place-type-radio-group">
                        <div
                            v-for="placeType in gestapoPlaceTypes"
                            :key="placeType.value"
                            class="place-type-radio-box"
                            :class="{
                                selected:
                                    gestapoPlaceTypeValue === placeType.value,
                            }"
                            @click="
                                gestapoPlaceTypeValue = placeType.value;
                                onGestapoPlaceTypeChange();
                            "
                            @focus="setActiveField('gestapoPlaceType')"
                            tabindex="0"
                        >
                            <div class="radio-box-content">
                                <img
                                    class="place-type-icon"
                                    :src="`/images/leaflet/markerIcons/coloredFilledWhite/${placeType.iconId}.svg`"
                                    :alt="placeType.label"
                                />
                                <span class="place-type-label">{{
                                    placeType.label
                                }}</span>
                                <div
                                    class="radio-indicator"
                                    v-if="
                                        gestapoPlaceTypeValue ===
                                        placeType.value
                                    "
                                >
                                    <v-icon>mdi-check</v-icon>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="gestapoPlaceTypeValue && typeGlossaryInfo"
                        class="place-type-info-text"
                    >
                        <div
                            v-if="!showFullGlossaryText"
                            class="shortened-text"
                            v-html="shortenedTypeGlossaryInfo"
                        ></div>
                        <div v-else v-html="typeGlossaryInfo"></div>
                        <div
                            class="read-more-link"
                            v-if="
                                typeGlossaryInfo &&
                                typeGlossaryInfo.length > 300
                            "
                        >
                            <a
                                href="#"
                                @click.prevent="toggleGlossaryTextExpansion"
                            >
                                {{
                                    showFullGlossaryText
                                        ? "Weniger anzeigen"
                                        : "Mehr lesen"
                                }}
                            </a>
                        </div>
                    </div>
                    <ValidationError
                        :show="showValidationErrors && !isGestapoPlaceTypeValid"
                        message="Bitte wählen Sie den Typ des Gestapoterror-Ortes."
                        showIcon
                    />
                </div>

                <!-- Titel/Kurzbeschreibung -->
                <BaseInfos
                    ref="baseInfosRef"
                    :title="title"
                    :description="description"
                    :entry-type="props.entryType"
                    :wikidata-results="wikidataResultsValue"
                    @update:title="(val) => (title = val)"
                    @update:description="(val) => (description = val)"
                    @fieldFocus="setActiveField"
                    @focusin="setActiveSection('title')"
                    @infoButtonClick="openSectionInfo"
                    @select-existing-entity="onSelectExistingEntity"
                    @validation="onBaseInfoValidation"
                />

                <!-- Datum der Gründung/Erbauung (P571) -->
                <TimeSelector
                    ref="foundingTimeSelectorRef"
                    :event-time-type="'point'"
                    :event-date="foundingDateValue"
                    :event-date-qualifier="foundingDateQualifierValue"
                    @update:eventDate="foundingDateValue = $event"
                    @update:eventDateQualifier="
                        foundingDateQualifierValue = $event
                    "
                    @fieldFocus="setActiveField"
                    @infoButtonClick="openSectionInfo"
                    :hideToggle="true"
                    :sectionLabel="'Datum der Gründung/Erbauung:'"
                    :pointLabel="'Datum der Gründung/Erbauung'"
                    :pointDateLabel="'Datum der Gründung/Erbauung'"
                    :propertyId="'P571'"
                    :validationMessageRequired="'Bitte geben Sie das Gründungsdatum an.'"
                    :validationMessageInvalid="'Das Gründungsdatum hat ein ungültiges Format.'"
                    :initialShowErrors="false"
                    :required="false"
                    @validation="onFoundingTimeValidation"
                />

                <!-- Auflösungsdatum (P576) -->
                <TimeSelector
                    ref="dissolutionTimeSelectorRef"
                    :event-time-type="'point'"
                    :event-date="dissolutionDateValue"
                    :event-date-qualifier="dissolutionDateQualifierValue"
                    @update:eventDate="dissolutionDateValue = $event"
                    @update:eventDateQualifier="
                        dissolutionDateQualifierValue = $event
                    "
                    @fieldFocus="setActiveField"
                    @infoButtonClick="openSectionInfo"
                    :hideToggle="true"
                    :sectionLabel="'Auflösungsdatum:'"
                    :pointLabel="'Auflösungsdatum'"
                    :pointDateLabel="'Auflösungsdatum'"
                    :propertyId="'P576'"
                    :validationMessageRequired="'Bitte geben Sie das Auflösungsdatum an.'"
                    :validationMessageInvalid="'Das Auflösungsdatum hat ein ungültiges Format.'"
                    :initialShowErrors="false"
                    :required="false"
                    @validation="onDissolutionTimeValidation"
                />

                <!-- Standort (P625) -->
                <LocationSelector
                    ref="locationSelectorRef"
                    :map-zoom="mapZoomValue"
                    :map-center="mapCenterValue"
                    :selected-location="selectedLocationValue"
                    :location-search="locationSearchValue"
                    :location-qualifier="locationQualifierValue"
                    :required="true"
                    @update:map-zoom="
                        ($event) => emit('update:mapZoom', $event)
                    "
                    @update:map-center="
                        ($event) => emit('update:mapCenter', $event)
                    "
                    @update:selected-location="
                        ($event) => emit('update:selectedLocation', $event)
                    "
                    @update:location-search="
                        (val) => (locationSearchValue = val)
                    "
                    @update:location-qualifier="
                        (val) => (locationQualifierValue = val)
                    "
                    :marker-icon-url="currentMarkerIconUrl"
                    :key="'location-selector-' + gestapoPlaceTypeValue"
                    @searchLocation="onSearchLocation"
                    @handleMapClick="onHandleMapClick"
                    @fieldFocus="setActiveField"
                    @infoButtonClick="openSectionInfo"
                    @validation="onLocationValidation"
                    @searchError="handleSearchError"
                />

                <!-- Adresse (P6375) -->
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
                        @update:modelValue="addressValue = $event"
                        @fieldFocus="setActiveField('address')"
                    />
                </div>

                <!-- Schlüsselereignis (P793) -->
                <div class="container-section key-event-section">
                    <div class="section-header">
                        <label class="section-label">Schlüsselereignis:</label>
                        <button
                            type="button"
                            class="info-icon"
                            @click="openSectionInfo('keyEvent')"
                        >
                            <v-icon>mdi-information-variant</v-icon>
                        </button>
                    </div>
                    <MultipleWikidataSelector
                        v-model="keyEventValue"
                        label="Schlüsselereignis"
                        dialog-title="Schlüsselereignisse auswählen"
                        search-label="Ereignis suchen..."
                        :search-results="keyEventSearchResultsValue"
                        :is-loading="isKeyEventSearchLoading"
                        :search-performed="keyEventSearchPerformed"
                        endpoint="commemorates"
                        icon="mdi-calendar-plus"
                        @search="onSearchKeyEvent"
                        field="keyEvent"
                        @fieldFocus="setActiveField('keyEvent')"
                    />
                </div>

                <!-- Leiter (P1037) -->
                <div class="container-section director-section">
                    <div class="section-header">
                        <label class="section-label">Leiter:</label>
                        <button
                            type="button"
                            class="info-icon"
                            @click="openSectionInfo('director')"
                        >
                            <v-icon>mdi-information-variant</v-icon>
                        </button>
                    </div>
                    <MultipleWikidataSelector
                        v-model="directorValue"
                        label="Leiter"
                        dialog-title="Leiter auswählen"
                        search-label="Nach Personen suchen..."
                        :search-results="directorSearchResultsValue"
                        :is-loading="isDirectorSearchLoading"
                        :search-performed="directorSearchPerformed"
                        endpoint="people"
                        icon="mdi-account-plus"
                        @search="onSearchDirector"
                        field="director"
                        @fieldFocus="setActiveField('director')"
                    />
                </div>

                <!-- Beschäftigte (P1128) -->
                <div class="container-section employees-section">
                    <div class="section-header">
                        <label class="section-label">Beschäftigte:</label>
                        <button
                            type="button"
                            class="info-icon"
                            @click="openSectionInfo('employees')"
                        >
                            <v-icon>mdi-information-variant</v-icon>
                        </button>
                    </div>
                    <MultipleWikidataSelector
                        v-model="employeesValue"
                        label="Beschäftigte"
                        dialog-title="Beschäftigte auswählen"
                        search-label="Nach Personen suchen..."
                        :search-results="employeesSearchResultsValue"
                        :is-loading="isEmployeesSearchLoading"
                        :search-performed="employeesSearchPerformed"
                        endpoint="people"
                        icon="mdi-account-plus"
                        @search="onSearchEmployees"
                        field="employees"
                        @fieldFocus="setActiveField('employees')"
                    />
                </div>

                <!-- Häftlingszahl (P5630) mit Qualifier -->
                <div class="container-section prisoner-count-section">
                    <div class="section-header">
                        <label class="section-label">Häftlingszahl:</label>
                        <button
                            type="button"
                            class="info-icon"
                            @click="openSectionInfo('prisonerCount')"
                        >
                            <v-icon>mdi-information-variant</v-icon>
                        </button>
                    </div>
                    <div class="count-with-qualifier">
                        <TextField
                            v-model="prisonerCountValue"
                            label="Häftlingszahl"
                            type="number"
                            property-id="P5630"
                            @update:modelValue="prisonerCountValue = $event"
                            @fieldFocus="setActiveField('prisonerCount')"
                        />
                        <IntegerQualifier
                            id="prisoner-count-qualifier"
                            v-model="prisonerCountQualifierValue"
                            label="Nachweisumstände"
                            property-id="P1480"
                            @fieldFocus="
                                setActiveField('prisonerCountQualifier')
                            "
                        />
                    </div>
                </div>

                <!-- Übergeordnete Organisation (P749) -->
                <div class="container-section parent-org-section">
                    <div class="section-header">
                        <label class="section-label"
                            >Übergeordnete Organisation:</label
                        >
                        <button
                            type="button"
                            class="info-icon"
                            @click="openSectionInfo('parentOrganization')"
                        >
                            <v-icon>mdi-information-variant</v-icon>
                        </button>
                    </div>
                    <MultipleWikidataSelector
                        v-model="parentOrgValue"
                        label="Übergeordnete Organisation"
                        dialog-title="Übergeordnete Organisation auswählen"
                        search-label="Nach Organisationen suchen..."
                        :search-results="parentOrgSearchResultsValue"
                        :is-loading="isParentOrgSearchLoading"
                        :search-performed="parentOrgSearchPerformed"
                        endpoint="organizations"
                        icon="mdi-domain-plus"
                        @search="onSearchParentOrg"
                        field="parentOrg"
                        @fieldFocus="setActiveField('parentOrg')"
                    />
                </div>

                <!-- Nachgeordnete Organisation (P355) -->
                <div class="container-section child-org-section">
                    <div class="section-header">
                        <label class="section-label"
                            >Nachgeordnete Organisation:</label
                        >
                        <button
                            type="button"
                            class="info-icon"
                            @click="openSectionInfo('childOrganization')"
                        >
                            <v-icon>mdi-information-variant</v-icon>
                        </button>
                    </div>
                    <MultipleWikidataSelector
                        v-model="childOrgValue"
                        label="Nachgeordnete Organisation"
                        dialog-title="Nachgeordnete Organisation auswählen"
                        search-label="Nach Organisationen suchen..."
                        :search-results="childOrgSearchResultsValue"
                        :is-loading="isChildOrgSearchLoading"
                        :search-performed="childOrgSearchPerformed"
                        endpoint="organizations"
                        icon="mdi-domain-plus"
                        @search="onSearchChildOrg"
                        field="childOrg"
                        @fieldFocus="setActiveField('childOrg')"
                    />
                </div>

                <!-- Vorgängerorganisation (P1365) -->
                <div class="container-section predecessor-org-section">
                    <div class="section-header">
                        <label class="section-label"
                            >Vorgängerorganisation:</label
                        >
                        <button
                            type="button"
                            class="info-icon"
                            @click="openSectionInfo('predecessorOrg')"
                        >
                            <v-icon>mdi-information-variant</v-icon>
                        </button>
                    </div>
                    <MultipleWikidataSelector
                        v-model="predecessorOrgValue"
                        label="Vorgängerorganisation"
                        dialog-title="Vorgängerorganisation auswählen"
                        search-label="Nach Organisationen suchen..."
                        :search-results="predecessorOrgSearchResultsValue"
                        :is-loading="isPredecessorOrgSearchLoading"
                        :search-performed="predecessorOrgSearchPerformed"
                        endpoint="organizations"
                        icon="mdi-domain-plus"
                        @search="onSearchPredecessorOrg"
                        field="predecessorOrg"
                        @fieldFocus="setActiveField('predecessorOrg')"
                    />
                </div>

                <!-- Nachfolgeorganisation (P1366) -->
                <div class="container-section successor-org-section">
                    <div class="section-header">
                        <label class="section-label"
                            >Nachfolgeorganisation:</label
                        >
                        <button
                            type="button"
                            class="info-icon"
                            @click="openSectionInfo('successorOrg')"
                        >
                            <v-icon>mdi-information-variant</v-icon>
                        </button>
                    </div>
                    <MultipleWikidataSelector
                        v-model="successorOrgValue"
                        label="Nachfolgeorganisation"
                        dialog-title="Nachfolgeorganisation auswählen"
                        search-label="Nach Organisationen suchen..."
                        :search-results="successorOrgSearchResultsValue"
                        :is-loading="isSuccessorOrgSearchLoading"
                        :search-performed="successorOrgSearchPerformed"
                        endpoint="organizations"
                        icon="mdi-domain-plus"
                        @search="onSearchSuccessorOrg"
                        field="successorOrg"
                        @fieldFocus="setActiveField('successorOrg')"
                    />
                </div>

                <!-- Bild (P18) -->
                <div class="container-section image-section">
                    <div class="section-header">
                        <label class="section-label">Bild:</label>
                        <button
                            type="button"
                            class="info-icon"
                            @click="openSectionInfo('image')"
                        >
                            <v-icon>mdi-information-variant</v-icon>
                        </button>
                    </div>
                    <div class="image-selection">
                        <button
                            type="button"
                            class="btn btn-primary btn-block"
                            @click="openImageSelector"
                        >
                            <v-icon start>mdi-camera</v-icon> Bild hinzufügen
                        </button>
                    </div>
                    <div v-if="imageUrlValue" class="selected-image mt-2">
                        <div class="image-container">
                            <img
                                class="preview-image"
                                :src="imageUrlValue"
                                @click="openLightbox"
                                alt="Bild Vorschau"
                            />
                            <div class="click-hint">
                                <v-icon>mdi-magnify-plus-outline</v-icon>
                            </div>
                        </div>
                        <div class="image-details">
                            <div class="image-filename">
                                {{ imageFilenameValue }}
                            </div>
                            <button
                                type="button"
                                class="btn btn-sm btn-danger remove-image"
                                @click="removeImage"
                            >
                                <v-icon>mdi-close</v-icon>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quellen -->
                <SourcesField
                    ref="sourcesFieldRef"
                    :selectedSources="selectedSourcesValue"
                    @update:selectedSources="selectedSourcesValue = $event"
                    @add="openSourcesSelector"
                    @remove="removeSource"
                    @fieldFocus="setActiveField('sources')"
                    @validation="onSourcesValidation"
                />

                <div class="mt-4 w-100">
                    <button
                        type="button"
                        class="button bg-secondary"
                        @click="onGoToStep(1)"
                    >
                        Zurück
                    </button>
                    <button
                        type="button"
                        class="button float-right"
                        @click="onSubmit"
                        :disabled="isSubmitting"
                    >
                        {{
                            isSubmitting
                                ? "Wird abgesendet..."
                                : "Eintrag einreichen"
                        }}
                    </button>
                </div>
            </form>
            <!-- Info-Box (Desktop) -->
        </div>

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

        <!-- Mobile Info-Overlay -->
        <div
            class="mobile-info-overlay"
            v-if="showMobileInfoOverlay && (isMobileView || props.isAdminView)"
            @click="closeMobileInfoOverlay"
        >
            <div class="overlay-content" @click.stop>
                <div class="overlay-header">
                    <h3>{{ getInfoTitle(lastActiveField) }}</h3>
                    <button
                        class="close-overlay"
                        @click="closeMobileInfoOverlay"
                    >
                        &times;
                    </button>
                </div>
                <div class="overlay-body">
                    <div v-html="getInfoContent(lastActiveField)"></div>
                </div>
            </div>
        </div>

        <!-- Dialogs -->
        <v-dialog v-model="imageDialogVisible" max-width="800">
            <WikimediaImageSelector
                @select="selectImage"
                @close="closeImageDialog"
            />
        </v-dialog>

        <v-dialog v-model="sourcesDialogVisible" max-width="800">
            <SourcesSelector
                @select="selectSource"
                @close="closeSourcesDialog"
            />
        </v-dialog>

        <Lightbox
            :is-open="lightboxOpen"
            :image-url="imageUrlValue"
            :image-alt="imageFilenameValue || 'Bild Vorschau'"
            @close="lightboxOpen = false"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import BaseInfos from "./fields/BaseInfos.vue";
import LocationSelector from "./fields/LocationSelector.vue";
import TextField from "./fields/common/TextField.vue";
import TimeSelector from "./fields/TimeSelector.vue";
import WikidataSelector from "./fields/common/WikidataSelector.vue";
import MultipleWikidataSelector from "./fields/common/MultipleWikidataSelector.vue";
import IntegerQualifier from "./fields/common/IntegerQualifier.vue";
import SourcesField from "./fields/SourcesField.vue";
import WikimediaImageSelector from "./fields/WikimediaImageSelector.vue";
import SourcesSelector from "./fields/SourcesSelector.vue";
import ValidationError from "../helpers/ValidationError.vue";
import Lightbox from "../helpers/Lightbox.vue";
import axios from "axios";
import { VIcon, VDialog } from "vuetify/components";
import { useFetchInfo } from "./composables/useFetchInfo";

// Props
const props = defineProps({
    entryType: {
        type: String,
        default: "Q106996250",
    },
    isAdminView: {
        type: Boolean,
        default: false,
    },
    wikidataResults: {
        type: Array,
        default: () => [],
    },
    mapZoom: {
        type: Number,
        default: 6,
    },
    mapCenter: {
        type: Object,
        default: () => ({
            lat: 51.1657,
            lng: 10.4515,
        }),
    },
    selectedLocation: {
        type: Object,
        default: () => null,
    },
    locationSearch: {
        type: String,
        default: "",
    },
    locationQualifier: {
        type: String,
        default: null,
    },
    formsTitle: {
        type: String,
        default: "",
    },
    formsDescription: {
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
});

// Emits
const emit = defineEmits([
    "update:wikidataResults",
    "update:formsTitle",
    "update:formsDescription",
    "update:mapZoom",
    "update:mapCenter",
    "update:selectedLocation",
    "update:locationSearch",
    "update:locationQualifier",
    "update:imageUrl",
    "update:imageFilename",
    "update:selectedSources",
    "goToStep",
    "submit",
    "searchLocation",
    "handleMapClick",
]);

// Title and description
const title = ref(props.formsTitle);
const description = ref(props.formsDescription);
const isMobileView = ref(false);

// Radio buttons for the GestapoPlace Type
const gestapoPlaceTypeValue = ref(null);
const gestapoPlaceTypes = [
    {
        label: "Arbeitserziehungslager",
        value: "Q277565",
        iconId: "laborEducationCamps",
    },
    { label: "Gefängnis", value: "Q40357", iconId: "prisons" },
    {
        label: "Erweitertes Polizeigefängnis",
        value: "Q108047650",
        iconId: "extPolicePrisons",
    },
    { label: "Außendienststelle", value: "Q108047541", iconId: "fieldOffices" },
    {
        label: "Staatspolizeistelle",
        value: "Q108047567",
        iconId: "statePoliceOffices",
    },
    {
        label: "Staatspolizeileitstelle",
        value: "Q108047581",
        iconId: "statePoliceHeadquarters",
    },
];

// Get glossary info for the selected place type
import glossaryItems from "../../data/glossaryData";
const showFullGlossaryText = ref(false);

const checkScreenSize = () => {
    isMobileView.value = window.innerWidth < 1200;
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
        title: `<p>In diesem Bereich werden die grundlegenden Informationen zum Ereignis erfasst.</p>
               <p><strong>Titel:</strong> Gebe einen präzisen und aussagekräftigen Titel ein, der Art des Ereignisses, Zeit/Ort und ggf. beteiligte Gruppen enthält.</p>
               <p><strong>Beschreibung:</strong> Beschreibe das Ereignis kurz und informativ mit den wichtigsten Fakten, beteiligten Personen und historischem Kontext.</p>
               <p>Beispiele für gute Titel: "Novemberpogrom 1938 in Osnabrück", "Deportation der jüdischen Bevölkerung aus Hamburg (1941)"</p>`,

        time: `<p>Hier werdem die zeitlichen Informationen zum Ereignis erfasst.</p>
               <p><strong>Diese Angabe ist verpflichtend.</strong></p>
               <p>Du kannst zwischen einem einzelnen Zeitpunkt oder einem Zeitraum wählen:</p>
               <ul>
                 <li><strong>Zeitpunkt:</strong> Für Ereignisse, die an einem bestimmten Datum stattfanden</li>
                 <li><strong>Zeitraum:</strong> Für länger andauernde Ereignisse mit Start- und Enddatum</li>
               </ul>
               <p>Falls das genaue Datum nicht bekannt ist, kannst du auch nur einen Monat eines Jahres oder ein Jahr auswählen.<br />
               Bei ungenauen Datumsangaben kannst du in der Select-Box unter dem Datum einen Qualifikator wie "ungefähr" setzen.</p>`,

        counts: `<p>In diesem Bereich kannst du die Geschädigtenzahl des Ereignisses dokumentieren.</p>
                <p>Folgende Informationen können hier ergänzt werden (sofern bekannt):</p>
                <ul>
                  <li><strong>Gesamtzahl der Geschädigten:</strong> Alle betroffenen Personen</li>
                  <li><strong>Anzahl der Überlebenden:</strong> Personen, die das Ereignis überlebt haben</li>
                  <li><strong>Anzahl der Todesopfer:</strong> Personen, die durch das Ereignis ums Leben kamen</li>
                  <li><strong>Anzahl der Festnahmen:</strong> Personen, die im Zuge des Ereignisses festgenommen wurden</li>
                </ul>
                <p>Verwende nur gesicherte Zahlen aus verlässlichen Quellen. Bei Unsicherheit ist ein Bereich (z.B. "etwa 100-150 Opfer") in der Beschreibung besser als eine exakte Zahl.</p>`,

        cause: `<p>Hier dokumentierst du die Ursache(n) der Verfolgung.</p>
               <p>Wähle die Hauptursache(n) aus oder füge neue hinzu. Typische Kategorien sind:</p>
               <ul>
                 <li>Religiöse Verfolgung</li>
                 <li>Rassistische Verfolgung</li>
                 <li>Politische Verfolgung</li>
                 <li>Soziale Verfolgung</li>
               </ul>
               <p>Eine präzise Dokumentation der Verfolgungsursachen ist wichtig für das Verständnis historischer Zusammenhänge.</p>`,

        location: `<p>Hier wird der genaue Ort des Ereignisses auf der Karte gewählt.</p>
                    <p><strong>Dieses Feld ist ein Pflichtfeld!</strong></p>
                  <p>Sie können:</p>
                  <ul>
                    <li>Direkt auf die Karte klicken, um einen Standort zu markieren</li>
                    <li>Nach einem Ort suchen, um ihn schneller zu finden</li>
                    <li>Die Karte zoomen und verschieben, um den genauen Standort zu lokalisieren</li>
                  </ul>
                  <p>Präzise Ortsangaben sind wichtig für die historische Dokumentation und ermöglichen eine genaue geografische Einordnung.</p>
                  <p>Wenn nur bespw. die Stadt bekannt ist, kann einfach das Stadtzentrum gewählt werden und dann in der Selectbox unter der Karte der Qualifikator "Ungefähr" um dies deutlich zu machen.</p>
                  `,

        image: `<p>Hier kannst du ein passendes Bild zum Ereignis hinzufügen.</p>
               <p>Geeignete Bilder sind:</p>
               <ul>
                 <li>Historische Fotos vom Ereignis</li>
                 <li>Bilder des Ortes</li>
                 <li>Dokumente zum Ereignis</li>
                 <li>Denkmäler oder Gedenktafeln</li>
               </ul>
               <p>Verwende nur Bilder, die rechtlich genutzt werden dürfen, und achten Sie auf korrekte Quellenangaben.</p>`,

        sources: `<p>Hier kannst du Quellenangaben zum Ereignis hinzufügen.</p>
                <p><strong>Diese Angabe ist verpflichtend:</strong> Jedes Ereignis muss mindestens eine Quelle enthalten.</p>
                <p>Du kannst verschiedene Arten von Quellen angeben:</p>
                <ul class="mb-3">
                  <li><strong>Bücher:</strong> Wissenschaftliche Werke, Monographien, Sammelbände etc.</li>
                  <li><strong>URLs:</strong> Vertrauenswürdige Webseiten, Online-Archive, Datenbanken</li>
                  <li><strong>Archive:</strong> Archivmaterial mit Inventarnummer</li>
                </ul>
                <p>Bei Buchquellen kannst du nach bereits in Wikidata erfassten Werken suchen oder neue Werke manuell hinzufügen. Geben möglichst genaue Seitenangaben an.</p>
                <p>Bei URLs ist das Zugriffsdatum wichtig, um die zeitliche Verfügbarkeit der Information zu dokumentieren.</p>
                <p>Bei Archivquellen gebe das entsprechende Archiv und die Signatur oder Inventarnummer an. (Die meisten Archivalien haben eine Signatur. Seltener werden Inventarnummern genutzt, meist z. B. bei Gegenständen und manchmal auch bei Fotos).</p>`,

        victims: `<p>Hier werden die Geschädigten des Ereignisses hinzugefügt.</p>
                <p>Du kannst nach bereits bekannten Personen suchen oder neue Personen manuell hinzufügen.</p>
                <p>Bei neuen Personen achte bitte auf korrekte Namensschreibweisen, Lebensdaten und eine kurze Beschreibung.</p>
                <p>Dies hilft bei der Verknüpfung mit anderen Ereignissen und der Nachverfolgung von Biographien.</p>`,

        perpetrators: `<p>Hier werden die für das Ereignis verantwortlichen Personen oder Gruppen dokumentiert.</p>
                     <p><strong>Diese Angabe ist verpflichtend:</strong> Jedes Ereignis muss mindestens einen Täter oder eine verantwortliche Organisation haben.</p>
                     <p>Beachte dabei die juristische und historische Genauigkeit, unterscheide zwischen direkten Tätern und Befehlsgebern und dokumentiere auch institutionelle Verantwortlichkeiten.</p>
                     <p>Zudem sind Befehlsgeber, organisatorisch Verantwortliche und direkt ausführende Täter gleichermaßen Täter.</p>
                     <p>Wenn du natürliche Personen anlegst, ist besonders auf eine historisch präzise und sorgfältige Arbeitsweise zu achten.</p>`,
    };

    return (
        sectionContents[sectionName] ||
        "<p>Wähle einen Bereich aus, um Hilfe zu erhalten.</p>"
    );
};

// Function to get the appropriate marker icon based on selected place type
const getMarkerIconForPlaceType = (typeValue) => {
    if (!typeValue)
        return "/images/leaflet/markerIcons/coloredFilledWhite/events.svg";

    const selectedType = gestapoPlaceTypes.find(
        (type) => type.value === typeValue,
    );
    if (!selectedType)
        return "/images/leaflet/markerIcons/coloredFilledWhite/events.svg";

    return `/images/leaflet/markerIcons/coloredFilledWhite/${selectedType.iconId}.svg`;
};

// Reactive marker icon URL that updates when place type changes
const currentMarkerIconUrl = computed(() => {
    const url = getMarkerIconForPlaceType(gestapoPlaceTypeValue.value);
    console.log("Computing currentMarkerIconUrl:", url);
    return url;
});

const typeGlossaryInfo = computed(() => {
    if (!gestapoPlaceTypeValue.value) return null;

    const selectedType = gestapoPlaceTypes.find(
        (type) => type.value === gestapoPlaceTypeValue.value,
    );
    if (!selectedType) return null;

    const glossaryItem = glossaryItems[selectedType.label];
    if (!glossaryItem || !glossaryItem.descriptions) return null;

    return glossaryItem.descriptions.join("<br><br>");
});

const shortenedTypeGlossaryInfo = computed(() => {
    if (!typeGlossaryInfo.value) return null;

    const maxChars = 300;
    if (typeGlossaryInfo.value.length <= maxChars) {
        return typeGlossaryInfo.value;
    }

    // Find the last complete sentence within the character limit
    const truncated = typeGlossaryInfo.value.substring(0, maxChars);
    const lastPeriod = truncated.lastIndexOf(".");

    if (lastPeriod > 0) {
        return truncated.substring(0, lastPeriod + 1);
    }

    return truncated + "...";
});

const toggleGlossaryTextExpansion = () => {
    showFullGlossaryText.value = !showFullGlossaryText.value;
};

// Wikidata search results
const wikidataResultsValue = computed({
    get: () => props.wikidataResults,
    set: (val) => emit("update:wikidataResults", val),
});

// Founding date
const foundingDateValue = ref(null);
const foundingDateQualifierValue = ref(null);

// Dissolution date
const dissolutionDateValue = ref(null);
const dissolutionDateQualifierValue = ref(null);

// Location
const mapZoomValue = computed({
    get: () => props.mapZoom,
    set: (val) => emit("update:mapZoom", val),
});

const mapCenterValue = computed({
    get: () => props.mapCenter,
    set: (val) => emit("update:mapCenter", val),
});

const selectedLocationValue = computed({
    get: () => props.selectedLocation,
    set: (val) => emit("update:selectedLocation", val),
});

const locationSearchValue = computed({
    get: () => props.locationSearch,
    set: (val) => emit("update:locationSearch", val),
});

const locationQualifierValue = computed({
    get: () => props.locationQualifier,
    set: (val) => emit("update:locationQualifier", val),
});

// Address
const addressValue = ref("");

// Key Event
const keyEventValue = ref([]);
const keyEventSearchResultsValue = ref([]);
const isKeyEventSearchLoading = ref(false);
const keyEventSearchPerformed = ref(false);

// Director (Leiter)
const directorValue = ref([]);
const directorSearchResultsValue = ref([]);
const isDirectorSearchLoading = ref(false);
const directorSearchPerformed = ref(false);

// Employees (Beschäftigte)
const employeesValue = ref([]);
const employeesSearchResultsValue = ref([]);
const isEmployeesSearchLoading = ref(false);
const employeesSearchPerformed = ref(false);

// Prisoner Count
const prisonerCountValue = ref("");
const prisonerCountQualifierValue = ref(null);

// Parent Organization
const parentOrgValue = ref([]);
const parentOrgSearchResultsValue = ref([]);
const isParentOrgSearchLoading = ref(false);
const parentOrgSearchPerformed = ref(false);

// Child Organization
const childOrgValue = ref([]);
const childOrgSearchResultsValue = ref([]);
const isChildOrgSearchLoading = ref(false);
const childOrgSearchPerformed = ref(false);

// Predecessor Organization
const predecessorOrgValue = ref([]);
const predecessorOrgSearchResultsValue = ref([]);
const isPredecessorOrgSearchLoading = ref(false);
const predecessorOrgSearchPerformed = ref(false);

// Successor Organization
const successorOrgValue = ref([]);
const successorOrgSearchResultsValue = ref([]);
const isSuccessorOrgSearchLoading = ref(false);
const successorOrgSearchPerformed = ref(false);

// Image
const imageUrlValue = computed({
    get: () => props.imageUrl,
    set: (val) => emit("update:imageUrl", val),
});

const imageFilenameValue = computed({
    get: () => props.imageFilename,
    set: (val) => emit("update:imageFilename", val),
});

// Sources
const selectedSourcesValue = computed({
    get: () => props.selectedSources || [],
    set: (value) => emit("update:selectedSources", value),
});

// UI States
const imageDialogVisible = ref(false);
const lightboxOpen = ref(false);
const sourcesDialogVisible = ref(false);
const showValidationErrors = ref(false);
const isSubmitting = ref(false);

// Active field tracking for help info
const activeField = ref(null);
const activeSection = ref(null);
// Import the composable for usage in the component
useFetchInfo();
const showMobileInfoOverlay = ref(false);

// Set the active field for help info
const setActiveField = (field) => {
    activeField.value = field;
};

// Set active section
const setActiveSection = (section) => {
    activeSection.value = section;
};

// Open section info
const openSectionInfo = (section) => {
    activeSection.value = section;
    showMobileInfoOverlay.value = true;
    emit("infoButtonClick", section);
};

// Mobile info overlay state is managed but closing function not used directly

// Gestapo Place Type Handler
const onGestapoPlaceTypeChange = () => {
    console.log("Gestapo Place Type changed to:", gestapoPlaceTypeValue.value);

    // Force the marker icon to update based on the new place type
    // This is critical to ensure the marker always reflects the correct type

    // If we have a location selector reference, force a refresh
    if (locationSelectorRef.value) {
        // Create a small delay to ensure Vue has updated the props
        setTimeout(() => {
            if (locationSelectorRef.value.refreshMarkerIcon) {
                console.log(
                    "Forcing marker icon refresh with new type:",
                    gestapoPlaceTypeValue.value,
                );

                // Force the computed property to reevaluate
                const iconUrl = getMarkerIconForPlaceType(
                    gestapoPlaceTypeValue.value,
                );
                console.log("New icon URL:", iconUrl);

                // Call refresh directly on the component
                locationSelectorRef.value.refreshMarkerIcon();
            }
        }, 100);
    }
};

// Watch for gestapoPlaceTypeValue changes to update marker
watch(
    () => gestapoPlaceTypeValue.value,
    (newValue, oldValue) => {
        console.log("Place type changed from", oldValue, "to", newValue);
        // Force refresh on next tick to ensure the DOM has updated
        setTimeout(() => {
            if (
                locationSelectorRef.value &&
                locationSelectorRef.value.refreshMarkerIcon
            ) {
                // Get the new icon URL based on the selected type
                const iconUrl = getMarkerIconForPlaceType(newValue);
                console.log("Updating marker icon to:", iconUrl);

                // Update the currentMarkerIconUrl computed value directly
                // This ensures Vue's reactivity system picks up the change
                emit("update:marker-icon-url", iconUrl);

                // Then call refresh on the component
                locationSelectorRef.value.refreshMarkerIcon();
            }
        }, 50);
    },
    { immediate: true }, // Run immediately when component mounts
);

// Form validation
const isGestapoPlaceTypeValid = computed(() => !!gestapoPlaceTypeValue.value);
const isBaseInfoValid = ref(false);
const isFoundingTimeValid = ref(true); // Optional field
const isDissolutionTimeValid = ref(true); // Optional field
const isLocationValid = ref(false);
const isSourcesValid = ref(false);

// Validation handlers
const onBaseInfoValidation = (isValid) => {
    isBaseInfoValid.value = isValid;
};

const onFoundingTimeValidation = (isValid) => {
    isFoundingTimeValid.value = isValid;
};

const onDissolutionTimeValidation = (isValid) => {
    isDissolutionTimeValid.value = isValid;
};

const onLocationValidation = (isValid) => {
    isLocationValid.value = isValid;
};

const handleSearchError = (error) => {
    console.log("Location search error:", error);
    // Optional: You can show a toast message or other UI notification here
};

const onSourcesValidation = (isValid) => {
    isSourcesValid.value = isValid;
};

// Validate the entire form
const validateForm = () => {
    console.log("Validating form with sources:", selectedSourcesValue.value);
    // Don't set showValidationErrors here since we set it in onSubmit

    // Call validate on form components that have validation
    if (
        baseInfosRef.value &&
        typeof baseInfosRef.value.validate === "function"
    ) {
        baseInfosRef.value.validate();
    }

    // Founding time validation is optional
    if (
        foundingTimeSelectorRef.value &&
        typeof foundingTimeSelectorRef.value.validate === "function"
    ) {
        // Just call validate but don't require it
        foundingTimeSelectorRef.value.validate();
    }

    // Dissolution time validation is optional
    if (
        dissolutionTimeSelectorRef.value &&
        typeof dissolutionTimeSelectorRef.value.validate === "function"
    ) {
        // Just call validate but don't require it
        dissolutionTimeSelectorRef.value.validate();
    }

    if (
        locationSelectorRef.value &&
        typeof locationSelectorRef.value.validate === "function"
    ) {
        locationSelectorRef.value.validate();
    }

    if (
        sourcesFieldRef.value &&
        typeof sourcesFieldRef.value.validate === "function"
    ) {
        sourcesFieldRef.value.validate();
    }

    // Log which validation checks are failing
    const validations = {
        gestapoPlaceType: isGestapoPlaceTypeValid.value,
        baseInfo: isBaseInfoValid.value,
        location: isLocationValid.value,
        sources: isSourcesValid.value,
    };

    console.log("Validation results:", validations);

    // Check only required fields in the final validation result
    return (
        isGestapoPlaceTypeValid.value &&
        isBaseInfoValid.value &&
        // Note: foundingTimeValid and dissolutionTimeValid are now always true
        isLocationValid.value &&
        isSourcesValid.value
    );
};

// Get form data for submission
const getData = () => {
    console.log("GestapoPlacesForm.getData() called - field values:");
    console.log("entity_subtype:", gestapoPlaceTypeValue.value);
    console.log("founding_date:", foundingDateValue.value);
    console.log("director:", directorValue.value);
    console.log("employees:", employeesValue.value);
    console.log("parent_organization:", parentOrgValue.value);
    console.log("child_organization:", childOrgValue.value);

    // Create data object with EXACT field names expected by backend
    const result = {
        // Basic Data
        title: title.value,
        description: description.value,
        type: "Q106996250", // Base type - Ort des Gestapoterrors
        entity_subtype: gestapoPlaceTypeValue.value, // Specific type of Gestapo place

        // Time fields
        founding_date: foundingDateValue.value,
        founding_date_qualifier: foundingDateQualifierValue.value,
        dissolution_date: dissolutionDateValue.value,
        dissolution_date_qualifier: dissolutionDateQualifierValue.value,

        // Location
        coordinates: selectedLocationValue.value,
        coordinates_qualifier: locationQualifierValue.value,

        // Address
        address: addressValue.value,

        // Key Event
        key_event: keyEventValue.value,

        // Director (Leiter)
        director: directorValue.value,

        // Employees (Beschäftigte)
        employees: employeesValue.value,

        // Prisoner Count
        prisoner_count: prisonerCountValue.value,
        prisoner_count_qualifier: prisonerCountQualifierValue.value,

        // Organizations
        parent_organization: parentOrgValue.value,
        child_organization: childOrgValue.value,
        predecessor_organization: predecessorOrgValue.value,
        successor_organization: successorOrgValue.value,

        // Image
        imageUrl: imageUrlValue.value,
        imageFilename: imageFilenameValue.value,

        // Sources
        sources: selectedSourcesValue.value,
    };

    console.log("GestapoPlacesForm getData() returning:", result);
    return result;
};

// Check if form is valid
// Form validity is checked directly in onSubmit through validateForm

// Component refs for validation
const baseInfosRef = ref(null);
const foundingTimeSelectorRef = ref(null);
const dissolutionTimeSelectorRef = ref(null);

// Expose getData method to parent component
defineExpose({
    getData,
    gestapoPlaceTypeValue,
    foundingDateValue,
    foundingDateQualifierValue,
    dissolutionDateValue,
    dissolutionDateQualifierValue,
    keyEventValue,
    directorValue,
    employeesValue,
    prisonerCountValue,
    prisonerCountQualifierValue,
    parentOrgValue,
    childOrgValue,
    predecessorOrgValue,
    successorOrgValue,
});
const locationSelectorRef = ref(null);
const sourcesFieldRef = ref(null);

// Event handlers
const onSelectExistingEntity = (entity) => {
    emit("select-existing-entity", entity);
};

const onSearchLocation = (query) => {
    console.log("Search location:", query);
    // Use the value directly if it's a string, otherwise extract the value from the ref object
    const searchValue =
        typeof query === "object" && query !== null ? query.value : query;

    try {
        emit("searchLocation", searchValue);
    } catch (error) {
        console.error("Error in location search:", error);
        // Display a user-friendly message if the Nominatim service is unavailable
        alert(
            "Die Standortsuche ist momentan nicht verfügbar. Bitte versuchen Sie es später erneut oder verwenden Sie die Kartenauswahl.",
        );
    }
};

const onHandleMapClick = (event) => {
    emit("handleMapClick", event);

    // Reset loading state in the LocationSelector if it's still loading
    if (locationSelectorRef.value && locationSelectorRef.value.isSearching) {
        locationSelectorRef.value.isSearching = false;
    }
};

const onGoToStep = (step) => {
    emit("goToStep", step);
};

const onSubmit = () => {
    console.log("GestapoPlacesForm: onSubmit triggered");

    // Prevent multiple submissions
    if (isSubmitting.value) {
        console.log("GestapoPlacesForm: Already submitting, ignoring click");
        return;
    }

    // Enable validation display
    showValidationErrors.value = true;
    isSubmitting.value = true;

    // Validate form
    const isValid = validateForm();
    console.log("GestapoPlacesForm: validateForm result:", isValid);

    if (isValid) {
        // Get data and submit
        const data = getData();

        // Log each field for debugging
        console.log("GestapoPlacesForm: Critical fields check:");
        console.log(" - type:", data.type);
        console.log(" - entity_subtype:", data.entity_subtype);
        console.log(" - founding_date:", data.founding_date);
        console.log(" - director:", data.director);
        console.log(" - employees:", data.employees);
        console.log(" - parent_organization:", data.parent_organization);
        console.log(" - child_organization:", data.child_organization);

        // Double-check important fields and set defaults if needed
        if (!data.entity_subtype && gestapoPlaceTypeValue.value) {
            data.entity_subtype = gestapoPlaceTypeValue.value;
            console.log("Fixed missing entity_subtype field");
        }

        console.log("GestapoPlacesForm: Emitting submit with data:", data);
        emit("submit", data);

        // Set a timeout to reset the submitting state in case the parent never responds
        setTimeout(() => {
            isSubmitting.value = false;
        }, 10000); // 10 second timeout
    } else {
        // Collect missing required fields for custom error message
        const missingFields = [];

        if (!isGestapoPlaceTypeValid.value) {
            missingFields.push("Ort des Gestapoterrors");
        }
        if (!isBaseInfoValid.value) {
            missingFields.push("Titel und Beschreibung");
        }
        if (!isLocationValid.value) {
            missingFields.push("Standort");
        }
        if (!isSourcesValid.value) {
            missingFields.push("Quellenangaben");
        }

        // Log custom error message
        const errorMessage = `Fehlende Pflichtfelder: ${missingFields.join(", ")}`;
        console.log("GestapoPlacesForm: " + errorMessage);

        isSubmitting.value = false;

        // Scroll to first error
        setTimeout(() => {
            const errorElement = document.querySelector(".validation-error");
            if (errorElement) {
                errorElement.scrollIntoView({
                    behavior: "smooth",
                    block: "center",
                });
            } else {
                console.log("No validation error elements found to scroll to");
            }
        }, 100);
    }
};

// Search Wikidata functions
const onSearchKeyEvent = async (query) => {
    if (!query) return;

    isKeyEventSearchLoading.value = true;
    keyEventSearchPerformed.value = true;

    try {
        const response = await axios.get(
            `/api/wikidata/search/commemorates?query=${encodeURIComponent(query)}`,
        );
        keyEventSearchResultsValue.value = response.data;
    } catch (error) {
        console.error("Error searching key events:", error);
        keyEventSearchResultsValue.value = [];
    } finally {
        isKeyEventSearchLoading.value = false;
    }
};

const onSearchDirector = async (query) => {
    if (!query) return;

    isDirectorSearchLoading.value = true;
    directorSearchPerformed.value = true;

    try {
        const response = await axios.get(
            `/api/wikidata/search/people?query=${encodeURIComponent(query)}`,
        );
        directorSearchResultsValue.value = response.data;
    } catch (error) {
        console.error("Error searching director:", error);
        directorSearchResultsValue.value = [];
    } finally {
        isDirectorSearchLoading.value = false;
    }
};

const onSearchEmployees = async (query) => {
    if (!query) return;

    isEmployeesSearchLoading.value = true;
    employeesSearchPerformed.value = true;

    try {
        // For quantity fields, we use the people search endpoint like victim/perpetrator fields
        const response = await axios.get(
            `/api/wikidata/search/people?query=${encodeURIComponent(query)}`,
        );
        employeesSearchResultsValue.value =
            response.data.length > 0
                ? response.data
                : [
                      {
                          id: query,
                          label: query,
                          description: "Anzahl der Beschäftigten",
                      },
                  ];
    } catch (error) {
        console.error("Error searching employees:", error);
        employeesSearchResultsValue.value = [];
    } finally {
        isEmployeesSearchLoading.value = false;
    }
};

const onSearchParentOrg = async (query) => {
    if (!query) return;

    isParentOrgSearchLoading.value = true;
    parentOrgSearchPerformed.value = true;

    try {
        const response = await axios.get(
            `/api/wikidata/search/organizations?query=${encodeURIComponent(query)}`,
        );
        parentOrgSearchResultsValue.value = response.data;
    } catch (error) {
        console.error("Error searching parent organization:", error);
        parentOrgSearchResultsValue.value = [];
    } finally {
        isParentOrgSearchLoading.value = false;
    }
};

const onSearchChildOrg = async (query) => {
    if (!query) return;

    isChildOrgSearchLoading.value = true;
    childOrgSearchPerformed.value = true;

    try {
        const response = await axios.get(
            `/api/wikidata/search/organizations?query=${encodeURIComponent(query)}`,
        );
        childOrgSearchResultsValue.value = response.data;
    } catch (error) {
        console.error("Error searching child organization:", error);
        childOrgSearchResultsValue.value = [];
    } finally {
        isChildOrgSearchLoading.value = false;
    }
};

const onSearchPredecessorOrg = async (query) => {
    if (!query) return;

    isPredecessorOrgSearchLoading.value = true;
    predecessorOrgSearchPerformed.value = true;

    try {
        const response = await axios.get(
            `/api/wikidata/search/organizations?query=${encodeURIComponent(query)}`,
        );
        predecessorOrgSearchResultsValue.value = response.data;
    } catch (error) {
        console.error("Error searching predecessor organization:", error);
        predecessorOrgSearchResultsValue.value = [];
    } finally {
        isPredecessorOrgSearchLoading.value = false;
    }
};

const onSearchSuccessorOrg = async (query) => {
    if (!query) return;

    isSuccessorOrgSearchLoading.value = true;
    successorOrgSearchPerformed.value = true;

    try {
        const response = await axios.get(
            `/api/wikidata/search/organizations?query=${encodeURIComponent(query)}`,
        );
        successorOrgSearchResultsValue.value = response.data;
    } catch (error) {
        console.error("Error searching successor organization:", error);
        successorOrgSearchResultsValue.value = [];
    } finally {
        isSuccessorOrgSearchLoading.value = false;
    }
};

// Image dialog handlers
const openImageSelector = () => {
    imageDialogVisible.value = true;
};

const closeImageDialog = () => {
    imageDialogVisible.value = false;
};

const selectImage = (image) => {
    console.log("Selected image:", image);
    imageUrlValue.value = image.url;
    imageFilenameValue.value = image.filename;
    emit("update:imageUrl", image.url);
    emit("update:imageFilename", image.filename);
    closeImageDialog();
};

const removeImage = () => {
    imageUrlValue.value = "";
    imageFilenameValue.value = "";
};

// Sources dialog handlers
const openSourcesSelector = () => {
    sourcesDialogVisible.value = true;
};

const closeSourcesDialog = () => {
    sourcesDialogVisible.value = false;
};

const selectSource = (data) => {
    // Get the source from the data object as sent by the SourcesSelector
    const source = data.source;

    // Create a new array with the current sources plus the new one
    const newSources = [...selectedSourcesValue.value, source];

    // Update the value through our computed setter
    selectedSourcesValue.value = newSources;

    // Close the dialog after selection
    closeSourcesDialog();
};

const removeSource = (index) => {
    // Create a copy of the array
    const newSources = [...selectedSourcesValue.value];
    // Remove the source at the specified index
    newSources.splice(index, 1);
    // Update through the computed setter
    selectedSourcesValue.value = newSources;
};

// Lightbox handler
const openLightbox = () => {
    if (imageUrlValue.value) {
        lightboxOpen.value = true;
    }
};

// When component mounts
onMounted(() => {
    checkScreenSize();
    window.addEventListener("resize", checkScreenSize);
    // Sync title and description with parent
    emit("update:formsTitle", title.value);
    emit("update:formsDescription", description.value);

    // Watch for changes to title and description
    watch(title, (newVal) => {
        emit("update:formsTitle", newVal);
    });

    watch(description, (newVal) => {
        emit("update:formsDescription", newVal);
    });
});
</script>
<!-- Global form styles -->
<style>
@import "./styles/form-styles.css";
</style>

<style scoped>
/* Component styles */
.image-container {
    position: relative;
    width: 100%;
    height: 150px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.image-container:hover .preview-image {
    transform: scale(1.05);
}

.image-container:hover .click-hint {
    opacity: 1;
}

.preview-image {
    max-width: 100%;
    max-height: 150px;
    object-fit: contain;
    transition: transform 0.3s ease;
}

.click-hint {
    position: absolute;
    bottom: 8px;
    right: 8px;
    background-color: rgba(0, 0, 0, 0.6);
    color: white;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

.gestapo-place-type-radio-group {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
}

.place-type-radio-box {
    flex: 0 0 calc(33.333% - 15px);
    height: 120px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
}

@media (max-width: 768px) {
    .place-type-radio-box {
        flex: 0 0 calc(50% - 15px);
    }
}

@media (max-width: 480px) {
    .place-type-radio-box {
        flex: 0 0 100%;
    }
}

.place-type-radio-box:hover {
    border-color: #1976d2;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.place-type-radio-box.selected {
    border-color: #1976d2;
    background-color: rgba(25, 118, 210, 0.05);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.radio-box-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    width: 100%;
}

.place-type-icon {
    width: 40px;
    height: 40px;
    margin-bottom: 10px;
}

.place-type-label {
    font-size: 14px;
    font-weight: 500;
}

.radio-indicator {
    position: absolute;
    top: 5px;
    right: 5px;
    color: #1976d2;
}

.place-type-info-text {
    padding: 15px;
    margin-bottom: 20px;
    background-color: #f5f5f5;
    border-radius: 4px;
    border-left: 4px solid #1976d2;
}

.read-more-link {
    text-align: right;
    margin-top: 10px;
    font-weight: 500;
}

.read-more-link a {
    color: #1976d2;
    text-decoration: none;
}

.read-more-link a:hover {
    text-decoration: underline;
}

.shortened-text {
    position: relative;
}
</style>
