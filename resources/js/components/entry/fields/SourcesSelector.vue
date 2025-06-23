<template>
    <div class="sources-selector">
        <v-card>
            <v-card-title>Quellenangabe hinzufügen</v-card-title>
            <v-card-text class="overlay-content">
                <!-- Quellentyp auswählen -->
                <div class="source-type-selector">
                    <label>Quellentyp auswählen:</label>
                    <div class="d-flex mt-2">
                        <v-btn
                            variant="outlined"
                            :color="
                                sourceType === 'book' ? 'primary' : 'default'
                            "
                            class="me-2"
                            @click="sourceType = 'book'"
                        >
                            <v-icon class="me-1">mdi-book</v-icon>
                            Buch
                        </v-btn>
                        <v-btn
                            variant="outlined"
                            :color="
                                sourceType === 'url' ? 'primary' : 'default'
                            "
                            class="me-2"
                            @click="sourceType = 'url'"
                        >
                            <v-icon class="me-1">mdi-link</v-icon>
                            URL
                        </v-btn>
                        <v-btn
                            variant="outlined"
                            :color="
                                sourceType === 'archive' ? 'primary' : 'default'
                            "
                            @click="sourceType = 'archive'"
                        >
                            <v-icon class="me-1">mdi-archive</v-icon>
                            Archiv
                        </v-btn>
                    </div>
                </div>

                <v-divider class="my-4"></v-divider>

                <!-- Buchquellenkomponente -->
                <div v-if="sourceType === 'book'" class="book-source-selector">
                    <!-- Search Interface - only shown when not adding a manual book -->
                    <div v-if="!selectedBook && !bookSearch.showManualForm">
                        <v-text-field
                            v-model="bookSearch.query"
                            label="Buchtitel suchen"
                            append-inner-icon="mdi-magnify"
                            @click:append-inner="searchBooks"
                            @keydown.enter="searchBooks"
                            :loading="bookSearch.loading"
                            :disabled="bookSearch.loading"
                            clearable
                        ></v-text-field>

                        <!-- Suchergebnisse -->
                        <v-alert
                            v-if="bookSearch.error"
                            type="error"
                            class="mb-3"
                        >
                            {{ bookSearch.error }}
                        </v-alert>

                        <div
                            v-if="bookSearch.loading"
                            class="d-flex justify-center my-4"
                        >
                            <v-progress-circular
                                indeterminate
                            ></v-progress-circular>
                        </div>

                        <v-alert
                            v-else-if="
                                !bookSearch.loading &&
                                bookSearch.results.length === 0 &&
                                bookSearch.performed
                            "
                            type="info"
                            class="mb-3"
                        >
                            Keine Bücher gefunden. Versuchen Sie einen anderen
                            Suchbegriff.
                        </v-alert>

                        <!-- Bücher Liste -->
                        <v-list
                            v-else-if="bookSearch.results.length > 0"
                            class="source-list"
                        >
                            <v-list-item
                                v-for="(book, index) in bookSearch.results"
                                :key="index"
                                @click="selectBook(book)"
                                class="source-item"
                            >
                                <v-list-item-title>{{
                                    book.label
                                }}</v-list-item-title>
                                <v-list-item-subtitle v-if="book.description">
                                    {{ book.description }}
                                </v-list-item-subtitle>
                                <v-list-item-subtitle class="id-text">
                                    {{ book.id }}
                                </v-list-item-subtitle>
                            </v-list-item>
                        </v-list>

                        <!-- Button zum Hinzufügen eines neuen Buchs -->
                        <div class="mt-4" v-if="bookSearch.performed">
                            <v-divider></v-divider>
                            <div
                                class="d-flex align-center justify-center mt-3"
                            >
                                <v-btn
                                    color="primary"
                                    @click="showManualBookForm"
                                >
                                    Neues Buch anlegen
                                </v-btn>
                            </div>
                        </div>
                    </div>

                    <!-- Ausgewählter Buchtitel mit Wikidata-Verlinkung -->
                    <div v-if="selectedBook" class="selected-source">
                        <div class="d-flex justify-space-between align-center">
                            <h3>{{ selectedBook.label }}</h3>
                            <v-btn
                                icon="mdi-close"
                                size="small"
                                @click="clearSelectedBook"
                                variant="text"
                            ></v-btn>
                        </div>
                        <p
                            v-if="selectedBook.description"
                            class="text-body-2 mb-2"
                        >
                            {{ selectedBook.description }}
                        </p>
                        <a
                            :href="`https://www.wikidata.org/wiki/${selectedBook.id}`"
                            target="_blank"
                            class="text-caption text-decoration-none"
                            v-if="
                                selectedBook.id && selectedBook.id !== 'manual'
                            "
                        >
                            {{ selectedBook.id }}
                            <v-icon size="x-small">mdi-open-in-new</v-icon>
                        </a>

                        <v-divider class="my-4"></v-divider>

                        <!-- Seitenangabe -->
                        <v-text-field
                            v-model="bookForm.pages"
                            label="Seitenangabe"
                            hint="Geben Sie die Seitenzahl(en) an"
                            class="mt-4"
                        ></v-text-field>
                    </div>

                    <!-- Formular für manuelle Bucheingabe -->
                    <div
                        v-if="bookSearch.showManualForm && !selectedBook"
                        class="manual-source-form mt-4"
                    >
                        <v-divider class="mb-4"></v-divider>
                        <h3 class="text-h6 mb-4">Buch manuell hinzufügen</h3>

                        <v-text-field
                            v-model="bookForm.manual.title"
                            label="Buchtitel *"
                            required
                        ></v-text-field>

                        <!-- Author selection component -->
                        <PersonsField
                            label="Autor*In"
                            fieldId="authors"
                            :peopleSearchResults="authorSearchResults"
                            :selectedPersons="selectedAuthors"
                            :showNewPersonForm="showNewAuthorForm"
                            :newPerson="newAuthor"
                            :isSearchLoading="isAuthorSearchLoading"
                            :searchPerformed="authorSearchPerformed"
                            :showInfoButton="false"
                            :hidePlaceOfDeath="true"
                            :hidePlaceOfDetention="true"
                            :hideIsOrganisation="true"
                            :isAuthorForm="true"
                            @searchPeople="onSearchAuthor"
                            @selectPerson="onSelectAuthor"
                            @startNewPersonForm="onStartNewAuthorForm"
                            @cancelNewPersonForm="onCancelNewAuthorForm"
                            @update:newPerson="newAuthor = $event"
                            @addNewPerson="onAddNewAuthor"
                            @removePerson="onRemoveAuthor"
                        />

                        <v-text-field
                            v-model="bookForm.manual.year"
                            label="Erscheinungsjahr"
                            type="number"
                        ></v-text-field>

                        <v-text-field
                            v-model="bookForm.manual.publishingDate"
                            label="Veröffentlichungsdatum"
                            type="date"
                        ></v-text-field>

                        <v-text-field
                            v-model="bookForm.manual.pages"
                            label="Seitenangabe"
                        ></v-text-field>

                        <div class="d-flex justify-end mt-4">
                            <v-btn
                                variant="text"
                                class="me-2"
                                @click="bookSearch.showManualForm = false"
                            >
                                Abbrechen
                            </v-btn>
                            <v-btn
                                color="primary"
                                @click="addManualBook"
                                :disabled="
                                    !bookForm.manual.title ||
                                    selectedAuthors.length === 0
                                "
                            >
                                Hinzufügen
                            </v-btn>
                        </div>
                    </div>
                </div>

                <!-- URL-Quellenkomponente -->
                <div v-if="sourceType === 'url'" class="url-source-selector">
                    <v-text-field
                        v-model="urlForm.url"
                        label="URL *"
                        placeholder="https://..."
                        :rules="[rules.required, rules.url]"
                        required
                    ></v-text-field>

                    <v-text-field
                        v-model="urlForm.title"
                        label="Titel *"
                        :rules="[rules.required]"
                        required
                    ></v-text-field>

                    <v-text-field
                        v-model="urlForm.accessDate"
                        label="Zugriffsdatum"
                        type="date"
                        :value="defaultDate"
                    ></v-text-field>
                </div>

                <!-- Archivquellenkomponente -->
                <div
                    v-if="sourceType === 'archive'"
                    class="archive-source-selector"
                >
                    <div v-if="!selectedArchive">
                        <v-text-field
                            v-model="archiveSearch.query"
                            label="Archiv suchen"
                            append-inner-icon="mdi-magnify"
                            @click:append-inner="searchArchives"
                            @keydown.enter="searchArchives"
                            :loading="archiveSearch.loading"
                            :disabled="archiveSearch.loading"
                            clearable
                        ></v-text-field>

                        <!-- Suchergebnisse -->
                        <v-alert
                            v-if="archiveSearch.error"
                            type="error"
                            class="mb-3"
                        >
                            {{ archiveSearch.error }}
                        </v-alert>

                        <div
                            v-if="archiveSearch.loading"
                            class="d-flex justify-center my-4"
                        >
                            <v-progress-circular
                                indeterminate
                            ></v-progress-circular>
                        </div>

                        <v-alert
                            v-else-if="
                                !archiveSearch.loading &&
                                archiveSearch.results.length === 0 &&
                                archiveSearch.performed
                            "
                            type="info"
                            class="mb-3"
                        >
                            Keine Archive gefunden. Versuchen Sie einen anderen
                            Suchbegriff.
                        </v-alert>

                        <!-- Archive Liste -->
                        <v-list
                            v-else-if="archiveSearch.results.length > 0"
                            class="source-list"
                        >
                            <v-list-item
                                v-for="(
                                    archive, index
                                ) in archiveSearch.results"
                                :key="index"
                                @click="selectArchive(archive)"
                                class="source-item"
                            >
                                <v-list-item-title>{{
                                    archive.label
                                }}</v-list-item-title>
                                <v-list-item-subtitle
                                    v-if="archive.description"
                                >
                                    {{ archive.description }}
                                </v-list-item-subtitle>
                                <v-list-item-subtitle class="id-text">
                                    {{ archive.id }}
                                </v-list-item-subtitle>
                            </v-list-item>
                        </v-list>

                        <!-- Option zum manuellen Hinzufügen, wenn nichts gefunden wurde -->
                        <div
                            v-if="
                                archiveSearch.performed &&
                                archiveSearch.results.length === 0
                            "
                            class="mt-4"
                        >
                            <v-divider></v-divider>
                            <div
                                class="d-flex align-center justify-space-between mt-3"
                            >
                                <div>Archiv nicht gefunden?</div>
                                <v-btn
                                    color="primary"
                                    @click="archiveSearch.showManualForm = true"
                                    variant="text"
                                >
                                    Manuell hinzufügen
                                </v-btn>
                            </div>
                        </div>
                    </div>

                    <!-- Ausgewähltes Archiv mit Wikidata-Verlinkung -->
                    <div v-if="selectedArchive" class="selected-source">
                        <div class="d-flex justify-space-between align-center">
                            <h3>{{ selectedArchive.label }}</h3>
                            <v-btn
                                icon="mdi-close"
                                size="small"
                                @click="clearSelectedArchive"
                                variant="text"
                            ></v-btn>
                        </div>
                        <p
                            v-if="selectedArchive.description"
                            class="text-body-2 mb-2"
                        >
                            {{ selectedArchive.description }}
                        </p>
                        <a
                            v-if="
                                selectedArchive.id &&
                                selectedArchive.id !== 'manual'
                            "
                            :href="`https://www.wikidata.org/wiki/${selectedArchive.id}`"
                            target="_blank"
                            class="text-caption text-decoration-none"
                        >
                            {{ selectedArchive.id }}
                            <v-icon size="x-small">mdi-open-in-new</v-icon>
                        </a>

                        <v-divider class="my-4"></v-divider>

                        <!-- Inventarnummer -->
                        <v-text-field
                            v-model="archiveForm.inventoryNumber"
                            label="Inventarnummer (Signatur) *"
                            hint="Geben Sie die Inventarnummer/Signatur an"
                            class="mt-4"
                            required
                            :rules="[
                                (v) => !!v || 'Inventarnummer ist erforderlich',
                            ]"
                        ></v-text-field>
                    </div>

                    <!-- Formular für manuelle Archiveingabe -->
                    <div
                        v-if="archiveSearch.showManualForm && !selectedArchive"
                        class="manual-source-form mt-4"
                    >
                        <v-divider class="mb-4"></v-divider>
                        <h3 class="text-h6 mb-4">Archiv manuell hinzufügen</h3>

                        <v-text-field
                            v-model="archiveForm.manual.title"
                            label="Archivname *"
                            required
                        ></v-text-field>

                        <v-text-field
                            v-model="archiveForm.manual.location"
                            label="Ort"
                        ></v-text-field>

                        <div class="d-flex justify-end mt-4">
                            <v-btn
                                variant="text"
                                class="me-2"
                                @click="archiveSearch.showManualForm = false"
                            >
                                Abbrechen
                            </v-btn>
                            <v-btn
                                color="primary"
                                @click="addManualArchive"
                                :disabled="!archiveForm.manual.title"
                            >
                                Hinzufügen
                            </v-btn>
                        </div>
                    </div>
                </div>
            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="grey" @click="closeDialog">Abbrechen</v-btn>
                <v-btn
                    color="primary"
                    @click="confirmSelection"
                    :disabled="!canConfirm"
                >
                    Bestätigen
                </v-btn>
            </v-card-actions>
        </v-card>
    </div>
