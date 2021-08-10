const Welcome = () => import('./components/Welcome.vue');
const Map = () => import('./components/Map.vue');

export const routes = [
    {
        name: 'welcome',
        path: '/',
        component: Welcome,
    },
    {
        name: 'map',
        path: '/map',
        component: Map,
    },
    {path: '*', redirect: '/'}
];
