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
                v-bind="eventFormProps"
                :peopleSearchResults="peopleSearchResults"
                :perpetratorsSearchResults="perpetratorsSearchResults"
                :causeSearchResults="causeSearchResults"
                :showNewVictimForm="showNewVictimForm"
                :showNewPerpetratorForm="showNewPerpetratorForm"
                :newVictim="newVictim"
                :newPerpetrator="newPerpetrator"
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
            />
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
                <button @click="getEventFormData" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Änderungen speichern
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import EventsForm from "./entry/EventsForm.vue";
import axios from "axios";

export default {
    components: {
        EventsForm,
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
            // For events (Q6983405)
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

            console.log("Initialized data:", {
                victims: this.formData.victims,
                perpetrators: this.formData.perpetrators,
                selected_causes: this.formData.selected_causes,
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
            if (!this.formData.coordinates || this.formData.coordinates === "")
                return null;

            try {
                // Try to parse as JSON object
                if (typeof this.formData.coordinates === "string") {
                    if (this.formData.coordinates.startsWith("{")) {
                        const coords = JSON.parse(this.formData.coordinates);
                        return {
                            lat: coords.lat || coords.latitude || 0,
                            lng: coords.lng || coords.longitude || 0,
                        };
                    } else if (this.formData.coordinates.startsWith("[")) {
                        const coords = JSON.parse(this.formData.coordinates);
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
                            return {
                                lat: parseFloat(parts[0].trim()),
                                lng: parseFloat(parts[1].trim()),
                            };
                        }
                    }
                } else if (typeof this.formData.coordinates === "object") {
                    // Already an object
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

        // Save the updated request
        saveRequest(formData) {
            // If formData is provided directly (from EventsForm's getData method), use it
            // Otherwise fall back to using data from eventFormProps
            let requestData;
            
            if (formData) {
                // Use the data provided directly from the EventsForm component
                console.log('Using data from EventsForm.getData():', formData);
                // The data from getData is already properly formatted for the database
                requestData = formData;
                
                // Ensure all array fields are properly stringified
                ['victims', 'perpetrators', 'selected_causes', 'sources'].forEach(field => {
                    if (requestData[field] && typeof requestData[field] !== 'string') {
                        requestData[field] = JSON.stringify(requestData[field]);
                    } else if (requestData[field] && typeof requestData[field] === 'string') {
                        // Make sure it's valid JSON by parsing and re-stringifying
                        try {
                            const parsed = JSON.parse(requestData[field]);
                            requestData[field] = JSON.stringify(parsed);
                        } catch (e) {
                            console.warn(`Field ${field} contains invalid JSON, setting to empty array`);
                            requestData[field] = '[]';
                        }
                    } else {
                        requestData[field] = '[]';
                    }
                });
                
                // Ensure all number fields are actual numbers
                ['victim_count', 'survivor_count', 'death_count', 'arrest_count'].forEach(field => {
                    requestData[field] = Number(requestData[field]) || 0;
                    if (isNaN(requestData[field])) {
                        requestData[field] = 0;
                    }
                });
                
                // Log victim counts after processing to help with debugging
                console.log('Processed victim counts in saveRequest:', {
                    victim_count: requestData.victim_count,
                    survivor_count: requestData.survivor_count,
                    death_count: requestData.death_count,
                    arrest_count: requestData.arrest_count,
                    victim_count_qualifier: requestData.victim_count_qualifier,
                    survivor_count_qualifier: requestData.survivor_count_qualifier,
                    death_count_qualifier: requestData.death_count_qualifier,
                    arrest_count_qualifier: requestData.arrest_count_qualifier
                });
                
                // Ensure coordinates is properly formatted
                if (requestData.coordinates && typeof requestData.coordinates !== 'string') {
                    requestData.coordinates = JSON.stringify(requestData.coordinates);
                } else if (!requestData.coordinates) {
                    requestData.coordinates = '';
                }
                
            } else if (this.requestType === "Q6983405") {
                // Fallback to using data from eventFormProps
                console.warn('No form data provided, using eventFormProps as fallback');
                // Create a data object with the correct field names for the database
                requestData = {
                    title: this.eventFormProps.formsTitle || "",
                    description: this.eventFormProps.formsDescription || "",
                    victims: JSON.stringify(this.eventFormProps.selectedVictims || []),
                    perpetrators: JSON.stringify(this.eventFormProps.selectedPerpetrators || []),
                    selected_causes: JSON.stringify(this.eventFormProps.selectedCauses || []),
                    sources: JSON.stringify(this.eventFormProps.selectedSources || []),
                    image_url: this.eventFormProps.imageUrl || '',
                    image_filename: this.eventFormProps.imageFilename || '',
                    // Prepare victim count values
                    victim_count: Number(this.eventFormProps.victimCount) || 0,
                    survivor_count: Number(this.eventFormProps.survivorCount) || 0,
                    death_count: Number(this.eventFormProps.deathCount) || 0,
                    arrest_count: Number(this.eventFormProps.arrestCount) || 0,
                    victim_count_qualifier: this.eventFormProps.victimCountQualifier || '',
                    survivor_count_qualifier: this.eventFormProps.survivorCountQualifier || '',
                    death_count_qualifier: this.eventFormProps.deathCountQualifier || '',
                    arrest_count_qualifier: this.eventFormProps.arrestCountQualifier || '',
                    coordinates: this.eventFormProps.selectedLocation ? 
                        JSON.stringify({
                            lat: parseFloat(this.eventFormProps.selectedLocation.lat || 
                                        this.eventFormProps.selectedLocation.latitude || 0),
                            lng: parseFloat(this.eventFormProps.selectedLocation.lng || 
                                        this.eventFormProps.selectedLocation.longitude || 0)
                        }) : ''
                };
                
                // Time information - based on the time type (point or range)
                if (this.eventFormProps.eventTimeType === 'point') {
                    requestData.time_point = this.eventFormProps.eventDate || '';
                    requestData.time_point_qualifier = this.eventFormProps.eventDateQualifier || '';
                    requestData.time_period_start = '';
                    requestData.time_period_end = '';
                    requestData.time_period_start_qualifier = '';
                    requestData.time_period_end_qualifier = '';
                } else {
                    requestData.time_period_start = this.eventFormProps.eventStartDate || '';
                    requestData.time_period_start_qualifier = this.eventFormProps.eventStartDateQualifier || '';
                    requestData.time_period_end = this.eventFormProps.eventEndDate || '';
                    requestData.time_period_end_qualifier = this.eventFormProps.eventEndDateQualifier || '';
                    requestData.time_point = '';
                    requestData.time_point_qualifier = '';
                }
            }
            
            if (!requestData) {
                console.error('No valid request data available');
                alert('Ein Fehler ist aufgetreten: Keine gu00fcltigen Daten verfu00fcgbar.');
                return;
            }
            
            // Final validation of critical fields
            if (!requestData.title) {
                console.error('Missing title in request data');
                alert('Der Titel darf nicht leer sein.');
                return;
            }
            
            if (!requestData.time_point && !requestData.time_period_start) {
                console.error('Missing time information in request data');
                alert('Es muss ein Zeitpunkt oder ein Zeitraum angegeben werden.');
                return;
            }
            
            if (!requestData.perpetrators || requestData.perpetrators === '[]') {
                console.error('Missing perpetrators in request data');
                alert('Es muss mindestens ein Tu00e4ter angegeben werden.');
                return;
            }
            
            if (!requestData.sources || requestData.sources === '[]') {
                console.error('Missing sources in request data');
                alert('Es muss mindestens eine Quelle angegeben werden.');
                return;
            }
            
            console.log('Sending request data:', requestData);
            console.log('Victim counts being sent:', {
                victim_count: requestData.victim_count,
                survivor_count: requestData.survivor_count,
                death_count: requestData.death_count,
                arrest_count: requestData.arrest_count,
                victim_count_qualifier: requestData.victim_count_qualifier,
                survivor_count_qualifier: requestData.survivor_count_qualifier,
                death_count_qualifier: requestData.death_count_qualifier,
                arrest_count_qualifier: requestData.arrest_count_qualifier
            });
            console.log('Description being sent:', requestData.description);
            
            // Use Axios with JSON data
            axios.post(`/admin/requests/${this.requestId}/update`, requestData, {
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
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
            if (this.requestType === 'Q6983405' && this.$refs.eventsForm) {
                try {
                    // First check if the form is valid
                    if (typeof this.$refs.eventsForm.isFormValid === 'function') {
                        const isValid = this.$refs.eventsForm.isFormValid();
                        console.log('Form validation result:', isValid);
                        
                        if (!isValid) {
                            alert('Bitte füllen Sie alle Pflichtfelder aus bevor Sie speichern.');
                            return;
                        }
                    }
                    
                    // Use the EventsForm's getData method to get properly formatted data
                    if (typeof this.$refs.eventsForm.getData === 'function') {
                        const formData = this.$refs.eventsForm.getData();
                        console.log('Got form data from EventsForm.getData():', formData);
                        
                        // Verify we have required data before proceeding
                        if (!formData.title || (!formData.time_point && !formData.time_period_start)) {
                            console.warn('Missing required data in form:', formData);
                            alert('Es fehlen Pflichtfelder. Bitte überprüfen Sie Ihre Eingaben.');
                            return;
                        }
                        
                        this.saveRequest(formData);
                        return;
                    } else {
                        console.warn('EventsForm does not have a getData method');
                    }
                } catch (e) {
                    console.error('Error getting data from EventsForm:', e);
                    alert('Ein Fehler ist aufgetreten: ' + e.message);
                    return;
                }
            }
            
            // If we can't use getData, fall back to the original method
            console.warn('Falling back to original saveRequest method without form data');
            this.saveRequest();
        },


        // Search people functionality
        searchPeople(query) {
            if (!query || query.length < 2) return;

            axios
                .get(
                    `/api/wikidata/search/people?q=${encodeURIComponent(query)}`,
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
                    `/api/wikidata/search/people?q=${encodeURIComponent(query)}`,
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
                    `/api/wikidata/search/causes?q=${encodeURIComponent(query)}`,
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
                    `/api/wikidata/search/places?q=${encodeURIComponent(query)}`,
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
