<template>
    <div
        class="gestapo-places-form-wrapper"
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
                    :marker-icon-url="`/images/leaflet/markerIcons/coloredFilledWhite/fieldOffices.svg`"
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
                    @searchLocation="onSearchLocation"
                    @handleMapClick="onHandleMapClick"
                    @fieldFocus="setActiveField"
                    @infoButtonClick="openSectionInfo"
                    @validation="onLocationValidation"
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
                    <WikidataSelector
                        v-model="keyEventValue"
                        label="Schlüsselereignis"
                        dialog-title="Schlüsselereignis auswählen"
                        search-label="Nach Ereignissen suchen..."
                        :search-results="keyEventSearchResultsValue"
                        :is-loading="isKeyEventSearchLoading"
                        :search-performed="keyEventSearchPerformed"
                        endpoint="commemorates"
                        property-id="P793"
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
                    <WikidataSelector
                        v-model="directorValue"
                        label="Leiter"
                        dialog-title="Leiter auswählen"
                        search-label="Nach Personen suchen..."
                        :search-results="directorSearchResultsValue"
                        :is-loading="isDirectorSearchLoading"
                        :search-performed="directorSearchPerformed"
                        endpoint="people"
                        property-id="P1037"
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
                    <WikidataSelector
                        v-model="employeesValue"
                        label="Beschäftigte"
                        dialog-title="Beschäftigte auswählen"
                        search-label="Nach Personen suchen..."
                        :search-results="employeesSearchResultsValue"
                        :is-loading="isEmployeesSearchLoading"
                        :search-performed="employeesSearchPerformed"
                        endpoint="people"
                        property-id="P1128"
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
                    <WikidataSelector
                        v-model="parentOrgValue"
                        label="Übergeordnete Organisation"
                        dialog-title="Übergeordnete Organisation auswählen"
                        search-label="Nach Organisationen suchen..."
                        :search-results="parentOrgSearchResultsValue"
                        :is-loading="isParentOrgSearchLoading"
                        :search-performed="parentOrgSearchPerformed"
                        endpoint="organizations"
                        property-id="P749"
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
                    <WikidataSelector
                        v-model="childOrgValue"
                        label="Nachgeordnete Organisation"
                        dialog-title="Nachgeordnete Organisation auswählen"
                        search-label="Nach Organisationen suchen..."
                        :search-results="childOrgSearchResultsValue"
                        :is-loading="isChildOrgSearchLoading"
                        :search-performed="childOrgSearchPerformed"
                        endpoint="organizations"
                        property-id="P355"
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
                    <WikidataSelector
                        v-model="predecessorOrgValue"
                        label="Vorgängerorganisation"
                        dialog-title="Vorgängerorganisation auswählen"
                        search-label="Nach Organisationen suchen..."
                        :search-results="predecessorOrgSearchResultsValue"
                        :is-loading="isPredecessorOrgSearchLoading"
                        :search-performed="predecessorOrgSearchPerformed"
                        endpoint="organizations"
                        property-id="P1365"
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
                    <WikidataSelector
                        v-model="successorOrgValue"
                        label="Nachfolgeorganisation"
                        dialog-title="Nachfolgeorganisation auswählen"
                        search-label="Nach Organisationen suchen..."
                        :search-results="successorOrgSearchResultsValue"
                        :is-loading="isSuccessorOrgSearchLoading"
                        :search-performed="successorOrgSearchPerformed"
                        endpoint="organizations"
                        property-id="P1366"
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
                    >
                        Eintrag einreichen
                    </button>
                </div>
            </form>
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

// Style for the component
const componentStyles = `
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
`;
// Append the style to the document
if (typeof document !== "undefined") {
    const styleElement = document.createElement("style");
    styleElement.textContent = componentStyles;
    document.head.appendChild(styleElement);
}

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
const keyEventValue = ref(null);
const keyEventSearchResultsValue = ref([]);
const isKeyEventSearchLoading = ref(false);
const keyEventSearchPerformed = ref(false);

