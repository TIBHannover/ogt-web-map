const searchWikidataItems = async (field, query) => {
    if (!query || query.trim() === "") return;
    
    isWikidataSearchLoading.value[field] = true;
    wikidataSearchPerformed.value[field] = true;
    
    try {
        let searchEndpoint = '/wikidata/search/people';
        let searchParams = { query };
        
        // Use the existing WikidataSearchController endpoint
        const response = await fetch(`${searchEndpoint}?${new URLSearchParams(searchParams).toString()}`);
        
        if (!response.ok) {
            throw new Error(`Server responded with status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error(`Expected JSON response but got ${contentType || 'unknown content type'}`);
        }
        
        const data = await response.json();
        
        // The existing controller returns data directly, not wrapped in a 'data' property
        handleWikidataResults(field, data);
    } catch (error) {
        console.error(`Error searching Wikidata for ${field}:`, error);
        wikidataSearchResults.value[field] = [];
    } finally {
        isWikidataSearchLoading.value[field] = false;
    }
};