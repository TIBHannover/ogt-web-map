<template>
    <a @click.stop="setMailToLink(encryptedEmail)">
        E-Mail
    </a>
</template>

<script>
export default {
    name: 'Email',
    props: ['encryptedEmail'],
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

</style>