</template>

<script setup>
import { ref, computed } from "vue";
import axios from "axios";
import PersonsField from "./Persons.vue";

const emit = defineEmits(["select", "close"]);

// Standard ist Buch als Quellentyp
const sourceType = ref("book");
const defaultDate = new Date().toISOString().substr(0, 10);

// Author search and selection states
const authorSearchResults = ref([]);
const selectedAuthors = ref([]);
const showNewAuthorForm = ref(false);
const isAuthorSearchLoading = ref(false);
const authorSearchPerformed = ref(false);
const newAuthor = ref({
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
});

// Validierung
const rules = {
    required: (v) => !!v || "Dieses Feld ist erforderlich",
    url: (v) =>
        /^(https?:\/\/)?([\da-z.-]+)\.([a-z.]{2,6})([\/\w .-]*)*\/?$/.test(v) ||
        "Bitte geben Sie eine gültige URL ein",
};

// Author search and selection methods
const onSearchAuthor = async (query) => {
    if (!query || query.trim() === "") {
        authorSearchResults.value = [];
        authorSearchPerformed.value = false;
        return;
    }

    if (query && query.length >= 2) {
        setTimeout(() => {
            if (!authorSearchResults.value.length) {
                isAuthorSearchLoading.value = true;
            }
        }, 300);
        authorSearchPerformed.value = true;
    } else {
        isAuthorSearchLoading.value = false;
        authorSearchPerformed.value = false;
        return;
    }

    try {
        const response = await axios.get("/api/wikidata/search/people", {
            params: { query },
        });

        // Handle both response formats (direct array or results property)
        if (Array.isArray(response.data)) {
            authorSearchResults.value = response.data;
        } else if (response.data && response.data.results) {
            authorSearchResults.value = response.data.results;
        } else {
            authorSearchResults.value = [];
        }

        console.log("Author search results:", authorSearchResults.value);
    } catch (err) {
        console.error("Error searching for authors:", err);
        authorSearchResults.value = [];
    } finally {
        isAuthorSearchLoading.value = false;
    }
};

