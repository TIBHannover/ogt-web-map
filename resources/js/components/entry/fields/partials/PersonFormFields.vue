<template>
    <div class="person-form-fields">
        <!-- Organization or Person switch -->
        <div class="form-group" v-if="!hideIsOrganisation">
            <v-checkbox
                v-model="personData.isOrganization"
                label="Dies ist eine Organisation/Institution"
                hide-details
                class="mb-3"
            ></v-checkbox>
        </div>

        <!-- Name fields -->
        <div class="form-group">
            <v-text-field
                v-model="personData.firstName"
                v-if="!personData.isOrganization"
                :label="
                    'Vorname' +
                    (personData.isOrganization ? ' (optional)' : ' *')
                "
                :rules="[personData.isOrganization ? '' : rules.required]"
                :required="!personData.isOrganization"
            ></v-text-field>
        </div>
        <div class="form-group">
            <v-text-field
                v-model="personData.lastName"
                :label="
                    personData.isOrganization
                        ? 'Name der Organisation *'
                        : 'Nachname *'
                "
                :rules="[rules.required]"
                required
            ></v-text-field>
        </div>

        <!-- Description -->
        <div class="form-group">
            <text-area
                v-model="personData.description"
                :label="
                    isAuthorForm
                        ? 'Kurze Beschreibung des Autors*'
                        : 'Kurze Beschreibung*'
                "
                :placeholder="
                    isAuthorForm
                        ? 'Beispiel: deutscher Schriftsteller...'
                        : 'Beispiel: deutscher Widerstandskämpfer... / Opfer des Nationalsozialismus'
                "
                rows="3"
                :rules="[rules.required, rules.maxLength]"
                :required="true"
                counter="250"
            ></text-area>
        </div>

        <!-- Birth name -->
        <div class="form-group" v-if="!personData.isOrganization">
            <text-field
                v-model="personData.birthName"
                label="Geburtsname"
                placeholder="Geburtsname, falls abweichend vom aktuellen Namen"
            ></text-field>
        </div>

        <!-- Employer - only for perpetrators -->
        <div
            class="form-group"
            v-if="
                !personData.isOrganization &&
                (personData.role === 'perpetrator' ||
                    roleLabel.toLowerCase().includes('täter'))
            "
        >
            <wikidata-selector
                v-model="personData.employer"
                label="Arbeitgeber"
                dialog-title="Arbeitgeber auswählen"
                search-label="Organisation suchen..."
                :search-results="wikidataSearchResults.employer || []"
                :is-loading="isWikidataSearchLoading.employer"
                :search-performed="wikidataSearchPerformed.employer"
                endpoint="people"
                @search="searchWikidataItems('employer', $event)"
            ></wikidata-selector>
        </div>

        <!-- Gender -->
        <div class="form-group" v-if="!personData.isOrganization">
            <gender-selector
                v-model="personData.gender"
                label="Geschlecht"
            ></gender-selector>
        </div>

        <!-- Birth/Death dates -->
        <div class="form-group" v-if="!personData.isOrganization">
            <v-text-field
                v-model="personData.birthDate"
                label="Geburtsdatum"
                type="date"
            ></v-text-field>
        </div>
        <div class="form-group" v-if="!personData.isOrganization">
            <v-text-field
                v-model="personData.deathDate"
                label="Sterbedatum"
                type="date"
            ></v-text-field>
        </div>

        <!-- Nationality -->
        <div class="form-group" v-if="!personData.isOrganization">
            <!-- Deutschland, Deutschland (1871-1945) -->
            <wikidata-selector
                v-model="personData.nationality"
                label="Staatsangehörigkeit"
                dialog-title="Staatsangehörigkeit auswählen"
                search-label="Land suchen..."
                :search-results="wikidataSearchResults.nationality || []"
                :is-loading="isWikidataSearchLoading.nationality"
                :search-performed="wikidataSearchPerformed.nationality"
                endpoint="nationalities"
                :suggestions="['Q1206012', 'Q183']"
                @search="searchWikidataItems('nationality', $event)"
            ></wikidata-selector>
        </div>

        <!-- Occupation -->
        <div class="form-group" v-if="!personData.isOrganization">
            <wikidata-selector
                v-model="personData.occupation"
                label="Beruf"
                dialog-title="Beruf auswählen"
                search-label="Beruf suchen..."
                :search-results="wikidataSearchResults.occupation || []"
                :is-loading="isWikidataSearchLoading.occupation"
                :search-performed="wikidataSearchPerformed.occupation"
                endpoint="occupations"
                @search="searchWikidataItems('occupation', $event)"
            ></wikidata-selector>
        </div>

        <!-- Places -->
        <div class="form-group" v-if="!personData.isOrganization">
            <wikidata-selector
                v-model="personData.place_of_birth"
                label="Geburtsort"
                dialog-title="Geburtsort auswählen"
                search-label="Ort suchen..."
                :search-results="wikidataSearchResults.place_of_birth || []"
                :is-loading="isWikidataSearchLoading.place_of_birth"
                :search-performed="wikidataSearchPerformed.place_of_birth"
                endpoint="places"
                @search="searchWikidataItems('place_of_birth', $event)"
            ></wikidata-selector>
        </div>

        <div class="form-group" v-if="!personData.isOrganization">
            <wikidata-selector
                v-model="personData.place_of_death"
                label="Sterbeort"
                dialog-title="Sterbeort auswählen"
                search-label="Ort suchen..."
                :search-results="wikidataSearchResults.place_of_death || []"
                :is-loading="isWikidataSearchLoading.place_of_death"
                :search-performed="wikidataSearchPerformed.place_of_death"
                endpoint="places"
                @search="searchWikidataItems('place_of_death', $event)"
            ></wikidata-selector>
        </div>

        <div
            class="form-group"
            v-if="!personData.isOrganization && !hidePlaceOfDetention"
        >
            <multiple-wikidata-selector
                v-model="personData.place_of_detention"
                label="Haftort(e)"
                dialog-title="Haftorte auswählen"
                search-label="Ort suchen..."
                :search-results="wikidataSearchResults.place_of_detention || []"
                :is-loading="isWikidataSearchLoading.place_of_detention"
                :search-performed="wikidataSearchPerformed.place_of_detention"
                endpoint="places"
                icon="mdi-map-marker-plus"
                @search="searchWikidataItems('place_of_detention', $event)"
            ></multiple-wikidata-selector>
        </div>

        <div class="form-group" v-if="!personData.isOrganization">
            <wikidata-selector
                v-model="personData.place_of_residence"
                label="Wohnort"
                dialog-title="Wohnort auswählen"
                search-label="Ort suchen..."
                :search-results="wikidataSearchResults.place_of_residence || []"
                :is-loading="isWikidataSearchLoading.place_of_residence"
                :search-performed="wikidataSearchPerformed.place_of_residence"
                endpoint="places"
                @search="searchWikidataItems('place_of_residence', $event)"
            ></wikidata-selector>
        </div>

        <!-- Image upload section could be added here -->
    </div>
