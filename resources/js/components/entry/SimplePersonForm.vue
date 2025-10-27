<template>
    <div class="person-form-wrapper">
        <div class="step-container">
            <h2 class="step-title">Schritt 2: Person-Details eingeben</h2>
            <form class="forms" @submit.prevent>
                <div class="entry-section">
                    <div class="section-header">
                        <label class="section-label">Person-Details:</label>
                        <button
                            type="button"
                            class="info-icon"
                            @click="openSectionInfo('person-entry')"
                            title="Informationen zu Person-Details"
                        >
                            <v-icon>mdi-information-variant</v-icon>
                        </button>
                    </div>

                    <!-- Mobile Info Overlay -->
                    <div
                        v-if="showMobileInfoOverlay"
                        class="mobile-info-overlay"
                        @click.self="closeMobileInfoOverlay"
                    >
                        <div class="mobile-info-content">
                            <div class="mobile-info-header">
                                <h3>{{ getInfoTitle(lastActiveField) }}</h3>
                                <button @click="closeMobileInfoOverlay">
                                    <v-icon>mdi-close</v-icon>
                                </button>
                            </div>
                            <div
                                class="mobile-info-body"
                                v-html="getInfoContent(lastActiveField)"
                            ></div>
                        </div>
                    </div>

                    <!-- Person form fields with sections -->
                    <div class="person-form-fields">
                        <!-- Required Information Section -->
                        <div class="form-section">
                            <h3 class="section-subtitle">
                                Erforderliche Informationen
                            </h3>

                            <!-- Role selection -->
                            <div class="form-group">
                                <v-select
                                    v-model="personData.role"
                                    label="Rolle*"
                                    :items="roleOptions"
                                    item-title="label"
                                    item-value="value"
                                    variant="outlined"
                                    class="mb-3"
                                    @click:append-inner="
                                        openSectionInfo('role')
                                    "
                                ></v-select>
                            </div>

                            <!-- Name fields -->
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <v-text-field
                                        v-model="personData.firstName"
                                        label="Vorname *"
                                        :rules="[rules.required]"
                                        required
                                        variant="outlined"
                                        class="mb-3"
                                    ></v-text-field>
                                </div>
                                <div class="form-group col-md-6">
                                    <v-text-field
                                        v-model="personData.lastName"
                                        label="Nachname *"
                                        :rules="[rules.required]"
                                        required
                                        variant="outlined"
                                        class="mb-3"
                                    ></v-text-field>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="form-group">
                                <v-textarea
                                    v-model="personData.description"
                                    label="Kurze Beschreibung *"
                                    placeholder="Beispiel: deutscher Widerstandskämpfer... / Opfer des Nationalsozialismus"
                                    rows="3"
                                    :rules="[rules.required, rules.maxLength]"
                                    required
                                    counter="250"
                                    variant="outlined"
                                    class="mb-4"
                                ></v-textarea>
                            </div>
                        </div>

                        <!-- Personal Details Section -->
                        <div class="form-section">
                            <div class="section-header">
                                <h3 class="section-subtitle">
                                    Persönliche Daten
                                </h3>
                                <button
                                    type="button"
                                    class="info-icon"
                                    @click="openSectionInfo('personal-data')"
                                    title="Informationen zu persönlichen Daten"
                                >
                                    <v-icon>mdi-information-variant</v-icon>
                                </button>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <v-text-field
                                        v-model="personData.birthName"
                                        label="Geburtsname"
                                        placeholder="Falls abweichend vom aktuellen Namen"
                                        variant="outlined"
                                        class="mb-3"
                                    ></v-text-field>
                                </div>
                                <div class="form-group col-md-6">
                                    <gender-selector
                                        v-model="personData.gender"
                                        label="Geschlecht"
                                        class="mb-3"
                                    ></gender-selector>
                                </div>
                            </div>

                            <div class="form-row">
                                <!-- Birth/Death dates -->
                                <div class="form-group col-md-6">
                                    <v-text-field
                                        v-model="personData.birthDate"
                                        label="Geburtsdatum"
                                        type="date"
                                        variant="outlined"
                                        class="mb-3"
                                    ></v-text-field>
                                </div>
                                <div class="form-group col-md-6">
                                    <v-text-field
                                        v-model="personData.deathDate"
                                        label="Sterbedatum"
                                        type="date"
                                        variant="outlined"
                                        class="mb-3"
                                    ></v-text-field>
                                </div>
                            </div>

                            <!-- Nationality & Occupation -->
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <!-- Nationalität / Citizenship (P27) -->
                                    <wikidata-selector
                                        v-model="personData.nationality"
                                        label="Staatsangehörigkeit"
                                        dialog-title="Staatsangehörigkeit auswählen"
                                        search-label="Land suchen..."
                                        :search-results="
                                            wikidataSearchResults.nationality ||
                                            []
                                        "
                                        :is-loading="
                                            isWikidataSearchLoading.nationality
                                        "
                                        :search-performed="
                                            wikidataSearchPerformed.nationality
                                        "
                                        endpoint="nationalities"
                                        @search="
                                            searchWikidataItems({
                                                field: 'nationality',
                                                query: $event,
                                            })
                                        "
                                        class="mb-3"
                                    ></wikidata-selector>
                                </div>
                                <div class="form-group col-md-6">
                                    <!-- Beruf / Occupation (P106) -->
                                    <wikidata-selector
                                        v-model="personData.occupation"
                                        label="Beruf"
                                        dialog-title="Beruf auswählen"
                                        search-label="Beruf suchen..."
                                        :search-results="
                                            wikidataSearchResults.occupation ||
                                            []
                                        "
                                        :is-loading="
                                            isWikidataSearchLoading.occupation
                                        "
                                        :search-performed="
                                            wikidataSearchPerformed.occupation
                                        "
                                        endpoint="occupations"
                                        @search="
                                            searchWikidataItems({
                                                field: 'occupation',
                                                query: $event,
                                            })
                                        "
                                        class="mb-3"
                                    ></wikidata-selector>
                                </div>
                            </div>
                        </div>

                        <!-- Places Section -->
                        <div class="form-section">
                            <div class="section-header">
                                <h3 class="section-subtitle">Ortsangaben</h3>
                                <button
                                    type="button"
                                    class="info-icon"
                                    @click="openSectionInfo('locations')"
                                    title="Informationen zu Ortsangaben"
                                >
                                    <v-icon>mdi-information-variant</v-icon>
                                </button>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <!-- Geburtsort / Place of birth (P19) -->
                                    <wikidata-selector
                                        v-model="personData.place_of_birth"
                                        label="Geburtsort"
                                        dialog-title="Geburtsort auswählen"
                                        search-label="Ort suchen..."
                                        :search-results="
                                            wikidataSearchResults.place_of_birth ||
                                            []
                                        "
                                        :is-loading="
                                            isWikidataSearchLoading.place_of_birth
                                        "
                                        :search-performed="
                                            wikidataSearchPerformed.place_of_birth
                                        "
                                        endpoint="places"
                                        @search="
                                            searchWikidataItems({
                                                field: 'place_of_birth',
                                                query: $event,
                                            })
                                        "
                                        class="mb-3"
                                    ></wikidata-selector>
                                </div>
                                <div class="form-group col-md-6">
                                    <!-- Sterbeort / Place of death (P20) -->
                                    <wikidata-selector
                                        v-model="personData.place_of_death"
                                        label="Sterbeort"
                                        dialog-title="Sterbeort auswählen"
                                        search-label="Ort suchen..."
                                        :search-results="
                                            wikidataSearchResults.place_of_death ||
                                            []
                                        "
                                        :is-loading="
                                            isWikidataSearchLoading.place_of_death
                                        "
                                        :search-performed="
                                            wikidataSearchPerformed.place_of_death
                                        "
                                        endpoint="places"
                                        @search="
                                            searchWikidataItems({
                                                field: 'place_of_death',
                                                query: $event,
                                            })
                                        "
                                        class="mb-3"
                                    ></wikidata-selector>
                                </div>
                            </div>

                            <div class="form-group">
                                <!-- Wohnort / Place of residence (P551) -->
                                <wikidata-selector
                                    v-model="personData.place_of_residence"
                                    label="Wohnort"
                                    dialog-title="Wohnort auswählen"
                                    search-label="Ort suchen..."
                                    :search-results="
                                        wikidataSearchResults.place_of_residence ||
                                        []
                                    "
                                    :is-loading="
                                        isWikidataSearchLoading.place_of_residence
                                    "
                                    :search-performed="
                                        wikidataSearchPerformed.place_of_residence
                                    "
                                    endpoint="places"
                                    @search="
                                        searchWikidataItems({
                                            field: 'place_of_residence',
                                            query: $event,
                                        })
                                    "
                                    class="mb-3"
                                ></wikidata-selector>
                            </div>

                            <!-- Place of detention (for victims only) -->
                            <div
                                class="form-group"
                                v-if="personData.role === 'victim'"
                            >
                                <multiple-wikidata-selector
                                    v-model="personData.place_of_detention"
                                    label="Haftorte"
                                    dialog-title="Haftorte auswählen"
                                    search-label="Haftorte suchen..."
                                    :search-results="
                                        wikidataSearchResults.place_of_detention ||
                                        []
                                    "
                                    :is-loading="
                                        isWikidataSearchLoading.place_of_detention
                                    "
                                    :search-performed="
                                        wikidataSearchPerformed.place_of_detention
                                    "
                                    endpoint="places"
                                    @search="
                                        searchWikidataItems({
                                            field: 'place_of_detention',
                                            query: $event,
                                        })
                                    "
                                ></multiple-wikidata-selector>
                            </div>

                            <!-- Employer (for perpetrators only) -->
                            <div
                                class="form-group"
                                v-if="personData.role === 'perpetrator'"
                            >
                                <wikidata-selector
                                    v-model="personData.employer"
                                    label="Arbeitgeber"
                                    dialog-title="Arbeitgeber auswählen"
                                    search-label="Arbeitgeber suchen..."
                                    :search-results="
                                        wikidataSearchResults.employer || []
                                    "
                                    :is-loading="
                                        isWikidataSearchLoading.employer
                                    "
                                    :search-performed="
                                        wikidataSearchPerformed.employer
                                    "
                                    endpoint="people"
                                    @search="
                                        searchWikidataItems({
                                            field: 'employer',
                                            query: $event,
                                        })
                                    "
                                ></wikidata-selector>
                            </div>

                            <!-- Image Selector -->
                            <div class="form-group">
                                <h4 class="mb-3">Bild</h4>
                                <image-selector
                                    :image-url="personData.imageUrl"
                                    :image-filename="personData.imageFilename"
                                    @update:image-url="
                                        personData.imageUrl = $event
                                    "
                                    @update:image-filename="
                                        personData.imageFilename = $event
                                    "
                                    @infoButtonClick="openSectionInfo('image')"
                                    @fieldFocus="setActiveField('image')"
                                ></image-selector>
                            </div>
                        </div>
                    </div>

                    <!-- Validation errors -->
                    <div
                        v-if="showPersonValidationErrors"
                        class="validation-errors-container"
                    >
                        <!-- First name validation error -->
                        <ValidationError
                            v-if="firstNameError"
                            message="Vorname ist ein Pflichtfeld."
                            showIcon
                            customClass="validation-error"
                        />

                        <!-- Last name validation error -->
                        <ValidationError
                            v-if="lastNameError"
                            message="Nachname ist ein Pflichtfeld."
                            showIcon
                            customClass="validation-error"
                        />

                        <!-- Description validation error -->
                        <ValidationError
                            v-if="descriptionError"
                            message="Kurzbeschreibung ist ein Pflichtfeld."
                            showIcon
                            customClass="validation-error"
                        />

                        <!-- Source validation error -->
                        <div
                            v-if="sourceError"
                            @click="handleSourceErrorClick"
                            style="
                                cursor: pointer;
                                color: #f44336;
                                margin-top: 8px;
                                display: flex;
                                align-items: center;
                            "
                        >
                            <v-icon color="error" size="small" class="mr-1"
                                >mdi-alert-circle</v-icon
                            >
                            <span
                                >Bitte geben Sie mindestens eine Quelle an.
                                (Klicken, um zur Quellen-Sektion zu
                                springen)</span
                            >
                        </div>
                    </div>
                </div>

                <SourcesField
                    ref="sourcesFieldRef"
                    :selectedSources="selectedSourcesValue"
                    @update:selectedSources="handleSourcesUpdate"
                    @fieldFocus="setActiveField"
                    @infoButtonClick="openSectionInfo('sources')"
                    @validation="onSourcesValidation"
                    :show-validation-error="sourceError"
                    :error="sourceError"
                    :error-messages="
                        sourceError
                            ? 'Bitte geben Sie mindestens eine Quelle an.'
                            : ''
                    "
                    :required="true"
                />

                <div class="actions-row">
                    <v-btn color="secondary" @click="goToPreviousStep">
                        Zurück
                    </v-btn>
                    <v-btn
                        color="primary"
                        @click="validateAndSubmit"
                        :disabled="isSubmitting"
                        :loading="isSubmitting"
                    >
                        Abschicken
                    </v-btn>
                </div>
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
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, inject, watch } from "vue";
import { useRouter } from "vue-router";
import SourcesField from "./fields/SourcesField.vue";
import ValidationError from "../helpers/ValidationError.vue";
import WikidataSelector from "./fields/common/WikidataSelector.vue";
import MultipleWikidataSelector from "./fields/common/MultipleWikidataSelector.vue";
import ImageSelector from "./fields/ImageSelector.vue";
import GenderSelector from "./fields/GenderSelector.vue";
import axios from "axios";

