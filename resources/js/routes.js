const Welcome = () => import('./components/Welcome.vue');
const Map = () => import('./components/Map.vue');
const Project = () => import('./components/Project.vue');
const Charts = () => import('./components/Charts.vue');
const Collaboration = () => import('./components/Collaboration.vue');

export const routes = [
    {
        name: 'welcome',
        path: '/welcome',
        component: Welcome,
    },
    {
        name: 'project',
        path: '/project',
        component: Project,
    },
    {
        name: 'map',
        path: '/map',
        component: Map,
    },
    {
        name: 'charts',
        path: '/charts',
        component: Charts,
    },
    {
        name: 'collaboration',
        path: '/collaboration',
        component: Collaboration,
    },
    {path: '*', redirect: '/welcome'},
];
