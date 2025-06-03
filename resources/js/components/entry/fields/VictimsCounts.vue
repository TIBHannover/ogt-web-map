<template>
    <div class="counts-section container-section">
        <div class="section-header">
            <label class="section-label">Geschädigte-Zahlen:</label>
            <button
                type="button"
                class="info-icon"
                @click="$emit('infoButtonClick', 'counts')"
            >
                <v-icon>mdi-information-variant</v-icon>
            </button>
        </div>
        <v-row class="align-end">
            <v-col cols="12" md="3">
                <div class="form-group">
                    <label for="victim-count">Anzahl der Geschädigten</label>
                    <input
                        type="number"
                        id="victim-count"
                        :value="victimCount"
                        @input="
                            $emit(
                                'update:victimCount',
                                Number($event.target.value),
                            )
                        "
                        min="0"
                        @focus="$emit('fieldFocus', 'victim-count')"
                    />
                    <WikidataQualifier
                        id="victim-count-qualifier"
                        :model-value="victimCountQualifier"
                        @update:model-value="
                            $emit('update:victimCountQualifier', $event)
                        "
                    />
                </div>
            </v-col>
            <v-col cols="12" md="3">
                <div class="form-group">
                    <label for="survivor-count">Anzahl der Überlebenden</label>
                    <input
                        type="number"
                        id="survivor-count"
                        :value="survivorCount"
                        @input="
                            $emit(
                                'update:survivorCount',
                                Number($event.target.value),
                            )
                        "
                        min="0"
                        @focus="$emit('fieldFocus', 'victim-count')"
                    />
                    <WikidataQualifier
                        id="survivor-count-qualifier"
                        :model-value="survivorCountQualifier"
                        @update:model-value="
                            $emit('update:survivorCountQualifier', $event)
                        "
                    />
                </div>
            </v-col>
            <v-col cols="12" md="3">
                <div class="form-group">
                    <label for="death-count">Anzahl der Todesfälle</label>
                    <input
                        type="number"
                        id="death-count"
                        :value="deathCount"
                        @input="
                            $emit(
                                'update:deathCount',
                                Number($event.target.value),
                            )
                        "
                        min="0"
                        @focus="$emit('fieldFocus', 'victim-count')"
                    />
                    <WikidataQualifier
                        id="death-count-qualifier"
                        :model-value="deathCountQualifier"
                        @update:model-value="
                            $emit('update:deathCountQualifier', $event)
                        "
                    />
                </div>
            </v-col>
            <v-col cols="12" md="3">
                <div class="form-group">
                    <label for="arrest-count">Anzahl der Festnahmen</label>
                    <input
                        type="number"
                        id="arrest-count"
                        :value="arrestCount"
                        @input="
                            $emit(
                                'update:arrestCount',
                                Number($event.target.value),
                            )
                        "
                        min="0"
                        @focus="$emit('fieldFocus', 'victim-count')"
                    />
                    <WikidataQualifier
                        id="arrest-count-qualifier"
                        :model-value="arrestCountQualifier"
                        @update:model-value="
                            $emit('update:arrestCountQualifier', $event)
                        "
                    />
                </div>
            </v-col>
        </v-row>
    </div>
</template>

<script setup>
import WikidataQualifier from "./common/WikidataQualifier.vue";

defineProps({
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
});

defineEmits([
    "update:victimCount",
    "update:survivorCount",
    "update:deathCount",
    "update:arrestCount",
    "update:victimCountQualifier",
    "update:survivorCountQualifier",
    "update:deathCountQualifier",
    "update:arrestCountQualifier",
    "fieldFocus",
    "infoButtonClick",
]);
</script>

<style scoped>
.container-section {
    width: 100%;
    margin-bottom: 1.5rem;
    background-color: #f5f5f5;
    padding: 1rem;
    border-radius: 4px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.section-label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.info-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background-color: #1976d2;
    color: white;
    border: none;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.info-icon:hover {
    background-color: #1565c0;
}

.form-group {
    margin-bottom: 0.75rem;
    width: 100%;
}

.form-group label {
    display: block;
    margin-bottom: 0.25rem;
}

.form-group input {
    padding: 0.75rem 1rem;
    width: 100%;
    background-color: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}

.form-group input:focus {
    outline: none;
    border-color: #1976d2;
    box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.25);
}
</style>
