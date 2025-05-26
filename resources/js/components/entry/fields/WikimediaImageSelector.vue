<template>
    <div class="wikimedia-image-selector">
        <v-card>
            <v-card-title>Bild suchen und auswählen</v-card-title>
            <v-card-text>
                <v-text-field
                    v-model="searchQuery"
                    label="Suchbegriff"
                    append-inner-icon="mdi-magnify"
                    @click:append-inner="searchImages"
                    @keydown.enter="searchImages"
                    :loading="loading"
                    :disabled="loading"
                    clearable
                ></v-text-field>

                <!-- Suchergebnisse -->
                <v-alert v-if="error" type="error" class="mb-3">
                    {{ error }}
                </v-alert>

                <div v-if="loading" class="d-flex justify-center my-4">
                    <v-progress-circular indeterminate></v-progress-circular>
                </div>

                <v-alert
                    v-else-if="
                        !loading && images.length === 0 && searchPerformed
                    "
                    type="info"
                    class="mb-3"
                >
                    Keine Bilder gefunden. Versuchen Sie einen anderen
                    Suchbegriff.
                </v-alert>

                <!-- Bilder Grid -->
                <v-row v-else-if="images.length > 0">
                    <v-col
                        v-for="(image, index) in images"
                        :key="index"
                        cols="12"
                        sm="6"
                        md="4"
                        lg="3"
                    >
                        <div
                            @click="confirmImageSelection(image)"
                            class="image-card"
                            :class="{
                                selected:
                                    selectedImage &&
                                    selectedImage.title === image.title,
                            }"
                        >
                            <v-img
                                :src="image.thumbUrl"
                                :alt="image.title"
                                height="150px"
                                cover
                                class="align-center"
                            >
                                <template v-slot:placeholder>
                                    <v-row
                                        class="fill-height ma-0"
                                        align="center"
                                        justify="center"
                                    >
                                        <v-progress-circular
                                            indeterminate
                                        ></v-progress-circular>
                                    </v-row>
                                </template>
                            </v-img>

                            <div class="image-title text-truncate pa-2">
                                {{ image.title }}
                            </div>

                            <!-- Ausgewählt Icon -->
                            <div
                                v-if="
                                    selectedImage &&
                                    selectedImage.title === image.title
                                "
                                class="selected-indicator"
                            >
                                <v-icon color="success" size="large"
                                    >mdi-check-circle</v-icon
                                >
                            </div>
                        </div>
                    </v-col>
                </v-row>
            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="grey" @click="closeDialog">Abbrechen</v-btn>
            </v-card-actions>
        </v-card>
    </div>
</template>

<script setup>
import { ref, defineEmits } from "vue";
import axios from "axios";

const emit = defineEmits(["select", "close"]);

const loading = ref(false);
const error = ref(null);
const searchQuery = ref("");
const images = ref([]);
const selectedImage = ref(null);
const searchPerformed = ref(false);

const searchImages = async () => {
    if (!searchQuery.value || searchQuery.value.trim() === "") {
        error.value = "Bitte geben Sie einen Suchbegriff ein.";
        return;
    }

    loading.value = true;
    error.value = null;

    try {
        const response = await axios.get(`/api/wikimedia/images`, {
            params: {
                query: searchQuery.value,
                limit: 20,
            },
        });

        if (response.data.success) {
            images.value = response.data.images;
            searchPerformed.value = true;
        } else {
            error.value = response.data.message || "Fehler bei der Bildsuche";
        }
    } catch (err) {
        console.error("Fehler bei der Bildsuche:", err);
        error.value =
            "Bei der Suche nach Bildern ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.";
    } finally {
        loading.value = false;
    }
};

const selectImage = (image) => {
    console.log("Image selected:", image.title);
    selectedImage.value = image;
};

// Funktion direkt mit der Auswahl und Bestätigung
const confirmImageSelection = (image) => {
    selectImage(image);
    if (image) {
        emit("select", {
            url: image.url,
            filename: image.title,
        });
    }
};

const closeDialog = () => {
    emit("close");
};
</script>

<style scoped>
.wikimedia-image-selector {
    max-height: 90vh;
    overflow-y: scroll;
}

.image-card {
    position: relative;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2px solid transparent;
    border-radius: 4px;
    overflow: hidden;
    background-color: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    margin-bottom: 16px;
}

.image-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    border-color: #ccc;
}

.image-card.selected {
    border-color: #4caf50;
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.3);
}

.image-title {
    font-size: 14px;
    background-color: #f5f5f5;
    border-top: 1px solid #eee;
}

.selected-indicator {
    position: absolute;
    top: 8px;
    right: 8px;
    background-color: white;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}
</style>
