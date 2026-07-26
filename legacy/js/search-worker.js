/**
 * Web Worker for Heavy Search Operations
 * Performs country filtering in background thread to prevent UI blocking
 */

self.addEventListener('message', (e) => {
    const { countries, searchTerm, enableFuzzy } = e.data;
    
    try {
        let results;
        
        if (!searchTerm) {
            // Return all countries if no search term
            results = countries;
        } else if (enableFuzzy) {
            // Fuzzy search with scoring
            results = countries
                .map(country => ({
                    ...country,
                    score: fuzzyScore(country.searchText, searchTerm)
                }))
                .filter(country => country.score > 0)
                .sort((a, b) => b.score - a.score);
        } else {
            // Simple substring search
            results = countries.filter(country => 
                country.searchText.includes(searchTerm.toLowerCase())
            );
        }
        
        // Send results back to main thread
        self.postMessage({
            success: true,
            results: results,
            searchTerm: searchTerm
        });
        
    } catch (error) {
        // Send error back to main thread
        self.postMessage({
            success: false,
            error: error.message,
            searchTerm: searchTerm
        });
    }
});

/**
 * Fuzzy matching algorithm with scoring
 */
function fuzzyScore(text, query) {
    let score = 0;
    let queryIndex = 0;
    
    // Character matching with position bonus
    for (let i = 0; i < text.length && queryIndex < query.length; i++) {
        if (text[i] === query[queryIndex]) {
            // Higher score for earlier matches
            score += (query.length - queryIndex) * 10;
            queryIndex++;
        }
    }
    
    // Must match all query characters
    if (queryIndex !== query.length) return 0;
    
    // Bonus scoring
    if (text.startsWith(query)) {
        score += 100; // Starts with query
    } else if (text.includes(' ' + query)) {
        score += 50; // Word boundary match
    }
    
    // Exact match bonus
    if (text === query) {
        score += 200;
    }
    
    // Length penalty (prefer shorter matches)
    score -= text.length - query.length;
    
    return score;
}

