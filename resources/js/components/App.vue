<template>
    <v-app>
        <navigation-sidebar></navigation-sidebar>
        <v-main>
            <router-view></router-view>
        </v-main>
        <div></div>
        <v-footer fixed padless width="100%" class="grey">
            <v-card class="flex grey" flat>
                <v-card-text class="pa-0 text-subtitle-2 font-weight-bold text--darken-4 text-center">
                    DIESE WEBSITE IST NOCH IM AUFBAU /<br>
                    THIS WEBSITE IS STILL UNDER CONSTRUCTION<br>
                    Feedback und Anregungen an: <a @click.stop="setMailToLink('mjtb/hspi')">E-Mail</a>
                </v-card-text>
            </v-card>
        </v-footer>
    </v-app>
</template>

<script>
import NavigationSidebar from './navigation/NavigationSidebar';

export default {
    name: 'App',
    components: {NavigationSidebar},
    methods: {
        /**
         * Set decrypted email link to open with default mail program.
         *
         * @param encryptedEmail
         */
        setMailToLink: function (encryptedEmail) {
            window.location.href = 'mailto:' + this.decryptEmail(encryptedEmail + 'Aujc/fv');
        },
        /**
         * Decrypt email - https://www.math.uni-hamburg.de/it/dienste/encryptma.html
         *
         * @param encryptedEmail
         * @returns {string} decrypted email
         */
        decryptEmail: function (encryptedEmail) {
            let utf16CharCode = 0;
            let decryptedEmail = '';
            for (let i = 0; i < encryptedEmail.length; i++) {
                utf16CharCode = encryptedEmail.charCodeAt(i);
                if (utf16CharCode >= 8364) {
                    utf16CharCode = 128;
                }
                decryptedEmail += String.fromCharCode(utf16CharCode - (1));
            }
            return decryptedEmail;
        },
    },
};
</script>

<style>
/* moved v-navigation-drawer over v-btn having z-index 4 */
.v-navigation-drawer--absolute {
    z-index: 5;
}
</style>
