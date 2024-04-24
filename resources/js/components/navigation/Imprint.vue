<template>
    <v-list>
        <v-subheader class="text-h5">Impressum</v-subheader>

        <v-list-item>
            <v-list-item-content>
                <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                    Impressum zu dieser Website – zugleich Anbieterkennzeichnung gem. § 5 Digitale Dienste Gesetz (DDG)
                </v-list-item-subtitle>
            </v-list-item-content>
        </v-list-item>

        <v-list-item>
            <v-list-item-content>
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
                    Telefon: 0511 762-8989<br>
                    Fax: 0511 762-4076<br>
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
                    Verantwortlich für den Inhalt nach § 18 Abs. 2 MStV: Dr. Sandra Niemeyer; E-Mail:<br>
                    <email encryptedEmail="tboesb/ojfnfzfs" :showEmail=true></email>
                </v-list-item-subtitle>
            </v-list-item-content>
        </v-list-item>

        <v-list-item>
            <v-list-item-content>
                <v-list-item-title>Urheberrechtshinweis:</v-list-item-title>
                <v-list-item-subtitle class="hyphens-auto text-justify white-space-normal" lang="de">
                    Das Layout der Homepage, die verwendeten Grafiken und Kartenmarker-Icons sowie die sonstigen
                    Inhalte sind urheberrechtlich geschützt.
                    <br><br>
                    <div class="font-weight-bold">Material Design Icons</div>
                    Für die Icons im Navigations- und im Kartenoptionsmenü der Website verwenden wir dankend
                    <a href="https://materialdesignicons.com/" target="_blank">Material Design Icons</a>,
                    die der Apache 2.0 Lizenz unterliegen:
                    <a href="https://dev.materialdesignicons.com/license" target="_blank">
                        https://dev.materialdesignicons.com/license
                    </a>
                    <br><br>
                    <div class="font-weight-bold">Wikidata und Wikimedia Commons</div>
                    Die auf der Websitekarte dargestellten Inhalte sind von Wikidata (struktrierte Metadaten) und
                    Wikimedia Commons (Bilder) eingebunden, die unter einer gemeinfreien Creative Commons Lizenz
                    verfügbar sind. Die Verwendung ist konform mit den Wikimedia Foundation Nutzungsbedingungen. Jeder
                    einzelne dargestellte Datensatz ist mit dem dazugehörigen Wikidata Dateneintrag verlinkt und kann
                    über die Webadresse
                    <a href="https://www.wikidata.org/wiki/" target="_blank">https://www.wikidata.org/wiki/</a>
                    eingesehen werden. Jedes Bild ist mit dem dazugehörigen Wikimedia Commons Eintrag verlinkt und kann
                    über die Webadresse
                    <a href="https://commons.wikimedia.org/wiki" target="_blank">https://commons.wikimedia.org/wiki</a>
                    eingesehen werden.
                </v-list-item-subtitle>
            </v-list-item-content>
        </v-list-item>

    </v-list>
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