const router = useRouter();
const ogtGlobals = inject("ogtGlobals");

// Parse title into first and last name
const parseNameFromTitle = (fullName) => {
    if (!fullName) return { firstName: "", lastName: "" };

    const parts = fullName.trim().split(" ");
    if (parts.length === 1) {
        return { firstName: "", lastName: parts[0] };
    } else {
        const lastName = parts.pop();
        const firstName = parts.join(" ");
        return { firstName, lastName };
    }
};

// Helper function to format a title from first and last name
const formatTitleFromName = (firstName, lastName) => {
    if (!firstName && !lastName) return "";
    return `${firstName || ""} ${lastName || ""}`.trim();
};

// Helper function to show toast messages if available, otherwise use console
// Show toast messages if available, otherwise use console
const showToast = (message, type = "error") => {
    // Prevent duplicate toasts by checking if the same message was shown recently
    if (
        window._lastToast === message &&
        Date.now() - window._lastToastTime < 3000
    ) {
        return;
    }

    // Store this toast to prevent duplicates
    window._lastToast = message;
    window._lastToastTime = Date.now();

    if (ogtGlobals?.showToast) {
        ogtGlobals.showToast(message, type);
    } else {
        console[
            type === "error" ? "error" : type === "success" ? "log" : "info"
        ](message);
    }
};
const props = defineProps({
    entryType: {
        type: String,
        default: "Q5",
    },
    isAdminView: {
        type: Boolean,
        default: false,
    },
    personRole: {
        type: String,
        default: "standalone",
        validator: (value) =>
            ["victim", "perpetrator", "standalone"].includes(value),
    },
    title: {
        type: String,
        default: "",
    },
    description: {
        type: String,
        default: "",
    },
    tempPersonData: {
        type: String,
        default: null,
    },
    selectedSources: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits([
    "goToStep",
    "handleMapClick",
    "submit", // Pass the form data as the parameter
    "update:title",
    "update:description",
    "update:selectedSources",
]);

// Form state
const title = ref(props.title || "");
const description = ref(props.description || "");
const isSubmitting = ref(false);
const baseInfosRef = ref(null);
const sourcesRef = ref(null);
const sourcesFieldRef = ref(null);
const showPersonValidationErrors = ref(false);
const firstNameError = ref(false);
const lastNameError = ref(false);
const sourceError = ref(false);
const descriptionError = ref(false);
// Store active timeouts for cleanup
const activeTimeouts = ref([]);
// Store safety timer ID
let safetyTimerId = null;
// Instead of using an object with refs that can cause undefined errors,
// we use individual refs for each validation field
const baseInfosValid = ref(false);
const sourcesValid = ref(false);

const isValid = ref({
    baseInfos: false,
    sources: false,
});

// Search state
// We don't need these anymore since we're not doing search in this component

// New person data
const personData = ref({
    firstName: "",
    lastName: "",
    birthName: "",
    description: props.description || "",
    birthDate: null,
    deathDate: null,
    // All Wikidata fields below store simple Q-numbers as direct strings
    gender: null, // Q6581097 (male) or Q6581072 (female) or other
    nationality: null, // Q183 (Germany) or Q1206012 (Deutsches Reich)
    place_of_birth: null, // Q-number for location
    place_of_death: null, // Q-number for location
    place_of_detention: [], // Array of Q-numbers for detention places (only field stored as array)
    place_of_residence: null, // Q-number for location
    occupation: null, // Q-number for occupation
    employer: null, // Q-number for employer
    image: null,
    imageUrl: null,
    imageFilename: null,
    role: "victim", // Default to "victim" (Geschädigter, Q2026714)
    wikidataId: null, // Wikidata ID if it comes from a search
});

// Options for dropdown selections
const roleOptions = ref([
    { label: "Geschädigter (Q2026714)", value: "victim", wikidata: "Q2026714" },
    {
        label: "Täter (Q111080573)",
        value: "perpetrator",
        wikidata: "Q111080573",
    },
]);

// Info-Box Funktionalität
const activeField = ref(null);
const activeSection = ref("title");
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
    showMobileInfoOverlay.value = true;
};

const closeMobileInfoOverlay = () => {
    showMobileInfoOverlay.value = false;
};

const getInfoTitle = (fieldName) => {
    const sectionTitles = {
        role: "Rolle der Person",
        "person-entry": "Person-Details",
        "personal-data": "Persönliche Daten",
        locations: "Ortsangaben",
        sources: "Quellenangaben",
    };

    if (sectionTitles[fieldName]) {
        return sectionTitles[fieldName];
    } else {
        return "Information";
    }
};

const getInfoContent = (fieldName) => {
    const sectionContents = {
        role: `<p>Hier wird die Rolle der Person im historischen Kontext definiert:</p>
              <ul>
                <li><strong>Geschädigter (Q2026714)</strong>: Person wurde im Nationalsozialismus verfolgt, inhaftiert oder ermordet</li>
                <li><strong>Täter (Q111080573)</strong>: Person war an der Verfolgung, Inhaftierung oder Ermordung von NS-Opfern beteiligt</li>
              </ul>
              <p>Diese Information wird in Wikidata als Property P2868 (Rolle) mit dem entsprechenden Wert gespeichert.</p>`,
        "person-entry": `<p>In diesem Bereich werden die grundlegenden Informationen zur Person erfasst.</p>
                       <p><strong>Pflichtfelder:</strong></p>
                       <ul>
                         <li><strong>Vorname und Nachname</strong>: Vollständiger Name der Person</li>
                         <li><strong>Kurze Beschreibung</strong>: Kurze Zusammenfassung über die Person (max. 250 Zeichen)</li>
                       </ul>`,
        "personal-data": `<p><strong>Persönliche Daten der Person:</strong></p>
                       <ul>
                         <li><strong>Geburtsname</strong>: Falls abweichend vom aktuellen Namen</li>
                         <li><strong>Geschlecht</strong>: Wenn bekannt</li>
                         <li><strong>Geburtsdatum/Sterbedatum</strong>: Im Format YYYY-MM-DD</li>
                         <li><strong>Staatsangehörigkeit</strong>: Nationalität der Person</li>
                         <li><strong>Beruf</strong>: Hauptberuf oder Tätigkeit der Person</li>
                       </ul>
                       <p>Diese Angaben helfen dabei, die Person eindeutig zu identifizieren und in den historischen Kontext einzuordnen.</p>`,
        locations: `<p><strong>Wichtige Orte im Leben der Person:</strong></p>
                       <ul>
                         <li><strong>Geburtsort</strong>: Wo die Person geboren wurde</li>
                         <li><strong>Sterbeort</strong>: Wo die Person verstorben ist</li>
                         <li><strong>Wohnort</strong>: Hauptwohnsitz oder wichtiger Aufenthaltsort</li>
                         <li><strong>Haftorte</strong>: Wo die Person inhaftiert war (nur für Geschädigte)</li>
                         <li><strong>Arbeitgeber</strong>: Organisation oder Institution (nur für Täter)</li>
                       </ul>
                       <p>Die Ortsangaben werden in Wikidata mit geografischen Koordinaten verknüpft und können auf der Karte angezeigt werden.</p>`,
        sources: `<p>Quellenangaben sind ein verpflichtendes Feld.</p>
               <p>Füge mindestens eine Quelle hinzu, um die Informationen zu dieser Person zu belegen:</p>
               <ul>
                 <li>Bücher, Zeitschriften oder wissenschaftliche Artikel</li>
                 <li>Archive und Sammlungen</li>
                 <li>Offizielle Dokumente und Berichte</li>
                 <li>Zeitzeugenberichte</li>
               </ul>
               <p>Gute Quellenangaben enthalten Autor, Titel, Jahr und möglichst eine URL oder DOI.</p>`,
        image: `<p>Hier können Sie ein Bild aus Wikimedia Commons auswählen:</p>
               <ul>
                 <li>Bilder helfen bei der Identifizierung der Person</li>
                 <li>Achten Sie auf freie Lizenzen</li>
                 <li>Historische Bilder sind besonders wertvoll</li>
                 <li>Stellen Sie sicher, dass das Bild zur Person passt</li>
                 <li>Klicken Sie auf "Bild auswählen" und suchen Sie nach einem passenden Bild</li>
               </ul>
               <p>Hinweis: In Wikimedia Commons finden Sie nur Bilder mit freier Lizenz, die öffentlich verwendet werden dürfen.</p>`,
    };

    return sectionContents[fieldName] || "Keine Informationen verfügbar.";
};

// We'll handle this in the main onMounted hook
onBeforeUnmount(() => {
    window.removeEventListener("resize", checkScreenSize);
});

// Using GenderSelector component instead of options here

// Using WikidataSelector components for nationality and occupation instead of static options

// We don't need validationStatus ref since we handle validation directly

// Form validation rules
const rules = {
    required: (value) => !!value || "Dieses Feld ist erforderlich",
    maxLength: (value) => {
        return !value || value.length <= 250 || "Maximal 250 Zeichen erlaubt";
    },
};

// Watch for changes in person's name and update title accordingly
// Added a guard to prevent infinite loop when both firstName and lastName are empty
watch(
    () => [personData.value.firstName, personData.value.lastName],
    ([newFirstName, newLastName], oldValues) => {
        // Only proceed if we have actual values to work with
        // or if this isn't the initial watch trigger with empty values
        const newTitle = formatTitleFromName(newFirstName, newLastName);
        const oldFirstName = oldValues ? oldValues[0] : null;
        const oldLastName = oldValues ? oldValues[1] : null;

        console.log(
            `Name changed to: ${newFirstName || ""} ${newLastName || ""}`,
        );

        // Skip update if both values are empty and this isn't the result of a real change
        if (newFirstName || newLastName || oldFirstName || oldLastName) {
            console.log(
                `Name changed - updating title to: "${newTitle}" from firstName: "${newFirstName}", lastName: "${newLastName}"`,
            );
            title.value = newTitle;
        } else {
            console.log("Skipping empty name update to prevent recursion");
        }
    },
);

// Watch for changes in personData description
watch(
    () => personData.value.description,
    (newDescription) => {
        // Safety check to ensure we don't cause a recursion
        const safeNewDesc =
            typeof newDescription === "string" ? newDescription : "";
        if (safeNewDesc !== description.value) {
            console.log(`Description changed in personData: "${safeNewDesc}"`);
            description.value = safeNewDesc;
        }
    },
);

// Watch for changes in component description
watch(
    () => description.value,
    (newDescription) => {
        // Safety check to ensure we don't cause a recursion
        const safeNewDesc =
            typeof newDescription === "string" ? newDescription : "";
        const currentDesc =
            typeof personData.value.description === "string"
                ? personData.value.description
                : "";

        if (safeNewDesc !== currentDesc) {
            console.log(`Description changed in component: "${safeNewDesc}"`);
            personData.value.description = safeNewDesc;
        }
    },
);

// Person data validation computed property
const isPersonDetailsValid = computed(() => {
    if (!personData.value) return false;

    // First name is required unless it's an organization
    const firstNameValid =
        personData.value.isOrganization ||
        (personData.value.firstName &&
            personData.value.firstName.trim().length > 0);

    // Last name is always required (or organization name)
    const lastNameValid =
        personData.value.lastName &&
        personData.value.lastName.trim().length > 0;

    // Description is required
    const descriptionValid =
        personData.value.description &&
        personData.value.description.trim().length > 0;

    // Return true only if all required fields are valid
    return firstNameValid && lastNameValid && descriptionValid;
});

// Watchers to update validation error states when personData changes
// Update error states based on validation results
const updateValidationErrors = () => {
    if (!personData.value) return;

    // First name validation
    const firstName = personData.value.firstName?.trim() || "";
    const firstNameValid = firstName.length > 0;
    firstNameError.value = !firstNameValid;

    console.log("First name validation:", {
        firstName: firstName,
        valid: firstNameValid,
        error: firstNameError.value,
    });

    // Last name validation
    const lastName = personData.value.lastName?.trim() || "";
    const lastNameValid = lastName.length > 0;
    lastNameError.value = !lastNameValid;

    console.log("Last name validation:", {
        lastName: lastName,
        valid: lastNameValid,
        error: lastNameError.value,
    });

    // Log the actual value to help with debugging
    console.log("Last name validation:", {
        value: lastName,
        valid: lastNameValid,
        error: lastNameError.value,
    });

    // Description validation - check both personData.description and component description
    const descriptionInPersonData =
        (personData.value.description?.trim() || "").length > 0;
    const descriptionInProps = (description.value?.trim() || "").length > 0;
    const descriptionValid = descriptionInPersonData || descriptionInProps;

    console.log("updateValidationErrors - Description check:", {
        descriptionInPersonData,
        descriptionInProps,
        descriptionValid,
        personDataDesc: personData.value.description || "",
        descriptionProp: description.value || "",
    });

    // Sync description between the two fields to ensure consistency
    if (descriptionInPersonData && !descriptionInProps) {
        description.value = personData.value.description?.trim() || "";
    } else if (!descriptionInPersonData && descriptionInProps) {
        personData.value.description = description.value?.trim() || "";
    }

    console.log("Description validation:", {
        description: personData.value.description || "",
        descriptionProp: description.value || "",
        valid: descriptionValid,
        error: !descriptionValid,
    });

    descriptionError.value = !descriptionValid;
};

// Watchers to update validation error states when personData changes
watch(
    () => personData.value?.firstName,
    (newValue) => {
        console.log(`First name changed to: "${newValue}"`);
        if (showPersonValidationErrors.value) {
            updateValidationErrors();
        }
    },
);

watch(
    () => personData.value?.description,
    (newValue) => {
        console.log(`Description changed to: "${newValue}"`);
        if (showPersonValidationErrors.value) {
            updateValidationErrors();
        }
    },
);

watch(
    () => personData.value?.lastName,
    (newValue) => {
        console.log(`Last name changed to: "${newValue}"`);
        if (showPersonValidationErrors.value) {
            updateValidationErrors();
        }
    },
);

// Wikidata search states
const wikidataSearchResults = ref({
    nationality: [],
    place_of_birth: [],
    place_of_death: [],
    place_of_detention: [],
    employer: [],
    occupation: [],
    place_of_residence: [],
    image: [],
});

const isWikidataSearchLoading = ref({
    nationality: false,
    place_of_birth: false,
    place_of_death: false,
    place_of_detention: false,
    employer: false,
    occupation: false,
    place_of_residence: false,
    image: false,
});

const wikidataSearchPerformed = ref({
    nationality: false,
    place_of_birth: false,
    place_of_death: false,
    place_of_detention: false,
    employer: false,
    occupation: false,
    place_of_residence: false,
    image: false,
});

const wikidataResultsValue = computed(() => {
    return [];
});

const setActiveSection = (sectionName) => {
    activeSection.value = sectionName;
};

const onBaseInfoValidation = (valid) => {
    baseInfosValid.value = valid;
    // Reset validation errors when field becomes valid
    if (valid && isPersonDetailsValid.value) {
        showPersonValidationErrors.value = false;
    }
};

// Removed updatePersonData function as it's not needed

const handleSourcesUpdate = (newSources) => {
    selectedSourcesValue.value = newSources;
    showPersonValidationErrors.value = false;
    // Update the validation state
    sourceError.value = !newSources.length;
};

const onSourcesValidation = (valid) => {
    sourcesValid.value = valid;
    // Reset validation errors when field becomes valid
    if (
        valid &&
        props &&
        props.selectedSources &&
        props.selectedSources.length > 0
    ) {
        showPersonValidationErrors.value = false;
        sourceError.value = false;
    } else {
        sourceError.value = true;
    }
};

const selectedSourcesValue = computed({
    get: () => (props && props.selectedSources ? props.selectedSources : []),
    set: (value) => {
        if (props && typeof emit === "function") {
            emit("update:selectedSources", value);
        }
    },
});

// This is called by the parent component when form validation is needed
const validateForm = async () => {
    // Show validation errors during form validation
    showPersonValidationErrors.value = true;

    // Run validation on all required fields
    updateValidationErrors();

    // Check sources validation
    if (!props.selectedSources || props.selectedSources.length === 0) {
        sourceError.value = true;
    } else {
        sourceError.value = false;
    }

    // Check if sources are missing (redundant but keeping for clarity)
    const noSources = !props?.selectedSources?.length;
    sourceError.value = noSources;

    // Make sure validation errors are visible
    if (noSources || !formIsValid) {
        showPersonValidationErrors.value = true;
    }
    // Check if the form is valid overall
    const firstName = personData.value.firstName?.trim() || "";
    const lastName = personData.value.lastName?.trim() || "";
    const descriptionValue = personData.value.description?.trim() || "";

    const formIsValid =
        firstName.length > 0 &&
        lastName.length > 0 &&
        descriptionValue.length > 0 &&
        props.selectedSources &&
        props.selectedSources.length > 0;

    console.log("Form validation complete:", {
        firstName: { value: firstName, valid: !!firstName },
        lastName: { value: lastName, valid: !!lastName },
        description: { value: descriptionValue, valid: !!descriptionValue },
        sources: {
            valid: props.selectedSources && props.selectedSources.length > 0,
        },
        overall: formIsValid,
    });

    return formIsValid;
};

// Expose methods and properties to parent components
defineExpose({
    validateForm,
    resetSubmitting,
    isValid: {
        get baseInfos() {
            return baseInfosValid.value;
        },
        get sources() {
            return sourcesValid.value;
        },
    },
});

// Helper function to validate form sections
const validateField = async (ref) => {
    if (!ref || !ref.value) return true;

    // Check if the ref has a validate function
    if (typeof ref.value.validate === "function") {
        return await ref.value.validate();
    }

    return true;
};

const onSelectExistingEntity = (entity) => {
    if (entity && entity.id) {
        title.value = entity.label || "";
        description.value = entity.description || "";
        // Additional logic for handling existing entity selection
    }
};

// Handle loading of person data from title prop or temp data
const initializeFromTitle = () => {
    // First check if we have temporary person data from the search overlay
    if (props.tempPersonData) {
        try {
            const tempData = JSON.parse(props.tempPersonData);
            console.log("Initializing from temp data:", tempData);

            // Create complete person data from temp data
            const updates = {
                firstName: tempData.firstName || "",
                lastName: tempData.lastName || "",
                description: tempData.description || "",
                role: tempData.role || personData.value.role,
                birthName:
                    tempData.birthName || personData.value.birthName || "",
                birthDate:
                    tempData.birthDate || personData.value.birthDate || null,
                deathDate:
                    tempData.deathDate || personData.value.deathDate || null,
                gender: tempData.gender || personData.value.gender || null,
                nationality:
                    tempData.nationality ||
                    personData.value.nationality ||
                    null,
                place_of_birth:
                    tempData.place_of_birth ||
                    personData.value.place_of_birth ||
                    null,
                place_of_death:
                    tempData.place_of_death ||
                    personData.value.place_of_death ||
                    null,
                place_of_detention:
                    tempData.place_of_detention ||
                    personData.value.place_of_detention ||
                    [],
                place_of_residence:
                    tempData.place_of_residence ||
                    personData.value.place_of_residence ||
                    null,
                occupation:
                    tempData.occupation || personData.value.occupation || null,
                imageUrl:
                    tempData.imageUrl || personData.value.imageUrl || null,
            };

            // Apply all updates at once to minimize reactivity issues
            Object.assign(personData.value, updates);

            // Sync component description
            description.value = updates.description;

            console.log("Set personData from tempData:", updates);

            // Title will be automatically updated by the watcher
            console.log("Name fields updated, title should update via watcher");

            return; // Skip the title parsing if we used temp data
        } catch (e) {
            console.error("Failed to parse temporary person data:", e);
        }
    }

    // Fall back to parsing from title if no temp data
    if (props.title) {
        const { firstName, lastName } = parseNameFromTitle(props.title);

        // Initialize with default empty values to avoid undefined issues
        personData.value = {
            firstName: firstName || "",
            lastName: lastName || "",
            birthName: "",
            description: props.description || "",
            birthDate: null,
            deathDate: null,
            gender: null,
            nationality: null,
            place_of_birth: null,
            place_of_death: null,
            place_of_detention: [],
            place_of_residence: null,
            occupation: null,
            employer: null,
            image: null,
            imageUrl: null,
            imageFilename: null,
            role: "victim", // Default to "victim" (Geschädigter, Q2026714)
        };

        // Set description from props if available
        if (props.description) {
            personData.value.description = props.description;
            description.value = props.description;
        }

        // Log the initialization
        console.log("Initialized from title:", {
            title: props.title,
            firstName: personData.value.firstName,
            lastName: personData.value.lastName,
            description: personData.value.description,
        });
    }
};

// Watch for changes to title or tempPersonData
watch(
    [() => props.title, () => props.tempPersonData],
    ([newTitle, newTempData], [oldTitle, oldTempData]) => {
        // Only initialize if we have real data to work with
        // Skip initialization if title is empty and we had no previous title
        if (newTitle || oldTitle || newTempData) {
            initializeFromTitle();
        } else {
            console.log("Skipping initialization with empty data");
        }
    },
);

const searchWikidataItems = async ({ field, query }) => {
    if (!query || !query.trim()) return;

    isWikidataSearchLoading.value[field] = true;
    wikidataSearchPerformed.value[field] = true;

    let apiEndpoint;

    switch (field) {
        case "nationality":
            apiEndpoint = "nationalities";
            break;
        case "place_of_birth":
        case "place_of_death":
        case "place_of_detention":
        case "place_of_residence":
            apiEndpoint = "places";
            break;
        case "occupation":
            apiEndpoint = "occupations";
            break;
        case "employer":
            apiEndpoint = "people";
            break;
        case "image":
            apiEndpoint = "wikimedia/images";
            break;
        default:
            apiEndpoint = "items";
    }

    try {
        const response = await axios.get(
            `/api/wikidata/search/${apiEndpoint}`,
            {
                params: { query },
            },
        );

        // Different API endpoints return different formats
        if (Array.isArray(response.data)) {
            // Direct array response
            wikidataSearchResults.value[field] = response.data;
        } else if (response.data && response.data.success) {
            // Success/results format
            wikidataSearchResults.value[field] = response.data.results || [];
        } else if (response.data && response.data.error) {
            // Error message in response
            showToast(
                `Fehler bei der Suche nach ${field}: ${response.data.error}`,
                "error",
            );
            wikidataSearchResults.value[field] = [];
        } else {
            // Unexpected format but still a valid response
            console.log("Unexpected response format:", response.data);
            wikidataSearchResults.value[field] =
                response.data.search ||
                response.data.results ||
                response.data ||
                [];
        }
    } catch (error) {
        console.error(`Error searching for ${field}:`, error);

        // More detailed error logging
        if (error.response) {
            console.log(`Response status: ${error.response.status}`);
            console.log(`Response data:`, error.response.data);
        }

        let errorMessage = "Unbekannter Fehler";

        if (error.response?.data?.error) {
            errorMessage = error.response.data.error;
        } else if (error.response?.data?.message) {
            errorMessage = error.response.data.message;
        } else if (error.message) {
            errorMessage = error.message;
        }

        showToast(
            `Fehler bei der Suche nach ${field}: ${errorMessage}`,
            "error",
        );
        wikidataSearchResults.value[field] = [];
    } finally {
        isWikidataSearchLoading.value[field] = false;
    }
};

const goToPreviousStep = () => {
    isSubmitting.value = false; // Ensure we reset the submitting state
    emit("goToStep", 1);
};

// Form validation and submission handler
const validateAndSubmit = async () => {
    // Prevent multiple submissions
    if (isSubmitting.value) {
        console.log(
            "Form submission already in progress, ignoring duplicate call",
        );
        return;
    }

    try {
        // Reset validation state first
        showPersonValidationErrors.value = false;
        // Reset all individual error flags
        firstNameError.value = false;
        lastNameError.value = false;
        descriptionError.value = false;
        sourceError.value = false;

        // Now set submission state and show validation UI
        isSubmitting.value = true;
        showPersonValidationErrors.value = true;

        // Update validation errors
        updateValidationErrors();

        // Check sources right away - this is a critical error
        const sourcesEmpty = !props?.selectedSources?.length;
        sourceError.value = sourcesEmpty;

        if (sourcesEmpty) {
            // Special handling for missing sources
            console.log("Source validation failed - missing sources");
            showToast("Bitte geben Sie mindestens eine Quelle an.", "error");
            // Focus the sources section
            const timeoutId = setTimeout(() => {
                handleSourceErrorClick();
                isSubmitting.value = false;
            }, 100);
            activeTimeouts.value.push(timeoutId);
            return;
        }

        // Gender field handling will be done in the submission part
        // Ensure personData is valid to prevent undefined errors
        if (!personData.value) {
            personData.value = {
                firstName: "",
                lastName: "",
                description: description.value || "",
                isOrganization: false,
                gender: null,
                nationality: null,
                place_of_birth: null,
                place_of_death: null,
                place_of_detention: [],
                place_of_residence: null,
                occupation: null,
                employer: null,
                imageUrl: null,
                imageFilename: null,
            };
        }

        // Ensure description is a string
        if (
            personData.value.description === null ||
            personData.value.description === undefined
        ) {
            personData.value.description = "";
        }

        if (description.value === null || description.value === undefined) {
            description.value = "";
        }

        // Show validation errors
        showPersonValidationErrors.value = true;

        // Update all validation error states
        updateValidationErrors();

        // Trigger validation in child components
        if (baseInfosRef?.value?.validateForm) {
            await baseInfosRef.value.validateForm();
        }

        if (sourcesRef?.value?.validateForm) {
            await sourcesRef.value.validateForm();
        }

        // Check sources right away - this is a critical error
        const sourceMissing = !props?.selectedSources?.length;
        sourceError.value = sourceMissing;

        if (sourceMissing) {
            // Special handling for missing sources
            console.log("Source validation failed - missing sources");
            isSubmitting.value = false;
            showToast("Bitte geben Sie mindestens eine Quelle an.", "error");
            // Focus the sources section
            handleSourceErrorClick();
            return false;
        }

        // Collect missing fields for error message
        const missingFields = [];

        if (!title.value || !title.value.trim()) {
            missingFields.push("Titel");
        }

        // Ensure description is synchronized between both fields
        try {
            let descriptionHasValue = false;
            try {
                descriptionHasValue =
                    description.value !== null &&
                    description.value !== undefined &&
                    typeof description.value === "string" &&
                    description.value.trim().length > 0;
            } catch (e) {
                console.error("Error checking description value:", e);
            }

            let personDataDescriptionEmpty = true;
            try {
                personDataDescriptionEmpty =
                    !personData.value.description ||
                    personData.value.description === null ||
                    personData.value.description === undefined ||
                    typeof personData.value.description !== "string" ||
                    personData.value.description.trim().length === 0;
            } catch (e) {
                console.error("Error checking personData description:", e);
            }

            if (descriptionHasValue && personDataDescriptionEmpty) {
                personData.value.description = String(description.value || "");
            }

            let personDataDescriptionHasValue = false;
            try {
                personDataDescriptionHasValue =
                    personData.value.description !== null &&
                    personData.value.description !== undefined &&
                    typeof personData.value.description === "string" &&
                    personData.value.description.trim().length > 0;
            } catch (e) {
                console.error(
                    "Error checking personData description value:",
                    e,
                );
            }

            let descriptionEmpty = true;
            try {
                descriptionEmpty =
                    !description.value ||
                    description.value === null ||
                    description.value === undefined ||
                    typeof description.value !== "string" ||
                    description.value.trim().length === 0;
            } catch (e) {
                console.error("Error checking if description is empty:", e);
            }

            if (personDataDescriptionHasValue && descriptionEmpty) {
                description.value = String(personData.value.description || "");
            }
        } catch (e) {
            console.error("Error synchronizing description fields:", e);
        }

        // Check for description in either the form description or personData description
        let hasDescription = false;
        try {
            hasDescription =
                (description?.value &&
                    typeof description.value === "string" &&
                    description.value.trim().length > 0) ||
                (personData?.value?.description &&
                    typeof personData.value.description === "string" &&
                    personData.value.description.trim().length > 0) ||
                false;
        } catch (e) {
            console.error("Error checking for description:", e);
            // Default to false if there's an error
            hasDescription = false;
        }

        console.log("Description validation:", {
            descriptionProp: description.value,
            personDataDescription: personData?.value?.description,
            hasDescription: hasDescription,
        });

        // Debug description validation
        console.log("Description validation debug:", {
            hasDescription,
            descriptionValue: description.value,
            personDataDesc: personData?.value?.description,
        });

        // Update description error state
        descriptionError.value = !hasDescription;

        if (!hasDescription) {
            missingFields.push("Kurzbeschreibung");
        }

        // Check if sources are missing
        const noSources = !props?.selectedSources?.length;
        sourceError.value = noSources;

        if (noSources) {
            missingFields.push("Quelle");
        }

        // Check for the first name (only required if not an organization)
        const firstNameRequired =
            personData?.value && !personData.value.isOrganization;
        const firstNameMissing =
            !personData.value.firstName ||
            personData.value.firstName.trim().length <= 0;

        const isFirstNameInvalid = firstNameRequired && firstNameMissing;
        // Update validation error state
        firstNameError.value = isFirstNameInvalid;

        if (isFirstNameInvalid) {
            missingFields.push("Vorname");
        }

        // Check for the last name or organization name
        const lastNameMissing =
            personData?.value &&
            (!personData.value.lastName || !personData.value.lastName.trim());

        // Update validation error state
        lastNameError.value = lastNameMissing;

        if (lastNameMissing) {
            missingFields.push(
                personData.value.isOrganization
                    ? "Name der Organisation"
                    : "Nachname",
            );
        }

        // Check overall form validity
        const formIsValid =
            baseInfosValid.value &&
            sourcesValid.value &&
            isPersonDetailsValid.value &&
            props?.selectedSources?.length > 0 &&
            missingFields.length === 0;

        if (!formIsValid) {
            showToast(
                `Fehlende Pflichtfelder: ${missingFields.length ? missingFields.join(", ") : "Bitte überprüfen Sie Ihre Eingaben"}`,
                "error",
            );
            return;
        }

        // Form is valid, hide validation errors
        showPersonValidationErrors.value = false;
        isSubmitting.value = true;
    } catch (error) {
        console.error("Error during form validation:", error);
        showToast(
            "Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.",
            "error",
        );
        return;
    }

    try {
        // Format the title properly from first and last name
        const formattedTitle =
            formatTitleFromName(
                personData.value.firstName,
                personData.value.lastName,
            ) || (title.value ? title.value.trim() : "");

        console.log("Submitting person form with role:", personData.value.role);

        // Make sure the role is properly set
        // The parent component (Entry.vue) will handle adding this person to
        // the proper victims or perpetrators array based on this role

        // Ensure role is either "victim" or "perpetrator" (default to "victim")
        if (!personData.value.role || personData.value.role === "standalone") {
            personData.value.role = "victim";
            console.log("Setting default role to victim");
        }

        console.log(`Submitting person with role: ${personData.value.role}`);

        // Ensure the description is properly copied between fields
        // If both have values, prefer the non-empty one
        try {
            let descriptionHasValue = false;
            try {
                descriptionHasValue =
                    description.value !== null &&
                    description.value !== undefined &&
                    typeof description.value === "string" &&
                    description.value.trim().length > 0;
            } catch (e) {
                console.error("Error checking if description has value:", e);
            }

            if (descriptionHasValue) {
                personData.value.description = String(description.value || "");
            } else {
                let personDataDescriptionHasValue = false;
                try {
                    personDataDescriptionHasValue =
                        personData.value.description !== null &&
                        personData.value.description !== undefined &&
                        typeof personData.value.description === "string" &&
                        personData.value.description.trim().length > 0;
                } catch (e) {
                    console.error(
                        "Error checking if personData description has value:",
                        e,
                    );
                }

                if (personDataDescriptionHasValue) {
                    description.value = String(
                        personData.value.description || "",
                    );
                }
            }
        } catch (e) {
            console.error("Error copying description between fields:", e);
        }

        // If neither has a value, set a default
        if (
            (!description.value ||
                typeof description.value !== "string" ||
                !description.value.trim()) &&
            (!personData.value.description ||
                typeof personData.value.description !== "string" ||
                !personData.value.description.trim())
        ) {
            const defaultDesc = "Opfer des Nationalsozialismus.";
            description.value = defaultDesc;
            personData.value.description = defaultDesc;
            console.log("Setting default description:", defaultDesc);
        }

        console.log("Submitting with first name:", personData.value.firstName);
        console.log("Submitting with last name:", personData.value.lastName);
        console.log("Formatted title:", formattedTitle);
        console.log("Description value:", description.value);
        console.log("personData description:", personData.value.description);

        // Get role from personData or props
        const role = personData.value.role || props.personRole || "standalone";
        console.log("Person role:", role);

        // Prepare the person data object
        const personDataObj = {
            firstName: personData.value.firstName || "",
            lastName: personData.value.lastName || "",
            title: formattedTitle,
            birthDate: personData.value.birthDate,
            deathDate: personData.value.deathDate,
            birthName: personData.value.birthName || "",
            description:
                personData.value.description || description.value || "",
            isOrganization: !!personData.value.isOrganization,
            // For gender, use array format to match backend validation expectations
            gender: personData.value.gender ? [personData.value.gender] : null,
            // For other Wikidata fields, store as direct strings (except place_of_detention)
            nationality: personData.value.nationality || null,
            place_of_birth: personData.value.place_of_birth || null,
            place_of_death: personData.value.place_of_death || null,
            place_of_detention: personData.value.place_of_detention || [],
            place_of_residence: personData.value.place_of_residence || null,
            occupation: personData.value.occupation || null,
            employer: personData.value.employer || null,
            birthName: personData.value.birthName || null,
            image: personData.value.image,
            imageUrl: personData.value.imageUrl,
            imageFilename: personData.value.imageFilename,
            role: role, // Use the determined role
            wikidataId: personData.value.wikidataId,
            type: "Q5", // Always mark as person type
        };

        // Make sure description is consistent
        let finalDescription = "";
        try {
            // Use description.value if it exists and is non-empty
            if (description.value !== null && description.value !== undefined) {
                const descStr =
                    typeof description.value === "string"
                        ? description.value
                        : String(description.value);
                if (descStr.trim().length > 0) {
                    finalDescription = descStr;
                }
            }

            // If we still don't have a description, try personData.description
            if (
                !finalDescription &&
                personData.value.description !== null &&
                personData.value.description !== undefined
            ) {
                const personDataDesc =
                    typeof personData.value.description === "string"
                        ? personData.value.description
                        : String(personData.value.description);
                if (personDataDesc.trim().length > 0) {
                    finalDescription = personDataDesc;
                }
            }

            // If still no description, use default
            if (!finalDescription) {
                finalDescription = "Opfer des Nationalsozialismus.";
            }

            // Ensure both values are set to the same string
            description.value = finalDescription;
            personData.value.description = finalDescription;

            console.log("Final description set to:", finalDescription);
        } catch (err) {
            console.error("Error finalizing description:", err);
            // Set fallback values in case of error
            finalDescription = "Opfer des Nationalsozialismus.";
            description.value = finalDescription;
            personData.value.description = finalDescription;
        }

        // Make sure validation errors are shown and up-to-date
        updateValidationErrors();
        showPersonValidationErrors.value = true;

        // Check sources again before submitting
        const finalSourcesCheck =
            !props.selectedSources || props.selectedSources.length === 0;
        if (finalSourcesCheck) {
            console.log("Sources missing in final validation");
            sourceError.value = true;
            showPersonValidationErrors.value = true;
            showToast("Bitte geben Sie mindestens eine Quelle an.", "error");
            const timeoutId = setTimeout(() => {
                handleSourceErrorClick();
                isSubmitting.value = false;
            }, 100);
            activeTimeouts.value.push(timeoutId);
            return;
        }

        // Make sure all WikiData fields are in correct format (array with objects)
        const fieldsToCheck = [
            "gender",
            "nationality",
            "place_of_birth",
            "place_of_death",
            "place_of_detention",
            "place_of_residence",
            "occupation",
            "employer",
        ];

        fieldsToCheck.forEach((field) => {
            if (
                typeof personData.value[field] === "string" &&
                personData.value[field]
            ) {
                console.log(
                    `Converting ${field} string ${personData.value[field]} to array format`,
                );
                personData.value[field] = [{ id: personData.value[field] }];
            } else if (
                personData.value[field] &&
                typeof personData.value[field] === "object" &&
                !Array.isArray(personData.value[field])
            ) {
                // If it's an object but not an array, wrap it in an array
                personData.value[field] = [personData.value[field]];
            }
        });

        // Check if there are any validation errors
        if (
            firstNameError.value ||
            lastNameError.value ||
            descriptionError.value
        ) {
            console.log("Validation errors found, stopping submission");
            showToast("Bitte korrigieren Sie die Fehler im Formular.", "error");
            isSubmitting.value = false;
            return;
        }

        // Prepare form data for submission
        // Final check for all WikiData fields to ensure they're in array format
        const wikidataFields = [
            "gender",
            "nationality",
            "place_of_birth",
            "place_of_death",
            "place_of_detention",
            "place_of_residence",
            "occupation",
            "employer",
        ];

        // Process all fields to ensure correct format - store only Q-numbers
        wikidataFields.forEach((field) => {
            if (personDataObj[field]) {
                // Special handling for place_of_detention which is always an array
                if (field === "place_of_detention") {
                    const detentionArray = [];

                    if (Array.isArray(personDataObj[field])) {
                        personDataObj[field].forEach((item) => {
                            // Handle if it's already a string Q-number
                            if (
                                typeof item === "string" &&
                                item.startsWith("Q")
                            ) {
                                detentionArray.push(item);
                            }
                            // Handle if it's an object with id property
                            else if (
                                item &&
                                item.id &&
                                typeof item.id === "string" &&
                                item.id.startsWith("Q")
                            ) {
                                detentionArray.push(item.id);
                            }
                        });
                    }

                    personDataObj[field] = detentionArray;
                } else {
                    // For gender, always use array format to match backend expectations
                    if (field === "gender") {
                        let qNumber = null;

                        // Handle different input formats to extract Q-number
                        if (Array.isArray(personDataObj[field])) {
                            if (personDataObj[field].length > 0) {
                                const firstItem = personDataObj[field][0];
                                if (
                                    typeof firstItem === "string" &&
                                    firstItem.startsWith("Q")
                                ) {
                                    qNumber = firstItem;
                                } else if (
                                    firstItem &&
                                    firstItem.id &&
                                    typeof firstItem.id === "string" &&
                                    firstItem.id.startsWith("Q")
                                ) {
                                    qNumber = firstItem.id;
                                }
                            }
                        }
                        // Handle if it's a string Q-number
                        else if (
                            typeof personDataObj[field] === "string" &&
                            personDataObj[field].startsWith("Q")
                        ) {
                            qNumber = personDataObj[field];
                        }
                        // Handle if it's an object with id property
                        else if (
                            personDataObj[field] &&
                            personDataObj[field].id &&
                            typeof personDataObj[field].id === "string" &&
                            personDataObj[field].id.startsWith("Q")
                        ) {
                            qNumber = personDataObj[field].id;
                        }

                        // Store gender as an array with a single item (backend requires array)
                        personDataObj[field] = qNumber ? [qNumber] : null;
                    } else {
                        // For all other fields, simplify to a single Q-number string
                        let qNumber = null;

                        // Handle different input formats to extract Q-number
                        if (Array.isArray(personDataObj[field])) {
                            if (personDataObj[field].length > 0) {
                                const firstItem = personDataObj[field][0];
                                if (
                                    typeof firstItem === "string" &&
                                    firstItem.startsWith("Q")
                                ) {
                                    qNumber = firstItem;
                                } else if (
                                    firstItem &&
                                    firstItem.id &&
                                    typeof firstItem.id === "string" &&
                                    firstItem.id.startsWith("Q")
                                ) {
                                    qNumber = firstItem.id;
                                }
                            }
                        }
                        // Handle if it's a string Q-number
                        else if (
                            typeof personDataObj[field] === "string" &&
                            personDataObj[field].startsWith("Q")
                        ) {
                            qNumber = personDataObj[field];
                        }
                        // Handle if it's an object with id property
                        else if (
                            personDataObj[field] &&
                            personDataObj[field].id &&
                            typeof personDataObj[field].id === "string" &&
                            personDataObj[field].id.startsWith("Q")
                        ) {
                            qNumber = personDataObj[field].id;
                        }

                        // Store as direct string (not in an array)
                        personDataObj[field] = qNumber;
                    }
                }

                // Log the final field format
                console.log(`Final ${field} format:`, personDataObj[field]);
            }
        });

        const formData = {
            title: formattedTitle,
            description: finalDescription,
            type: props.entryType || "Q5", // Q5 for human
            sources: props.selectedSources || [],
            // Only set standalone if role is "standalone"
            is_standalone_person: role === "standalone",
            // Add to victims or perpetrators array based on role
            victims: role === "victim" ? [personDataObj] : [],
            perpetrators: role === "perpetrator" ? [personDataObj] : [],
            // We don't want data in the root level anymore as specified in the requirements
            // Ensure person fields at root level are null
            gender: null,
            nationality: null,
            place_of_birth: null,
            place_of_death: null,
            place_of_detention: null,
            place_of_residence: null,
            occupation: null,
            employer: null,
            image: null,
            imageUrl: null,
            imageFilename: null,
        };

        console.log(
            "Form data being submitted:",
            JSON.stringify(formData, null, 2),
        );
        console.log("Final description being submitted:", formData.description);
        console.log("is_standalone_person:", formData.is_standalone_person);
        console.log("victims array:", formData.victims);
        console.log("perpetrators array:", formData.perpetrators);

        // Generate a secure random token for the entry
        const generateSecureToken = () => {
            const array = new Uint8Array(24);
            window.crypto.getRandomValues(array);
            return Array.from(array, (byte) =>
                byte.toString(16).padStart(2, "0"),
            ).join("");
        };

        const secureToken = generateSecureToken();
        formData.statusToken = secureToken;

        console.log(
            "Form validated, emitting submission data to parent component:",
            formData,
        );

        // Log the data structure to verify it's correct
        console.log("Victims array:", formData.victims);
        console.log("Perpetrators array:", formData.perpetrators);
        console.log("Root level person fields (should be null):", {
            gender: formData.gender,
            nationality: formData.nationality,
            place_of_birth: formData.place_of_birth,
            place_of_death: formData.place_of_death,
            place_of_detention: formData.place_of_detention,
            place_of_residence: formData.place_of_residence,
            occupation: formData.occupation,
            employer: formData.employer,
        });

        // Instead of submitting directly, emit the data to the parent component
        // The parent will handle the actual submission and manage the loading state
        emit("submit", formData);
        // We don't reset isSubmitting here - parent component will handle the actual submission
        // and loading state should persist until the submission is complete
    } catch (error) {
        console.error("Error validating form:", error);
        showToast(`Fehler bei der Validierung: ${error.message}`, "error");
        isSubmitting.value = false;
    }

    // Add a safeguard timeout to reset the loading state after 10 seconds
    // in case the parent component doesn't handle it
    safetyTimerId = setTimeout(() => {
        if (isSubmitting.value) {
            console.log(
                "Safeguard timeout: resetting isSubmitting after 10 seconds",
            );
            isSubmitting.value = false;
            try {
                showToast(
                    "Die Anfrage wurde automatisch zurückgesetzt. Bitte versuchen Sie es erneut.",
                    "warning",
                );
            } catch (e) {
                console.error("Error showing toast:", e);
            }
        }
    }, 10000);
};

// Method to reset the submitting state (exposed to parent component)
const resetSubmitting = () => {
    console.log("resetSubmitting called by parent component");
    isSubmitting.value = false;
    // Also ensure validation state is cleared when explicitly reset
    if (!showPersonValidationErrors.value) {
        // Only clear errors if we're not currently showing validation errors
        firstNameError.value = false;
        lastNameError.value = false;
        descriptionError.value = false;
        sourceError.value = false;
    }
};

// Handle click on source error validation message
const handleSourceErrorClick = () => {
    console.log("Source validation error clicked, focusing sources section");
    // Focus sources section
    setActiveField("sources");
    // Scroll to sources field
    if (sourcesFieldRef.value) {
        const element = sourcesFieldRef.value.$el;
        if (element) {
            console.log("Scrolling to sources field element:", element);
            element.scrollIntoView({
                behavior: "smooth",
                block: "center",
            });

            // Try to focus the sources field input if available
            const focusTimeoutId = setTimeout(() => {
                try {
                    if (
                        typeof sourcesFieldRef.value?.focusInput === "function"
                    ) {
                        sourcesFieldRef.value.focusInput();
                    }
                } catch (err) {
                    console.error("Error focusing sources input:", err);
                }
            }, 500);
            activeTimeouts.value.push(focusTimeoutId);
        }
    } else {
        console.warn("Sources field reference not found");
    }
    // Reset submitting state and make sure errors are visible
    isSubmitting.value = false;
    showPersonValidationErrors.value = true;

    // Force validation update
    updateValidationErrors();
    sourceError.value = true;
};

// Lifecycle hooks
// Clean up state when component is unmounted
onBeforeUnmount(() => {
    isSubmitting.value = false;
    window.removeEventListener("resize", checkScreenSize);

    // Clear the safety timeout if it exists
    if (safetyTimerId) {
        clearTimeout(safetyTimerId);
    }

    // Clear any other active timeouts
    activeTimeouts.value.forEach((id) => {
        if (id) clearTimeout(id);
    });
    // Clear the array after cleaning up
    activeTimeouts.value = [];
});

// Main onMounted hook to handle all initialization
onMounted(() => {
    // Reset submission state for safety
    isSubmitting.value = false;

    // Initialize from title data
    initializeFromTitle();

    // Setup for mobile view detection
    checkScreenSize();
    window.addEventListener("resize", checkScreenSize);

    // Initialize component properties
    if (props.title) {
        title.value = props.title;
    }
    if (props.description) {
        description.value = props.description;
        // Also initialize personData.description if needed
        if (personData.value && !personData.value.description) {
            personData.value.description = props.description;
        }
    }

    // Reset validation state on component mount
    showPersonValidationErrors.value = false;
    baseInfosValid.value = true; // Start with true validation state
    sourcesValid.value = true; // Start with true validation state
    firstNameError.value = false;
    lastNameError.value = false;
    sourceError.value = false;
    descriptionError.value = false;

    // Don't show errors on initial load, but prepare the validation state
    updateValidationErrors();
});

// Watchers for title and description to emit updates
watch(title, (newValue, oldValue) => {
    // Only emit updates when we have meaningful changes
    // This prevents propagating empty title updates that can cause infinite loops
    if (newValue !== props.title && (newValue || oldValue)) {
        console.log(`Emitting title update: "${newValue}"`);
        emit("update:title", newValue);
    }
});

// Watch for changes in selectedSources
watch(
    () => props.selectedSources,
    (newSources) => {
        if (newSources && showPersonValidationErrors.value) {
            sourceError.value = newSources.length === 0;
        }
    },
    { deep: true },
);

// Watch for changes in the description field
watch(description, (newValue) => {
    if (newValue !== props.description) {
        emit("update:description", newValue);

        // Update description error state when description changes
        const hasDescription = newValue && newValue.trim().length > 0;
        if (showPersonValidationErrors.value) {
            descriptionError.value = !hasDescription;
        }
    }
});

// Watch for changes in props.description
watch(
    () => props.description,
    (newValue) => {
        if (newValue !== description.value) {
            description.value = newValue;

            // Update description validation state
            if (showPersonValidationErrors.value) {
                // Call updateValidationErrors to check both description fields
                updateValidationErrors();
            }
        }
    },
);

// Watch props.description and props.title for changes
watch(
    () => props.description,
    (newValue) => {
        if (newValue !== description.value) {
            description.value = newValue;
        }
    },
);

watch(
    () => props.title,
    (newValue) => {
        // Prevent infinite loops by not updating title when it's empty
        // unless current title has an actual value
        if (newValue !== title.value && (newValue || title.value)) {
            console.log(
                `Props title changed to "${newValue}" from "${title.value}"`,
            );
            title.value = newValue;
        }
    },
);
</script>

<style scoped>
.person-form-wrapper {
    margin-bottom: 2rem;
}

.entry-section {
    margin-bottom: 2rem;
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 8px;
    width: 100%;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.section-label {
    font-weight: bold;
    font-size: 1.1rem;
}

.info-icon {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--v-primary-base);
}

.search-input-container {
    display: flex;
    margin-bottom: 1rem;
}

.search-results {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.search-result-item {
    cursor: pointer;
    transition: background-color 0.2s;
}

.search-result-item:hover {
    background-color: #f0f0f0;
}

.search-loading,
.search-no-results {
    display: flex;
    align-items: center;
    padding: 1rem;
    color: #666;
}

.search-loading > * + * {
    margin-left: 0.5rem;
}

.result-metadata {
    font-size: 0.85rem;
    color: #666;
}

.result-id {
    font-size: 0.8rem;
    color: #888;
}

.wikidata-link {
    color: #3366cc;
    text-decoration: none;
}

.wikidata-link:hover {
    text-decoration: underline;
}

.actions-row {
    display: flex;
    justify-content: space-between;
    margin-top: 2rem;
}

/* Styles for person form fields */
.person-form-fields {
    margin-bottom: 1rem;
}

.form-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background-color: #f8f9fa;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.section-subtitle {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 1.2rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #dee2e6;
    margin-top: 0;
    margin-right: auto;
}

.form-row {
    display: flex;
    flex-wrap: wrap;
    margin-right: -15px;
    margin-left: -15px;
}

.col-md-6 {
    flex: 0 0 50%;
    max-width: 50%;
    padding-right: 15px;
    padding-left: 15px;
}

.form-group {
    margin-bottom: 1rem;
    width: 100%;
}

.role-description {
    margin-top: -0.5rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    color: #666;
    font-style: italic;
    padding-left: 0.5rem;
    border-left: 3px solid #4caf50;
}

.section-header {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
}

.info-icon {
    background: none;
    border: none;
    cursor: pointer;
    color: #1976d2;
    padding: 0.25rem;
    margin-left: 0.5rem;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.info-icon:hover {
    background-color: rgba(25, 118, 210, 0.1);
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
    padding: 1rem;
}

.mobile-info-content {
    background-color: white;
    border-radius: 8px;
    max-width: 500px;
    width: 100%;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.mobile-info-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #eee;
}

.mobile-info-header h3 {
    margin: 0;
    font-size: 1.2rem;
    color: #333;
}

.mobile-info-header button {
    background: none;
    border: none;
    cursor: pointer;
    color: #666;
}

.mobile-info-body {
    padding: 1rem;
    font-size: 1rem;
    line-height: 1.5;
    color: #333;
}

.mobile-info-body p {
    margin-bottom: 1rem;
}

.mobile-info-body ul {
    margin-left: 1.5rem;
    margin-bottom: 1rem;
}

.validation-errors-container {
    margin-top: 1.5rem;
    margin-bottom: 1rem;
    padding: 1rem;
    background-color: #fff8f8;
    border-left: 4px solid #e53935;
    border-radius: 4px;
}

.validation-error {
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.validation-error:last-child {
    margin-bottom: 0;
}

@media (max-width: 768px) {
    .col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }

    .form-section {
        padding: 1rem;
    }
}
</style>
