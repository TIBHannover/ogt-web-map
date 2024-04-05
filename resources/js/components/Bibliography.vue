<template>
    <v-container id="container" class="mb-110">
        <v-row :style="{
            'padding-left': (freeClientWidth < 75 && ! isMenuDisplayed) ? ((75 - freeClientWidth) + 'px') : 0
        }">
            <v-col>
                <h1 class="font-family-special-elite font-weight-bold hyphens-auto py-4 text-h4 text-md-h3" lang="de">
                    Literatur
                </h1>
            </v-col>
        </v-row>

        <v-row>
            <v-col>
                <ol class="biblio">
                    <li>Banse, Dietrich. Das Aussenlager Uelzen des Konzentrationslagers Neuengamme. Neuauflage der Dokumentation von 1990. Uelzen: Geschichtswerksatt Uelzen e.V., 2015.</li>
                    <li>Beese-Kubba, Julia. Arbeitserziehungslager und Arbeitskräftepolitik im nationalsozialistischen Deutschland: das Beispiel Lahde mit dem Zweiglager Steinbergen. Osnabrück, 2010.</li>
                    <li>Benz, Wolfgang, und Barbara Distel (Hrsg.). Frühe Lager, Dachau, Emslandlager. 2. Aufl. Bd. 2. Der Ort des Terrors. Bd. 3. München: C.H. Beck, 2014.</li>
                    <li>Benz, Wolfgang, und Barbara Distel (Hrsg.). Sachsenhausen, Buchenwald. 2. Aufl. Der Ort des Terrors. Bd. 3. München: C.H. Beck, 2015.</li>
                    <li>Benz, Wolfgang, Barbara Distel, und Angelika Königseder (Hrsg.). Niederhagen/Wewelsburg, Lublin-Majdanek, Arbeitsdorf, Herzogenbusch (Vught), Bergen-Belsen, Mittelbau-Dora. Der Ort des Terrors, Bd. 7. München: Beck, 2008.</li>
                    <li>Benz, Wolfgang, Barbara Distel, und Angelika Königseder (Hrsg.). Arbeitserziehungslager, Ghettos, Jugendschutzlager, Polizeihaftlager, Sonderlager, Zigeunerlager, Zwangsarbeiterlager. Der Ort des Terrors, Bd. 9. München: Beck, 2009.</li>
                    <li>Borgsen, Werner, und Klaus Volland. Stalag X B Sandbostel: zur Geschichte eines Kriegsgefangenen- und KZ-Auffanglagers in Norddeutschland 1939 – 1945. 4., um Einen Anh. Erg. Aufl. Bremen: Ed. Temmen, 2010.</li>
                    <li>Buchholz, Marlis. Die hannoverschen Judenhäuser: zur Situation der Juden in der Zeit der Ghettoisierung und Verfolgung 1941 bis 1945. Hildesheim: Lax, 1987.</li>
                    <li>Buggeln, Marc. Einen Ort der Erinnerung schaffen: KZ und Zwangsarbeit in Hannover-Limmer 1944/45. Hannover, 2011, 2011.</li>
                    <li>Cajani, Luigi. „Die italienischen Militär-Internierten im nationalsozialistischen Deutschland“. In: Europa und der „Reichseinsatz“. Ausländische Zivilarbeiter, Kriegsgefangene und KZ-Häftlinge in Deutschland 1938-1945, Herbert, Ulrich (Hrsg.), S. 295–316. Essen, 1991.</li>
                    <li>Rohde, Reinhard, und Tim Wegener. Celle im Nationalsozialismus: ein zeitgeschichtlicher Stadtführer. Bielefeld: Verl. Für Regionalgeschichte, 2012.</li>
                    <li>Causing, Kathrin. Das Lager vor der Haustür: Konzentrationslagerhäftlinge im Außenlager Hildesheim. Hildesheimer Jahrbuch für Stadt und Stift Hildesheim (Hrsg.) 2004.</li>
                    <li>Credé, Norbert. Das Lager „Schwarzer Weg“. Ein Gestapo-Straflager in Wilhelmshaven zwischen Arbeitserziehungslager und KZ. In: Gedenkstättenrundbrief 40, (1991)</li>
                    <li>Creydt, Detlef. Für die Wunderwaffen in Südniedersachsen 1943 – 1945: Organ. Todt, Volkswagen, Lorenz, Siemens, Dt. Edelstahl, Salzgitter, Ph. Holzmann. Braunschweig: Steinweg, 1993.</li>
                    <li>Doerry, Janine, Alexandra Klei, Elisabeth Thalhofer, und Karsten Wilke (Hrsg.). NS-Zwangslager in Westdeutschland, Frankreich und den Niederlanden: Geschichte und Erinnerung. Paderborn: Schöningh, 2008.</li>
                    <li>Döscher, Hans-Jürgen. „Geheime Staatspolizei und allgemeine Verwaltung im Regierungsbezirk Stade“. In Stader Jahrbuch 1972, S. 70–90. Stade: Selbstverlag des Stader Geschichts- und Heimatvereins, 1972.</li>
                    <li>Ehresmann, Andreas, und Jens Binner. Das Stalag X B Sandbostel: Geschichte und Nachgeschichte eines Kriegsgefangenenlagers : Katalog der Dauerausstellung. Das Stalag X B Sandbostel : Geschichte und Nachgeschichte eines Kriegsgefangenenlagers : Katalog der Dauerausstellung. 1. Aufl. München: Dölling und Galitz Verlag, 2015.</li>
                    <li>Faulenbach, Bernd, und Andrea Kaltofen (Hrsg.). Hölle im Moor: die Emslandlager 1933-1945. Zweite durchgesehene Auflage. Göttingen: Wallstein Verlag, 2017.</li>
                    <li>Fr   Frerichs, Holger. Zwangsarbeit – Hunger – Tod: Arbeitskommandos, Lager und Grabstätten sowjetischer Kriegsgefangener in Wilhelmshaven und Friesland 1941-1945. Wilhelmshaven: Brune-Mettcker Druck- und Verlagsgesellschaft mbH, 2016.</li>
                    <li>Fröbe, Rainer. Konzentrationslager in Hannover : KZ-Arbeit und Rüstungsindustrie in der Spätphase des Zweiten Weltkriegs. Veröffentlichungen der Historischen Kommission für Niedersachsen und Bremen ; 35 : Quellen und Untersuchungen zur allgemeinen Geschichte Niedersachsens in der Neuzeit 8. Hildesheim: Lax, 1985.</li>
                    <li>Schmid, Hans-Dieter (Hrsg.). Ahlem: die Geschichte einer jüdischen Gartenbauschule und ihres Einflusses auf Gartenbau und Landschaftsarchitektur in Deutschland und Israel. 2. Ergänzte Auflage, Bremen: Edition Temmen, 2017.</li>
                    <li>Gedenkstätten Gestapokeller und Augustaschacht e.V. (Hrsg.). Polizeigewalt und Zwangsarbeit: Das Arbeitserziehungslager Ohrbeck. Hasbergen: Gedenkstätten Gestapokeller und Augustaschlacht, 2020.</li>
                    <li>Giesecke, Donald. „Flugplatz Goslar: vom zivilen Flughafen zum militärischen Fliegerhorst Goslar, 1927 bis 1945“, o. J.</li>
                    <li>Grieger, Manfred. „Das KZ-Aussenlager ‚Hecht‘ in Holzen bei Eschershausen“. In Wiederentdeckt : Zeugnisse aus dem Konzentrationslager Holzen ; Begleitband zur Wanderausstellung; Wagner, Jens-Christian (Hrsg.), S. 184–91. Göttingen: Wallstein, 2013.</li>
                    <li>Mommsen, Hans, und Manfred Grieger. Das Volkswagenwerk und seine Arbeiter im Dritten Reich. Düsseldorf: ECON, 1996</li>
                    <li>Krause-Schmitt, Ursula. Niedersachsen <2>: Regierungsbezirke Hannover und Weser-Ems. Heimatgeschichtlicher Wegweiser zu Stätten des Widerstandes und der Verfolgung 1933-1945, Nr. 3. Köln: Pahl-Rugenstein, 1986.</li>
                    <li>Hillmann, Barbara, Volrad Kluge, und Erdwig Kramer. Lw. 2/XI – Muna Lübberstedt: Zwangsarbeit für den Krieg. Bremen: Ed. Temmen, 1996.</li>
                    <li>Hofe, Alexander vom. Vier Prinzen zu Schaumburg-Lippe und das parallele Unrechtssystem. Madrid: Vierprinzen, 2006.</li>
                    <li>Hoffmann, Katharina. Ausländische ZwangsarbeiterInnen in Oldenburg während des Zweiten Weltkrieges: eine Rekonstruktion der Lebensverhältnisse und Analyse von Erinnerungen deutscher und polnischer ZeitzeugInnen. Oldenburg, 1999.</li>
                    <li>Homeyer, Friedel. KZ Neuengamme – Außenlager Stöcken (Akku), Außenlager Misburg, Außenlager Limmer, Außenlager Stöcken (Conti), Außenlager Langenhagen, Außenlager Mühlenberg, Außenlager Ahlem. 3. Aufl. Hannover: Landkreis Hannover Kulturamt (Hrsg.), 1993.</li>
                    <li>Hütt, Götz. Das Außenkommando des KZ Buchenwald in Duderstadt: ungarische Jüdinnen im Rüstungsbetrieb Polte. Norderstedt: Books on Demand, 2005.</li>
                    <li>Issmer, Volker. Das Arbeitserziehungslager Ohrbeck bei Osnabrück: eine Dokumentation. Osnabrück: Landschaftsverband Osnabrücker Land (Hrsg.), 2000.</li>
                    <li>Janz, Wolfgang. Erinnerungsstätten an Unmenschlichkeiten des Nationalsozialismus im Landkreis Goslar. Goslar: Verein Spurensuche Goslar e.V. (Hrsg.), 2003.</li>
                    <li>Kahle, Hans-Jürgen. „Das Haus Lehmkuhle 2“. In: Geheime Staatspolizei, Aussendienststelle Cuxhaven. Kahle, Hans-Jürgen; Neumann, Wolfgang (Hrsg.) Cuxhaven 1989, 5–13.</li>
                    <li>Keller, Rolf, und Silke Petry (Hrsg.). Sowjetische Kriegsgefangene im Arbeitseinsatz: 1941 – 1945 ; Dokumente zu den Lebens- und Arbeitsbedingungen in Norddeutschland. Göttingen: Wallstein-Verl., 2013.</li>
                    <li>Krause-Hotopp, Diethelm. „‘Wir lernen nur, wenn wir nicht vergessen‘: das Konzentrationslager Schandelah-Wohld und sein Ende (1944-1945)“. Landkreis Wolfenbüttel: : Heimatbuch … Landkreis Wolfenbüttel 62 (2016): 189–98.</li>
                    <li>Lange, Ingo. Das Frauen-Arbeitslager in Horneburg N. E. – Außenlager des Konzentrationslagers Neuengamme: eine Verarbeitung der Erkenntnisse aus den Broschüren zum Arbeitslager Horneburg Eine Zwischenstation auf dem Wege zum KZ-Bergen-Belsen, Gegen die Gewöhnung an Schreckliches und Mitten unter uns mußten sie durch die Hölle. Horneburg, 2000.</li>
                    <li>Lo Lotfi, Gabriele. KZ der Gestapo: Arbeitserziehungslager im Dritten Reich. Stuttgart: Dt. Verl.-Anst., 2000.</li>
                    <li>Mlynek, Klaus. Gestapo Hannover meldet …: Polizei- und Regierungsberichte für das mittlere und südliche Niedersachsen zwischen 1933 und 1937, Hildesheim: Lax, 1986.</li>
                    <li>Mosebach-Tegtmeier, Ellen. „‘Für sich stand die Gestapo‘: Anmerkungen über Wilhelmshaven in der Zeit des Nationalsozialismus“. In Justiz an der Jade : Festschrift zur Einweihung des Erweiterungs- und Umbaues des Amtsgerichts Wilhelmshaven. Czoska, Frank; Reinhardt, Werner /(Hrsg.), 1985, S. 533–70.</li>
                    <li>Müller, Hartmut. Die Frauen von Obernheide: jüdische Zwangsarbeiterinnen in Bremen 1944/1945. Bremen: Donat Verl., 1988.</li>
                    <li>Müller, Jürgen. Dachs IV: der Bau des unterirdischen Hydrierwerkes Dachs IV bei Osterode am Harz zum Ende des Zweiten Weltkrieges. 1. Aufl. Clausthal-Zellerfeld: Papierflieger, 2004.</li>
                    <li>Preuß, Werner H. Lager Kaland: das Kommando des Konzentrationslagers Neuengamme im Herzen Lüneburgs ; 12. August bis 13. November 1943 ; Dokumentation. 2. Aufl. Lüneburg: Geschichtswerkstatt Lüneburg(Hrsg.), 2008.</li>
                    <li>Räcker-Wellnitz, Ulrich: Örtliche Schaltzentrale des Terros: Die Gestapo in Wilhelmshaven. In Wilhelmshavener Zeitung (Beilage ‚Heimat am Meer‘), diverse Ausgaben.</li>
                    <li>Richter, Axel. Das Unterkommando Vechelde des Konzentrationslagers Neuengamme: zum Einsatz von KZ-Häftlingen in der Rüstungsproduktion. Vechelde: Gemeinde Vechelde, 1985.</li>
                    <li>Schmid, Hans-Dieter. „‘Anständige Beamte‘ und ‚üble Schläger‘. Die Staatspolizeistelle Hannover“. In Die Gestapo – Mythos undaaa Realität,Paul, Gerhard; Mallmann, Klaus-Michael (Hrsg.), S. 133–60. Darmstadt: Wissenschaftliche Buchgesellschaft, 1995.</li>
                    <li>Steinwascher, Gerd, Detlef Schmiechen-Ackermann, und Karl-Heinz Schneider (Hrsg.). Geschichte Niedersachsens-Von der Weimarer Republik bis zur Wiedervereinigung. Bd. 5. Hannover: Verlag Hahnsche Buchhandlung, 2010.</li>
                    <li>Siedbürger, Günther. Zwangsarbeit im Landkreis Göttingen 1939 – 1945. Duderstadt: Mecke, 2005.</li>
                    <li>Sie Siegfried, Klaus-Jörg. Das Leben der Zwangsarbeiter im Volkswagenwerk 1939-1945. 1. Aufl. Frankfurt/Main, New York: Campus Verlag, 1988.</li>
                    <li>Steinwascher, Gerd (Hrsg.). Gestapo Osnabrück meldet …: Polizei- und Regierungsberichte aus dem Regierungsbezirk Osnabrück aus den Jahren 1933 bis 1936. Osnabrück: Verein für Geschichte und Landeskunde von Osnabrück, 1995.</li>
                    <li>Steinwascher, Gerd, „Führerprinzip und Gleichschaltung – Verwaltung und Verfassung unter den Nationalsozialisten“. In Vom Ständestaat zur freiheitlich-demokratischen Republik. Etappen in Schaumburg, Hubert Höing (Hrsg.), S. 179–93. Schaumburger Studien 55. Melle: Verlag Ernst Knoth GmbH, 1995.</li>
                    <li>Stillfried, Janet. Das Sachsenross unterm Hakenkreuz: Reiseführer durch Hannover und Umgebung 1933-1945. Göttingen: MatrixMedia Verlag, 2016.</li>
                    <li>Thalhofer, Elisabeth: Entgrenzung der Gewalt. Gestapo-Lager in der Endphase des Dritten Reichs, Paderborn: Schöningh, 2010.</li>
                    <li>Tech, Andrea. Arbeitserziehungslager in Nordwestdeutschland 1940 – 1945. Bergen-Belsen-Schriften. Göttingen: Vandenhoeck & Ruprecht, 2003.</li>
                    <li>E    Ehrhardt, Frank, und Braunschweigische Landschaft e.V., Arbeitsgruppe Geschichtsvereine (Hrsg.). Topographie der Erinnerung: Gedenkstätten für die Opfer des Nationalsozialismus im Gebiet der Braunschweigischen Landschaft. Braunschweig: Appelhans, 2004.</li>
                    <li>Vries, Immo de. Kriegsverbrechen in Lüneburg: das Massengrab im Tiergarten. Lüneburg: Geschichtswerkstatt (Hrsg.), 2000.</li>
                    <li>VVN-BDA Kreisverband Lüneburg (Hrsg.). Die Staatspolizei Lüneburg. Strukturen und Täter. Lüneburg, 2011.</li>
                    <li>Wagner, Jens-Christian. Produktion des Todes: das KZ Mittelbau-Dora. Grundlegend überarbeitete, Erweiterte und auf Den neuesten Forschungsstand gebrachte Neuauflage. Göttingen: Wallstein Verlag, 2015.</li>
                    <li>Wagner, Jens-Christian. Wiederentdeckt: Zeugnisse aus dem Konzentrationslager Holzen ; Begleitband zur Wanderausstellung. Wiederentdeckt : Zeugnisse aus dem Konzentrationslager Holzen ; Begleitband zur Wanderausstellung, Wagner, Jens-Christian (Hrsg.), Göttingen: Wallstein-Verl., 2013.</li>
                    <li>Wessels, Rolf. Das Arbeitserziehungslager in Liebenau: 1940 – 1943. Landkreis Nienburg/Weser, 1990.</li>
                    <li>Wienecke, Annette. „Besondere Vorkommnisse nicht bekannt“: Zwangsarbeiter in unterirdischen Rüstungsbetrieben ; wie ein Heidedorf kriegswichtig wurde. Bonn: Pahl-Rugenstein, 1996.</li>
                    <li>Winkelhake, Friedrich. Bad Eilsen im Nationalsozialismus: von 1933 bis zur Zeit nach dem Zusammenbruch des 3. Reiches ; Kirche im NS-Staat ; Beispiele aus unserer Schaumburger Region. Bad Eilsen, 2010, 2010.</li>
                    <li>Woock, Joachim. Zwangsarbeit ausländischer Arbeitskräfte im Regionalbereich Verden/Aller (1939 - 1945): Arbeits- und Lebenssituationen im Spiegel von Archivalien und Erinnerungsberichten ausländischer Zeitzeugen. Norderstedt: Books on Demand, 2004.</li>
                    <li>Wysocki, Gerd. Arbeit für den Krieg: Herrschaftsmechanismen in der Rüstungsindustrie des „Dritten Reiches“ ; Arbeitseinsatz, Sozialpolitik und staatspolizeiliche Repression bei den Reichswerken „Hermann Göring“ im Salzgitter-Gebiet 1937/38 bis 1945. Braunschweig: Steinweg-Verl., 1992.</li>
                    <li>Wysocki, Gerd. Die Geheime Staatspolizei im Land Braunschweig: Polizeirecht und Polizeipraxis im Nationalsozialismus. Frankfurt/Main [u.a.]: Campus-Verl., 1997.</li>
                </ol>
                <br><br><br><br><!-- Dev banner offset (temp) -->
            </v-col>
            <v-col>
                <p>
                    Die interaktive Kartenansicht spiegelt Kontext-gefilterte Einträge von Wikidata wider.
                    Inhalte auf Wikidata können grundsätzlich von jeder Person angelegt und geändert werden.
                    Viele der Einträge entstammen der <b>Gestapo.Terror.Orte</b>-Forschung.
                    Die folgende Literaturliste enthält Quellenangaben für die in der Forschung erhobenen
                    Inhalte, sowie die regelmäßige Kuratierung bereits existierender Inhalte Dritter.
                </p>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
import glossaryData from '../data/glossaryData';

export default {
    name: 'Bibliography',
    props: ['isMenuDisplayed'],
    data() {
        return {
            freeClientWidth: document.documentElement.clientWidth,
        };
    },
    created() {
        window.addEventListener('resize', this.setFreeClientWidth);
    },
    destroyed() {
        window.removeEventListener('resize', this.setFreeClientWidth);
    },
    mounted() {
        
    },
    methods: {
        /**
         * Determine width of free space between content and client border.
         *
         * @param event
         */
        setFreeClientWidth(event) {
            const container = document.getElementById('container');

            this.freeClientWidth = (document.documentElement.clientWidth - container.clientWidth) / 2;
        },
    },
};
</script>

<style scoped>
.font-family-special-elite {
    font-family: "Special Elite" !important;
}

.biblio {
    filter: opacity(0.75);
}
.biblio li:not(:last-child) {
    margin-bottom: 0.25rem;
}
</style>