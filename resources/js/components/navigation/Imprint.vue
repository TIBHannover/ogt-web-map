<template>
    <v-list rounded>
        <v-list-item @click.stop="$emit('setActiveMenu', 'generalInfoSubMenu')">
            <v-list-item-icon>
                <v-icon>mdi-backburger</v-icon>
            </v-list-item-icon>
            <v-list-item-content>
                <v-list-item-title>zurück zum vorherigen Menü</v-list-item-title>
            </v-list-item-content>
        </v-list-item>
        <v-divider></v-divider>
        <v-subheader class="text-uppercase">Impressum</v-subheader>
        <v-list-item>
            <v-list-item-content>
                <v-list-item-subtitle class="hyphens-auto text-justify white-space-normal" lang="de">
                    Impressum zu dieser Website – zugleich Anbieterkennzeichnung gem. § 5 Telemediengesetz (TMG)
                    <br><br>
                </v-list-item-subtitle>
                <v-list-item-title>Herausgeber:</v-list-item-title>
                <v-list-item-subtitle>
                    Technische Informationsbibliothek (TIB)<br>
                    Welfengarten 1 B, 30167 Hannover<br>
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
                <v-list-item-subtitle class="hyphens-auto text-justify white-space-normal" lang="de">
                    Die Technische Informationsbibliothek (TIB) ist eine Stiftung öffentlichen Rechts des Landes Niedersachsen.
                    <br><br>
                </v-list-item-subtitle>
                <v-list-item-title>Aufsichtsbehörde:</v-list-item-title>
                <v-list-item-subtitle class="hyphens-auto text-justify white-space-normal" lang="de">
                    Ministerium für Wissenschaft und Kultur des Landes Niedersachsen
                </v-list-item-subtitle>
            </v-list-item-content>
        </v-list-item>
        <v-list-item>
            <v-list-item-content>
                <v-list-item-title>Kontakt:</v-list-item-title>
                <v-list-item-subtitle>
                    Telefon: 0511 762-8989<br>
                    Fax: 0511 762-4076<br>
                    E-Mail:
                    <a :href="mailPrefix+contactEmail">{{contactEmail}}</a>
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
                <v-list-item-subtitle class="hyphens-auto text-justify white-space-normal" lang="de">
                    Verantwortlich für den Inhalt nach § 55 Abs. 2 RStV: Dr. Sandra Niemeyer; E-Mail:
                    <a :href="mailPrefix+editorialEmail">{{editorialEmail}}</a>
                </v-list-item-subtitle>
            </v-list-item-content>
        </v-list-item>
        <v-list-item>
            <v-list-item-content>
                <v-list-item-title>Urheberrechtshinweis:</v-list-item-title>
                <v-list-item-subtitle class="hyphens-auto text-justify white-space-normal" lang="de">
                    Das Layout der Homepage, die verwendeten Grafiken sowie die sonstigen Inhalte sind urheberrechtlich geschützt.
                </v-list-item-subtitle>
            </v-list-item-content>
        </v-list-item>
    </v-list>
</template>

<script>
export default {
    name: 'Imprint',
    data() {
        return {
            mailPrefix: atob("bWFpbHRvOg=="),
            editorialEmail: atob("c2FuZHJhLm5pZW1leWVyQHRpYi5ldQ=="),
            contactEmail: atob("aW5mb3JtYXRpb25AdGliLmV1"),
        };
    },
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
