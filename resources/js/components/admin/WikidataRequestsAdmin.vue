<template>
    <v-container>
        <v-row>
            <v-col>
                <h1
                    class="font-family-special-elite font-weight-bold py-4 text-h4"
                >
                    Wikidata-Einträge verwalten
                </h1>
            </v-col>
        </v-row>

        <!-- Tabs für unterschiedliche Status -->
        <v-tabs v-model="activeTab" bg-color="transparent">
            <v-tab value="pending">Ausstehend</v-tab>
            <v-tab value="approved">Genehmigt</v-tab>
            <v-tab value="rejected">Abgelehnt</v-tab>
            <v-tab value="error">Fehler</v-tab>
        </v-tabs>

        <v-card-text>
            <!-- Alert für Benachrichtigungen -->
            <v-alert
                v-if="notification.message"
                :type="notification.type"
                class="mb-4"
                dismissible
                @click:close="notification.message = ''"
            >
                {{ notification.message }}
            </v-alert>

            <!-- Ladeindikator -->
            <div v-if="loading" class="d-flex justify-center my-8">
                <v-progress-circular indeterminate color="primary" size="64" />
            </div>

            <!-- Keine Einträge vorhanden -->
            <v-alert v-else-if="!hasEntries" type="info" class="my-4">
                Keine {{ tabLabel }} Einträge gefunden.
            </v-alert>

            <!-- Tabelle mit Einträgen -->
            <v-table v-else class="mt-4">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titel</th>
                        <th>Typ</th>
                        <th>Datum</th>
                        <th>Erstellt von</th>
                        <th v-if="activeTab === 'approved'">Wikidata ID</th>
                        <th v-if="activeTab === 'rejected'">Ablehnungsgrund</th>
                        <th v-if="activeTab === 'error'">Fehlermeldung</th>
                        <th v-if="activeTab === 'pending'">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="entry in entries" :key="entry.id">
                        <td>{{ entry.id }}</td>
                        <td>
                            <div class="font-weight-bold">
                                {{ entry.title }}
                            </div>
                            <div class="text-caption text-grey">
                                {{ entry.description }}
                            </div>
                        </td>
                        <td>
                            <v-chip size="small">{{
                                getTypeName(entry.type)
                            }}</v-chip>
                        </td>
                        <td>{{ formatDate(entry.created_at) }}</td>
                        <td>{{ entry.submitted_by }}</td>
                        <td v-if="activeTab === 'approved'">
                            <a
                                :href="getWikidataUrl(entry.wikidata_id)"
                                target="_blank"
                            >
                                {{ entry.wikidata_id }}
                            </a>
                        </td>
                        <td v-if="activeTab === 'rejected'">
                            {{ entry.rejection_reason }}
                        </td>
                        <td v-if="activeTab === 'error'">
                            {{ entry.error_message }}
                            <v-btn
                                size="small"
                                color="warning"
                                class="ml-2"
                                @click="retryEntry(entry.id)"
                            >
                                Wiederholen
                            </v-btn>
                        </td>
                        <td v-if="activeTab === 'pending'" class="d-flex">
                            <v-btn
                                color="success"
                                size="small"
                                class="mr-2"
                                @click="openApproveDialog(entry)"
                            >
                                Genehmigen
                            </v-btn>
                            <v-btn
                                color="error"
                                size="small"
                                @click="openRejectDialog(entry)"
                            >
                                Ablehnen
                            </v-btn>
                        </td>
                    </tr>
                </tbody>
            </v-table>

            <!-- Paginierung -->
            <div
                v-if="pagination.total > pagination.perPage"
                class="d-flex justify-center mt-4"
            >
                <v-pagination
                    v-model="pagination.currentPage"
                    :length="Math.ceil(pagination.total / pagination.perPage)"
                    @update:model-value="loadEntries"
                />
            </div>
        </v-card-text>

        <!-- Dialog für Genehmigung -->
        <v-dialog v-model="approveDialog.visible" max-width="600px">
            <v-card>
                <v-card-title>Eintrag genehmigen</v-card-title>
                <v-card-text>
                    <p>
                        Sind Sie sicher, dass Sie diesen Eintrag genehmigen
                        möchten?<br />
                        Der Eintrag wird in Wikidata erstellt.
                    </p>
                    <v-alert type="info" class="mt-3">
                        <strong>Titel:</strong> {{ approveDialog.entry?.title
                        }}<br />
                        <strong>Beschreibung:</strong>
                        {{ approveDialog.entry?.description }}<br />
                        <strong>Typ:</strong>
                        {{ getTypeName(approveDialog.entry?.type) }}
                    </v-alert>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey" @click="approveDialog.visible = false"
                        >Abbrechen</v-btn
                    >
                    <v-btn
                        color="success"
                        @click="approveEntry"
                        :loading="approveDialog.loading"
                    >
                        Genehmigen
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Dialog für Ablehnung -->
        <v-dialog v-model="rejectDialog.visible" max-width="600px">
            <v-card>
                <v-card-title>Eintrag ablehnen</v-card-title>
                <v-card-text>
                    <p>Bitte geben Sie einen Grund für die Ablehnung an:</p>
                    <v-alert type="info" class="mt-3 mb-3">
                        <strong>Titel:</strong> {{ rejectDialog.entry?.title
                        }}<br />
                        <strong>Beschreibung:</strong>
                        {{ rejectDialog.entry?.description }}<br />
                        <strong>Typ:</strong>
                        {{ getTypeName(rejectDialog.entry?.type) }}
                    </v-alert>
                    <v-textarea
                        v-model="rejectDialog.reason"
                        label="Ablehnungsgrund"
                        required
                        rows="3"
                        auto-grow
                    ></v-textarea>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="grey" @click="rejectDialog.visible = false"
                        >Abbrechen</v-btn
                    >
                    <v-btn
                        color="error"
                        @click="rejectEntry"
                        :loading="rejectDialog.loading"
                        :disabled="!rejectDialog.reason"
                    >
                        Ablehnen
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import axios from "axios";

