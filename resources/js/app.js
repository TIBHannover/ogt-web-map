import('./bootstrap');

import '@mdi/font/css/materialdesignicons.css';
import '@fontsource/roboto/latin.css';
import '@fontsource/special-elite/latin.css';

import { createApp } from 'vue'
import { createWebHistory, createRouter } from "vue-router";
import VueAxios from 'vue-axios';
import axios from 'axios';
import {routes} from './routes';
import 'vuetify/styles';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import {itemDescriptions} from '../lang/de/itemDescriptions';

import App from './components/App.vue';

const router = createRouter({
    history: createWebHistory('/ogt/'),  // createWebHistory für den "history" mode verwenden
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (to.hash) {
            return {
                behavior: 'smooth',
                selector: to.hash,
            };
        }
    },
});

const vuetify = createVuetify({
    components,
    directives,
    theme: {
        themes: {
            light: {
                anchor: 'black',
            },
        },
    },
});

const app = createApp(App);
app.config.globalProperties.$ogtGlobals = {
    isTestingEnv: process.env.MIX_APP_ENV == 'testing' ? true : false,
    texts: {
        itemDescriptions: itemDescriptions,
    },
    proxyPath: process.env.MIX_PROXY_PATH ?? '',
};

app.use(VueAxios, axios);
app.axios.defaults.baseURL = process.env.MIX_BASE_URL ?? '';
app.use(router);
app.use(vuetify);
app.mount('#app');
