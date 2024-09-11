/**
 * Convert query to Wikidata query service URL.
 *
 * @param query
 * @returns {string}
 */
function getQueryUrl(query) {
    let trimmedQuery = query.trim().replace(/\s+/g, ' ').replace(/\sSELECT/i, '\nSELECT');
    return 'https://query.wikidata.org/embed.html#' + encodeURIComponent(trimmedQuery);
}

// View relations between
// * places of Gestapo terror,
// * group instances of place of Gestapo terror,
// * memorials, which commemorate a place of Gestapo terror,
// * events, where perpetrator is a place of Gestapo terror.
const graphViewRelationsQuery = `
    #defaultView:Graph
    SELECT ?nodeFrom ?nodeFromLabel (SAMPLE(?nodeFromImage) AS ?nodeFromImageSample) ?rgb ?nodeTo ?nodeToLabel ?nodeToImage
    WHERE
    { 
        {
            SELECT       
                (?location AS ?nodeFrom)  
                (?locationImage AS ?nodeFromImage)          
                ("456879" AS ?rgb)
                (?instanceOf AS ?nodeTo)
            WHERE 
            {      
                VALUES ?statePoliceOffices { wd:Q108048310 wd:Q2101520 wd:Q108047567 }.
                VALUES ?statePoliceHeadquarters { wd:Q108047581 }.
                VALUES ?fieldOffices { wd:Q108047541 wd:Q108047989 wd:Q108047676 wd:Q108047833 wd:Q108047775 }.          
                ?location wdt:P31 wd:Q106996250;
                       wdt:P31 ?instanceOf.
                FILTER(?instanceOf IN (?statePoliceOffices, ?statePoliceHeadquarters, ?fieldOffices)).
                OPTIONAL { ?location wdt:P18 ?locationImage. }
            } 
        }
        UNION
        {
            SELECT       
                (?location AS ?nodeFrom)  
                (?locationImage AS ?nodeFromImage)          
                ("3d3b3d" AS ?rgb)
                (?instanceOf AS ?nodeTo)
            WHERE 
            {      
                VALUES ?extPolicePrisons { wd:Q108047650 wd:Q108048094 }.
                VALUES ?laborEducationCamps { wd:Q277565 }.
                VALUES ?prisons { wd:Q40357 }.          
                ?location wdt:P31 wd:Q106996250;
                       wdt:P31 ?instanceOf.
                FILTER(?instanceOf IN (?extPolicePrisons, ?laborEducationCamps, ?prisons)).
                OPTIONAL { ?location wdt:P18 ?locationImage. }
            } 
        }  
        UNION
        {
            SELECT       
                (?memorial AS ?nodeFrom)  
                (?memorialImage AS ?nodeFromImage)          
                ("f3a923" AS ?rgb)
                (?location AS ?nodeTo)
            WHERE 
            {      
                VALUES ?memorials { wd:Q5003624 }.
                ?location wdt:P31 wd:Q106996250.
                ?memorial wdt:P547 ?location; 
                       wdt:P31 ?instanceOf.
                FILTER(?instanceOf IN (?memorials)).                        
                OPTIONAL { ?memorial wdt:P18 ?memorialImage. }
            } 
        }
        UNION
        {
            SELECT       
                (?event AS ?nodeFrom)  
                (?eventImage AS ?nodeFromImage)          
                ("b32527" AS ?rgb)
                (?location AS ?nodeTo)
            WHERE 
            {      
                VALUES ?events { wd:Q6983405 }.
                ?location wdt:P31 wd:Q106996250.
                ?event wdt:P8031 ?location; 
                       wdt:P31 ?instanceOf.
                FILTER(?instanceOf IN (?events)).                        
                OPTIONAL { ?event wdt:P18 ?eventImage. }
            } 
        }  
        SERVICE wikibase:label { bd:serviceParam wikibase:language "de,en" }    
    }
    GROUP BY ?nodeFrom ?nodeFromLabel ?rgb ?nodeTo ?nodeToLabel ?nodeToImage`;

