/**
 * Search Engine Test Suite
 * Manual tests to verify all functionality
 */

const SearchEngineTests = (() => {
    const testResults = [];

    // Mock question data for testing
    const mockQuestions = [
        {
            id: 'test-1',
            question: 'What is Laravel middleware?',
            answer: 'Middleware provides a convenient mechanism for filtering HTTP requests entering your application. For example, Laravel includes middleware that verifies the user of your application is authenticated.',
            category: 'PHP Laravel API Security',
            difficulty: 'intermediate',
            tags: ['laravel', 'middleware', 'http', 'security'],
            source: 'php-laravel-api-security'
        },
        {
            id: 'test-2',
            question: 'How do React hooks work?',
            answer: 'Hooks are functions that let you use state and other React features without writing a class. They were introduced in React 16.8 and allow you to reuse stateful logic.',
            category: 'Frontend React NextJS',
            difficulty: 'advanced',
            tags: ['react', 'hooks', 'state', 'components'],
            source: 'frontend-react-nextjs'
        },
        {
            id: 'test-3',
            question: 'What is database indexing?',
            answer: 'Database indexing is a data structure technique to efficiently retrieve records from database files based on some attributes. Indexes improve the speed of data retrieval operations.',
            category: 'Database Optimization',
            difficulty: 'intermediate',
            tags: ['database', 'indexing', 'performance', 'optimization'],
            source: 'database-optimization'
        },
        {
            id: 'test-4',
            question: 'Explain OAuth 2.0 authentication flow',
            answer: 'OAuth 2.0 is an authorization framework that enables applications to obtain limited access to user accounts. It works by delegating user authentication to the service that hosts the user account.',
            category: 'PHP Laravel API Security',
            difficulty: 'advanced',
            tags: ['oauth', 'authentication', 'security', 'api'],
            source: 'php-laravel-api-security'
        },
        {
            id: 'test-5',
            question: 'What are JavaScript closures?',
            answer: 'A closure is the combination of a function bundled together with references to its surrounding state (the lexical environment). Closures are created every time a function is created.',
            category: 'Frontend React NextJS',
            difficulty: 'beginner',
            tags: ['javascript', 'closures', 'scope', 'functions'],
            source: 'frontend-react-nextjs'
        }
    ];

    /**
     * Test 1: Fuzzy Search - should match with typos
     */
    const testFuzzySearch = () => {
        console.log('\n--- Test 1: Fuzzy Search ---');
        
        const Fuse = window.Fuse;
        if (!Fuse) {
            return logResult('Fuzzy Search', false, 'Fuse.js not loaded');
        }

        const fuse = new Fuse(mockQuestions, {
            keys: ['question', 'answer', 'tags'],
            threshold: 0.4,
            includeScore: true
        });

        // Test with typo: "midleware" instead of "middleware"
        const results = fuse.search('midleware');
        const passed = results.length > 0 && results[0].item.id === 'test-1';
        
        logResult('Fuzzy Search (typo tolerance)', passed, 
            passed ? `Found "${results[0].item.question}" with typo` : 'Failed to match with typo');
        
        return passed;
    };

    /**
     * Test 2: Multi-field Search - search across question, answer, and tags
     */
    const testMultiFieldSearch = () => {
        console.log('\n--- Test 2: Multi-field Search ---');
        
        const Fuse = window.Fuse;
        if (!Fuse) {
            return logResult('Multi-field Search', false, 'Fuse.js not loaded');
        }

        const fuse = new Fuse(mockQuestions, {
            keys: [
                { name: 'question', weight: 0.4 },
                { name: 'answer', weight: 0.3 },
                { name: 'tags', weight: 0.2 },
                { name: 'category', weight: 0.1 }
            ],
            threshold: 0.4,
            includeScore: true
        });

        // Search for term that appears in different fields
        const results = fuse.search('authentication');
        const hasResults = results.length > 0;
        
        logResult('Multi-field Search', hasResults, 
            hasResults ? `Found ${results.length} results` : 'No results found');
        
        return hasResults;
    };

    /**
     * Test 3: Category Filter
     */
    const testCategoryFilter = () => {
        console.log('\n--- Test 3: Category Filter ---');
        
        const category = 'PHP Laravel API Security';
        const filtered = mockQuestions.filter(q => q.category === category);
        const passed = filtered.length === 2; // Should find test-1 and test-4
        
        logResult('Category Filter', passed, 
            `Found ${filtered.length} questions in "${category}"`);
        
        return passed;
    };

    /**
     * Test 4: Difficulty Filter
     */
    const testDifficultyFilter = () => {
        console.log('\n--- Test 4: Difficulty Filter ---');
        
        const difficulty = 'advanced';
        const filtered = mockQuestions.filter(q => q.difficulty === difficulty);
        const passed = filtered.length === 2; // Should find test-2 and test-4
        
        logResult('Difficulty Filter', passed, 
            `Found ${filtered.length} questions with difficulty "${difficulty}"`);
        
        return passed;
    };

    /**
     * Test 5: Tag Filter
     */
    const testTagFilter = () => {
        console.log('\n--- Test 5: Tag Filter ---');
        
        const tag = 'security';
        const filtered = mockQuestions.filter(q => q.tags.includes(tag));
        const passed = filtered.length === 2; // Should find test-1 and test-4
        
        logResult('Tag Filter', passed, 
            `Found ${filtered.length} questions with tag "${tag}"`);
        
        return passed;
    };

    /**
     * Test 6: Combined Filters (Category + Difficulty + Tag)
     */
    const testCombinedFilters = () => {
        console.log('\n--- Test 6: Combined Filters ---');
        
        const filters = {
            category: 'PHP Laravel API Security',
            difficulty: 'advanced',
            tag: 'security'
        };
        
        const filtered = mockQuestions.filter(q => 
            q.category === filters.category &&
            q.difficulty === filters.difficulty &&
            q.tags.includes(filters.tag)
        );
        
        const passed = filtered.length === 1; // Should find only test-4
        
        logResult('Combined Filters', passed, 
            `Found ${filtered.length} question(s) matching all filters`);
        
        return passed;
    };

    /**
     * Test 7: Search History (localStorage)
     */
    const testSearchHistory = () => {
        console.log('\n--- Test 7: Search History ---');
        
        try {
            const testKey = 'test_search_history';
            const testData = ['query1', 'query2', 'query3'];
            
            // Test write
            localStorage.setItem(testKey, JSON.stringify(testData));
            
            // Test read
            const retrieved = JSON.parse(localStorage.getItem(testKey));
            
            // Test clear
            localStorage.removeItem(testKey);
            
            const passed = JSON.stringify(retrieved) === JSON.stringify(testData);
            
            logResult('Search History (localStorage)', passed, 
                passed ? 'localStorage read/write successful' : 'localStorage failed');
            
            return passed;
        } catch (error) {
            logResult('Search History', false, `Error: ${error.message}`);
            return false;
        }
    };

    /**
     * Test 8: Highlighting Function
     */
    const testHighlighting = () => {
        console.log('\n--- Test 8: Text Highlighting ---');
        
        const text = 'What is Laravel middleware?';
        const query = 'Laravel';
        
        const highlighted = highlightText(text, query);
        const passed = highlighted.includes('<mark>') && highlighted.includes('</mark>');
        
        logResult('Text Highlighting', passed, 
            passed ? `Result: ${highlighted}` : 'Highlighting failed');
        
        return passed;
    };

    /**
     * Test 9: Relevance Scoring
     */
    const testRelevanceScoring = () => {
        console.log('\n--- Test 9: Relevance Scoring ---');
        
        const Fuse = window.Fuse;
        if (!Fuse) {
            return logResult('Relevance Scoring', false, 'Fuse.js not loaded');
        }

        const fuse = new Fuse(mockQuestions, {
            keys: ['question', 'answer'],
            includeScore: true
        });

        const results = fuse.search('Laravel');
        
        // Results should be sorted by relevance (lower score = better match)
        const passed = results.length > 0 && 
                      results[0].score !== undefined &&
                      results[0].score >= 0 && results[0].score <= 1;
        
        logResult('Relevance Scoring', passed, 
            passed ? `Top result score: ${results[0].score.toFixed(3)}` : 'Scoring failed');
        
        return passed;
    };

    /**
     * Test 10: Empty Query Handling
     */
    const testEmptyQuery = () => {
        console.log('\n--- Test 10: Empty Query Handling ---');
        
        const Fuse = window.Fuse;
        if (!Fuse) {
            return logResult('Empty Query', false, 'Fuse.js not loaded');
        }

        const fuse = new Fuse(mockQuestions, {
            keys: ['question']
        });

        const results = fuse.search('');
        
        // Empty query should return empty results
        const passed = results.length === 0;
        
        logResult('Empty Query Handling', passed, 
            passed ? 'Correctly returned no results' : 'Should return empty for empty query');
        
        return passed;
    };

    /**
     * Helper: Highlight text (simple implementation)
     */
    const highlightText = (text, query) => {
        if (!query) return text;
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    };

    /**
     * Helper: Log test result
     */
    const logResult = (testName, passed, message) => {
        const result = {
            test: testName,
            passed: passed,
            message: message
        };
        
        testResults.push(result);
        
        const icon = passed ? '✅' : '❌';
        console.log(`${icon} ${testName}: ${message}`);
        
        return passed;
    };

    /**
     * Run all tests
     */
    const runAllTests = () => {
        console.clear();
        console.log('===========================================');
        console.log('  SEARCH ENGINE TEST SUITE');
        console.log('===========================================');
        
        testResults.length = 0;
        
        testFuzzySearch();
        testMultiFieldSearch();
        testCategoryFilter();
        testDifficultyFilter();
        testTagFilter();
        testCombinedFilters();
        testSearchHistory();
        testHighlighting();
        testRelevanceScoring();
        testEmptyQuery();
        
        console.log('\n===========================================');
        console.log('  TEST SUMMARY');
        console.log('===========================================');
        
        const passed = testResults.filter(r => r.passed).length;
        const total = testResults.length;
        const percentage = ((passed / total) * 100).toFixed(1);
        
        console.log(`Passed: ${passed}/${total} (${percentage}%)`);
        console.log('===========================================\n');
        
        return { passed, total, percentage, results: testResults };
    };

    /**
     * Run individual test
     */
    const runTest = (testName) => {
        const tests = {
            'fuzzy': testFuzzySearch,
            'multifield': testMultiFieldSearch,
            'category': testCategoryFilter,
            'difficulty': testDifficultyFilter,
            'tag': testTagFilter,
            'combined': testCombinedFilters,
            'history': testSearchHistory,
            'highlighting': testHighlighting,
            'scoring': testRelevanceScoring,
            'empty': testEmptyQuery
        };
        
        if (tests[testName]) {
            console.clear();
            tests[testName]();
        } else {
            console.error(`Test "${testName}" not found. Available tests:`, Object.keys(tests));
        }
    };

    // Public API
    return {
        runAllTests,
        runTest,
        getResults: () => testResults,
        getMockData: () => mockQuestions
    };
})();

// Make available globally
window.SearchEngineTests = SearchEngineTests;

// Auto-run tests if in test mode
if (window.location.search.includes('test=true')) {
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
            SearchEngineTests.runAllTests();
        }, 1000);
    });
}

console.log('Search Engine Tests loaded. Run SearchEngineTests.runAllTests() to execute.');
