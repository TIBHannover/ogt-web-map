const Welcome = () => import('./components/Welcome.vue');
const Map = () => import('./components/Map.vue');
const Project = () => import('./components/Project.vue');
const Charts = () => import('./components/Charts.vue');
const Database = () => import('./components/charts/Database.vue');
const Photos = () => import('./components/charts/Photos.vue');

export const routes = [
    {
        name: 'welcome',
        path: '/',
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
        name: 'charts-database',
        path: '/charts/database',
        component: Database,
    },
    {
        name: 'charts-photos',
        path: '/charts/photos',
        component: Photos,
    },
    {path: '*', redirect: '/'},
];
