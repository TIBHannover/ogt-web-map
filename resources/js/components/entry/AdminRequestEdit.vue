<template>
    <div class="edit-request-form">
        <div v-if="loading" class="text-center p-5">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3">Daten werden geladen...</p>
        </div>

        <div v-else>
            <!-- Ereignisformular (wenn Typ Q6983405) -->
            <EventsForm
                v-if="requestType === 'Q6983405'"
                v-model:formsTitle="formData.title"
                v-model:formsDescription="formData.description"
                :entry-type="requestType"
                :eventTimeType="getEventTimeType()"
                @update:eventTimeType="handleEventTimeTypeChange($event)"
                v-model:eventDate="formData.time_point"
                v-model:eventDateQualifier="formData.time_point_qualifier"
                v-model:eventStartDate="formData.time_period_start"
                v-model:eventStartDateQualifier="
                    formData.time_period_start_qualifier
                "
                v-model:eventEndDate="formData.time_period_end"
                v-model:eventEndDateQualifier="
                    formData.time_period_end_qualifier
                "
                v-model:victimCount="formData.victim_count"
                v-model:survivorCount="formData.survivor_count"
                v-model:deathCount="formData.death_count"
                v-model:arrestCount="formData.arrest_count"
                v-model:victimCountQualifier="formData.victim_count_qualifier"
                v-model:survivorCountQualifier="
                    formData.survivor_count_qualifier
                "
                v-model:deathCountQualifier="formData.death_count_qualifier"
                v-model:arrestCountQualifier="formData.arrest_count_qualifier"
                v-model:selectedVictims="formData.victims"
                v-model:selectedPerpetrators="formData.perpetrators"
                v-model:selectedCauses="formData.selected_causes"
                v-model:imageUrl="formData.image_url"
                v-model:imageFilename="formData.image_filename"
                v-model:selectedSources="formData.sources"
                :peopleSearchResults="peopleSearchResults"
                :perpetratorsSearchResults="perpetratorsSearchResults"
                :causeSearchResults="causeSearchResults"
                :showNewVictimForm="showNewVictimForm"
                :showNewPerpetratorForm="showNewPerpetratorForm"
                :newVictim="newVictim"
                :newPerpetrator="newPerpetrator"
                :isAdminView="true"
                @update:newVictim="newVictim = $event"
                @update:newPerpetrator="newPerpetrator = $event"
                @submit="saveRequest($event)"
                @updateVictim="updateVictim"
                @updatePerpetrator="updatePerpetrator"
                @searchPeople="searchPeople"
                @searchPerpetrators="searchPerpetrators"
                @searchCause="searchCause"
                @searchLocation="searchLocation"
                @selectVictim="selectVictim"
                @removeVictim="removeVictim"
                @startNewVictimForm="startNewVictimForm"
                @cancelNewVictimForm="cancelNewVictimForm"
                @addNewVictim="addNewVictim"
                @selectPerpetrator="selectPerpetrator"
                @removePerpetrator="removePerpetrator"
                @startNewPerpetratorForm="startNewPerpetratorForm"
                @cancelNewPerpetratorForm="cancelNewPerpetratorForm"
                @addNewPerpetrator="addNewPerpetrator"
                @selectCause="selectCause"
                @removeCause="removeCause"
                ref="eventsForm"
            >
                <!-- Custom Admin Coordinate Editor slot -->
                <template #location-selector>
                    <AdminCoordinateEditor
                        :coordinates="formData.coordinates"
                        :coordinates-qualifier="formData.coordinates_qualifier"
                        @update:coordinates="updateCoordinates"
                        @update:coordinates-qualifier="
                            formData.coordinates_qualifier = $event
                        "
                    />
                </template>
            </EventsForm>
            <!-- Weitere Formulartypen ku00f6nnen hier hinzugefu00fcgt werden -->
            <div v-else class="alert alert-warning">
                Formular fu00fcr den Typ "{{ requestType }}" ist noch nicht
                implementiert.
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a
                    :href="`/admin/requests/${requestId}`"
                    class="btn btn-secondary"
                >
                    <i class="bi bi-x-circle me-1"></i> Abbrechen
                </a>
                <button
                    @click="debugFormFields"
                    class="btn btn-warning me-2"
                    type="button"
                >
                    <i class="bi bi-bug me-1"></i> Debug Felder
                </button>
                <button @click="getEventFormData" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Änderungen speichern
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import EventsForm from "./EventsForm.vue";
import AdminCoordinateEditor from "./fields/AdminCoordinateEditor.vue";
import axios from "axios";

