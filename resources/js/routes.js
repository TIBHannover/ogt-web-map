const Welcome = () => import("./components/Welcome.vue");
const Map = () => import("./components/Map.vue");
const Project = () => import("./components/Project.vue");
const Glossary = () => import("./components/Glossary.vue");
const Charts = () => import("./components/Charts.vue");
const Counts = () => import("./components/Counts.vue");
const Entry = () => import("./components/Entry.vue");
const Tours = () => import("./components/Tours.vue");
const ReminderCalendar = () => import("./components/ReminderCalendar.vue");
// Admin-Komponenten werden nicht mehr verwendet - jetzt über PHP implementiert
// const Admin = () => import("./components/admin/WikidataRequestsAdmin.vue");
// const WikidataRequestDetail = () => import("./components/admin/WikidataRequestDetail.vue");
const Bibliography = () => import("./components/Bibliography.vue");
const Workshops = () => import("./components/Workshops.vue");
const Materials = () => import("./components/Materials.vue");
const Forschungsdaten = () => import("./components/ResearchData.vue");
const ThankYou = () => import("./components/entry/ThankYou.vue");
const EntryStatus = () => import("./components/entry/EntryStatus.vue");

export const routes = [
    {
        name: "welcome",
        path: "/starteite",
        component: Welcome,
    },
    {
        name: "thank-you",
        path: "/thank-you",
        component: ThankYou,
        props: true,
        meta: {
            title: "Vielen Dank für Ihren Eintrag",
        },
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
    {
        name: "entry",
        path: "/entry",
        component: Entry,
        meta: {
            title: "Neuen Eintrag anlegen für: Gestapo. Terror. Orte",
        },
    },
    {
        name: "entry-status",
        path: "/entry-status",
        component: EntryStatus,
        props: (route) => ({ tokenId: route.query.id }),
        meta: {
            title: "Eintragstatus ansehen für: Gestapo. Terror. Orte",
        },
    },
    {
        name: "bibliography",
        path: "/literatur",
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
            title: "Werkstätten für historische Daten: Gestapo. Terror. Orte",
        },
    },
    {
        name: "materials",
        path: "/materialien",
        component: Materials,
        meta: {
            title: "Materialien: Gestapo. Terror. Orte",
        },
    },
    {
        name: "forschungsdaten",
        path: "/forschungsdaten",
        component: Forschungsdaten,
        meta: {
            title: "Forschungsdaten: Gestapo. Terror. Orte",
        },
    },
    {
        name: "tours",
        path: "/touren",
        component: Tours,
        meta: {
            title: "Touren: Gestapo. Terror. Orte",
        },
    },
    {
        name: "erinnerungskalender",
        path: "/erinnerungskalender",
        component: ReminderCalendar,
        meta: {
            title: "Erinnerungskalender: Gestapo. Terror. Orte.",
        },
    },
    {
        path: "/images/:pathMatch(.*)*",
        name: "static-assets",
        beforeEnter: (to, from, next) => {
            next(false); // Verhindert das Routing für diese Pfade
        },
    },
    { path: "/:catchAll(.*)", redirect: "/starteite" },
];
