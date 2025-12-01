import { ref } from "vue";

/**
 * Composable for fetching info content for form fields and sections
 *
 * @returns {Object} Methods for fetching info content
 */
export function useFetchInfo() {
    /**
     * Get information content for a field
     *
     * @param {string} fieldId - The ID of the field to get information for
     * @returns {Object} The information content for the field
     */
    const getInfoContent = (fieldId) => {
        // Field information mapping
        const fieldInfo = {
            gestapoPlaceType: {
                title: "Ort des Gestapoterrors",
                content:
                    "Wählen Sie den Typ des Ortes des Gestapoterrors aus. Die Auswahl bestimmt, welche weiteren Felder angezeigt werden.",
            },
            title: {
                title: "Titel des Eintrags",
                content:
                    "Geben Sie einen aussagekräftigen Titel für den Ort des Gestapoterrors ein. Dieser wird in Wikidata als Bezeichner verwendet.",
            },
            description: {
                title: "Kurzbeschreibung",
                content:
                    "Kurze Beschreibung des Gestapoterrors-Ortes. Diese wird in Wikidata als Kurzbeschreibung verwendet.",
            },
            foundingDate: {
                title: "Datum der Gründung/Erbauung",
                content:
                    "Das Datum, an dem der Ort des Gestapoterrors gegründet oder erbaut wurde (P571 in Wikidata).",
            },
            dissolutionDate: {
                title: "Auflösungsdatum",
                content:
                    "Das Datum, an dem der Ort des Gestapoterrors aufgelöst oder geschlossen wurde (P576 in Wikidata).",
            },
            location: {
                title: "Lage",
                content:
                    "Die geografischen Koordinaten des Ortes des Gestapoterrors (P625 in Wikidata).",
            },
            address: {
                title: "Adresse",
                content:
                    "Die Adresse des Ortes des Gestapoterrors (P6375 in Wikidata).",
            },
            keyEvent: {
                title: "Schlüsselereignis",
                content:
                    "Bedeutende Ereignisse, die mit diesem Ort verbunden sind (P793 in Wikidata).",
            },
            director: {
                title: "Leitung",
                content:
                    "Die Person, die den Ort des Gestapoterrors geleitet hat (P1037 in Wikidata).",
            },
            employees: {
                title: "Mitarbeitende",
                content:
                    "Die Anzahl der Personen, die an diesem Ort beschäftigt waren (P1128 in Wikidata).",
            },
            prisonerCount: {
                title: "Häftlingszahl",
                content:
                    "Die Anzahl der Häftlinge, die an diesem Ort inhaftiert waren (P5630 in Wikidata).",
            },
            parentOrg: {
                title: "Übergeordnete Organisation",
                content:
                    "Die Organisation, der dieser Ort des Gestapoterrors untergeordnet war (P749 in Wikidata).",
            },
            childOrg: {
                title: "Nachgeordnete Organisation",
                content:
                    "Organisationen, die diesem Ort des Gestapoterrors untergeordnet waren (P355 in Wikidata).",
            },
            predecessorOrg: {
                title: "Vorgängerorganisation",
                content:
                    "Die Organisation, die diesem Ort des Gestapoterrors vorausging (P1365 in Wikidata).",
            },
            successorOrg: {
                title: "Nachfolgeorganisation",
                content:
                    "Die Organisation, die diesem Ort des Gestapoterrors nachfolgte (P1366 in Wikidata).",
            },
            image: {
                title: "Bild",
                content:
                    "Ein Bild des Ortes des Gestapoterrors (P18 in Wikidata). Dieses Bild muss unter einer freien Lizenz stehen und kann von Wikimedia Commons hochgeladen werden.",
            },
            sources: {
                title: "Quellen",
                content:
                    "Quellen, die die Informationen zu diesem Ort des Gestapoterrors belegen.",
            },
        };

        return (
            fieldInfo[fieldId] || {
                title: "Information",
                content: "Keine Information verfügbar.",
            }
        );
    };

    /**
     * Get information content for a section
     *
     * @param {string} sectionId - The ID of the section to get information for
     * @returns {Object} The information content for the section
     */
    const getSectionInfoContent = (sectionId) => {
        // Section information mapping
        const sectionInfo = {
            gestapoPlaceType: {
                title: "Ort des Gestapoterrors",
                content: `
                    <p>Wählen Sie den spezifischen Typ des Ortes des Gestapoterrors aus der Liste aus.</p>
                    <ul>
                        <li><strong>Arbeitserziehungslager (Q277565)</strong>: Ein Lager, in dem Zwangsarbeit als Form der "Umerziehung" eingesetzt wurde.</li>
                        <li><strong>Gefängnis (Q40357)</strong>: Eine Einrichtung, in der Personen inhaftiert wurden.</li>
                        <li><strong>Erweitertes Polizeigefängnis (Q108047650)</strong>: Ein Gefängnis mit erweiterten Befugnissen der Polizei.</li>
                        <li><strong>Außendienststelle (Q108047541)</strong>: Eine externe Niederlassung oder Zweigstelle der Gestapo.</li>
                        <li><strong>Staatspolizeistelle (Q108047567)</strong>: Eine regionale Dienststelle der Gestapo.</li>
                        <li><strong>Staatspolizeileitstelle (Q108047581)</strong>: Eine übergeordnete Leitstelle der Gestapo.</li>
                    </ul>
                `,
            },
            baseInfo: {
                title: "Grundlegende Informationen",
                content: `
                    <p>Geben Sie hier die grundlegenden Informationen zum Ort des Gestapoterrors ein:</p>
                    <ul>
                        <li><strong>Titel</strong>: Ein eindeutiger, beschreibender Name für den Ort</li>
                        <li><strong>Kurzbeschreibung</strong>: Eine kurze Zusammenfassung (1-2 Sätze)</li>
                    </ul>
                    <p>Diese Informationen werden in Wikidata als Bezeichner und Kurzbeschreibung verwendet.</p>
                `,
            },
            location: {
                title: "Lage",
                content: `
                    <p>Wählen Sie die genaue geografische Lage des Ortes auf der Karte aus (P625 in Wikidata).</p>
                    <p>Sie können nach einem Ort suchen oder direkt auf der Karte klicken, um die Koordinaten festzulegen.</p>
                    <p><strong>Hinweis</strong>: Die Lageangabe ist ein Pflichtfeld, da Orte des Gestapoterrors stets geografisch verortet werden müssen.</p>
                `,
            },
            timePeriod: {
                title: "Zeitraum",
                content: `
                    <p>Geben Sie Informationen zum zeitlichen Bestehen des Ortes an:</p>
                    <ul>
                        <li><strong>Datum der Gründung/Erbauung (P571)</strong>: Wann wurde der Ort gegründet oder errichtet?</li>
                        <li><strong>Auflösungsdatum (P576)</strong>: Wann wurde der Ort aufgelöst oder geschlossen?</li>
                    </ul>
                    <p>Für beide Datumsangaben können Sie verschiedene Genauigkeitsstufen angeben (Tag, Monat, Jahr).</p>
                `,
            },
            address: {
                title: "Adresse",
                content: `
                    <p>Die Adresse des Ortes des Gestapoterrors (P6375 in Wikidata).</p>
                    <p>Geben Sie die historische Adresse ein, sofern bekannt. Die Adresse kann auch die heutige Adresse sein, wenn sich die Straßenbezeichnung geändert hat.</p>
                `,
            },
            keyEvent: {
                title: "Schlüsselereignis",
                content: `
                    <p>Bedeutende Ereignisse, die mit diesem Ort verbunden sind (P793 in Wikidata).</p>
                    <p>Suchen Sie nach bekannten Ereignissen, die mit diesem Ort in Verbindung stehen, z.B. wichtige Verhaftungen, Widerstandsaktionen, etc.</p>
                `,
            },
            director: {
                title: "Leitung",
                content: `
                    <p>Die Person, die den Ort des Gestapoterrors geleitet hat (P1037 in Wikidata).</p>
                    <p>Suchen Sie nach dem Namen des Leiters/der Leiterin. Wenn mehrere Personen den Ort im Laufe der Zeit geleitet haben, können Sie mehrere Einträge hinzufügen.</p>
                `,
            },
            employees: {
                title: "Mitarbeitende",
                content: `
                    <p>Die Anzahl der Personen, die an diesem Ort beschäftigt waren (P1128 in Wikidata).</p>
                    <p>Geben Sie eine Schätzung oder genaue Anzahl ein, sofern bekannt.</p>
                `,
            },
            prisonerCount: {
                title: "Häftlingszahl",
                content: `
                    <p>Die Anzahl der Häftlinge, die an diesem Ort inhaftiert waren (P5630 in Wikidata).</p>
                    <p>Geben Sie eine Schätzung oder genaue Anzahl ein, sofern bekannt.</p>
                    <p>Sie können auch zusätzliche Informationen zur Erhebung dieser Zahl angeben (Nachweisumstände, P1480).</p>
                `,
            },
            organizations: {
                title: "Organisatorische Beziehungen",
                content: `
                    <p>Informationen zu den organisatorischen Beziehungen dieses Ortes:</p>
                    <ul>
                        <li><strong>Übergeordnete Organisation (P749)</strong>: Die Organisation, der dieser Ort untergeordnet war</li>
                        <li><strong>Nachgeordnete Organisation (P355)</strong>: Organisationen, die diesem Ort untergeordnet waren</li>
                        <li><strong>Vorgängerorganisation (P1365)</strong>: Die Organisation, die diesem Ort vorausging</li>
                        <li><strong>Nachfolgeorganisation (P1366)</strong>: Die Organisation, die diesem Ort nachfolgte</li>
                    </ul>
                `,
            },
            image: {
                title: "Bild",
                content: `
                    <p>Ein Bild des Ortes des Gestapoterrors (P18 in Wikidata).</p>
                    <p>Das Bild sollte unter einer freien Lizenz stehen und kann von Wikimedia Commons hochgeladen werden.</p>
                    <p>Sie können nach bestehenden Bildern suchen oder ein neues Bild hochladen.</p>
                `,
            },
            sources: {
                title: "Quellen",
                content: `
                    <p>Quellen, die die Informationen zu diesem Ort des Gestapoterrors belegen.</p>
                    <p>Alle Angaben müssen mit Quellen belegt werden. Geben Sie möglichst spezifische und verlässliche Quellen an.</p>
                    <p>Mögliche Quellen sind:</p>
                    <ul>
                        <li>Fachliteratur</li>
                        <li>Archivquellen</li>
                        <li>Zeitzeugenberichte</li>
                        <li>Offizielle Dokumente und Berichte</li>
                    </ul>
                `,
            },
        };

        return (
            sectionInfo[sectionId] || {
                title: "Information",
                content: "Keine Information verfügbar.",
            }
        );
    };

    return {
        getInfoContent,
        getSectionInfoContent,
    };
}
