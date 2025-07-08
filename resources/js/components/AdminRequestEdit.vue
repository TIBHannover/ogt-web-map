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
                @submit="saveRequest"
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
                <button @click="saveRequest" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> u00c4nderungen speichern
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
                    } else if (typeof this.formData.victims === 'string') {
                        try {
                            victims = JSON.parse(this.formData.victims) || [];
                        } catch (e) {
                            console.error('Error parsing victims in computed prop:', e);
                            victims = [];
                        }
                    }
                }
                
                let perpetrators = [];
                if (this.formData.perpetrators) {
                    if (Array.isArray(this.formData.perpetrators)) {
                        perpetrators = this.formData.perpetrators;
                    } else if (typeof this.formData.perpetrators === 'string') {
                        try {
                            perpetrators = JSON.parse(this.formData.perpetrators) || [];
                        } catch (e) {
                            console.error('Error parsing perpetrators in computed prop:', e);
                            perpetrators = [];
                        }
                    }
                }
                
                // Handle selected causes
                let selectedCauses = [];
                if (this.formData.selected_causes) {
                    if (Array.isArray(this.formData.selected_causes)) {
                        selectedCauses = this.formData.selected_causes;
                    } else if (typeof this.formData.selected_causes === 'string') {
                        try {
                            selectedCauses = JSON.parse(this.formData.selected_causes) || [];
                        } catch (e) {
                            console.error('Error parsing selected_causes in computed prop:', e);
                            selectedCauses = [];
                        }
                    }
                }
                
                return {
                    formsTitle: this.formData.title || "",
                    formsDescription: this.formData.description || "",
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
                        } else if (typeof this.formData.sources === 'string') {
                            try {
                                return JSON.parse(this.formData.sources) || [];
                            } catch (e) {
                                console.error('Error parsing sources in computed prop:', e);
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
            if (this.formData.victims && typeof this.formData.victims === 'string') {
                try {
                    this.formData.victims = JSON.parse(this.formData.victims) || [];
                    console.log('Parsed victims from string to array');
                } catch (e) {
                    console.error('Failed to parse victims as JSON:', e);
                    this.formData.victims = [];
                }
            } else if (!this.formData.victims) {
                this.formData.victims = [];
            }
            
            if (this.formData.perpetrators && typeof this.formData.perpetrators === 'string') {
                try {
                    this.formData.perpetrators = JSON.parse(this.formData.perpetrators) || [];
                    console.log('Parsed perpetrators from string to array');
                } catch (e) {
                    console.error('Failed to parse perpetrators as JSON:', e);
                    this.formData.perpetrators = [];
                }
            } else if (!this.formData.perpetrators) {
                this.formData.perpetrators = [];
            }

            if (this.formData.selected_causes && typeof this.formData.selected_causes === 'string') {
                try {
                    this.formData.selected_causes = JSON.parse(this.formData.selected_causes) || [];
                    console.log('Parsed selected_causes from string to array');
                } catch (e) {
                    console.error('Failed to parse selected_causes as JSON:', e);
                    this.formData.selected_causes = [];
                }
            } else if (!this.formData.selected_causes) {
                this.formData.selected_causes = [];
            }
            
            if (this.formData.sources && typeof this.formData.sources === 'string') {
                try {
                    this.formData.sources = JSON.parse(this.formData.sources) || [];
                    console.log('Parsed sources from string to array');
                } catch (e) {
                    console.error('Failed to parse sources as JSON:', e);
                    this.formData.sources = [];
                }
            } else if (!this.formData.sources) {
                this.formData.sources = [];
            }

            console.log('Initialized data:', {
                victims: this.formData.victims,
                perpetrators: this.formData.perpetrators,
                selected_causes: this.formData.selected_causes
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
            if (!this.formData.coordinates || this.formData.coordinates === '') return null;

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
                } else if (typeof this.formData.coordinates === 'object') {
                    // Already an object
                    return {
                        lat: this.formData.coordinates.lat || this.formData.coordinates.latitude || 0,
                        lng: this.formData.coordinates.lng || this.formData.coordinates.longitude || 0,
                    };
                }
                
                console.warn("Could not parse coordinates format:", this.formData.coordinates);
            } catch (e) {
                console.error("Error parsing coordinates:", e, this.formData.coordinates);
            }

            return null;
        },

        // Save the updated request
        saveRequest() {
            // Prepare data based on form type
            let formData = new FormData();

            if (this.requestType === "Q6983405") {
                // Gather data from event form
                formData.append("title", this.eventFormProps.formsTitle || "");
                formData.append(
                    "description",
                    this.eventFormProps.formsDescription || "",
                );
                
                // Handle victims data - make sure it's valid JSON
                try {
                    formData.append(
                        "victims",
                        JSON.stringify(this.eventFormProps.selectedVictims || []),
                    );
                } catch (e) {
                    console.error("Error stringifying victims:", e);
                    formData.append("victims", JSON.stringify([]));
                }
                
                // Handle perpetrators data - make sure it's valid JSON
                try {
                    formData.append(
                        "perpetrators",
                        JSON.stringify(this.eventFormProps.selectedPerpetrators || []),
                    );
                } catch (e) {
                    console.error("Error stringifying perpetrators:", e);
                    formData.append("perpetrators", JSON.stringify([]));
                }

                // Time information
                if (this.eventFormProps.eventTimeType === "point") {
                    formData.append(
                        "time_point",
                        this.eventFormProps.eventDate || "",
                    );
                    formData.append(
                        "time_point_qualifier",
                        this.eventFormProps.eventDateQualifier || "",
                    );
                    // Clear period fields
                    formData.append("time_period_start", "");
                    formData.append("time_period_end", "");
                    formData.append("time_period_start_qualifier", "");
                    formData.append("time_period_end_qualifier", "");
                } else {
                    formData.append(
                        "time_period_start",
                        this.eventFormProps.eventStartDate || "",
                    );
                    formData.append(
                        "time_period_start_qualifier",
                        this.eventFormProps.eventStartDateQualifier || "",
                    );
                    formData.append(
                        "time_period_end",
                        this.eventFormProps.eventEndDate || "",
                    );
                    formData.append(
                        "time_period_end_qualifier",
                        this.eventFormProps.eventEndDateQualifier || "",
                    );
                    // Clear point field
                    formData.append("time_point", "");
                    formData.append("time_point_qualifier", "");
                }

                // Counts - ensure they are numbers or empty strings
                formData.append(
                    "victim_count",
                    this.eventFormProps.victimCount || 0,
                );
                formData.append(
                    "victim_count_qualifier",
                    this.eventFormProps.victimCountQualifier || "",
                );
                formData.append(
                    "survivor_count",
                    this.eventFormProps.survivorCount || 0,
                );
                formData.append(
                    "survivor_count_qualifier",
                    this.eventFormProps.survivorCountQualifier || "",
                );
                formData.append("death_count", this.eventFormProps.deathCount || 0);
                formData.append(
                    "death_count_qualifier",
                    this.eventFormProps.deathCountQualifier || "",
                );
                formData.append(
                    "arrest_count",
                    this.eventFormProps.arrestCount || 0,
                );
                formData.append(
                    "arrest_count_qualifier",
                    this.eventFormProps.arrestCountQualifier || "",
                );

                // Causes - ensure it's valid JSON
                try {
                    const causesData = Array.isArray(this.eventFormProps.selectedCauses) 
                        ? this.eventFormProps.selectedCauses 
                        : (typeof this.eventFormProps.selectedCauses === 'string' 
                            ? JSON.parse(this.eventFormProps.selectedCauses) 
                            : []);
                            
                    formData.append(
                        "selected_causes",
                        JSON.stringify(causesData),
                    );
                } catch (e) {
                    console.error("Error handling causes data:", e);
                    formData.append("selected_causes", JSON.stringify([]));
                }

                // Location
                if (this.eventFormProps.selectedLocation) {
                    try {
                        // Make sure the location object has lat and lng properties
                        const locationObj = {
                            lat: parseFloat(this.eventFormProps.selectedLocation.lat || this.eventFormProps.selectedLocation.latitude || 0),
                            lng: parseFloat(this.eventFormProps.selectedLocation.lng || this.eventFormProps.selectedLocation.longitude || 0)
                        };
                        
                        // Only append if we have valid coordinates
                        if (!isNaN(locationObj.lat) && !isNaN(locationObj.lng)) {
                            formData.append("coordinates", JSON.stringify(locationObj));
                            console.log("Sending coordinates:", locationObj);
                        } else {
                            console.warn("Invalid location coordinates:", this.eventFormProps.selectedLocation);
                            formData.append("coordinates", "");
                        }
                    } catch (e) {
                        console.error("Error processing coordinates:", e, this.eventFormProps.selectedLocation);
                        formData.append("coordinates", "");
                    }
                } else {
                    formData.append("coordinates", "");
                }

                // Image
                formData.append(
                    "image_url",
                    this.eventFormProps.imageUrl || "",
                );
                formData.append(
                    "image_filename",
                    this.eventFormProps.imageFilename || "",
                );

                // Sources - ensure it's valid JSON
                try {
                    formData.append(
                        "sources",
                        JSON.stringify(this.eventFormProps.selectedSources || []),
                    );
                } catch (e) {
                    console.error("Error stringifying sources:", e);
                    formData.append("sources", JSON.stringify([]));
                }
            }

            // Log form data for debugging
            console.log('Form data being sent:', {
                title: this.eventFormProps.formsTitle,
                description: this.eventFormProps.formsDescription,
                victims: this.eventFormProps.selectedVictims,
                perpetrators: this.eventFormProps.selectedPerpetrators,
                time_type: this.eventFormProps.eventTimeType,
                time_point: this.eventFormProps.eventTimeType === 'point' ? this.eventFormProps.eventDate : null,
                time_period_start: this.eventFormProps.eventTimeType === 'range' ? this.eventFormProps.eventStartDate : null,
                time_period_end: this.eventFormProps.eventTimeType === 'range' ? this.eventFormProps.eventEndDate : null,
                selected_causes: this.eventFormProps.selectedCauses,
                location: this.eventFormProps.selectedLocation,
                sources: this.eventFormProps.selectedSources
            });
            
            // Send the update request
            axios
                .post(`/admin/requests/${this.requestId}/update`, formData)
                .then((response) => {
                    console.log('Update successful:', response.data);
                    // Redirect to the detail view
                    window.location.href = `/admin/requests/${this.requestId}?success=1`;
                })
                .catch((error) => {
                    console.error("Error updating request:", error);
                    
                    // Log more details about the error
                    if (error.response) {
                        console.error('Error response data:', error.response.data);
                        console.error('Error response status:', error.response.status);
                        console.error('Error response headers:', error.response.headers);
                    } else if (error.request) {
                        console.error('Error request:', error.request);
                    } else {
                        console.error('Error message:', error.message);
                    }
                    
                    let errorMessage = "Ein Fehler ist beim Speichern aufgetreten. Bitte versuchen Sie es erneut.";
                    
                    // Extract more detailed error message if available
                    if (error.response && error.response.data) {
                        if (error.response.data.error) {
                            errorMessage += "\n\nDetails: " + error.response.data.error;
                        } else if (error.response.data.message) {
                            errorMessage += "\n\nDetails: " + error.response.data.message;
                        } else if (typeof error.response.data === 'string') {
                            errorMessage += "\n\nDetails: " + error.response.data;
                        } else {
                            errorMessage += "\n\nStatus-Code: " + error.response.status;
                        }
                    }
                    
                    alert(errorMessage);
                });
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
                console.warn('Attempted to select invalid cause:', cause);
                return;
            }
            
            // Initialize if needed
            if (!this.formData.selected_causes) {
                this.formData.selected_causes = [];
            }

            // Ensure it's an array
            let causes;
            try {
                if (typeof this.formData.selected_causes === 'string') {
                    causes = JSON.parse(this.formData.selected_causes);
                } else if (Array.isArray(this.formData.selected_causes)) {
                    causes = [...this.formData.selected_causes];
                } else {
                    console.warn('selected_causes is not in expected format:', this.formData.selected_causes);
                    causes = [];
                }
            } catch (e) {
                console.error("Error parsing selected_causes in selectCause:", e);
                causes = [];
            }

            // Check if cause already exists
            if (!causes.some((c) => c.id === cause.id)) {
                causes.push(cause);
                this.formData.selected_causes = [...causes]; // Create a new array reference for reactivity
                console.log('Added cause:', cause, 'Current causes:', this.formData.selected_causes);
            } else {
                console.log('Cause already exists:', cause.id);
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
                if (typeof this.formData.selected_causes === 'string') {
                    causes = JSON.parse(this.formData.selected_causes);
                } else if (Array.isArray(this.formData.selected_causes)) {
                    causes = [...this.formData.selected_causes];
                } else {
                    console.warn('selected_causes is not in expected format:', this.formData.selected_causes);
                    causes = [];
                }
            } catch (e) {
                console.error("Error parsing selected_causes in removeCause:", e);
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