const onSelectAuthor = (person) => {
    selectedAuthors.value = [person]; // Only allow one author for now
};

const onStartNewAuthorForm = () => {
    showNewAuthorForm.value = true;
};

const onCancelNewAuthorForm = () => {
    showNewAuthorForm.value = false;
    newAuthor.value = {
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
    };
};

const onAddNewAuthor = () => {
    if (!newAuthor.value.firstName && !newAuthor.value.lastName) {
        return; // Require at least first or last name
    }

    // Create a temporary ID for the new author
    const tempId = "temp_" + Date.now();

    // Add to selected authors
    const authorToAdd = {
        id: tempId,
        temp_id: tempId,
        firstName: newAuthor.value.firstName,
        lastName: newAuthor.value.lastName,
        label: `${newAuthor.value.firstName} ${newAuthor.value.lastName}`.trim(),
        description: newAuthor.value.description || "Autor*In",
        birthDate: newAuthor.value.birthDate,
        deathDate: newAuthor.value.deathDate,
        isOrganization: newAuthor.value.isOrganization,
        gender: newAuthor.value.gender,
        nationality: newAuthor.value.nationality,
        place_of_birth: newAuthor.value.place_of_birth,
        place_of_death: newAuthor.value.place_of_death,
        place_of_detention: newAuthor.value.place_of_detention,
    };

    selectedAuthors.value = [authorToAdd]; // Only allow one author for now

    // Reset form
    showNewAuthorForm.value = false;
    newAuthor.value = {
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
    };
};

