import { createApp } from "vue";
import AdminRequestEdit from "./AdminRequestEdit.vue";
import { createVuetify } from "vuetify";
import * as components from "vuetify/components";
import * as directives from "vuetify/directives";
import VueAxios from "vue-axios";
import axios from "axios";
import "leaflet/dist/leaflet.css";
import "vuetify/styles";

// Function to initialize the edit form
window.initRequestEditForm = function (requestData, requestType, requestId) {
    const vuetify = createVuetify({
        components,
        directives,
        theme: {
            defaultTheme: "light",
        },
    });

    const app = createApp(AdminRequestEdit, {
        initialData: requestData,
        requestType: requestType,
        requestId: requestId,
    });

    app.use(VueAxios, axios);
    app.use(vuetify);
    app.mount("#edit-request-form");
};
