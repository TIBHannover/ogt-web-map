const Welcome = () => import('./components/Welcome.vue');
const Map = () => import('./components/Map.vue');
const Project = () => import('./components/Project.vue');
const Glossary = () => import('./components/Glossary.vue');
const Glossary2 = () => import('./components/Glossary2.vue');
const Charts = () => import('./components/Charts.vue');
const Charts2 = () => import('./components/Charts2.vue');
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
        name: 'glossary',
        path: '/glossary',
        component: Glossary,
    },
    {
        name: 'glossary-new',
        path: '/glossary-new',
        component: Glossary2,
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
        name: 'charts-new',
        path: '/charts-new',
        component: Charts2,
    },
    {
        name: 'collaboration',
        path: '/collaboration',
        component: Collaboration,
    },
    {path: '*', redirect: '/welcome'},
];
