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
        <v-subheader class="text-uppercase">Datenschutz</v-subheader>
        <v-list-item>
            <v-list-item-content>
                <v-list-item-subtitle class="hyphens-auto text-justify white-space-normal" lang="de">
                Datenschutz hat einen hohen Stellenwert für die Technische Informationsbibliothek (TIB). Eine Nutzung
                der Dienstleistungen der TIB ist grundsätzlich ohne jede Angabe personenbezogener Daten möglich. Sofern
                eine betroffene Person besondere Services über die Internetseiten der TIB in Anspruch nehmen möchte,
                kann jedoch eine Verarbeitung personenbezogener Daten erforderlich werden. Ist die Verarbeitung
                personenbezogener Daten erforderlich und besteht für eine solche Verarbeitung keine gesetzliche Grundlage,
                holen wir eine Einwilligung der betroffenen Person ein.
                <br><br>
                Die Verarbeitung personenbezogener Daten, beispielsweise des Namens, der Anschrift, der E-Mail-Adresse
                oder der Telefonnummer einer betroffenen Person erfolgt stets im Einklang mit der Datenschutz-Grundverordnung
                und den für die TIB geltenden landes- und institutionsspezifischen Datenschutzbestimmungen. Mittels
                dieser Datenschutzerklärung möchten wir die Öffentlichkeit über Art, Umfang und Zweck der von uns
                erhobenen, genutzten und verarbeiteten personenbezogenen Daten sowie die betroffenen Personen zustehenden
                Rechte informieren.
                <br><br>
                Die in dieser Datenschutzerklärung verwendeten Begrifflichkeiten sind im Sinne der Europäischen
                <a href="https://eur-lex.europa.eu/legal-content/DE/TXT/?uri=CELEX%3A32016R0679">Datenschutz-Grundverordnung (DSGVO)</a>
                zu verstehen.
                </v-list-item-subtitle>
            </v-list-item-content>
        </v-list-item>
        <v-list-item>
            <v-list-item-content>
                <v-list-item-title class="hyphens-auto white-space-normal" lang="de">
                    Name und Anschrift des für die Verarbeitung Verantwortlichen
                </v-list-item-title>
                <v-list-item-subtitle class="hyphens-auto text-justify white-space-normal" lang="de">
                    Verantwortlicher im Sinne des Datenschutzrechts ist die:
                    <br><br>
                    Technische Informationsbibliothek (TIB)<br>
                    Welfengarten 1 B<br>
                    30167 Hannover<br>
                    Deutschland
                    <br><br>
                    Tel.: 0511 762-8989,<br>
                    E-Mail: <a :href="mailPrefix+infoEmail">{{infoEmail}}</a><br>
                    Website: <a href="https://www.tib.eu">www.tib.eu</a>
                </v-list-item-subtitle>
            </v-list-item-content>
        </v-list-item>
        <v-list-item>
            <v-list-item-content>
                <v-list-item-title class="hyphens-auto white-space-normal" lang="de">
                    Name und Anschrift der Datenschutzbeauftragten
                </v-list-item-title>
                <v-list-item-subtitle class="hyphens-auto text-justify white-space-normal" lang="de">
                    Die Datenschutzbeauftragte des für die Verarbeitung Verantwortlichen ist:<br>
                    <br>
                    Elke Brehm<br>
                    Tel.: 0511 762-8138<br>
                    E-Mail: <a :href="mailPrefix+dataProtectionEmail">{{dataProtectionEmail}}</a><br>
                    <br>
                    <div class="font-weight-bold">Post:</div>
                    Technische Informationsbibliothek (TIB)<br>
                    Datenschutzbeauftragte<br>
                    Welfengarten 1 B<br>
                    30167 Hannover<br>
                    Deutschland<br>
                    <br>
                    <div class="font-weight-bold">Besuchsadresse:</div>
                    TIB Conti-Campus<br>
                    Königsworther Platz 1 B<br>
                    30167 Hannover<br>
                    <br>
                    Jede betroffene Person kann sich jederzeit bei allen Fragen und Anregungen zum Datenschutz direkt an
                    unsere Datenschutzbeauftragte wenden.
                </v-list-item-subtitle>
            </v-list-item-content>
        </v-list-item>
        <v-list-item>
            <v-list-item-content>
                <v-list-item-title class="hyphens-auto white-space-normal" lang="de">
                    Kontaktmöglichkeit über die Internetseiten
                </v-list-item-title>
                <v-list-item-subtitle class="hyphens-auto text-justify white-space-normal" lang="de">
                    Die Internetseiten der TIB enthalten Angaben und Formulare, die eine schnelle elektronische
                    Kontaktaufnahme per Kontaktformular oder per E-Mail auch für nicht bei der TIB registrierte
                    Kundinnen und Kunden unter Angabe von Namen und E-Mail-Adresse ermöglichen. Die Angabe von Name und
                    E-Mail-Adresse ist zur späteren Kontaktaufnahme nach Klärung des Anliegens der betroffenen Person
                    erforderlich. Die per E-Mail oder über ein Kontaktformular von der betroffenen Person an die TIB
                    übermittelten personenbezogenen Daten werden automatisch intern und nur zum Zweck der Bearbeitung
                    oder der Kontaktaufnahme zur betroffenen Person gespeichert.
                </v-list-item-subtitle>
            </v-list-item-content>
        </v-list-item>
        <v-list-item>
            <v-list-item-content>
                <v-list-item-title>
                    Webseite mit Cookies
                </v-list-item-title>
                <v-list-item-subtitle class="hyphens-auto white-space-normal" lang="de">
                    Die Internetseiten der TIB verwenden Cookies. Cookies sind Textdateien, welche über einen
                    Internetbrowser auf einem Computersystem abgelegt und gespeichert werden, und dienen dazu, das
                    Angebot der TIB nutzerfreundlicher, effektiver und sicherer zu machen.
                    <br><br>
                    Die meisten der verwendeten Cookies sind sogenannte Session-Cookies, die nach Ende des Besuchs
                    automatisch gelöscht werden. Andere Cookies bleiben auf dem Endgerät der Nutzerin oder des Nutzers
                    gespeichert, bis die Nutzerin oder der Nutzer diese löschen. Diese Cookies ermöglichen es der TIB,
                    den Browser der Nutzerin oder des Nutzers beim nächsten Besuch wiederzuerkennen.
                    <br><br>
                    Die Nutzerinnen und Nutzer können das Setzen von Cookies durch die Internetseiten der TIB jederzeit
                    durch die Auswahl der entsprechenden Einstellungen des genutzten Internetbrowsers verhindern und
                    dauerhaft widersprechen. Ferner können bereits gesetzte Cookies jederzeit über den Internetbrowser
                    oder andere Softwareprogramme gelöscht werden. Dies ist in allen gängigen Internetbrowsern möglich.
                    Deaktiviert die betroffene Person das Setzen von Cookies in dem genutzten Internetbrowser, sind
                    unter Umständen nicht alle Funktionen der Internetseiten der TIB vollumfänglich nutzbar.
                </v-list-item-subtitle>
            </v-list-item-content>
        </v-list-item>
        <v-list-item>
            <v-list-item-content>
                <v-list-item-title>
                    Plugins und Tools
                </v-list-item-title>
                <v-list-item-subtitle class="hyphens-auto text-justify white-space-normal" lang="de">
                    <div class="font-weight-bold">OpenStreetMap</div>
                    Wir haben auf unserer Website Kartenausschnitte des Online-Kartentools "OpenStreetMap" eingebunden.
                    Angeboten wird diese Funktion von OpenStreetMap Foundation (OSMF), St John’s Innovation Centre,
                    Cowley Road, Cambridge, CB4 0WS, United Kingdom.<br>
                    Durch die Verwendung dieser Kartenfunktion wird Ihre IP-Adresse an OpenStreetMap weitergeleitet.<br>
                    Ferner kann Ihr Standort erfasst werden, wenn Sie dies in Ihren Geräteeinstellungen – z. B. auf
                    Ihrem Handy – zugelassen haben. Der Anbieter dieser Seite hat keinen Einfluss auf diese
                    Datenübertragung. Details entnehmen Sie der Datenschutzerklärung von OpenStreetMap unter folgendem
                    Link:<br>
                    <a href="https://wiki.osmfoundation.org/wiki/Privacy_Policy">
                        https://wiki.osmfoundation.org/wiki/Privacy_Policy
                    </a>
                    <!--
                    <br><br>
                    <div class="font-weight-bold">Google Web Fonts</div>
                    Diese Website nutzt zur einheitlichen Darstellung von Schriftarten sogenannte Web Fonts, die von
                    Google bereitgestellt werden. Beim Aufruf einer Seite lädt Ihr Browser die benötigten Web Fonts in
                    ihren Browsercache, um Texte und Schriftarten korrekt anzuzeigen. Wenn Ihr Browser Web Fonts nicht
                    unterstützt, wird eine Standardschrift von Ihrem Computer genutzt. Weitere Informationen zu Google
                    Web Fonts finden Sie unter
                    <a href="https://developers.google.com/fonts/faq">https://developers.google.com/fonts/faq</a>
                    und in der Datenschutzerklärung von Google
                    (<a href="https://www.google.com/policies/privacy/">https://www.google.com/policies/privacy/</a>)
                    -->
                    <br><br>
                    <div class="font-weight-bold">Material Design Icons</div>
                    Außerdem nutzen wir Icons von
                    <a href="https://materialdesignicons.com/">https://materialdesignicons.com/</a>.
                    Weitere Informationen zu Material Design Icons finden Sie unter
                    <a href="https://dev.materialdesignicons.com/license">https://dev.materialdesignicons.com/license</a>.
                    <br><br>
                    <div class="font-weight-bold">Wikidata und Wikimedia Commons</div>
                    Bilder und Daten zu Orten, Ereignissen und Personen werden von Wikidata und Wikimedia Commons
                    auf der Website eingebunden.
                </v-list-item-subtitle>
            </v-list-item-content>
        </v-list-item>
    </v-list>
</template>

<script>
export default {
    name: 'DataProtection',
    data() {
        return {
            mailPrefix: atob("bWFpbHRvOg=="),
            infoEmail: atob("aW5mb3JtYXRpb25AdGliLmV1"),
            dataProtectionEmail: atob("ZGF0ZW5zY2h1dHpAdGliLmV1"),
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