const onRemoveAuthor = (id) => {
    selectedAuthors.value = [];
};

// ========== BUCH ==========
const selectedBook = ref(null);
const bookSearch = ref({
    query: "",
    loading: false,
    error: null,
    results: [],
    performed: false,
    showManualForm: false,
});

const bookForm = ref({
    pages: "",
    manual: {
        title: "",
        year: "",
        pages: "",
        publishingDate: null,
    },
});

const showManualBookForm = () => {
    bookSearch.value.showManualForm = true;
    bookSearch.value.results = [];
    bookSearch.value.query = "";
};

const searchBooks = async () => {
    if (!bookSearch.value.query || bookSearch.value.query.trim() === "") {
        bookSearch.value.error = "Bitte geben Sie einen Suchbegriff ein.";
        return;
    }

    bookSearch.value.loading = true;
    bookSearch.value.error = null;

    try {
        const response = await axios.get(`/api/wikidata/search/books`, {
            params: {
                query: bookSearch.value.query,
            },
        });

        if (response.data.success) {
            bookSearch.value.results = response.data.results;
            bookSearch.value.performed = true;
        } else {
            bookSearch.value.error =
                response.data.message || "Fehler bei der Buchsuche";
        }
    } catch (err) {
        console.error("Fehler bei der Buchsuche:", err);
        bookSearch.value.error =
            "Bei der Suche nach Büchern ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.";
    } finally {
        bookSearch.value.loading = false;
    }
};

