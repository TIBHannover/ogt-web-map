<template>
    <div class="thankyou-container">
        <div class="thankyou-card">
            <h1 class="title">Vielen Dank für Ihren Beitrag!</h1>
            <div class="icon-container">
                <v-icon size="x-large" color="success">mdi-check-circle</v-icon>
            </div>
            <p class="message">
                Ihr Eintrag wurde erfolgreich übermittelt und wird nun von
                unserem Team geprüft. Nach erfolgreicher Prüfung wird er auf der
                Karte erscheinen.
            </p>
            <p class="sub-message">
                Dieser Prozess kann einige Tage dauern. Wir danken Ihnen für
                Ihre Geduld und Ihren wertvollen Beitrag zum Projekt.
            </p>
            <div class="actions">
                <v-btn color="primary" @click="goToHome"
                    >Zurück zur Startseite</v-btn
                >
                <v-btn color="secondary" @click="addAnother"
                    >Weiteren Eintrag hinzufügen</v-btn
                >
            </div>
        </div>
    </div>
</template>

<script setup>
import { useRouter } from "vue-router";

const router = useRouter();
const props = defineProps({
    requestId: {
        type: String,
        default: null,
    },
});

const goToHome = () => {
    router.push("/");
};

const addAnother = () => {
    router.push("/entry");
};
</script>

<style scoped>
.thankyou-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 80vh;
    padding: 2rem;
    background-color: #f5f5f5;
}

.thankyou-card {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    padding: 3rem;
    max-width: 800px;
    text-align: center;
}

.title {
    font-size: 2rem;
    color: #1976d2;
    margin-bottom: 1.5rem;
}

.icon-container {
    margin: 2rem 0;
}

.message {
    font-size: 1.2rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.sub-message {
    color: #666;
    margin-bottom: 2rem;
}

.actions {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-top: 2rem;
}

@media (max-width: 600px) {
    .thankyou-card {
        padding: 2rem 1rem;
    }

    .actions {
        flex-direction: column;
        gap: 0.5rem;
    }

    .title {
        font-size: 1.5rem;
    }

    .message {
        font-size: 1rem;
    }
}
</style>
