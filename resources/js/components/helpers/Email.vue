<template>
    <a @click.stop="setMailToLink(encryptedEmail)" class="text-no-wrap">
        <v-icon v-if="showIcon" dense>mdi-email-outline</v-icon>
        <span id="showEmail" v-show="showEmail">
            <span id="localPart">{{ decryptedEmail }}</span><span id="domain">{{ domain }}</span>{{ domainSuffix }}
        </span>
        <span v-show="!showEmail">
            E-Mail
        </span>
    </a>
</template>

<script>
export default {
    name: 'Email',
    props: {
        encryptedEmail: {
            type: String,
            required: true,
        },
        showEmail: {
            type: Boolean,
            default: false,
        },
        showIcon: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            decryptedEmail: this.decryptEmail(this.encryptedEmail),
            domain: 'tib',
            domainSuffix: 'eu',
        };
    },
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

<style scoped>
#domain:after {
    content: '.';
}

#localPart:after {
    content: '@';
}
</style>