const selectBook = (book) => {
    selectedBook.value = book;
    // Falls die Suchfeld angezeigt wird, blenden wir es jetzt aus
    bookSearch.value.query = "";
};

const clearSelectedBook = () => {
    selectedBook.value = null;
    bookForm.value.pages = "";
};

const addManualBook = () => {
    const manual = bookForm.value.manual;
    if (!manual.title || selectedAuthors.value.length === 0) {
        bookSearch.value.error =
            "Bitte geben Sie mindestens Titel und einen Autor ein.";
        return;
    }

    // Get author info from selected authors
    const authorInfo = selectedAuthors.value[0];
    const authorName =
        authorInfo.label ||
        `${authorInfo.firstName || ""} ${authorInfo.lastName || ""}`.trim();

    // Create a custom book object
    selectedBook.value = {
        id: "manual",
        label: manual.title,
        description: `${authorName}${manual.year ? `, ${manual.year}` : ""}`,
        author: authorInfo, // Store the entire author object for Wikidata
        year: manual.year || null,
        publishingDate: manual.publishingDate || null,
        isManual: true,
    };

    // If pages were entered, set them
    if (manual.pages) {
        bookForm.value.pages = manual.pages;
    }

    // Hide the manual form
    bookSearch.value.showManualForm = false;
};

// ========== URL ==========
const urlForm = ref({
    url: "",
    title: "",
    accessDate: new Date().toISOString().substr(0, 10),
});

// ========== ARCHIV ==========
const selectedArchive = ref(null);
const archiveSearch = ref({
    query: "",
    loading: false,
    error: null,
    results: [],
    performed: false,
    showManualForm: false,
});

const archiveForm = ref({
    inventoryNumber: "",
    manual: {
        title: "",
        location: "",
    },
});

