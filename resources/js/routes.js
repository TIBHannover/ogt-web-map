const Welcome = () => import("./components/Welcome.vue");
const Map = () => import("./components/Map.vue");
const Project = () => import("./components/Project.vue");
const Glossary = () => import("./components/Glossary.vue");
const Charts = () => import("./components/Charts.vue");
const Counts = () => import("./components/Counts.vue");
// const Entry = () => import('./components/Entry.vue');
const Bibliography = () => import("./components/Bibliography.vue");
const Collaboration = () => import("./components/Collaboration.vue");

export const routes = [
    {
        name: "welcome",
        path: "/welcome",
        component: Welcome,
    },
    {
        name: "project",
        path: "/project",
        component: Project,
    },
    {
        name: "glossary",
        path: "/glossary",
        component: Glossary,
    },
    {
        name: "map",
        path: "/map",
        component: Map,
    },
    {
        name: "charts",
        path: "/charts",
        component: Charts,
    },
    {
        name: "counts",
        path: "/counts",
        component: Counts,
    },
    /* {
        name: 'entry',
        path: '/entry',
        component: Entry,
    }, */
    {
        name: "bibliography",
        path: "/bibliography",
        component: Bibliography,
    },
    {
        name: "collaboration",
        path: "/collaboration",
        component: Collaboration,
    },
    { path: "/:catchAll(.*)", redirect: "/welcome" },
];