const graphViewRelations = Object.freeze({
    imageUrl: '/images/charts/graph-view-relations.png',
    queryUrl: getQueryUrl(graphViewRelationsQuery),
    subtitle:
        `Ein interaktiver Graph, der die hierarchische Anordnung und Untersuchung von Daten zu Organisationen, 
        Ereignissen und Erinnerungsorten ermöglicht.`,
    title: 'Verknüpfung der Daten',
});

// G 2

const graph2 = Object.freeze({
    imageUrl: '/images/charts/graph-view-2.png',
    queryUrl: getQueryUrl(`
    #defaultView:ScatterChart
    SELECT ?location ?locationLabel ?employee ?employeeLabel
    WHERE {
    ?location      wdt:P31   wd:Q106996250 ;
                    wdt:P1037 ?employee .
    ?employee      wdt:P31   wd:Q5 ;
                    wdt:P108  wd:Q43250 .
    SERVICE wikibase:label {
        bd:serviceParam wikibase:language "[AUTO_LANGUAGE],de,en"
    }
    }
    LIMIT 500
    `),
    subtitle:
        `Ein interaktiver Graph, der die erfassten Gestapo-Mitarbeiter*innen in leitenden Positionen in Beziehung zu ihren Einsatzorten setzt.`,
    title: 'Einsatzorte erfasster Gestapo-Mitarbeiter*innen',
});

//G 3

const graph3 = Object.freeze({
    imageUrl: '/images/charts/graph-view-3.png',
    queryUrl: getQueryUrl(`
    #defaultView:Table
    SELECT
    (COUNT (DISTINCT ?police) AS ?policeCount)
    (COUNT (DISTINCT ?prison) AS ?prisonCount)
    (COUNT (DISTINCT ?memorial) AS ?memorialCount)
    WHERE {
    {
        ?police           wdt:P31   wd:Q106996250 ,
                                    ?o .
        FILTER (?o IN (wd:Q35535, wd:Q108047567, wd:Q108047676, wd:Q108047581, wd:Q108047541, wd:Q2101520))
    } UNION {
        ?prison           wdt:P31   wd:Q106996250 ,
                                    ?o .
        FILTER (?o IN (wd:Q277565, wd:Q40357, wd:Q108047581))
    } UNION {
        ?memorial         wdt:P31   wd:Q5003624 ;
                        wdt:P547  ?pgt .
        ?pgt              wdt:P31   wd:Q106996250 .
    }
    SERVICE wikibase:label {
        bd:serviceParam wikibase:language "[AUTO_LANGUAGE],de,en"
    }
    }
    GROUP BY ?prisonCount ?memorialCount
    LIMIT 5000
    `),
    subtitle:
        `Ein interaktiver Graph, der die aufzeigt, wie die verschiedenen Orte um das Wirken der Gestapo im Verhältnis standen bzw. stehen.`,
    title: 'Verhältnis von Dienststellen, Haftstätten, und Erinnerungsorten',
});

// G 4

const graph4 = Object.freeze({
    imageUrl: '/images/charts/graph-view-4.png',
    queryUrl: getQueryUrl(`
    #defaultView:Timeline
    SELECT DISTINCT ?memorial ?memorialLabel ?openingDate
    WHERE {
    ?memorial    wdt:P31   wd:Q5003624 ;
                wdt:P547  ?location ;
                wdt:P1619 ?openingDate .
    ?location    wdt:P31   wd:Q106996250 .
    SERVICE wikibase:label {
        bd:serviceParam wikibase:language "[AUTO_LANGUAGE],de,en"
    }
    }
    ORDER BY ASC(?openingDate)
    LIMIT 500
    `),
    subtitle:
        `Eine Tabelle, die die verschiedenen Erinnerungsorte zu Gestapo-Verbrechen sortiert nach Eröffungsdatum auflistet.`,
    title: 'Erinnerungsorte und zugehörige Eröffnungsdaten',
});

export default Object.freeze({
    graphViewRelations,
    graph2,
    graph3,
    graph4
});