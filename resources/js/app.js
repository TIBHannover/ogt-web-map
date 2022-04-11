require('./bootstrap');

import Vue from 'vue';
import App from './components/App.vue';
import VueRouter from 'vue-router';
import VueAxios from 'vue-axios';
import axios from 'axios';
import {routes} from './routes';
import Vuetify from 'vuetify';

window.Vue = Vue;

Vue.use(VueRouter);
Vue.use(VueAxios, axios);
Vue.use(Vuetify);

Vue.axios.defaults.baseURL = process.env.MIX_BASE_URL ?? '';
Vue.prototype.$ogtGlobals = {
    proxyPath: process.env.MIX_PROXY_PATH ?? '',
};

const router = new VueRouter({
    base: '/ogt/',
    mode: 'history',
    routes: routes,
});

const app = new Vue({
    el: '#app',
    render: h => h(App),
    router: router,
    vuetify: new Vuetify(),
});
