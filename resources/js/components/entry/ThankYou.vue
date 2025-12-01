<template>
    <div class="thankyou-container">
        <div class="thankyou-card">
            <h1 class="title">Vielen Dank für deinen Beitrag!</h1>
            <div class="icon-container">
                <v-icon size="x-large" color="success">mdi-check-circle</v-icon>
            </div>
            <p class="message">
                Dein Eintrag wurde erfolgreich übermittelt und wird nun von
                unserem Team geprüft. Nach erfolgreicher Prüfung wird er auf der
                Karte erscheinen.
            </p>
            <p class="sub-message">
                Dieser Prozess kann einige Tage dauern. Wir danken dir für deine
                Geduld und deinen wertvollen Beitrag zum Projekt.
            </p>

            <div class="status-box">
                <h2>Status deines Eintrags</h2>
                <p>
                    Aktueller Status:
                    <span class="status-label">Ausstehend</span>
                </p>
                <p v-if="statusToken">
                    Du kannst den aktuellen Status Ihres Eintrags jederzeit
                    unter folgendem Link prüfen:
                </p>
                <div v-if="statusToken" class="status-link">
                    <a :href="statusUrl" @click.prevent="goToStatusPage">{{
                        statusUrl
                    }}</a>
                    <button
                        class="copy-button"
                        @click="copyStatusLink"
                        title="Link kopieren"
                    >
                        <v-icon>mdi-content-copy</v-icon>
                    </button>
                </div>

                <!-- Email notification form -->
                <!--
                <div v-if="statusToken" class="email-notification-form">
                    <h3>Per E-Mail über den Status informiert werden</h3>
                    <p>
                        Erhalte den Status-Link per E-Mail und werde
                        benachrichtigt, wenn dein Eintrag veröffentlicht wird.
                    </p>

                    <div class="form-group">
                        <v-text-field
                            v-model="email"
                            label="Ihre E-Mail-Adresse"
                            placeholder="beispiel@email.de"
                            :rules="[rules.required, rules.email]"
                            variant="outlined"
                            density="comfortable"
                        ></v-text-field>
                    </div>

                    <div class="notification-options">
                        <v-checkbox
                            v-model="notifyOnPublish"
                            label="Benachrichtigen Sie mich, wenn mein Eintrag veröffentlicht wird"
                            color="primary"
                        ></v-checkbox>
                    </div>

                    <v-btn
                        :loading="sending"
                        :disabled="!isValidEmail || sending"
                        color="primary"
                        @click="sendStatusEmail"
                    >
                        Status-Link per E-Mail senden
                    </v-btn>

                    <div v-if="emailSent" class="success-message">
                        <v-icon color="success">mdi-check-circle</v-icon>
                        E-Mail wurde erfolgreich gesendet!
                    </div>
                    <div v-if="emailError" class="error-message">
                        <v-icon color="error">mdi-alert-circle</v-icon>
                        {{ emailError }}
                    </div>
                </div>

                <p v-if="statusToken" class="status-tip">
                    <v-icon small color="info">mdi-information-outline</v-icon>
                    Tipp: Speicher diesen Link, um später den Status deines
                    Eintrags zu überprüfen.
                </p>
                <p v-else class="status-tip">
                    <v-icon small color="warning"
                        >mdi-alert-circle-outline</v-icon
                    >
                    Hinweis: Der Status Ihres Eintrags kann derzeit nicht
                    verfolgt werden. Bitte kontaktiere uns bei Fragen.
                </p>
                -->
            </div>
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
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";

const router = useRouter();
const props = defineProps({
    requestId: {
        type: String,
        default: null,
    },
});

const statusToken = ref("");
const copySuccess = ref(false);
const email = ref("");
const notifyOnPublish = ref(true);
const sending = ref(false);
const emailSent = ref(false);
const emailError = ref("");

const rules = {
    required: (value) => !!value || "Erforderlich",
    email: (value) => {
        const pattern =
            /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return pattern.test(value) || "Ungültige E-Mail-Adresse";
    },
};

const isValidEmail = computed(() => {
    return email.value && rules.email(email.value) === true;
});

const statusUrl = computed(() => {
    if (!statusToken.value) return "";
    // Ensure consistent URL format with the EntryStatus component's expectation
    const url = `${window.location.origin}/eintrag-status?id=${statusToken.value}`;
    console.log("Generated status URL:", url);
    return url;
});

