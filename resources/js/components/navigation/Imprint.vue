<template>
    <section>
        <v-list>
            <v-subheader class="text-h5">Impressum</v-subheader>
            <v-list-item>
                <v-list-item-content>
                    <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                        Impressum zu dieser Website – zugleich Anbieterkennzeichnung gem. § 5 Telemediengesetz (TMG)
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>

            <v-list-item>
                <v-list-item-content>
                    <v-list-item-title>Herausgeber:</v-list-item-title>
                    <v-list-item-subtitle>
                        Technische Informationsbibliothek (TIB)
                        <br>
                        Welfengarten 1 B, 30167 Hannover
                        <br>
                        Postfach 6080, 30060 Hannover
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>

            <v-list-item>
                <v-list-item-content>
                    <v-list-item-title>Vertretungsberechtigte Person:</v-list-item-title>
                    <v-list-item-subtitle>
                        Prof. Dr. Sören Auer (Direktor der TIB)
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>

            <v-list-item>
                <v-list-item-content>
                    <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                        Die Technische Informationsbibliothek (TIB) ist eine Stiftung öffentlichen Rechts des Landes
                        Niedersachsen.
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>

            <v-list-item>
                <v-list-item-content>
                    <v-list-item-title>Aufsichtsbehörde:</v-list-item-title>
                    <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                        Ministerium für Wissenschaft und Kultur des Landes Niedersachsen
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>

            <v-list-item>
                <v-list-item-content>
                    <v-list-item-title>Kontakt:</v-list-item-title>
                    <v-list-item-subtitle>
                        Telefon: 0511 762-8989
                        <br>
                        Fax: 0511 762-4076
                        <br>
                        E-Mail:
                        <email encryptedEmail="jogpsnbujpo" :showEmail=true></email>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>

            <v-list-item>
                <v-list-item-content>
                    <v-list-item-title>Umsatzsteuer-Identifikationsnummer:</v-list-item-title>
                    <v-list-item-subtitle>
                        DE 214931803
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>

            <v-list-item>
                <v-list-item-content>
                    <v-list-item-title>Redaktion:</v-list-item-title>
                    <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                        Verantwortlich für den Inhalt nach § 55 Abs. 2 RStV: Dr. Sandra Niemeyer; E-Mail:<br>
                        <email encryptedEmail="tboesb/ojfnfzfs" :showEmail=true></email>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>

            <v-list-item>
                <v-list-item-content>
                    <v-list-item-title>Urheberrechtshinweis:</v-list-item-title>
                    <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                        Das Layout der Homepage, die verwendeten Grafiken sowie die sonstigen Inhalte sind
                        urheberrechtlich geschützt.
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>

        </v-list>
    </section>
</template>

<script>
import Email from '../helpers/Email';

export default {
    name: 'Imprint',
    components: {Email},
};
</script>

<style scoped>
.hyphens-auto {
    hyphens: auto;
}

/* to enable linebreaks for long labels */
.white-space-normal {
    white-space: normal;
}
</style>
