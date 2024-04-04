<template>
    <v-container id="container" class="mb-110">
        <v-row :style="{
            'padding-left': (freeClientWidth < 75 && ! isMenuDisplayed) ? ((75 - freeClientWidth) + 'px') : 0
        }">
            <v-col>
                <h1 class="font-family-special-elite font-weight-bold hyphens-auto py-4 text-h4 text-md-h3" lang="de">
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
                            <input v-model="formsType" name="type" type="radio" value="Q6983405">
                            <label for="Q6983405">Ereignis</label>
                        </div>
                        <div>
                            <input v-model="formsType" name="type" type="radio" value="Q5003624">
                            <label for="Q5003624">Erinnerungsort</label>
                        </div>
                        <div>
                            <input v-model="formsType" name="type" type="radio" value="Q108047567">
                            <label for="Q108047567">Staatspolizeistelle</label>
                        </div>
                        <div>
                            <input v-model="formsType" name="type" type="radio" value="Q108047541">
                            <label for="Q108047541">Staatspolizeiaußenstelle</label>
                        </div>
                        <div>
                            <input v-model="formsType" name="type" type="radio" value="Q277565">
                            <label for="Q277565">Arbeitserziehungslager</label>
                        </div>
                        <div>
                            <input v-model="formsType" name="type" type="radio" value="Q40357">
                            <label for="Q40357">Gefängnis</label>
                        </div>
                    </fieldset>
                    <label for="title">Titel</label>
                    <input v-model="formsTitle" name="title" type="text" maxlength="250" placeholder="z.B. Novemberpogrom 1938 in Osnabrück">
                    <label for="description">Kurzbeschreibung</label>
                    <textarea v-model="formsDescription" name="description" maxlength="750" placeholder="z.B. vom NS-Regime gelenkte Gewaltmaßnahmen gegen Juden in Osnabrück."></textarea>
                    <label for="location">Koordinaten (DMS)</label>
                    <input v-model="formsLocation" name="location" type="text" placeholder="z.B. 52°8'41.14&quot;N, 9°56'55.61&quot;E">
                    <button class="button" type="button" @click="submit">Anlegen</button>
                    <span class="error">{{ error }}</span>
                </form>
                <br><br><br><br><!-- Dev banner offset (temp) -->
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
import glossaryData from '../data/glossaryData';

export default {
    name: 'Forms',
    props: ['isMenuDisplayed'],
    data() {
        return {
            freeClientWidth: document.documentElement.clientWidth,
            error: null,
            formsType: "Q6983405",
            formsTitle: null,
            formsDescription: null,
            formsLocation: null,
        };
    },
    created() {
        window.addEventListener('resize', this.setFreeClientWidth);
    },
    destroyed() {
        window.removeEventListener('resize', this.setFreeClientWidth);
    },
    mounted() {
        
    },
    methods: {
        /**
         * Determine width of free space between content and client border.
         *
         * @param event
         */
        setFreeClientWidth(event) {
            const container = document.getElementById('container');

            this.freeClientWidth = (document.documentElement.clientWidth - container.clientWidth) / 2;
        },
        submit() {
            this.error = null;

            const writeObj = {
                // default properties
                P17: "Q7318",           // country: Nazi Germany
                P31: "Q106996250"       // instance of: Place of Gestapo Terror
            };
            const missingFields = [];

            if(!this.formsType) {
                missingFields.push("Typ");
            } else {
                writeObj.P31 = this.formsType;
            }

            if(!(this.formsTitle || "").trim()) {
                missingFields.push("Titel");
            } else {
                writeObj.label = this.formsTitle.trim();
            }

            if(!(this.formsDescription || "").trim()) {
                missingFields.push("Kurzbeschreibung");
            } else {
                writeObj.description= this.formsDescription.trim();
            }

            if(!(this.formsLocation || "").trim()) {
                missingFields.push("Koordinaten");
            } else {
                if(!/\d+°\d+'\d+.\d+"N, *\d+°\d+'\d+.\d+"E/.test(this.formsLocation.trim())) {
                    this.error = "Inkorrektes Korrdinatenformat. <lat-degrees>°<lat-minutes>'<lat-seconds>.<lat-seconds-fraction>\"N, *<long-degrees>°<lat-minutes>'<long-seconds>.<long-seconds-fraction>\"E";
                }

                writeObj.P625 = this.formsLocation.trim().replace(/, */, ", ");
            }

            if(missingFields.length) {
                this.error = `Fehlende Felder: ${missingFields.join(", ")}`;
                return;
            }

            console.log("TODO: Submit", writeObj);
            // (Re-)investigate poorly supported Wikidata REST API
        }
    },
};
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
.forms input, .forms textarea {
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