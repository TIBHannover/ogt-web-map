<template>
    <div class="main-section container-section" @focusin="$emit('focusin')">
        <div class="section-header">
            <label class="section-label">Basis Informationen:</label>
            <button
                type="button"
                class="info-icon"
                @click="$emit('infoButtonClick', 'title')"
            >
                <v-icon>mdi-information-variant</v-icon>
            </button>
        </div>

        <label for="title" class="">Titel *</label>
        <input
            :value="title"
            @input="$emit('update:title', $event.target.value)"
            name="title"
            type="text"
            maxlength="250"
            placeholder="z.B. Novemberpogrom 1938 in Osnabrück"
            @focus="$emit('fieldFocus', 'title')"
        />

        <!-- Anzeige der Suchergebnisse -->
        <div v-if="wikidataResults.length > 0" class="search-results">
            <p class="search-info">
                Möglicherweise existiert dieser Eintrag bereits:
            </p>
            <div
                v-for="result in wikidataResults"
                :key="result.id"
                class="search-result-item"
                @click="$emit('selectExistingEntity', result)"
            >
                <div class="result-title">{{ result.label }}</div>
                <div class="result-description" v-if="result.description">
                    {{ result.description }}
                </div>
                <div class="result-id">{{ result.id }}</div>
            </div>
        </div>

        <label for="description" class="mt-3 d-block">Kurzbeschreibung *</label>
        <textarea
            :value="description"
            @input="$emit('update:description', $event.target.value)"
            name="description"
            maxlength="750"
            placeholder="z.B. vom NS-Regime gelenkte Gewaltmaßnahmen gegen Juden in Osnabrück."
            @focus="$emit('fieldFocus', 'title')"
        ></textarea>
    </div>
</template>

<script setup>
defineProps({
    stepTitle: {
        type: String,
        default: "Schritt 2: Ereignis-Details eingeben",
    },
    title: {
        type: String,
        default: "",
    },
    description: {
        type: String,
        default: "",
    },
    wikidataResults: {
        type: Array,
        default: () => [],
    },
});

defineEmits([
    "update:title",
    "update:description",
    "fieldFocus",
    "focusin",
    "infoButtonClick",
    "selectExistingEntity",
]);
</script>

<style scoped>
.step-container {
    background-color: rgba(255, 255, 255, 0.9);
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.step-title {
    margin-bottom: 20px;
    font-size: 1.5rem;
    color: #333;
}

.main-section {
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

input,
textarea {
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

input:focus,
textarea:focus {
    outline: none;
    border-color: #1976d2;
    box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.25);
}

textarea {
    min-height: 8.5rem;
    resize: none;
}

/* Wikidata search results */
.search-results {
    width: 100%;
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #ccc;
    border-radius: 4px;
    margin-top: -0.5rem;
    background-color: #fff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    z-index: 20;
    position: relative;
}

.search-info {
    padding: 10px 15px;
    font-size: 0.9rem;
    color: #666;
    border-bottom: 1px solid #eee;
    margin: 0;
}

.search-result-item {
    padding: 10px 12px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    transition: background-color 0.2s;
}

.search-result-item:hover {
    background-color: #f5f5f5;
}

.search-result-item:last-child {
    border-bottom: none;
}

.result-title {
    font-weight: bold;
    margin-bottom: 3px;
}

.result-description {
    font-size: 0.9rem;
    color: #555;
    margin-bottom: 3px;
}

.result-id {
    font-size: 0.8rem;
    color: #888;
}
</style>
