const Welcome = () => import("./components/Welcome.vue");
const Map = () => import("./components/Map.vue");
const Project = () => import("./components/Project.vue");
const Glossary = () => import("./components/Glossary.vue");
const Charts = () => import("./components/Charts.vue");
const Counts = () => import("./components/Counts.vue");
// const Entry = () => import('./components/Entry.vue');
const Bibliography = () => import("./components/Bibliography.vue");
const Workshops = () => import("./components/Workshops.vue");

export const routes = [
    {
        name: "welcome",
        path: "/starteite",
        component: Welcome,
    },
    {
        name: "project",
        path: "/projekt",
        component: Project,
        meta: {
            title: "Projektübersicht: Gestapo. Terror. Orte",
        },
    },
    {
        name: "glossary",
        path: "/glossar",
        component: Glossary,
        meta: {
            title: "Glossar: Gestapo. Terror. Orte",
        },
    },
    {
        name: "map",
        path: "/map",
        component: Map,
        meta: {
            title: "Kartenansicht: Gestapo. Terror. Orte",
        },
    },
    {
        name: "charts",
        path: "/charts",
        component: Charts,
        meta: {
            title: "Datenvisualisierung: Gestapo. Terror. Orte",
        },
    },
    {
        name: "counts",
        path: "/counts",
        component: Counts,
        meta: {
            title: "Zahlen: Gestapo. Terror. Orte",
        },
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
        meta: {
            title: "Literatur: Gestapo. Terror. Orte",
        },
    },
    {
        name: "workshops",
        path: "/werkstaette",
        component: Workshops,
        meta: {
            title: "Werkstätte für historische Daten: Gestapo. Terror. Orte",
        },
    },
    { path: "/:catchAll(.*)", redirect: "/starteite" },
];
