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
                        >
                            <v-icon>mdi-information-variant</v-icon>
                        </button>
                    </div>

                    <PersonFormFields
                        :person-data="personData"
                        :wikidata-search-results="wikidataSearchResults"
                        :is-wikidata-search-loading="isWikidataSearchLoading"
                        :wikidata-search-performed="wikidataSearchPerformed"
                        :role-label="''"
                        :hide-is-organisation="false"
                        :hide-place-of-detention="false"
                        @search-wikidata-items="searchWikidataItems"
                    />
                </div>

                <SourcesField
                    ref="sourcesRef"
                    v-model="sources"
                    @validation="onSourcesValidation"
                    @infoButtonClick="openSectionInfo"
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
    </div>
</template>

<script setup>
import { ref, computed, onMounted, inject, watch } from "vue";
import { useRouter } from "vue-router";
import SourcesField from "./fields/SourcesField.vue";
import PersonFormFields from "./fields/partials/PersonFormFields.vue";
import axios from "axios";

const router = useRouter();
const ogtGlobals = inject("ogtGlobals");

// Initialize form on mount
onMounted(() => {
    initializeFromTitle();
});

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
        default: "Q5", // Q5 is "human" in Wikidata
    },
    isAdminView: {
        type: Boolean,
        default: false,
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
});

const emit = defineEmits([
    "goToStep",
    "handleMapClick",
    "submit",
    "update:title",
    "update:description",
]);

// Form state
const title = ref(props.title || "");
const description = ref(props.description || "");
const sources = ref([]);
const isSubmitting = ref(false);
const activeSection = ref("title");
const baseInfosRef = ref(null);
const sourcesRef = ref(null);
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
    birthDate: null,
    deathDate: null,
    description: "",
    birthName: "",
    isOrganization: false,
    gender: null,
    nationality: null,
    place_of_birth: null,
    place_of_death: null,
    place_of_detention: [],
    place_of_residence: null,
    occupation: null,
    imageUrl: null,
    imageFilename: null,
    role: "standalone", // Used to identify this as a standalone person, not a victim/perpetrator
});

// Wikidata search states
const wikidataSearchResults = ref({
    nationality: [],
    place_of_birth: [],
    place_of_death: [],
    place_of_detention: [],
    employer: [],
    occupation: [],
    place_of_residence: [],
});

const isWikidataSearchLoading = ref({
    nationality: false,
    place_of_birth: false,
    place_of_death: false,
    place_of_detention: false,
    employer: false,
    occupation: false,
    place_of_residence: false,
});

const wikidataSearchPerformed = ref({
    nationality: false,
    place_of_birth: false,
    place_of_death: false,
    place_of_detention: false,
    employer: false,
    occupation: false,
    place_of_residence: false,
});

const wikidataResultsValue = computed(() => {
    return [];
});

// Methods
const setActiveField = (field) => {
    // Use for handling field focus
};

const setActiveSection = (section) => {
    activeSection.value = section;
};

const openSectionInfo = (section) => {
    // Handle info button clicks for sections
    console.log("Open info for section:", section);
};

const onBaseInfoValidation = (valid) => {
    isValid.value.baseInfos = valid;
};

const onSourcesValidation = (valid) => {
    isValid.value.sources = valid;
};

// This is called by the parent component when form validation is needed
const validateForm = async () => {
    let isValid = true;

    // Validate base info (title, description)
    const baseInfoValid = await validateField(baseInfosRef);
    if (!baseInfoValid) {
        isValid = false;
    }

    // Validate sources
    const sourcesValid = await validateField(sourcesRef);
    if (!sourcesValid) {
        isValid = false;
    }

    return isValid;
};

// Expose the validateForm method to the parent component
defineExpose({ validateForm });

