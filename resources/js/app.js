import("./bootstrap");

import "@mdi/font/css/materialdesignicons.css";
import "@fontsource/roboto/latin.css";
import "@fontsource/special-elite/latin.css";

import { createApp } from "vue";
import { createWebHistory, createRouter } from "vue-router";
import VueAxios from "vue-axios";
import axios from "axios";
import { routes } from "./routes";
import "vuetify/styles";
import { createVuetify } from "vuetify";
import * as components from "vuetify/components";
import * as directives from "vuetify/directives";
import { itemDescriptionsWikidata } from "../lang/de/itemDescriptionsWikidata";
import { itemDescriptionsFactgrid } from "../lang/de/itemDescriptionsFactgrid";

// Initialize global config object for app settings
window.ogtConfig = window.ogtConfig || {};
window.ogtConfig.exportTarget =
    window.ogtConfig.exportTarget ||
    process.env.MIX_EXPORT_TARGET ||
    "wikidata";

import App from "./components/App.vue";

const router = createRouter({
    history: createWebHistory("/"), // createWebHistory für den "history" mode verwenden
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (to.hash) {
            return {
                behavior: "smooth",
                selector: to.hash,
            };
        }
    },
});

// Replace page title with title from routes array
router.beforeEach((to, from, next) => {
    const nearestWithTitle = to.matched
        .slice()
        .reverse()
        .find((r) => r.meta && r.meta.title);

    if (nearestWithTitle) {
        document.title = nearestWithTitle.meta.title;
    }
    next();
});

const vuetify = createVuetify({
    components,
    directives,
    theme: {
        defaultTheme: "light",
        light: {
            anchor: "black",
        },
    },
});

const app = createApp(App);
app.provide("ogtGlobals", {
    isTestingEnv: process.env.MIX_APP_ENV == "testing" ? true : false,
    texts: {
        itemDescriptions:
            window.ogtConfig.exportTarget == "factgrid"
                ? itemDescriptionsFactgrid
                : itemDescriptionsWikidata,
    },
    proxyPath: process.env.MIX_PROXY_PATH ?? "",
    exportTarget:
        window.ogtConfig.exportTarget ||
        process.env.MIX_EXPORT_TARGET ||
        "wikidata",
});
app.provide("configLeaflet", window.configLeaflet);

app.use(VueAxios, axios);
app.axios.defaults.baseURL = process.env.MIX_BASE_URL ?? "";
app.use(router);
app.use(vuetify);
app.mount("#app");
