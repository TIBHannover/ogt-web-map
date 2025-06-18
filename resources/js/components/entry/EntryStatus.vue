<template>
    <v-container class="status-container">
        <v-card class="status-card" v-if="entryData">
            <v-card-title class="status-title">
                <h1>Status: {{ entryData.title }}</h1>
            </v-card-title>
            
            <v-card-text>
                <div class="status-info">
                    <div class="status-section">
                        <h2>Eintragsinformationen</h2>
                        <div class="status-detail">
                            <span class="label">Titel:</span>
                            <span class="value">{{ entryData.title }}</span>
                        </div>
                        <div class="status-detail">
                            <span class="label">Typ:</span>
                            <span class="value">{{ getTypeLabel(entryData.type) }}</span>
                        </div>
                        <div class="status-detail">
                            <span class="label">Eingereicht am:</span>
                            <span class="value">{{ formatDate(entryData.created_at) }}</span>
                        </div>
                    </div>

                    <div class="status-section">
                        <h2>Aktueller Status</h2>
                        <div class="status-detail">
                            <span class="label">Status:</span>
                            <span class="status-badge" :class="getStatusClass(entryData.status)">
                                {{ getStatusLabel(entryData.status) }}
                            </span>
                        </div>
                        <div class="status-detail" v-if="entryData.status_updated_at">
                            <span class="label">Letzte Aktualisierung:</span>
                            <span class="value">{{ formatDate(entryData.status_updated_at) }}</span>
                        </div>
                        <div class="status-detail" v-if="entryData.status === 'approved' && entryData.wikidata_id">
                            <span class="label">Wikidata ID:</span>
                            <span class="value">
                                <a :href="getWikidataUrl(entryData.wikidata_id)" target="_blank">
                                    {{ entryData.wikidata_id }}
                                    <v-icon small>mdi-open-in-new</v-icon>
                                </a>
                            </span>
                        </div>
                        <div class="status-detail" v-if="entryData.comment">
                            <span class="label">Kommentar:</span>
                            <span class="value">{{ entryData.comment }}</span>
                        </div>
                    </div>

                    <div class="status-section" v-if="entryData.status_history && entryData.status_history.length">
                        <h2>Status Verlauf</h2>
                        <div class="status-timeline">
                            <div class="timeline-item" v-for="(item, index) in entryData.status_history" :key="index">
                                <div class="timeline-icon" :class="getStatusClass(item.status)"></div>
                                <div class="timeline-content">
                                    <h3>{{ getStatusLabel(item.status) }}</h3>
                                    <p class="timeline-date">{{ formatDate(item.date) }}</p>
                                    <p v-if="item.comment">{{ item.comment }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </v-card-text>

            <v-card-actions>
                <v-btn color="primary" @click="goToHome">Zurück zur Startseite</v-btn>
                <v-btn color="secondary" @click="addAnother">Weiteren Eintrag hinzufügen</v-btn>
            </v-card-actions>
        </v-card>

        <v-card class="status-card error-card" v-else-if="error">
            <v-card-title class="status-title">
                <h1>Fehler</h1>
            </v-card-title>
            <v-card-text>
                <p>{{ error }}</p>
            </v-card-text>
            <v-card-actions>
                <v-btn color="primary" @click="goToHome">Zurück zur Startseite</v-btn>
            </v-card-actions>
        </v-card>

        <v-card class="status-card" v-else>
            <v-card-text class="text-center">
                <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
                <p class="mt-4">Lade Eintragsdaten...</p>
            </v-card-text>
        </v-card>
    </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const props = defineProps({
    tokenId: {
        type: String,
        required: true
    }
});

const router = useRouter();
const entryData = ref(null);
const error = ref(null);
const isLoading = ref(true);

onMounted(async () => {
    try {
        if (!props.tokenId) {
            error.value = "Keine Eintrags-ID gefunden. Bitte überprüfen Sie den Link.";
            return;
        }
        
        const response = await axios.get(`/api/entry-status/${props.tokenId}`);
        
        if (response.data.success) {
            entryData.value = response.data.data;
        } else {
            error.value = response.data.message || "Ein Fehler ist aufgetreten beim Laden der Eintragsdaten.";
        }
    } catch (err) {
        console.error("Fehler beim Laden des Eintragsstatus:", err);
        error.value = "Ein Fehler ist aufgetreten beim Laden der Eintragsdaten. Bitte versuchen Sie es später erneut.";
    } finally {
        isLoading.value = false;
    }
});

const formatDate = (dateString) => {
    if (!dateString) return 'k.A.';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
};

const getTypeLabel = (typeCode) => {
    const types = {
        'Q6983405': 'Ereignis',
        'Q5003624': 'Erinnerungsort',
        'Q108047567': 'Staatspolizeistelle',
        'Q108047541': 'Staatspolizeiaußenstelle',
        'Q277565': 'Arbeitserziehungslager',
        'Q40357': 'Gefängnis'
    };
    return types[typeCode] || typeCode;
};

const getStatusLabel = (status) => {
    const statusLabels = {
        'pending': 'Ausstehend',
        'in_review': 'In Prüfung',
        'revision_needed': 'Überarbeitung nötig',
        'approved': 'Genehmigt',
        'rejected': 'Abgelehnt',
        'wikidata_submitted': 'An Wikidata übermittelt',
        'wikidata_published': 'In Wikidata veröffentlicht'
    };
    return statusLabels[status] || status;
};

const getStatusClass = (status) => {
    const statusClasses = {
        'pending': 'status-pending',
        'in_review': 'status-in-review',
        'revision_needed': 'status-revision',
        'approved': 'status-approved',
        'rejected': 'status-rejected',
        'wikidata_submitted': 'status-wikidata-submitted',
        'wikidata_published': 'status-wikidata-published'
    };
    return statusClasses[status] || '';
};

const getWikidataUrl = (wikidataId) => {
    return `https://www.wikidata.org/wiki/${wikidataId}`;
};

const goToHome = () => {
    router.push('/');
};

const addAnother = () => {
    router.push('/entry');
};
</script>

<style scoped>
.status-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 80vh;
    padding: 2rem;
    background-color: #f5f5f5;
}