export default {
    components: {
        EventsForm,
        AdminCoordinateEditor,
    },
    props: {
        initialData: {
            type: Object,
            required: true,
        },
        requestType: {
            type: String,
            required: true,
        },
        requestId: {
            type: [String, Number],
            required: true,
        },
    },
    data() {
        return {
            loading: true,
            formData: {},
            peopleSearchResults: [],
            perpetratorsSearchResults: [],
            causeSearchResults: [],
            locationSearchResults: [],
            showNewVictimForm: false,
            showNewPerpetratorForm: false,
            newVictim: {
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
            },
            newPerpetrator: {
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
            },
        };
    },
    computed: {
        // Prepare props for EventsForm component
        eventFormProps() {
            // This computed property is now less important as we're using v-model directives directly
            // We keep it for backward compatibility and debugging
            if (this.requestType === "Q6983405") {
                // Make sure victims and perpetrators are arrays
                let victims = [];
                if (this.formData.victims) {
                    if (Array.isArray(this.formData.victims)) {
                        victims = this.formData.victims;
                    } else if (typeof this.formData.victims === "string") {
                        try {
                            victims = JSON.parse(this.formData.victims) || [];
                        } catch (e) {
                            console.error(
                                "Error parsing victims in computed prop:",
                                e,
                            );
                            victims = [];
                        }
                    }
                }

                let perpetrators = [];
                if (this.formData.perpetrators) {
                    if (Array.isArray(this.formData.perpetrators)) {
                        perpetrators = this.formData.perpetrators;
                    } else if (typeof this.formData.perpetrators === "string") {
                        try {
                            perpetrators =
                                JSON.parse(this.formData.perpetrators) || [];
                        } catch (e) {
                            console.error(
                                "Error parsing perpetrators in computed prop:",
                                e,
                            );
                            perpetrators = [];
                        }
                    }
                }

                // Handle selected causes
                let selectedCauses = [];
                if (this.formData.selected_causes) {
                    if (Array.isArray(this.formData.selected_causes)) {
                        selectedCauses = this.formData.selected_causes;
                    } else if (
                        typeof this.formData.selected_causes === "string"
                    ) {
                        try {
                            selectedCauses =
                                JSON.parse(this.formData.selected_causes) || [];
                        } catch (e) {
                            console.error(
                                "Error parsing selected_causes in computed prop:",
                                e,
                            );
                            selectedCauses = [];
                        }
                    }
                }

                // For debugging purposes - log the computed props
                console.log("EventFormProps computed values:", {
                    title: this.formData.title,
                    description: this.formData.description,
                    victims: victims.length,
                    perpetrators: perpetrators.length,
                    selectedCauses: selectedCauses.length,
                    time_point: this.formData.time_point,
                    time_period_start: this.formData.time_period_start,
                });

                return {
                    formsTitle: this.formData.title || "",
                    formsDescription: this.formData.description || "",
                    isAdminView: true,
                    selectedVictims: victims,
                    selectedPerpetrators: perpetrators,
                    eventTimeType: this.formData.time_point ? "point" : "range",
                    eventDate: this.formData.time_point || null,
                    eventDateQualifier:
                        this.formData.time_point_qualifier || "",
                    eventStartDate: this.formData.time_period_start || null,
                    eventStartDateQualifier:
                        this.formData.time_period_start_qualifier || "",
                    eventEndDate: this.formData.time_period_end || null,
                    eventEndDateQualifier:
                        this.formData.time_period_end_qualifier || "",
                    victimCount: this.formData.victim_count || 0,
                    survivorCount: this.formData.survivor_count || 0,
                    deathCount: this.formData.death_count || 0,
                    arrestCount: this.formData.arrest_count || 0,
                    victimCountQualifier:
                        this.formData.victim_count_qualifier || "",
                    survivorCountQualifier:
                        this.formData.survivor_count_qualifier || "",
                    deathCountQualifier:
                        this.formData.death_count_qualifier || "",
                    arrestCountQualifier:
                        this.formData.arrest_count_qualifier || "",
                    selectedCauses: selectedCauses,
                    selectedLocation: this.getLocationFromCoordinates(),
                    imageUrl: this.formData.image_url || null,
                    imageFilename: this.formData.image_filename || null,
                    selectedSources: (() => {
                        // Make sure sources is an array
                        if (!this.formData.sources) return [];

                        if (Array.isArray(this.formData.sources)) {
                            return this.formData.sources;
                        } else if (typeof this.formData.sources === "string") {
                            try {
                                return JSON.parse(this.formData.sources) || [];
                            } catch (e) {
                                console.error(
                                    "Error parsing sources in computed prop:",
                                    e,
                                );
                                return [];
                            }
                        }
                        return [];
                    })(),
                };
            }

            return {};
        },
    },
    mounted() {
        this.initializeData();
    },
    methods: {
        initializeData() {
            this.loading = true;
            // Use the passed initialData directly
            this.formData = { ...this.initialData };

            // Handle arrays that might be strings
            if (
                this.formData.victims &&
                typeof this.formData.victims === "string"
            ) {
                try {
                    this.formData.victims =
                        JSON.parse(this.formData.victims) || [];
                    console.log("Parsed victims from string to array");
                } catch (e) {
                    console.error("Failed to parse victims as JSON:", e);
                    this.formData.victims = [];
                }
            } else if (!this.formData.victims) {
                this.formData.victims = [];
            }

            if (
                this.formData.perpetrators &&
                typeof this.formData.perpetrators === "string"
            ) {
                try {
                    this.formData.perpetrators =
                        JSON.parse(this.formData.perpetrators) || [];
                    console.log("Parsed perpetrators from string to array");
                } catch (e) {
                    console.error("Failed to parse perpetrators as JSON:", e);
                    this.formData.perpetrators = [];
                }
            } else if (!this.formData.perpetrators) {
                this.formData.perpetrators = [];
            }

            if (
                this.formData.selected_causes &&
                typeof this.formData.selected_causes === "string"
            ) {
                try {
                    this.formData.selected_causes =
                        JSON.parse(this.formData.selected_causes) || [];
                    console.log("Parsed selected_causes from string to array");
                } catch (e) {
                    console.error(
                        "Failed to parse selected_causes as JSON:",
                        e,
                    );
                    this.formData.selected_causes = [];
                }
            } else if (!this.formData.selected_causes) {
                this.formData.selected_causes = [];
            }

            if (
                this.formData.sources &&
                typeof this.formData.sources === "string"
            ) {
                try {
                    this.formData.sources =
                        JSON.parse(this.formData.sources) || [];
                    console.log("Parsed sources from string to array");
                } catch (e) {
                    console.error("Failed to parse sources as JSON:", e);
                    this.formData.sources = [];
                }
            } else if (!this.formData.sources) {
                this.formData.sources = [];
            }

            // Parse coordinates if they're stored as a string
            if (
                this.formData.coordinates &&
                typeof this.formData.coordinates === "string"
            ) {
                try {
                    // First, try to parse as JSON object directly
                    if (this.formData.coordinates.trim().startsWith("{")) {
                        this.formData.coordinates = JSON.parse(
                            this.formData.coordinates,
                        );
                        console.log(
                            "Parsed coordinates from JSON string to object",
                        );
                    } else if (
                        this.formData.coordinates.trim().startsWith("[")
                    ) {
                        // Try parsing as a JSON array
                        const coordArray = JSON.parse(
                            this.formData.coordinates,
                        );
                        if (
                            Array.isArray(coordArray) &&
                            coordArray.length >= 2
                        ) {
                            this.formData.coordinates = {
                                lat: coordArray[0],
                                lng: coordArray[1],
                            };
                            console.log(
                                "Parsed coordinates from array to object",
                            );
                        }
                    } else {
                        // Try parsing as comma-separated string
                        const parts = this.formData.coordinates.split(",");
                        if (parts.length >= 2) {
                            this.formData.coordinates = {
                                lat: parseFloat(parts[0].trim()),
                                lng: parseFloat(parts[1].trim()),
                            };
                            console.log(
                                "Parsed coordinates from comma-separated string",
                            );
                        }
                    }
                } catch (e) {
                    console.error(
                        "Failed to parse coordinates:",
                        e,
                        this.formData.coordinates,
                    );
                    this.formData.coordinates = null;
                }
            }

            // Ensure the time type is correctly set
            if (
                this.formData.time_point &&
                this.formData.time_point.trim() !== ""
            ) {
                this.formData.eventTimeType = "point";
            } else if (
                this.formData.time_period_start &&
                this.formData.time_period_start.trim() !== ""
            ) {
                this.formData.eventTimeType = "range";
            } else {
                this.formData.eventTimeType = "point"; // Default to point
            }

            // Convert numeric strings to numbers
            [
                "victim_count",
                "survivor_count",
                "death_count",
                "arrest_count",
            ].forEach((field) => {
                if (
                    this.formData[field] &&
                    typeof this.formData[field] === "string"
                ) {
                    this.formData[field] = Number(this.formData[field]) || 0;
                }
            });

            // Check for missing fields in victims and perpetrators
            if (this.formData.victims && this.formData.victims.length > 0) {
                console.log("First victim data:", this.formData.victims[0]);
                console.log("First victim keys:", Object.keys(this.formData.victims[0]));
                console.log("First victim has birthName:", this.formData.victims[0].hasOwnProperty('birthName'));
                console.log("First victim has imageFilename:", this.formData.victims[0].hasOwnProperty('imageFilename'));
                console.log("First victim has employer:", this.formData.victims[0].hasOwnProperty('employer'));
                
                // Add missing fields to victims if needed
                this.formData.victims.forEach(victim => {
                    // Make sure role is set to 'victim'
                    victim.role = 'victim';
                    if (!victim.hasOwnProperty('birthName')) {
                        victim.birthName = '';
                        console.log("Added missing birthName field to victim");
                    }
                    
                    if (!victim.hasOwnProperty('imageUrl')) {
                        victim.imageUrl = null;
                        console.log("Added missing imageUrl field to victim");
                    }
                    
                    if (!victim.hasOwnProperty('imageFilename')) {
                        victim.imageFilename = null;
                        console.log("Added missing imageFilename field to victim");
                    }
                    
                    if (!victim.hasOwnProperty('employer')) {
                        victim.employer = null;
                        console.log("Added missing employer field to victim");
                    }
                });
            }
            
            if (this.formData.perpetrators && this.formData.perpetrators.length > 0) {
                console.log("First perpetrator data:", this.formData.perpetrators[0]);
                console.log("First perpetrator keys:", Object.keys(this.formData.perpetrators[0]));
                
                // Add missing fields to perpetrators if needed
                this.formData.perpetrators.forEach(perpetrator => {
                    if (!perpetrator.hasOwnProperty('birthName')) {
                        perpetrator.birthName = '';
                        console.log("Added missing birthName field to perpetrator");
                    }
                    
                    if (!perpetrator.hasOwnProperty('imageUrl')) {
                        perpetrator.imageUrl = null;
                        console.log("Added missing imageUrl field to perpetrator");
                    }
                    
                    if (!perpetrator.hasOwnProperty('imageFilename')) {
                        perpetrator.imageFilename = null;
                        console.log("Added missing imageFilename field to perpetrator");
                    }
                    
                    if (!perpetrator.hasOwnProperty('employer')) {
                        perpetrator.employer = null;
                        console.log("Added missing employer field to perpetrator");
                    }
                    
                    // Make sure role is set to 'perpetrator'
                    perpetrator.role = 'perpetrator';
                });
            }

            console.log("Initialized data:", {
                victims: this.formData.victims,
                perpetrators: this.formData.perpetrators,
                selected_causes: this.formData.selected_causes,
                coordinates: this.formData.coordinates,
                time_point: this.formData.time_point,
                time_period_start: this.formData.time_period_start,
                eventTimeType: this.formData.eventTimeType,
            });

            this.loading = false;
        },

        // Update a victim in the victims array
        updateVictim(updatedPerson) {
            if (!this.formData.victims) {
                this.formData.victims = [];
            }

            // Find the index of the person to update
            const personIndex = this.formData.victims.findIndex(
                (p) =>
                    (p.id && p.id === updatedPerson.id) ||
                    (p.tempId && p.tempId === updatedPerson.id),
            );

            // If found, update the person
            if (personIndex !== -1) {
                // Vue 3 doesn't have Vue.set, so use array splice which is reactive
                this.formData.victims.splice(personIndex, 1, updatedPerson);
            }
        },

        // For debugging form fields
        debugFormFields() {
            console.log("=== FORM DATA DEBUGGING ===");
            console.log("Current formData state:", {
                title: this.formData.title,
                description: this.formData.description,
                victims: this.formData.victims,
                perpetrators: this.formData.perpetrators,
                selected_causes: this.formData.selected_causes,
                coordinates: this.formData.coordinates,
                coordinates_qualifier: this.formData.coordinates_qualifier,
                time_point: this.formData.time_point,
                time_period_start: this.formData.time_period_start,
                victim_count: this.formData.victim_count,
                survivor_count: this.formData.survivor_count,
                death_count: this.formData.death_count,
                arrest_count: this.formData.arrest_count,
                sources: this.formData.sources,
            });

            // Get current form values from EventsForm component if available
            if (
                this.$refs.eventsForm &&
                typeof this.$refs.eventsForm.getData === "function"
            ) {
                const formData = this.$refs.eventsForm.getData();
                console.log("EventsForm component data:", formData);

                // Compare values between formData and EventsForm
                console.log("=== Data Comparison ===");
                console.log(
                    "Title matches:",
                    this.formData.title === formData.title,
                );
                console.log(
                    "Description matches:",
                    this.formData.description === formData.description,
                );
                console.log(
                    "Victim count matches:",
                    this.formData.victim_count === formData.victim_count,
                );
                console.log(
                    "Perpetrator count (arrays):",
                    this.formData.perpetrators
                        ? this.formData.perpetrators.length
                        : 0,
                    "vs",
                    formData.perpetrators ? formData.perpetrators.length : 0,
                );

                alert(
                    "Debug information logged to console. Please check your browser developer tools.",
                );
            } else {
                console.log("Cannot access EventsForm component methods.");
                alert(
                    "Debug information for formData logged to console. EventsForm component is not accessible.",
                );
            }
        },

        // Update a perpetrator in the perpetrators array
        updatePerpetrator(updatedPerson) {
            if (!this.formData.perpetrators) {
                this.formData.perpetrators = [];
            }

            // Find the index of the person to update
            const personIndex = this.formData.perpetrators.findIndex(
                (p) =>
                    (p.id && p.id === updatedPerson.id) ||
                    (p.tempId && p.tempId === updatedPerson.id),
            );

            // If found, update the person
            if (personIndex !== -1) {
                // Vue 3 doesn't have Vue.set, so use array splice which is reactive
                this.formData.perpetrators.splice(
                    personIndex,
                    1,
                    updatedPerson,
                );
            }
        },

        getLocationFromCoordinates() {
            // Parse coordinates and return a location object
            console.log(
                "getLocationFromCoordinates called, raw coordinates:",
                this.formData.coordinates,
            );

            if (!this.formData.coordinates || this.formData.coordinates === "")
                return null;

            try {
                // Try to parse as JSON object
                if (typeof this.formData.coordinates === "string") {
                    if (this.formData.coordinates.startsWith("{")) {
                        const coords = JSON.parse(this.formData.coordinates);
                        console.log(
                            "Parsed coordinates from JSON object string",
                        );
                        return {
                            lat: coords.lat || coords.latitude || 0,
                            lng: coords.lng || coords.longitude || 0,
                        };
                    } else if (this.formData.coordinates.startsWith("[")) {
                        const coords = JSON.parse(this.formData.coordinates);
                        console.log(
                            "Parsed coordinates from JSON array string",
                        );
                        if (Array.isArray(coords) && coords.length >= 2) {
                            return {
                                lat: coords[0],
                                lng: coords[1],
                            };
                        }
                    } else {
                        // Try comma-separated format
                        const parts = this.formData.coordinates.split(",");
                        if (parts.length >= 2) {
                            const lat = parseFloat(parts[0].trim());
                            const lng = parseFloat(parts[1].trim());
                            console.log(
                                "Parsed coordinates from comma-separated string:",
                                lat,
                                lng,
                            );
                            return {
                                lat: lat,
                                lng: lng,
                            };
                        }
                    }
                } else if (typeof this.formData.coordinates === "object") {
                    // Already an object
                    console.log("Using coordinates directly as object");
                    return {
                        lat:
                            this.formData.coordinates.lat ||
                            this.formData.coordinates.latitude ||
                            0,
                        lng:
                            this.formData.coordinates.lng ||
                            this.formData.coordinates.longitude ||
                            0,
                    };
                }

                console.warn(
                    "Could not parse coordinates format:",
                    this.formData.coordinates,
                );
            } catch (e) {
                console.error(
                    "Error parsing coordinates:",
                    e,
                    this.formData.coordinates,
                );
            }

            return null;
        },

        // Helper method to get event time type
        getEventTimeType() {
            return this.formData.time_point ? "point" : "range";
        },

        // Handle event time type changes
        handleEventTimeTypeChange(type) {
            if (type === "point") {
                // Set time_point if it's empty but there's a time_period_start
                if (
                    !this.formData.time_point &&
                    this.formData.time_period_start
                ) {
                    this.formData.time_point = this.formData.time_period_start;
                    this.formData.time_point_qualifier =
                        this.formData.time_period_start_qualifier || "";
                } else if (!this.formData.time_point) {
                    // Set a default date if no date is set
                    this.formData.time_point = new Date()
                        .toISOString()
                        .split("T")[0];
                }

                // Clear period fields
                this.formData.time_period_start = "";
                this.formData.time_period_start_qualifier = "";
                this.formData.time_period_end = "";
                this.formData.time_period_end_qualifier = "";
            } else {
                // type === 'range'
                // Set time_period_start if it's empty but there's a time_point
                if (
                    !this.formData.time_period_start &&
                    this.formData.time_point
                ) {
                    this.formData.time_period_start = this.formData.time_point;
                    this.formData.time_period_start_qualifier =
                        this.formData.time_point_qualifier || "";
                } else if (!this.formData.time_period_start) {
                    // Set default start date if no date is set
                    this.formData.time_period_start = new Date()
                        .toISOString()
                        .split("T")[0];
                }

                // Clear point fields
                this.formData.time_point = "";
                this.formData.time_point_qualifier = "";
            }

            console.log(
                "Event time type changed to:",
                type,
                "Current time values:",
                {
                    time_point: this.formData.time_point,
                    time_period_start: this.formData.time_period_start,
                    time_period_end: this.formData.time_period_end,
                },
            );
        },

        // Helper method to get location object
        getLocationObject() {
            if (!this.formData.coordinates) return null;

            try {
                if (typeof this.formData.coordinates === "string") {
                    return JSON.parse(this.formData.coordinates);
                } else {
                    return this.formData.coordinates;
                }
            } catch (e) {
                console.error("Failed to parse coordinates", e);
                return null;
            }
        },

        updateCoordinates(coordinates) {
            console.log("updateCoordinates called with:", coordinates);

            // Handle different input formats consistently
            if (!coordinates) {
                this.formData.coordinates = null;
                return;
            }

            // Always store as string in format "lat, lng"
            if (typeof coordinates === "object") {
                const lat = parseFloat(coordinates.lat || 0);
                const lng = parseFloat(coordinates.lng || 0);
                this.formData.coordinates = `${lat}, ${lng}`;
            } else if (typeof coordinates === "string") {
                // Already a string, just standardize formatting
                const parts = coordinates.split(",");
                if (parts.length >= 2) {
                    const lat = parseFloat(parts[0].trim());
                    const lng = parseFloat(parts[1].trim());
                    if (!isNaN(lat) && !isNaN(lng)) {
                        this.formData.coordinates = `${lat}, ${lng}`;
                    } else {
                        this.formData.coordinates = coordinates.trim();
                    }
                } else {
                    this.formData.coordinates = coordinates.trim();
                }
            }

            console.log(
                "Stored coordinates in formData:",
                this.formData.coordinates,
            );
        },

        // Save the updated request
        saveRequest(formData) {
            // If no valid form data is provided, we can't proceed
            if (!formData) {
                console.error("No valid request data available");
                alert(
                    "Ein Fehler ist aufgetreten: Keine gu00fcltigen Daten verfu00fcgbar.",
                );
                return;
            }

            // Log the form data before processing
            console.log("Form data received for saving:", formData);
            console.log("Coordinates before processing:", formData.coordinates);

            // Create a new object instead of using spreading or JSON parsing
            const requestData = {};

            // Copy all primitive properties
            Object.keys(formData).forEach((key) => {
                if (
                    typeof formData[key] !== "object" ||
                    formData[key] === null
                ) {
                    requestData[key] = formData[key];
                }
            });

            // Explicitly handle coordinates in their original object format
            if (formData.coordinates) {
                // Keep the object format if it's already an object
                if (
                    typeof formData.coordinates === "object" &&
                    formData.coordinates.lat &&
                    formData.coordinates.lng
                ) {
                    // Just copy the object directly
                    requestData.coordinates = {
                        lat: formData.coordinates.lat,
                        lng: formData.coordinates.lng,
                    };
                } else if (typeof formData.coordinates === "string") {
                    // Try to parse string into object format
                    const parts = formData.coordinates.split(",");
                    if (parts.length >= 2) {
                        requestData.coordinates = {
                            lat: parseFloat(parts[0].trim()),
                            lng: parseFloat(parts[1].trim()),
                        };
                    } else {
                        // If we can't parse, just keep the string
                        requestData.coordinates = formData.coordinates.trim();
                    }
                }
            }

            console.log(
                "Coordinates after formatting as object:",
                requestData.coordinates,
            );

            // Ensure all array fields are properly stringified
            ["victims", "perpetrators", "selected_causes", "sources"].forEach(
                (field) => {
                    if (formData[field]) {
                        if (typeof formData[field] === "string") {
                            requestData[field] = formData[field];
                        } else {
                            requestData[field] = JSON.stringify(
                                formData[field],
                            );
                        }
                    } else {
                        requestData[field] = "[]";
                    }
                },
            );

            // Final check on coordinates
            console.log(
                "Final coordinates being sent to API:",
                requestData.coordinates,
            );
            console.log("Full request data being sent:", requestData);

            // Try storing coordinates directly in a custom header for debugging
            const headers = {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
                "X-Debug-Coordinates": JSON.stringify(
                    requestData.coordinates || {},
                ),
            };

            axios
                .post(`/admin/requests/${this.requestId}/update`, requestData, {
                    headers: headers,
                })
                .then((response) => {
                    console.log("Update successful:", response.data);
                    // Force a page reload to ensure we get fresh data
                    window.location.href = `/admin/requests/${this.requestId}?success=1&t=${new Date().getTime()}`;
                })
                .catch((error) => {
                    console.error("Error updating request:", error);

                    // Log more details about the error
                    if (error.response) {
                        console.error(
                            "Error response data:",
                            error.response.data,
                        );
                        console.error(
                            "Error response status:",
                            error.response.status,
                        );
                        console.error(
                            "Error response headers:",
                            error.response.headers,
                        );
                    } else if (error.request) {
                        console.error("Error request:", error.request);
                    } else {
                        console.error("Error message:", error.message);
                    }

                    let errorMessage =
                        "Ein Fehler ist beim Speichern aufgetreten. Bitte versuchen Sie es erneut.";

                    // Extract more detailed error message if available
                    if (error.response && error.response.data) {
                        if (error.response.data.error) {
                            errorMessage +=
                                "\n\nDetails: " + error.response.data.error;
                        } else if (error.response.data.message) {
                            errorMessage +=
                                "\n\nDetails: " + error.response.data.message;
                        } else if (typeof error.response.data === "string") {
                            errorMessage +=
                                "\n\nDetails: " + error.response.data;
                        } else {
                            errorMessage +=
                                "\n\nStatus-Code: " + error.response.status;
                        }
                    }

                    alert(errorMessage);
                });
        },

        // Get data from EventsForm and submit it
        getEventFormData() {
            if (this.requestType === "Q6983405" && this.$refs.eventsForm) {
                try {
                    // First check if the form is valid
                    if (
                        typeof this.$refs.eventsForm.isFormValid === "function"
                    ) {
                        const isValid = this.$refs.eventsForm.isFormValid();
                        console.log("Form validation result:", isValid);

                        if (!isValid) {
                            alert(
                                "Bitte fu00fcllen Sie alle Pflichtfelder aus bevor Sie speichern.",
                            );
                            return;
                        }
                    }

                    // Get form data directly from the component
                    let formData;
                    if (typeof this.$refs.eventsForm.getData === "function") {
                        formData = this.$refs.eventsForm.getData();

                        // Directly set coordinates from parent component data
                        formData.coordinates = this.formData.coordinates || "";

                        // Make sure coordinates_qualifier is included
                        formData.coordinates_qualifier =
                            this.formData.coordinates_qualifier || "";
                    } else {
                        // Get form data directly from the component
                        formData = {
                            title: this.formData.title,
                            description: this.formData.description,
                            victims: JSON.stringify(
                                this.formData.victims || [],
                            ),
                            perpetrators: JSON.stringify(
                                this.formData.perpetrators || [],
                            ),
                            selected_causes: JSON.stringify(
                                this.formData.selected_causes || [],
                            ),
                            sources: JSON.stringify(
                                this.formData.sources || [],
                            ),

                            // Time information
                            time_point: this.formData.time_point || "",
                            time_point_qualifier:
                                this.formData.time_point_qualifier || "",
                            time_period_start:
                                this.formData.time_period_start || "",
                            time_period_start_qualifier:
                                this.formData.time_period_start_qualifier || "",
                            time_period_end:
                                this.formData.time_period_end || "",
                            time_period_end_qualifier:
                                this.formData.time_period_end_qualifier || "",

                            // Counts
                            victim_count:
                                Number(this.formData.victim_count) || 0,
                            survivor_count:
                                Number(this.formData.survivor_count) || 0,
                            death_count: Number(this.formData.death_count) || 0,
                            arrest_count:
                                Number(this.formData.arrest_count) || 0,

                            // Qualifiers
                            victim_count_qualifier:
                                this.formData.victim_count_qualifier || "",
                            survivor_count_qualifier:
                                this.formData.survivor_count_qualifier || "",
                            death_count_qualifier:
                                this.formData.death_count_qualifier || "",
                            arrest_count_qualifier:
                                this.formData.arrest_count_qualifier || "",

                            // Image
                            image_url: this.formData.image_url || "",
                            image_filename: this.formData.image_filename || "",
                        };

                        // Handle coordinates - ensure they're in the right format
                        formData.coordinates = this.formData.coordinates || "";
                        console.log(
                            "Set coordinates in fallback data:",
                            formData.coordinates,
                        );

                        // Add coordinates qualifier
                        formData.coordinates_qualifier =
                            this.formData.coordinates_qualifier || "";

                        console.log(
                            "Using fallback data from formData:",
                            formData,
                        );
                    }

                    // Check required fields
                    if (!formData.title) {
                        alert("Bitte geben Sie einen Titel ein.");
                        return;
                    }

                    if (
                        !formData.perpetrators ||
                        formData.perpetrators === "[]"
                    ) {
                        alert("Bitte geben Sie mindestens einen Tu00e4ter an.");
                        return;
                    }

                    if (
                        (!formData.time_point || formData.time_point === "") &&
                        (!formData.time_period_start ||
                            formData.time_period_start === "")
                    ) {
                        alert(
                            "Bitte geben Sie einen Zeitpunkt oder Zeitraum an.",
                        );
                        return;
                    }

                    if (!formData.sources || formData.sources === "[]") {
                        alert("Bitte geben Sie mindestens eine Quelle an.");
                        return;
                    }

                    // Submit the form data
                    this.saveRequest(formData);
                } catch (e) {
                    console.error("Error preparing form data:", e);
                    alert("Ein Fehler ist aufgetreten: " + e.message);
                }
                return;
            }

            alert("Dieser Formulartyp wird noch nicht unterstu00fctzt.");
        },

        // Search people functionality
        searchPeople(query) {
            if (!query || query.length < 2) return;

            axios
                .get(
                    `/api/wikidata/search/people?query=${encodeURIComponent(query)}`,
                )
                .then((response) => {
                    this.peopleSearchResults = response.data;
                })
                .catch((error) => {
                    console.error("Error searching people:", error);
                });
        },

        // Search perpetrators functionality
        searchPerpetrators(query) {
            if (!query || query.length < 2) return;

            axios
                .get(
                    `/api/wikidata/search/people?query=${encodeURIComponent(query)}`,
                )
                .then((response) => {
                    this.perpetratorsSearchResults = response.data;
                })
                .catch((error) => {
                    console.error("Error searching perpetrators:", error);
                });
        },

        // Search causes functionality
        searchCause(query) {
            if (!query || query.length < 2) return;

            axios
                .get(
                    `/api/wikidata/search/causes?query=${encodeURIComponent(query)}`,
                )
                .then((response) => {
                    this.causeSearchResults = response.data;
                })
                .catch((error) => {
                    console.error("Error searching causes:", error);
                });
        },

        // Search locations functionality
        searchLocation(query) {
            if (!query || query.length < 2) return;

            axios
                .get(
                    `/api/wikidata/search/places?query=${encodeURIComponent(query)}`,
                )
                .then((response) => {
                    this.locationSearchResults = response.data;
                })
                .catch((error) => {
                    console.error("Error searching locations:", error);
                });
        },

        // Select a victim
        selectVictim(person) {
            if (!this.formData.victims) {
                this.formData.victims = [];
            }

            // Check if person already exists
            if (!this.formData.victims.some((p) => p.id === person.id)) {
                this.formData.victims.push(person);
            }
        },

        // Remove a victim
        removeVictim(id) {
            if (!this.formData.victims) return;

            const index = this.formData.victims.findIndex(
                (p) => (p.id && p.id === id) || (p.tempId && p.tempId === id),
            );

            if (index !== -1) {
                this.formData.victims.splice(index, 1);
            }
        },

        // Start adding a new victim
        startNewVictimForm() {
            // Implementation depends on your component structure
            this.showNewVictimForm = true;
        },

        // Cancel adding a new victim
        cancelNewVictimForm() {
            // Implementation depends on your component structure
            this.showNewVictimForm = false;
        },

        // Add a new victim
        addNewVictim(person) {
            if (!this.formData.victims) {
                this.formData.victims = [];
            }

            // Use the passed person or the form data
            const newVictim = person || this.newVictim || {};

            // Add a temporary ID if none exists
            if (!newVictim.id && !newVictim.tempId) {
                newVictim.tempId = "temp_" + new Date().getTime();
            }

            this.formData.victims.push(newVictim);
            this.showNewVictimForm = false;
        },

        // Select a perpetrator
        selectPerpetrator(person) {
            if (!this.formData.perpetrators) {
                this.formData.perpetrators = [];
            }

            // Check if person already exists
            if (!this.formData.perpetrators.some((p) => p.id === person.id)) {
                this.formData.perpetrators.push(person);
            }
        },

        // Remove a perpetrator
        removePerpetrator(id) {
            if (!this.formData.perpetrators) return;

            const index = this.formData.perpetrators.findIndex(
                (p) => (p.id && p.id === id) || (p.tempId && p.tempId === id),
            );

            if (index !== -1) {
                this.formData.perpetrators.splice(index, 1);
            }
        },

        // Start adding a new perpetrator
        startNewPerpetratorForm() {
            // Implementation depends on your component structure
            this.showNewPerpetratorForm = true;
        },

        // Cancel adding a new perpetrator
        cancelNewPerpetratorForm() {
            // Implementation depends on your component structure
            this.showNewPerpetratorForm = false;
        },

        // Add a new perpetrator
        addNewPerpetrator(person) {
            if (!this.formData.perpetrators) {
                this.formData.perpetrators = [];
            }

            // Use the passed person or the form data
            const newPerpetrator = person || this.newPerpetrator || {};

            // Add a temporary ID if none exists
            if (!newPerpetrator.id && !newPerpetrator.tempId) {
                newPerpetrator.tempId = "temp_" + new Date().getTime();
            }

            this.formData.perpetrators.push(newPerpetrator);
            this.showNewPerpetratorForm = false;
        },

        // Select a cause
        selectCause(cause) {
            if (!cause || !cause.id) {
                console.warn("Attempted to select invalid cause:", cause);
                return;
            }

            // Initialize if needed
            if (!this.formData.selected_causes) {
                this.formData.selected_causes = [];
            }

            // Ensure it's an array
            let causes;
            try {
                if (typeof this.formData.selected_causes === "string") {
                    causes = JSON.parse(this.formData.selected_causes);
                } else if (Array.isArray(this.formData.selected_causes)) {
                    causes = [...this.formData.selected_causes];
                } else {
                    console.warn(
                        "selected_causes is not in expected format:",
                        this.formData.selected_causes,
                    );
                    causes = [];
                }
            } catch (e) {
                console.error(
                    "Error parsing selected_causes in selectCause:",
                    e,
                );
                causes = [];
            }

            // Check if cause already exists
            if (!causes.some((c) => c.id === cause.id)) {
                causes.push(cause);
                this.formData.selected_causes = [...causes]; // Create a new array reference for reactivity
                console.log(
                    "Added cause:",
                    cause,
                    "Current causes:",
                    this.formData.selected_causes,
                );
            } else {
                console.log("Cause already exists:", cause.id);
            }
        },

        // Remove a cause
        removeCause(id) {
            console.log("Removing cause with ID:", id);

            if (!this.formData.selected_causes) {
                this.formData.selected_causes = [];
                return;
            }

            // Ensure it's an array
            let causes;
            try {
                if (typeof this.formData.selected_causes === "string") {
                    causes = JSON.parse(this.formData.selected_causes);
                } else if (Array.isArray(this.formData.selected_causes)) {
                    causes = [...this.formData.selected_causes];
                } else {
                    console.warn(
                        "selected_causes is not in expected format:",
                        this.formData.selected_causes,
                    );
                    causes = [];
                }
            } catch (e) {
                console.error(
                    "Error parsing selected_causes in removeCause:",
                    e,
                );
                causes = [];
            }

            console.log("Current causes:", causes);

            const index = causes.findIndex((c) => c.id === id);
            console.log("Found at index:", index);

            if (index !== -1) {
                causes.splice(index, 1);
                this.formData.selected_causes = [...causes]; // Create a new array reference for reactivity
                console.log("Updated causes:", this.formData.selected_causes);
            }
        },
    },
};
</script>

<!-- Global form styles -->
<style>
@import "../entry/styles/form-styles.css";
</style>
