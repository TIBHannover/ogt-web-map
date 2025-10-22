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

                    <!-- Validation errors -->
                    <div v-if="showPersonValidationErrors">
                        <!-- First name validation error -->
                        <ValidationError
                            v-if="firstNameError"
                            message="Vorname ist ein Pflichtfeld."
                            showIcon
                            customClass="mt-2"
                        />

                        <!-- Last name validation error -->
                        <ValidationError
                            v-if="lastNameError"
                            :message="
                                personData.value &&
                                personData.value.isOrganization
                                    ? 'Name der Organisation ist ein Pflichtfeld.'
                                    : 'Nachname ist ein Pflichtfeld.'
                            "
                            showIcon
                            customClass="mt-2"
                        />

                        <!-- Description validation error -->
                        <ValidationError
                            v-if="descriptionError"
                            message="Kurzbeschreibung ist ein Pflichtfeld."
                            showIcon
                            customClass="mt-2"
                        />

                        <!-- Source validation error -->
                        <ValidationError
                            v-if="sourceError"
                            message="Mindestens eine Quelle ist erforderlich."
                            showIcon
                            customClass="mt-2"
                        />
                    </div>
                </div>

                <SourcesField
                    ref="sourcesFieldRef"
                    :selectedSources="selectedSourcesValue"
                    @update:selectedSources="handleSourcesUpdate"
                    @fieldFocus="setActiveField"
                    @infoButtonClick="openSectionInfo"
                    @validation="onSourcesValidation"
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
    </div>
</template>

<script setup>
import { ref, computed, onMounted, inject, watch } from "vue";
import { useRouter } from "vue-router";
import SourcesField from "./fields/SourcesField.vue";
import PersonFormFields from "./fields/partials/PersonFormFields.vue";
import ValidationError from "../helpers/ValidationError.vue";
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
        default: "Q5",
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
    selectedSources: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits([
    "goToStep",
    "handleMapClick",
    "submit",
    "update:title",
    "update:description",
    "update:selectedSources",
]);

// Form state
const title = ref(props.title || "");
const description = ref(props.description || "");
const isSubmitting = ref(false);
const activeSection = ref("title");
const baseInfosRef = ref(null);
const sourcesRef = ref(null);
const showPersonValidationErrors = ref(false);
const firstNameError = ref(false);
const lastNameError = ref(false);
const sourceError = ref(false);
const descriptionError = ref(false);
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
    const firstNameValid =
        personData.value.isOrganization ||
        (personData.value.firstName &&
            personData.value.firstName.trim().length > 0);
    firstNameError.value = !firstNameValid && !personData.value.isOrganization;

    // Last name validation
    const lastNameValid =
        personData.value.lastName &&
        personData.value.lastName.trim().length > 0;
    lastNameError.value = !lastNameValid;

    // Description validation - check both the personData.description and the component's description prop
    const descriptionInPersonData =
        personData.value.description &&
        personData.value.description.trim().length > 0;
    const descriptionInProps =
        description.value && description.value.trim().length > 0;
    const descriptionValid = descriptionInPersonData || descriptionInProps;

    descriptionError.value = !descriptionValid;
};

watch(
    () => personData.value?.firstName,
    () => {
        if (showPersonValidationErrors.value) {
            updateValidationErrors();
        }
    },
);

watch(
    () => personData.value?.description,
    () => {
        if (showPersonValidationErrors.value) {
            updateValidationErrors();
        }
    },
);

watch(
    () => personData.value?.lastName,
    () => {
        if (showPersonValidationErrors.value) {
            updateValidationErrors();
        }
    },
);

watch(
    () => personData.value?.isOrganization,
    () => {
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
    baseInfosValid.value = valid;
    // Reset validation errors when field becomes valid
    if (valid && isPersonDetailsValid.value) {
        showPersonValidationErrors.value = false;
    }
};

const handleSourcesUpdate = (newSources) => {
    selectedSourcesValue.value = newSources;
    showPersonValidationErrors.value = false;
    sourceError.value = newSources.length === 0;
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
    let isValid = true;

    let formIsValid = true;

    try {
        const isBaseInfoValid = await validateField(baseInfosRef);
        if (!isBaseInfoValid) {
            formIsValid = false;
        }

        // Update our local ref safely
        baseInfosValid.value = !!isBaseInfoValid;

        // Validate sources
        const areSourcesValid = await validateField(sourcesRef);
        if (
            !areSourcesValid ||
            !props.selectedSources ||
            props.selectedSources.length === 0
        ) {
            formIsValid = false;
        }

        // Update our local ref safely
        sourcesValid.value = !!areSourcesValid;
    } catch (error) {
        console.error("Error validating form:", error);
        formIsValid = false;
    }

    return formIsValid;
};

defineExpose({
    validateForm,
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

// Form validation and submission handler
const validateAndSubmit = async () => {
    try {
        // Ensure personData is valid to prevent undefined errors
        if (!personData.value) {
            personData.value = {
                firstName: "",
                lastName: "",
                description: "",
                isOrganization: false,
                // Add other default fields
            };
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

        // Collect missing fields for error message
        const missingFields = [];

        if (!title.value || !title.value.trim()) {
            missingFields.push("Titel");
        }

        // Check for description in either the form description or personData description
        const hasDescription =
            (description?.value && description.value.trim()) ||
            (personData?.value?.description &&
                personData.value.description.trim());

        // Update description error state
        descriptionError.value = !hasDescription;

        if (!hasDescription) {
            missingFields.push("Kurzbeschreibung");
        }

        // Check if sources are missing
        const sourceMissing = !props?.selectedSources?.length;
        sourceError.value = sourceMissing;

        if (sourceMissing) {
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
        // Prepare form data for submission
        const formData = {
            title: title.value ? title.value.trim() : "",
            description:
                (description.value ? description.value.trim() : "") ||
                (personData.value.description
                    ? personData.value.description.trim()
                    : ""),
            type: props.entryType || "Q5", // Q5 for human
            sources: props.selectedSources || [],
            person: {
                firstName: personData.value.firstName || "",
                lastName: personData.value.lastName || "",
                birthDate: personData.value.birthDate,
                deathDate: personData.value.deathDate,
                birthName: personData.value.birthName || "",
                description:
                    personData.value.description || description.value || "",
                isOrganization: !!personData.value.isOrganization,
                gender: personData.value.gender,
                nationality: personData.value.nationality,
                place_of_birth: personData.value.place_of_birth,
                place_of_death: personData.value.place_of_death,
                place_of_detention: personData.value.place_of_detention || [],
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
        // Also initialize personData.description if needed
        if (personData.value && !personData.value.description) {
            personData.value.description = props.description;
        }
    }

    // Initialize from any temp data
    initializeFromTitle();

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
watch(title, (newValue) => {
    if (newValue !== props.title) {
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
        if (newValue !== title.value) {
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
