require('./bootstrap');

import '@mdi/font/css/materialdesignicons.css';
import Vue from 'vue';
import App from './components/App.vue';
import VueRouter from 'vue-router';
import VueAxios from 'vue-axios';
import axios from 'axios';
import {routes} from './routes';
import Vuetify from 'vuetify';
import {itemDescriptions} from '../lang/de/itemDescriptions';

window.Vue = Vue;

Vue.prototype.$ogtGlobals = {
    isProductionEnv: process.env.MIX_APP_ENV == 'production' ? true : false,
    texts: {
        itemDescriptions: itemDescriptions,
    },
    // reverse proxy sub path, required to load resources within Vue files
    proxyPath: process.env.MIX_PROXY_PATH ?? '',
};

Vue.use(VueRouter);
Vue.use(VueAxios, axios);
Vue.use(Vuetify);

Vue.axios.defaults.baseURL = process.env.MIX_BASE_URL ?? '';

const router = new VueRouter({
    base: '/ogt/',
    mode: 'history',
    routes: routes,
});

const app = new Vue({
    el: '#app',
    render: h => h(App),
    router: router,
    vuetify: new Vuetify({
        theme: {
            themes: {
                light: {
                    anchor: 'black', // defaults to 'primary'
                },
            },
        },
    }),
});