// Director (Leiter)
const directorValue = ref(null);
const directorSearchResultsValue = ref([]);
const isDirectorSearchLoading = ref(false);
const directorSearchPerformed = ref(false);

// Employees (Beschäftigte)
const employeesValue = ref(null);
const employeesSearchResultsValue = ref([]);
const isEmployeesSearchLoading = ref(false);
const employeesSearchPerformed = ref(false);

// Prisoner Count
const prisonerCountValue = ref("");
const prisonerCountQualifierValue = ref(null);

// Parent Organization
const parentOrgValue = ref(null);
const parentOrgSearchResultsValue = ref([]);
const isParentOrgSearchLoading = ref(false);
const parentOrgSearchPerformed = ref(false);

// Child Organization
const childOrgValue = ref(null);
const childOrgSearchResultsValue = ref([]);
const isChildOrgSearchLoading = ref(false);
const childOrgSearchPerformed = ref(false);

// Predecessor Organization
const predecessorOrgValue = ref(null);
const predecessorOrgSearchResultsValue = ref([]);
const isPredecessorOrgSearchLoading = ref(false);
const predecessorOrgSearchPerformed = ref(false);

// Successor Organization
const successorOrgValue = ref(null);
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
};

// Form validation
const isGestapoPlaceTypeValid = computed(() => !!gestapoPlaceTypeValue.value);
const isBaseInfoValid = ref(false);
const isFoundingTimeValid = ref(false);
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

const onSourcesValidation = (isValid) => {
    isSourcesValid.value = isValid;
};

// Validate the entire form
const validateForm = () => {
    console.log("Validating form with sources:", selectedSourcesValue.value);
    showValidationErrors.value = true;

    // Call validate on form components that have validation
    if (
        baseInfosRef.value &&
        typeof baseInfosRef.value.validate === "function"
    ) {
        baseInfosRef.value.validate();
    }

    if (
        foundingTimeSelectorRef.value &&
        typeof foundingTimeSelectorRef.value.validate === "function"
    ) {
        foundingTimeSelectorRef.value.validate();
    }

    if (
        dissolutionTimeSelectorRef.value &&
        typeof dissolutionTimeSelectorRef.value.validate === "function"
    ) {
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

    return (
        isGestapoPlaceTypeValid.value &&
        isBaseInfoValid.value &&
        isFoundingTimeValid.value &&
        isDissolutionTimeValid.value &&
        isLocationValid.value &&
        isSourcesValid.value
    );
};

// Get form data for submission
const getData = () => {
    return {
        // Basic Data
        title: title.value,
        description: description.value,
        entity_type: "Q106996250", // Base type - Ort des Gestapoterrors
        entity_subtype: gestapoPlaceTypeValue.value, // Specific type of Gestapo place

        // Time fields
        founding_date: foundingDateValue.value,
        founding_date_qualifier: foundingDateQualifierValue.value,
        dissolution_date: dissolutionDateValue.value,
        dissolution_date_qualifier: dissolutionDateQualifierValue.value,

        // Location
        location: selectedLocationValue.value,
        location_qualifier: locationQualifierValue.value,

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
        image_url: imageUrlValue.value,
        image_filename: imageFilenameValue.value,

        // Sources
        sources: selectedSourcesValue.value,
    };
};

// Check if form is valid
const isFormValid = () => {
    return validateForm();
};

// Component refs for validation
const baseInfosRef = ref(null);
const foundingTimeSelectorRef = ref(null);
const dissolutionTimeSelectorRef = ref(null);
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
    if (isFormValid()) {
        emit("submit", getData());
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
        console.error("Error searching directors:", error);
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
        console.error("Error searching parent organizations:", error);
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
        console.error("Error searching child organizations:", error);
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
        console.error("Error searching predecessor organizations:", error);
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
        console.error("Error searching successor organizations:", error);
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

<style scoped>
/* Component styles */
</style>