// Helper function to validate a field with a ref
const validateField = async (fieldRef) => {
    if (fieldRef.value && typeof fieldRef.value.validate === "function") {
        return await fieldRef.value.validate();
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
            if (tempData.firstName !== undefined) {
                personData.value.firstName = tempData.firstName || "";
            }
            if (tempData.lastName !== undefined) {
                personData.value.lastName = tempData.lastName || "";
            }
            return; // Skip the title parsing if we used temp data
        } catch (e) {
            console.error("Failed to parse temporary person data:", e);
        }
    }

    // Fall back to parsing from title if no temp data
    if (props.title) {
        const { firstName, lastName } = parseNameFromTitle(props.title);
        personData.value.firstName = firstName;
        personData.value.lastName = lastName;
        personData.value.description = props.description || "";
    }
};

// Watch for changes to title or tempPersonData
watch([() => props.title, () => props.tempPersonData], () => {
    initializeFromTitle();
});

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
    emit("goToStep", 1);
};

const validateAndSubmit = async () => {
    // Trigger validation in child components
    if (
        baseInfosRef.value &&
        typeof baseInfosRef.value.validateForm === "function"
    ) {
        baseInfosRef.value.validateForm();
    }

    if (
        sourcesRef.value &&
        typeof sourcesRef.value.validateForm === "function"
    ) {
        sourcesRef.value.validateForm();
    }

    // Check if all form sections are valid
    const missingFields = [];

    if (!title.value.trim()) {
        missingFields.push("Titel");
    }

    if (!description.value.trim()) {
        missingFields.push("Kurzbeschreibung");
    }

    if (!sources.value || sources.value.length === 0) {
        missingFields.push("Quelle");
    }

    if (!personData.value.firstName && !personData.value.isOrganization) {
        missingFields.push("Vorname");
    }

    if (!personData.value.lastName) {
        missingFields.push(
            personData.value.isOrganization
                ? "Name der Organisation"
                : "Nachname",
        );
    }

    if (missingFields.length) {
        showToast(
            `Fehlende Pflichtfelder: ${missingFields.join(", ")}`,
            "error",
        );
        return;
    }

    isSubmitting.value = true;

    try {
        // Prepare form data for submission
        const formData = {
            title: title.value.trim(),
            description: description.value.trim(),
            type: props.entryType, // Q5 for human
            sources: sources.value,
            person: {
                firstName: personData.value.firstName,
                lastName: personData.value.lastName,
                birthDate: personData.value.birthDate,
                deathDate: personData.value.deathDate,
                birthName: personData.value.birthName,
                description: personData.value.description || description.value,
                isOrganization: personData.value.isOrganization,
                gender: personData.value.gender,
                nationality: personData.value.nationality,
                place_of_birth: personData.value.place_of_birth,
                place_of_death: personData.value.place_of_death,
                place_of_detention: personData.value.place_of_detention,
                place_of_residence: personData.value.place_of_residence,
                occupation: personData.value.occupation,
                role: "standalone", // Mark as standalone person
                wikidataId: personData.value.wikidataId, // If we selected an existing person
            },
        };

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

        console.log("Submitting person data:", formData);

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");

        const response = await axios.post("/api/wikidata/entities", formData, {
            headers: {
                "X-CSRF-TOKEN": csrfToken,
            },
        });

        if (response.data.success) {
            // Handle successful submission
            const requestId = secureToken || response.data.requestId;
            localStorage.setItem("entryStatusToken", requestId);

            // Signal completion to parent
            emit("submit");

            // Redirect to thank-you page
            router.push({
                name: "thank-you",
                params: { requestId },
                query: { token: requestId },
            });
        } else {
            showToast(
                response.data.message ||
                    "Fehler bei der Erstellung des Eintrags",
                "error",
            );
        }
    } catch (error) {
        console.error("Error submitting form:", error);
        showToast(
            `Fehler: ${error.response?.data?.message || error.message}`,
            "error",
        );
    } finally {
        isSubmitting.value = false;
    }
};

// Lifecycle hooks
onMounted(() => {
    // Initialize component on mount
    if (props.title) {
        title.value = props.title;
    }
    if (props.description) {
        description.value = props.description;
    }
});

// Watchers for title and description to emit updates
if (title.value !== props.title) {
    emit("update:title", title.value);
}

if (description.value !== props.description) {
    emit("update:description", description.value);
}
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
</style>
