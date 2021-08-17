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

const router = new VueRouter({
    mode: 'history',
    routes: routes,
});

const app = new Vue({
    el: '#app',
    render: h => h(App),
    router: router,
    vuetify: new Vuetify(),
});