</template>

<script setup>
import { computed } from "vue";
import GenderSelector from "../GenderSelector.vue";
import WikidataSelector from "../common/WikidataSelector.vue";
import MultipleWikidataSelector from "../common/MultipleWikidataSelector.vue";
import TextArea from "../common/TextArea.vue";
import TextField from "../common/TextField.vue";

const props = defineProps({
    personData: {
        type: Object,
        required: true,
    },
    wikidataSearchResults: {
        type: Object,
        required: true,
    },
    isWikidataSearchLoading: {
        type: Object,
        required: true,
    },
    wikidataSearchPerformed: {
        type: Object,
        required: true,
    },
    roleLabel: {
        type: String,
        default: "",
    },
    hideIsOrganisation: {
        type: Boolean,
        default: false,
    },
    hidePlaceOfDetention: {
        type: Boolean,
        default: false,
    },
    isAuthorForm: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["search-wikidata-items"]);

const rules = {
    required: (value) => !!value || "Dieses Feld ist erforderlich",
    maxLength: (value) => {
        return !value || value.length <= 250 || "Maximal 250 Zeichen erlaubt";
    },
};

// Methods
const searchWikidataItems = (field, query) => {
    emit("search-wikidata-items", { field, query });
};
</script>

<style scoped>
.person-form-fields {
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}
</style>