// Zustandsvariablen
const loading = ref(false);
const entries = ref([]);
const activeTab = ref("pending");
const notification = ref({ type: "info", message: "" });

// Paginierung
const pagination = ref({
    total: 0,
    perPage: 10,
    currentPage: 1,
});

// Dialoge
const approveDialog = ref({
    visible: false,
    entry: null,
    loading: false,
});

const rejectDialog = ref({
    visible: false,
    entry: null,
    reason: "",
    loading: false,
});

// Entity-Typen Mapping
const entityTypes = {
    Q6983405: "Ereignis",
    Q5003624: "Erinnerungsort",
    Q108047567: "Staatspolizeistelle",
    Q108047541: "Staatspolizeiaußenstelle",
    Q277565: "Arbeitserziehungslager",
    Q40357: "Gefängnis",
};

// Computed Properties
const hasEntries = computed(() => entries.value.length > 0);
const tabLabel = computed(() => {
    switch (activeTab.value) {
        case "pending":
            return "ausstehende";
        case "approved":
            return "genehmigte";
        case "rejected":
            return "abgelehnte";
        case "error":
            return "fehlerhafte";
        default:
            return "";
    }
});

// Beim Tab-Wechsel Einträge neu laden
watch(activeTab, () => {
    pagination.value.currentPage = 1;
    loadEntries();
});

// Einträge laden
const loadEntries = async () => {
    loading.value = true;
    try {
        const status = activeTab.value;
        const page = pagination.value.currentPage;

        const response = await axios.get(`/api/wikidata/requests`, {
            params: {
                status,
                page,
                perPage: pagination.value.perPage,
            },
        });

        entries.value = response.data.requests.data;
        pagination.value.total = response.data.requests.total;
    } catch (error) {
        console.error("Fehler beim Laden der Einträge:", error);
        notification.value = {
            type: "error",
            message:
                "Fehler beim Laden der Einträge. Bitte versuchen Sie es später erneut.",
        };
    } finally {
        loading.value = false;
    }
};

// Eintrag genehmigen
const approveEntry = async () => {
    approveDialog.value.loading = true;
    try {
        await axios.post(
            `/api/wikidata/requests/${approveDialog.value.entry.id}/approve`,
        );

        notification.value = {
            type: "success",
            message:
                "Der Eintrag wurde erfolgreich genehmigt und wird in Wikidata erstellt.",
        };

        // Dialog schließen und Liste aktualisieren
        approveDialog.value.visible = false;
        loadEntries();
    } catch (error) {
        console.error("Fehler bei der Genehmigung:", error);
        notification.value = {
            type: "error",
            message: `Fehler bei der Genehmigung: ${error.response?.data?.message || error.message}`,
        };
    } finally {
        approveDialog.value.loading = false;
    }
};

// Eintrag ablehnen
const rejectEntry = async () => {
    if (!rejectDialog.value.reason) {
        notification.value = {
            type: "error",
            message: "Bitte geben Sie einen Ablehnungsgrund an.",
        };
        return;
    }

    rejectDialog.value.loading = true;
    try {
        await axios.post(
            `/api/wikidata/requests/${rejectDialog.value.entry.id}/reject`,
            {
                rejection_reason: rejectDialog.value.reason,
            },
        );

        notification.value = {
            type: "success",
            message: "Der Eintrag wurde erfolgreich abgelehnt.",
        };

        // Dialog schließen und Liste aktualisieren
        rejectDialog.value.visible = false;
        rejectDialog.value.reason = "";
        loadEntries();
    } catch (error) {
        console.error("Fehler bei der Ablehnung:", error);
        notification.value = {
            type: "error",
            message: `Fehler bei der Ablehnung: ${error.response?.data?.message || error.message}`,
        };
    } finally {
        rejectDialog.value.loading = false;
    }
};

// Fehlerhaften Eintrag erneut versuchen
const retryEntry = async (id) => {
    try {
        const response = await axios.post(`/api/wikidata/requests/${id}/retry`);

        notification.value = {
            type: "success",
            message: "Der Eintrag wurde zur erneuten Verarbeitung eingereicht.",
        };

        loadEntries();
    } catch (error) {
        console.error("Fehler beim erneuten Versuch:", error);
        notification.value = {
            type: "error",
            message: `Fehler beim erneuten Versuch: ${error.response?.data?.message || error.message}`,
        };
    }
};

// Dialog für Genehmigung öffnen
const openApproveDialog = (entry) => {
    approveDialog.value.entry = entry;
    approveDialog.value.visible = true;
};

// Dialog für Ablehnung öffnen
const openRejectDialog = (entry) => {
    rejectDialog.value.entry = entry;
    rejectDialog.value.visible = true;
    rejectDialog.value.reason = "";
};

// Hilfsfunktionen
const formatDate = (dateString) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    return new Intl.DateTimeFormat("de-DE", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
};

const getTypeName = (typeId) => {
    return entityTypes[typeId] || typeId;
};

const getWikidataUrl = (id) => {
    return `https://www.wikidata.org/wiki/${id}`;
};

// Komponente initialisieren
onMounted(() => {
    loadEntries();
});
</script>

<style scoped>
.font-family-special-elite {
    font-family: "Special Elite" !important;
}
</style>
