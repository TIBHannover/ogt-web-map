<template>
    <v-container id="container" class="mb-110" :class="{ 'pa-md-4': mdAndUp }">
        <!-- header -->
        <v-row
            :style="{
                'padding-left':
                    freeClientWidth < 75 && !isMenuDisplayed
                        ? computedPadding
                        : 0,
            }"
        >
            <v-col>
                <h1
                    class="font-family-special-elite font-weight-bold hyphens-auto py-4 text-h4 text-md-h3"
                    lang="de"
                    :class="{ 'text-center': ogtGlobals.isMobile }"
                >
                    Touren
                </h1>

                <div class="tour-section">
                    <h2>Leitfaden für Touren</h2>
                    <p>
                        Hier finden Sie Informationen, wie Sie selbst eine
                        thematische Stadtführung zu historischen Orten der
                        Gestapo-Verfolgung planen und durchführen können.
                    </p>
                    <div class="download-section">
                        <a
                            href="./resources/Leitfaden_Touren_Gestapo_Terror_Orte_compressed.pdf"
                            class="download-button"
                            download
                        >
                            <i class="fas fa-download"></i> Leitfaden für Touren
                            herunterladen
                        </a>
                    </div>
                </div>

                <!--
            <div class="tour-section">
              <h2>Verfügbare Touren</h2>
              <p>
                Entdecken Sie unsere thematischen Stadtführungen zu historischen Orten der Gestapo-Verfolgung.
                Die Touren bieten eine einzigartige Möglichkeit, die Geschichte vor Ort zu erfahren und zu verstehen.
              </p>
              </div>-->
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup>
import { useDisplay } from "vuetify";
import {
    ref,
    inject,
    defineProps,
    onMounted,
    onUnmounted,
    computed,
} from "vue";
import TableOfContentsSidebar from "./navigation/TableOfContentsSidebar";

// Get display breakpoints and use them in the template
const { mdAndUp } = useDisplay();

// Global state for consistent access across components
const ogtGlobals = inject("ogtGlobals");

// Props
const props = defineProps({
    isMenuDisplayed: Boolean,
});
// Reactive references
const freeClientWidth = ref(document.documentElement.clientWidth);

// Used in template for dynamic padding calculation
const computedPadding = computed(() => {
    return freeClientWidth.value < 75 && !props.isMenuDisplayed
        ? `${75 - freeClientWidth.value}px`
        : "0";
});

const setFreeClientWidth = () => {
    const container = document.getElementById("container");
    if (container) {
        freeClientWidth.value =
            (document.documentElement.clientWidth - container.clientWidth) / 2;
    }
};

// Lifecycle hooks
onMounted(() => {
    setFreeClientWidth();
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

.font-size-29 {
    font-size: 1.8125rem !important;
}
.tours-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.tour-section {
    margin-bottom: 3rem;
    background-color: #f9f9f9;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.download-section {
    margin-top: 1.5rem;
}

.download-button {
    display: inline-block;
    padding: 0.8rem 1.5rem;
    background-color: #3490dc;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-weight: bold;
    transition: background-color 0.3s;
}

.download-button:hover {
    background-color: #2779bd;
}
</style>
