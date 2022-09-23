<template>
    <v-footer class="transparent" fixed>
        <v-card class="grey text-center" width="100%">
            <v-card-text class="font-weight-bold pa-1 text-subtitle-2">
                DIESE WEBSITE IST NOCH IM AUFBAU – Feedback und Anregungen an:
                <email encryptedEmail="phu/qspkfdu" :showEmail=true></email>
                <v-divider></v-divider>
                THIS WEBSITE IS STILL UNDER CONSTRUCTION – questions and suggestions to:
                <email encryptedEmail="phu/qspkfdu" :showEmail=true></email>
            </v-card-text>
        </v-card>
    </v-footer>
</template>

<script>
import Email from './helpers/Email';

export default {
    name: 'Footer',
    components: {Email},
};
</script>

<style scoped>

</style>