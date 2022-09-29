/**
 * Convert query to Wikidata query service URL.
 *
 * @param query
 * @returns {string}
 */
function getQueryUrl(query) {
    query = query.replace(/\s+/g, ' ').replace(/\sSELECT/i, '\nSELECT');
    return 'https://query.wikidata.org/embed.html#' + encodeURIComponent(query);
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

export default Object.freeze({
    graphViewRelations,
});