onMounted(() => {
    console.log("ThankYou Component mounted, props:", props);
    console.log("Request ID from props:", props.requestId);
    console.log("URL query params:", router.currentRoute.value.query);

    // Priority 1: Check props
    if (props.requestId) {
        statusToken.value = props.requestId;
        console.log("Status token set from props:", statusToken.value);
        // Store token for later use
        localStorage.setItem("entryStatusToken", statusToken.value);
    }
    // Priority 2: Check URL query parameter
    else if (router.currentRoute.value.query.token) {
        statusToken.value = router.currentRoute.value.query.token;
        //console.log('Status token set from URL query:', statusToken.value);
        // Store token for later use
        localStorage.setItem("entryStatusToken", statusToken.value);
    }
    // Additional check for 'id' parameter as fallback
    else if (router.currentRoute.value.query.id) {
        statusToken.value = router.currentRoute.value.query.id;
        console.log(
            "Status token set from URL id parameter:",
            statusToken.value,
        );
        localStorage.setItem("entryStatusToken", statusToken.value);
    }
    // Priority 3: Check localStorage as fallback
    else {
        const savedToken = localStorage.getItem("entryStatusToken");
        if (savedToken) {
            statusToken.value = savedToken;
            console.log(
                "Status token set from localStorage:",
                statusToken.value,
            );
        } else {
            console.log("No token found in props, URL or localStorage");
        }
    }
});

const goToHome = () => {
    router.push("/");
};

const addAnother = () => {
    router.push("/neuer-eintrag");
};

const goToStatusPage = () => {
    if (!statusToken.value) {
        console.error("Cannot navigate to status page: no token available");
        return;
    }

    console.log("Navigating to status page with token:", statusToken.value);
    // Use the same query parameter format that EntryStatus expects
    router.push({
        path: "/eintrag-status",
        query: { id: statusToken.value },
    });
};

const copyStatusLink = () => {
    if (!statusUrl.value) {
        console.error("Cannot copy status link: no URL available");
        alert("Es ist kein Status-Link verfügbar.");
        return;
    }

    console.log("Copying status link to clipboard:", statusUrl.value);
    navigator.clipboard
        .writeText(statusUrl.value)
        .then(() => {
            copySuccess.value = true;
            alert("Link in die Zwischenablage kopiert!");
            setTimeout(() => {
                copySuccess.value = false;
            }, 2000);
        })
        .catch((err) => {
            console.error("Fehler beim Kopieren des Links:", err);
        });
};

const sendStatusEmail = async () => {
    if (!statusToken.value || !isValidEmail.value) {
        return;
    }

    sending.value = true;
    emailError.value = "";
    emailSent.value = false;

    try {
        const response = await fetch("/api/eintrag-status/send-email", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content"),
            },
            body: JSON.stringify({
                token: statusToken.value,
                email: email.value,
                notify_on_publish: notifyOnPublish.value,
            }),
        });

        const data = await response.json();

        if (data.success) {
            emailSent.value = true;
            // Clear the form or disable it after successful submission
            sending.value = false;
        } else {
            emailError.value = data.message || "Fehler beim Senden der E-Mail.";
            sending.value = false;
        }
    } catch (error) {
        console.error("Email sending error:", error);
        emailError.value =
            "Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.";
        sending.value = false;
    }
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

/* Status box styles */
.status-box {
    background-color: #e8f5e9;
    border: 1px solid #c8e6c9;
    border-radius: 6px;
    padding: 1.5rem;
    margin: 1.5rem 0;
    text-align: left;
}

/* Email notification form */
.email-notification-form {
    background-color: #f1f8e9;
    border: 1px solid #dcedc8;
    border-radius: 6px;
    padding: 1.5rem;
    margin: 1.5rem 0;
}

.email-notification-form h3 {
    font-size: 1.1rem;
    margin-top: 0;
    margin-bottom: 0.5rem;
    color: #2e7d32;
}

.form-group {
    margin-bottom: 1rem;
}

.notification-options {
    margin-bottom: 1.5rem;
}

.success-message {
    margin-top: 1rem;
    color: #2e7d32;
    display: flex;
    align-items: center;
    gap: 8px;
}

.error-message {
    margin-top: 1rem;
    color: #d32f2f;
    display: flex;
    align-items: center;
    gap: 8px;
}

.status-box h2 {
    font-size: 1.3rem;
    margin-top: 0;
    margin-bottom: 1rem;
    color: #2e7d32;
}

.status-label {
    display: inline-block;
    background-color: #ffa726;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-weight: 600;
}

.status-link {
    display: flex;
    align-items: center;
    background-color: #f1f8e9;
    border: 1px solid #dcedc8;
    border-radius: 4px;
    padding: 0.75rem;
    margin: 1rem 0;
    word-break: break-all;
}

.status-link a {
    color: #1976d2;
    text-decoration: none;
    flex: 1;
}

.status-link a:hover {
    text-decoration: underline;
}

.copy-button {
    background: none;
    border: none;
    cursor: pointer;
    color: #757575;
    margin-left: 8px;
    padding: 4px;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.copy-button:hover {
    background-color: #eeeeee;
}

.status-tip {
    font-size: 0.9rem;
    color: #666;
    margin-top: 1rem;
    display: flex;
    align-items: center;
    gap: 8px;
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

    .status-box {
        padding: 1rem;
    }
}
</style>