.status-card {
    max-width: 900px;
    width: 100%;
    margin: 2rem auto;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.status-title {
    background-color: #f0f4f8;
    border-bottom: 1px solid #e0e4e8;
    padding: 1.5rem;
}

.status-title h1 {
    font-size: 1.5rem;
    color: #1976d2;
    margin: 0;
}

.status-info {
    padding: 1rem 0;
}

.status-section {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #e0e4e8;
}

.status-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.status-section h2 {
    font-size: 1.25rem;
    color: #333;
    margin-bottom: 1rem;
}

.status-detail {
    display: flex;
    margin-bottom: 0.75rem;
    line-height: 1.5;
}

.status-detail .label {
    min-width: 180px;
    font-weight: 600;
    color: #555;
}

.status-detail .value {
    flex: 1;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-weight: 600;
    color: white;
}

.status-pending {
    background-color: #ffa726;
}

.status-in-review {
    background-color: #29b6f6;
}

.status-revision {
    background-color: #ef5350;
}

.status-approved {
    background-color: #66bb6a;
}

.status-rejected {
    background-color: #e53935;
}

.status-wikidata-submitted {
    background-color: #7e57c2;
}

.status-wikidata-published {
    background-color: #4caf50;
}

.error-card {
    text-align: center;
}

/* Timeline styles */
.status-timeline {
    margin-top: 1.5rem;
    position: relative;
}

.status-timeline:before {
    content: '';
    position: absolute;
    top: 0;
    left: 15px;
    height: 100%;
    width: 2px;
    background: #e0e4e8;
}

.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
    padding-left: 40px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-icon {
    position: absolute;
    left: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: 4px solid white;
    background-color: #bbdefb;
}

.timeline-content {
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.timeline-content h3 {
    font-size: 1rem;
    margin-top: 0;
    margin-bottom: 0.5rem;
}

.timeline-date {
    font-size: 0.9rem;
    color: #666;
    margin-top: 0;
    margin-bottom: 0.5rem;
}

@media (max-width: 600px) {
    .status-container {
        padding: 1rem;
    }
    
    .status-detail {
        flex-direction: column;
    }
    
    .status-detail .label {
        min-width: auto;
        margin-bottom: 0.25rem;
    }
}
</style>