const searchArchives = async () => {
    if (!archiveSearch.value.query || archiveSearch.value.query.trim() === "") {
        archiveSearch.value.error = "Bitte geben Sie einen Suchbegriff ein.";
        return;
    }

    archiveSearch.value.loading = true;
    archiveSearch.value.error = null;

    try {
        const response = await axios.get(`/api/wikidata/search/archives`, {
            params: {
                query: archiveSearch.value.query,
            },
        });

        if (response.data.success) {
            archiveSearch.value.results = response.data.results;
            archiveSearch.value.performed = true;
        } else {
            archiveSearch.value.error =
                response.data.message || "Fehler bei der Archivsuche";
        }
    } catch (err) {
        console.error("Fehler bei der Archivsuche:", err);
        archiveSearch.value.error =
            "Bei der Suche nach Archiven ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.";
    } finally {
        archiveSearch.value.loading = false;
    }
};

const selectArchive = (archive) => {
    selectedArchive.value = archive;
    // Falls die Suchfeld angezeigt wird, blenden wir es jetzt aus
    archiveSearch.value.query = "";
};

const clearSelectedArchive = () => {
    selectedArchive.value = null;
    archiveForm.value.inventoryNumber = "";
};

const addManualArchive = () => {
    const manual = archiveForm.value.manual;
    if (!manual.title) {
        archiveSearch.value.error =
            "Bitte geben Sie mindestens einen Archivnamen ein.";
        return;
    }

    // Create a custom archive object
    selectedArchive.value = {
        id: "manual",
        label: manual.title,
        description: manual.location ? `Standort: ${manual.location}` : "",
        location: manual.location || null,
        isManual: true,
    };

    // Hide the manual form
    archiveSearch.value.showManualForm = false;
};

// ========== GEMEINSAME FUNKTIONEN ==========
const canConfirm = computed(() => {
    if (sourceType.value === "book") {
        // If it's a manual book, also check for author
        if (selectedBook.value && selectedBook.value.isManual) {
            return (
                selectedBook.value !== null && selectedAuthors.value.length > 0
            );
        }
        return selectedBook.value !== null;
    } else if (sourceType.value === "url") {
        return (
            urlForm.value.url &&
            urlForm.value.title &&
            rules.url(urlForm.value.url) === true
        );
    } else if (sourceType.value === "archive") {
        return (
            selectedArchive.value !== null &&
            !!archiveForm.value.inventoryNumber
        );
    }
    return false;
});

const confirmSelection = () => {
    if (!canConfirm.value) return;

    let source = null;

    if (sourceType.value === "book" && selectedBook.value) {
        source = {
            type: "book",
            id: selectedBook.value.id,
            title: selectedBook.value.label,
            description: selectedBook.value.description,
            pages: bookForm.value.pages,
            isManual: selectedBook.value.isManual || false,
            author: selectedBook.value.author || null,
            year: selectedBook.value.year || null,
            publishingDate: selectedBook.value.publishingDate || null,
        };

        // If we have a manual book, add the author information
        if (source.isManual && selectedAuthors.value.length > 0) {
            source.author = selectedAuthors.value[0];
        }
    } else if (sourceType.value === "url") {
        source = {
            type: "url",
            url: urlForm.value.url,
            title: urlForm.value.title,
            accessDate: urlForm.value.accessDate,
        };
    } else if (sourceType.value === "archive" && selectedArchive.value) {
        source = {
            type: "archive",
            id: selectedArchive.value.id,
            title: selectedArchive.value.label,
            description: selectedArchive.value.description,
            inventoryNumber: archiveForm.value.inventoryNumber,
            isManual: selectedArchive.value.isManual || false,
            location: selectedArchive.value.location || null,
        };
    }

    if (source) {
        emit("select", { source });
    }
};

const closeDialog = () => {
    emit("close");
};
</script>

<style scoped>
.sources-selector {
    min-width: 500px;
}

.overlay-content {
    max-height: 80vh;
    overflow-y: auto;
}

.source-type-selector {
    margin-bottom: 20px;
}

.source-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    margin-bottom: 16px;
}

.source-item {
    cursor: pointer;
    transition: background-color 0.2s ease;
    border-bottom: 1px solid #f0f0f0;
}

.source-item:hover {
    background-color: #f5f5f5;
}

.id-text {
    color: #9e9e9e;
    font-size: 12px;
}

.selected-source {
    padding: 16px;
    background-color: #f5f5f5;
    border-radius: 4px;
    margin-bottom: 16px;
}
</style>
