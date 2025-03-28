<template>
    <v-app>
        <navigation-sidebar
            @isMenuDisplayed="setIsMenuDisplayed"
        ></navigation-sidebar>
        <v-main class="bg-grey-lighten-3">
            <router-view :isMenuDisplayed="isMenuDisplayed"></router-view>
        </v-main>
    </v-app>
</template>

<script setup>
import { ref, provide } from "vue";
import NavigationSidebar from "./navigation/NavigationSidebar";

// Reactive state
const isMenuDisplayed = ref(false);

// Provide menu state globally
provide("isMenuDisplayed", isMenuDisplayed);

// Methods
const setIsMenuDisplayed = (value) => {
    isMenuDisplayed.value = value;
};
</script>

<style></style>
