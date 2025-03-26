<template>
    <v-container id="container" class="mb-110">
        <v-row
            :style="{
                'padding-left':
                    freeClientWidth < 75 && !isMenuDisplayed
                        ? 75 - freeClientWidth + 'px'
                        : 0,
            }"
        >
            <v-col>
                <h1
                    class="font-family-special-elite font-weight-bold hyphens-auto py-4 text-h4 text-md-h3"
                    lang="de"
                >
                    Eintrag anlegen
                </h1>
            </v-col>
        </v-row>

        <v-row>
            <v-col>
                <form class="forms">
                    <fieldset v-model="formsType">
                        <legend for="type">Eintrag-Typ</legend>
                        <div>
                            <input
                                v-model="formsType"
                                name="type"
                                type="radio"
                                value="Q6983405"
                            />
                            <label for="Q6983405">Ereignis</label>
                        </div>
                        <div>
                            <input
                                v-model="formsType"
                                name="type"
                                type="radio"
                                value="Q5003624"
                            />
                            <label for="Q5003624">Erinnerungsort</label>
                        </div>
                        <div>
                            <input
                                v-model="formsType"
                                name="type"
                                type="radio"
                                value="Q108047567"
                            />
                            <label for="Q108047567">Staatspolizeistelle</label>
                        </div>
                        <div>
                            <input
                                v-model="formsType"
                                name="type"
                                type="radio"
                                value="Q108047541"
                            />
                            <label for="Q108047541"
                                >Staatspolizeiaußenstelle</label
                            >
                        </div>
                        <div>
                            <input
                                v-model="formsType"
                                name="type"
                                type="radio"
                                value="Q277565"
                            />
                            <label for="Q277565">Arbeitserziehungslager</label>
                        </div>
                        <div>
                            <input
                                v-model="formsType"
                                name="type"
                                type="radio"
                                value="Q40357"
                            />
                            <label for="Q40357">Gefängnis</label>
                        </div>
                    </fieldset>
                    <label for="title">Titel</label>
                    <input
                        v-model="formsTitle"
                        name="title"
                        type="text"
                        maxlength="250"
                        placeholder="z.B. Novemberpogrom 1938 in Osnabrück"
                    />
                    <label for="description">Kurzbeschreibung</label>
                    <textarea
                        v-model="formsDescription"
                        name="description"
                        maxlength="750"
                        placeholder="z.B. vom NS-Regime gelenkte Gewaltmaßnahmen gegen Juden in Osnabrück."
                    ></textarea>
                    <label for="location">Koordinaten (DMS)</label>
                    <input
                        v-model="formsLocation"
                        name="location"
                        type="text"
                        placeholder="z.B. 52°8'41.14&quot;N, 9°56'55.61&quot;E"
                    />
                    <button class="button" type="button" @click="submit">
                        Anlegen
                    </button>
                    <span class="bg-error">{{ error }}</span>
                </form>
                <br /><br /><br /><br />
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import glossaryData from "../data/glossaryData";

// Props
const props = defineProps({
    isMenuDisplayed: Boolean,
});

// Reactive references
const freeClientWidth = ref(document.documentElement.clientWidth);
const error = ref(null);
const formsType = ref("Q6983405");
const formsTitle = ref(null);
const formsDescription = ref(null);
const formsLocation = ref(null);

// Methods
const setFreeClientWidth = () => {
    const container = document.getElementById("container");
    freeClientWidth.value =
        (document.documentElement.clientWidth - container.clientWidth) / 2;
};

const submit = () => {
    error.value = null;

    const writeObj = {
        // default properties
        P17: "Q7318", // country: Nazi Germany
        P31: "Q106996250", // instance of: Place of Gestapo Terror
    };
    const missingFields = [];

    if (!formsType.value) {
        missingFields.push("Typ");
    } else {
        writeObj.P31 = formsType.value;
    }

    if (!(formsTitle.value || "").trim()) {
        missingFields.push("Titel");
    } else {
        writeObj.label = formsTitle.value.trim();
    }

    if (!(formsDescription.value || "").trim()) {
        missingFields.push("Kurzbeschreibung");
    } else {
        writeObj.description = formsDescription.value.trim();
    }

    if (!(formsLocation.value || "").trim()) {
        missingFields.push("Koordinaten");
    } else {
        if (
            !/\d+°\d+'\d+.\d+"N, *\d+°\d+'\d+.\d+"E/.test(
                formsLocation.value.trim(),
            )
        ) {
            error.value =
                "Inkorrektes Korrdinatenformat. <lat-degrees>°<lat-minutes>'<lat-seconds>.<lat-seconds-fraction>\"N, *<long-degrees>°<lat-minutes>'<long-seconds>.<long-seconds-fraction>\"E";
        }

        writeObj.P625 = formsLocation.value.trim().replace(/, */, ", ");
    }

    if (missingFields.length) {
        error.value = `Fehlende Felder: ${missingFields.join(", ")}`;
        return;
    }

    console.log("TODO: Submit", writeObj);
    // (Re-)investigate poorly supported Wikidata REST API
};

// Lifecycle hooks
onMounted(() => {
    window.addEventListener("resize", setFreeClientWidth);
});

onUnmounted(() => {
    window.removeEventListener("resize", setFreeClientWidth);
});
</script>

<style scoped>
.font-family-special-elite {
    font-family: "Special Elite" !important;
}

.forms {
    --max-width: 35rem;

    display: flex;
    flex-direction: column;
    align-items: flex-start;
    max-width: var(--max-width);
}
.forms > *:not(:last-child) {
    margin-bottom: 1rem;
}
.forms input,
.forms textarea {
    padding: 0.5rem 0.75rem;
    width: 100%;
    background-color: white;
}
.forms textarea {
    min-width: var(--max-width);
    min-height: 8.5rem;
    resize: none;
}
.forms fieldset {
    display: block;
    width: 100%;
    border-color: transparent;
}
.forms fieldset div {
    margin-top: 0.75rem;
}
.forms fieldset div {
    display: flex;
    flex-direction: row;
    align-items: center;
}
.forms fieldset div input {
    margin-right: 0.75rem;
    width: 1rem;
    height: 1rem;
}
.forms fieldset div label {
    flex: 1 0 0;
}
.forms button {
    align-self: flex-end;
    margin-top: 1rem;
}
.forms .error {
    margin: 1rem 0;
    background-color: transparent !important;
    color: red;
}
</style>
