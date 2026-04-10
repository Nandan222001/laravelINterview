// ============================================================================
// DATA LOADER MODULE
// Fetches and parses markdown question files from /interview-bank/**/questions*.md
// Implements caching, regex parsing for multiple formats, and searchable indexing
// ============================================================================

const DataLoader = (() => {
    // Configuration
    const config = {
        questionFilesPattern: '/interview-bank/**/questions*.md',
        cacheKey: 'interview_bank_data',
        cacheVersionKey: 'interview_bank_cache_version',
        currentVersion: '1.0.0',
        cacheDuration: 24 * 60 * 60 * 1000, // 24 hours in milliseconds
        enableCache: true
    };

    // State
    let parsedData = null;
    let searchIndex = null;

    // ============================================================================
    // CACHE MANAGEMENT
    // ============================================================================

    /**
     * Check if cached data is valid and not expired
     */
    const isCacheValid = () => {
        if (!config.enableCache) return false;

        try {
            const cachedVersion = localStorage.getItem(config.cacheVersionKey);
            const cachedTimestamp = localStorage.getItem(`${config.cacheKey}_timestamp`);

            if (!cachedVersion || !cachedTimestamp) {
                return false;
            }

            if (cachedVersion !== config.currentVersion) {
                clearCache();
                return false;
            }

            const cacheAge = Date.now() - parseInt(cachedTimestamp, 10);
            if (cacheAge > config.cacheDuration) {
                clearCache();
                return false;
            }

            return true;
        } catch (error) {
            console.error('Cache validation error:', error);
            return false;
        }
    };

    /**
     * Get data from localStorage cache
     */
    const getCachedData = () => {
        try {
            const cached = localStorage.getItem(config.cacheKey);
            if (!cached) return null;

            return JSON.parse(cached);
        } catch (error) {
            console.error('Cache retrieval error:', error);
            clearCache();
            return null;
        }
    };

    /**
     * Save data to localStorage cache
     */
    const setCachedData = (data) => {
        if (!config.enableCache) return;

        try {
            localStorage.setItem(config.cacheKey, JSON.stringify(data));
            localStorage.setItem(config.cacheVersionKey, config.currentVersion);
            localStorage.setItem(`${config.cacheKey}_timestamp`, Date.now().toString());
        } catch (error) {
            console.error('Cache storage error:', error);
            // Handle quota exceeded error
            if (error.name === 'QuotaExceededError') {
                clearCache();
            }
        }
    };

    /**
     * Clear all cached data
     */
    const clearCache = () => {
        try {
            localStorage.removeItem(config.cacheKey);
            localStorage.removeItem(config.cacheVersionKey);
            localStorage.removeItem(`${config.cacheKey}_timestamp`);
        } catch (error) {
            console.error('Cache clear error:', error);
        }
    };

    // ============================================================================
    // FILE DISCOVERY
    // ============================================================================

    /**
     * Discover all question markdown files
     * Since we can't use glob patterns in browser, we'll manually list known directories
     */
    const discoverQuestionFiles = async () => {
        const knownDirectories = [
            { dir: 'realtime-communication', name: 'Realtime Communication' },
            { dir: 'php-laravel-api-security', name: 'PHP Laravel API Security' },
            { dir: 'database-optimization', name: 'Database Optimization' },
            { dir: 'database-general', name: 'Database General' },
            { dir: 'ai-llm-serverless', name: 'AI LLM Serverless' },
            { dir: 'devops-cloud-k8s', name: 'DevOps Cloud K8s' },
            { dir: 'frontend-react-nextjs', name: 'Frontend React NextJS' },
            { dir: 'cms-platforms', name: 'CMS Platforms' },
            { dir: 'general-php-interview', name: 'General PHP Interview' },
            { dir: 'web-technologies', name: 'Web Technologies' }
        ];

        const files = [];

        for (const { dir, name } of knownDirectories) {
            // Try multiple file name patterns
            const patterns = [
                `questions.md`,
                `questions_${dir}.md`,
                `questions_${dir}_1000.md`,
                `${dir}-questions.md`,
                `${dir}.md`
            ];

            for (const pattern of patterns) {
                const filePath = `/interview-bank/${dir}/${pattern}`;
                files.push({
                    path: filePath,
                    directory: dir,
                    category: name
                });
            }
        }

        return files;
    };

    // ============================================================================
    // FILE FETCHING
    // ============================================================================

    /**
     * Fetch a single markdown file
     */
    const fetchMarkdownFile = async (filePath) => {
        try {
            const response = await fetch(filePath);

            if (!response.ok) {
                if (response.status === 404) {
                    return null; // File doesn't exist, skip silently
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const content = await response.text();
            return content;
        } catch (error) {
            console.warn(`Failed to fetch ${filePath}:`, error.message);
            return null;
        }
    };

    /**
     * Fetch all question files
     */
    const fetchAllQuestionFiles = async () => {
        const files = await discoverQuestionFiles();
        const results = [];

        console.log(`Fetching ${files.length} potential question files...`);

        // Fetch all files in parallel
        const fetchPromises = files.map(async (fileInfo) => {
            const content = await fetchMarkdownFile(fileInfo.path);
            if (content) {
                return {
                    ...fileInfo,
                    content
                };
            }
            return null;
        });

        const fetchedFiles = await Promise.all(fetchPromises);

        // Filter out null results (files that don't exist)
        const validFiles = fetchedFiles.filter(file => file !== null);

        console.log(`Successfully fetched ${validFiles.length} question files`);

        return validFiles;
    };

    // ============================================================================
    // MARKDOWN PARSING - Multiple Format Support
    // ============================================================================

    /**
     * Extract metadata from markdown frontmatter or inline tags
     */
    const extractMetadata = (content) => {
        const metadata = {
            tags: [],
            difficulty: null,
            category: null,
            totalQuestions: 0
        };

        // Extract tags from **Tags**: format
        const tagsMatch = content.match(/\*\*Tags?\*\*:\s*`([^`]+)`/gi);
        if (tagsMatch) {
            tagsMatch.forEach(match => {
                const tags = match.match(/`([^`]+)`/g);
                if (tags) {
                    tags.forEach(tag => {
                        const cleanTag = tag.replace(/`/g, '').trim();
                        cleanTag.split(',').forEach(t => {
                            const trimmed = t.trim();
                            if (trimmed && !metadata.tags.includes(trimmed)) {
                                metadata.tags.push(trimmed);
                            }
                        });
                    });
                }
            });
        }

        // Extract difficulty from **Difficulty**: format
        const difficultyMatch = content.match(/\*\*Difficulty\*\*:\s*(\w+)/i);
        if (difficultyMatch) {
            metadata.difficulty = difficultyMatch[1].toLowerCase();
        }

        // Count questions (multiple patterns)
        const questionPatterns = [
            /^#{2,3}\s+Q\d+:/gm,                           // ## Q1: or ### Q1:
            /^\*\*Q\d+\*\*:/gm,                            // **Q1**:
            /^\d+\.\s+/gm,                                 // 1. numbered list
            /^#{2,3}\s+\d+\./gm,                          // ## 1. or ### 1.
            /^\*\*Question\*\*:/gm                         // **Question**:
        ];

        let maxCount = 0;
        questionPatterns.forEach(pattern => {
            const matches = content.match(pattern);
            if (matches && matches.length > maxCount) {
                maxCount = matches.length;
            }
        });

        metadata.totalQuestions = maxCount;

        return metadata;
    };

    /**
     * Parse heading-based Q&A format (### Q1: Question)
     */
    const parseHeadingBasedQA = (content, fileInfo) => {
        const questions = [];
        const regex = /^#{2,3}\s+(Q\d+):\s*(.+?)$([\s\S]*?)(?=^#{2,3}\s+Q\d+:|^#{1,3}\s+Section|$)/gmi;

        let match;
        while ((match = regex.exec(content)) !== null) {
            const questionNumber = match[1];
            const questionText = match[2].trim();
            let answerText = match[3].trim();

            // Extract difficulty and tags from answer section
            let difficulty = null;
            const tags = [];

            const diffMatch = answerText.match(/\*\*Difficulty\*\*:\s*(\w+)/i);
            if (diffMatch) {
                difficulty = diffMatch[1].toLowerCase();
            }

            const tagsMatch = answerText.match(/\*\*Tags?\*\*:\s*`([^`]+)`/i);
            if (tagsMatch) {
                tagsMatch[1].split(',').forEach(tag => tags.push(tag.trim()));
            }

            // Extract actual answer (remove metadata)
            answerText = answerText.replace(/\*\*Difficulty\*\*:.*$/m, '');
            answerText = answerText.replace(/\*\*Tags?\*\*:.*$/m, '');
            answerText = answerText.replace(/^\*\*Question\*\*:.*$/m, '');
            answerText = answerText.replace(/^\*\*Answer\*\*:\s*/m, '');
            answerText = answerText.trim();

            questions.push({
                id: `${fileInfo.directory}-${questionNumber}`,
                number: questionNumber,
                question: questionText,
                answer: answerText,
                difficulty: difficulty || 'medium',
                tags: tags,
                category: fileInfo.category,
                source: fileInfo.directory
            });
        }

        return questions;
    };

    /**
     * Parse numbered list format (1. Question)
     */
    const parseNumberedListQA = (content, fileInfo) => {
        const questions = [];
        const regex = /^(\d+)\.\s+(.+?)$/gm;

        let match;
        let currentQuestion = null;

        const lines = content.split('\n');
        
        for (let i = 0; i < lines.length; i++) {
            const line = lines[i];
            const questionMatch = line.match(/^(\d+)\.\s+(.+?)$/);

            if (questionMatch) {
                // Save previous question if exists
                if (currentQuestion) {
                    questions.push(currentQuestion);
                }

                // Start new question
                currentQuestion = {
                    id: `${fileInfo.directory}-Q${questionMatch[1]}`,
                    number: `Q${questionMatch[1]}`,
                    question: questionMatch[2].trim(),
                    answer: '',
                    difficulty: 'medium',
                    tags: [],
                    category: fileInfo.category,
                    source: fileInfo.directory
                };
            } else if (currentQuestion && line.trim()) {
                // Accumulate answer lines
                currentQuestion.answer += line + '\n';
            }
        }

        // Add last question
        if (currentQuestion) {
            questions.push(currentQuestion);
        }

        return questions;
    };

    /**
     * Parse bold Q&A format (**Q1**: Question)
     */
    const parseBoldQAFormat = (content, fileInfo) => {
        const questions = [];
        const regex = /\*\*(Q\d+)\*\*:\s*(.+?)(?=\*\*Q\d+\*\*:|$)/gs;

        let match;
        while ((match = regex.exec(content)) !== null) {
            const questionNumber = match[1];
            const fullText = match[2].trim();

            // Split question and answer if they're on separate lines
            const parts = fullText.split('\n').filter(l => l.trim());
            const question = parts[0] || '';
            const answer = parts.slice(1).join('\n').trim();

            questions.push({
                id: `${fileInfo.directory}-${questionNumber}`,
                number: questionNumber,
                question: question,
                answer: answer,
                difficulty: 'medium',
                tags: [],
                category: fileInfo.category,
                source: fileInfo.directory
            });
        }

        return questions;
    };

    /**
     * Parse Question/Answer heading format
     */
    const parseQuestionAnswerHeadings = (content, fileInfo) => {
        const questions = [];
        const regex = /\*\*Question\*\*:\s*(.+?)\s*\*\*Answer\*\*:\s*([\s\S]+?)(?=\*\*Question\*\*:|#{2,3}\s+|$)/gi;

        let match;
        let questionIndex = 1;
        while ((match = regex.exec(content)) !== null) {
            const questionText = match[1].trim();
            const answerText = match[2].trim();

            questions.push({
                id: `${fileInfo.directory}-Q${questionIndex}`,
                number: `Q${questionIndex}`,
                question: questionText,
                answer: answerText,
                difficulty: 'medium',
                tags: [],
                category: fileInfo.category,
                source: fileInfo.directory
            });

            questionIndex++;
        }

        return questions;
    };

    /**
     * Parse compact format (**Q11-20**: Topic summary)
     */
    const parseCompactSummaryFormat = (content, fileInfo) => {
        const questions = [];
        const regex = /\*\*Q(\d+)-?(\d+)?\*\*:\s*(.+?)(?:\n\*\*Q\d+\*\*:|$)/gs;

        let match;
        while ((match = regex.exec(content)) !== null) {
            const startNum = parseInt(match[1], 10);
            const endNum = match[2] ? parseInt(match[2], 10) : startNum;
            const summary = match[3].trim();

            // Create a summary entry
            questions.push({
                id: `${fileInfo.directory}-Q${startNum}-${endNum}`,
                number: `Q${startNum}${endNum !== startNum ? `-${endNum}` : ''}`,
                question: `Questions ${startNum}-${endNum}`,
                answer: summary,
                difficulty: 'medium',
                tags: [],
                category: fileInfo.category,
                source: fileInfo.directory,
                isSummary: true
            });
        }

        return questions;
    };

    /**
     * Main parser that tries all formats
     */
    const parseMarkdownContent = (content, fileInfo) => {
        let questions = [];

        // Try different parsing strategies
        const parsers = [
            parseHeadingBasedQA,
            parseBoldQAFormat,
            parseQuestionAnswerHeadings,
            parseNumberedListQA,
            parseCompactSummaryFormat
        ];

        for (const parser of parsers) {
            const result = parser(content, fileInfo);
            if (result.length > questions.length) {
                questions = result;
            }
        }

        // Extract metadata for all questions
        const metadata = extractMetadata(content);

        // Enhance questions with metadata
        questions = questions.map((q, index) => {
            // Extract inline tags from question or answer
            const inlineTags = [...q.tags];
            const tagMatches = (q.question + ' ' + q.answer).match(/`([^`]+)`/g);
            if (tagMatches) {
                tagMatches.forEach(match => {
                    const tag = match.replace(/`/g, '').trim();
                    if (tag.length < 30 && !inlineTags.includes(tag)) {
                        inlineTags.push(tag);
                    }
                });
            }

            // Extract difficulty from question text
            const diffMatch = q.question.match(/\(?(junior|mid|senior|staff|expert)\)?/i);
            const difficulty = diffMatch ? diffMatch[1].toLowerCase() : q.difficulty || 'medium';

            return {
                ...q,
                tags: [...new Set([...inlineTags, ...metadata.tags])].slice(0, 10), // Limit to 10 tags
                difficulty: difficulty,
                metadata: {
                    fileCategory: fileInfo.category,
                    totalQuestionsInFile: metadata.totalQuestions,
                    indexInFile: index + 1
                }
            };
        });

        return questions;
    };

    // ============================================================================
    // SEARCH INDEX BUILDING
    // ============================================================================

    /**
     * Build searchable index from parsed questions
     */
    const buildSearchIndex = (allQuestions) => {
        const index = {
            byId: {},
            byCategory: {},
            byDifficulty: {},
            byTag: {},
            all: allQuestions
        };

        allQuestions.forEach(question => {
            // Index by ID
            index.byId[question.id] = question;

            // Index by category
            if (!index.byCategory[question.category]) {
                index.byCategory[question.category] = [];
            }
            index.byCategory[question.category].push(question);

            // Index by difficulty
            if (!index.byDifficulty[question.difficulty]) {
                index.byDifficulty[question.difficulty] = [];
            }
            index.byDifficulty[question.difficulty].push(question);

            // Index by tags
            question.tags.forEach(tag => {
                if (!index.byTag[tag]) {
                    index.byTag[tag] = [];
                }
                index.byTag[tag].push(question);
            });
        });

        return index;
    };

    // ============================================================================
    // PUBLIC API
    // ============================================================================

    /**
     * Load all question data (with caching)
     */
    const loadData = async (forceRefresh = false) => {
        // Check cache first
        if (!forceRefresh && isCacheValid()) {
            console.log('Loading data from cache...');
            const cached = getCachedData();
            if (cached) {
                parsedData = cached.questions;
                searchIndex = cached.searchIndex;
                return { questions: parsedData, searchIndex };
            }
        }

        console.log('Fetching fresh data from server...');

        // Fetch all files
        const files = await fetchAllQuestionFiles();

        // Parse all files
        const allQuestions = [];
        files.forEach(file => {
            const questions = parseMarkdownContent(file.content, file);
            allQuestions.push(...questions);
        });

        console.log(`Parsed ${allQuestions.length} questions from ${files.length} files`);

        // Build search index
        searchIndex = buildSearchIndex(allQuestions);
        parsedData = allQuestions;

        // Cache the results
        setCachedData({
            questions: parsedData,
            searchIndex: {
                byCategory: Object.keys(searchIndex.byCategory),
                byDifficulty: Object.keys(searchIndex.byDifficulty),
                byTag: Object.keys(searchIndex.byTag),
                totalQuestions: allQuestions.length
            }
        });

        return { questions: parsedData, searchIndex };
    };

    /**
     * Search questions by query string
     */
    const search = (query, options = {}) => {
        if (!searchIndex) {
            console.warn('Search index not loaded. Call loadData() first.');
            return [];
        }

        const {
            categories = [],
            difficulties = [],
            tags = [],
            caseSensitive = false,
            maxResults = 100
        } = options;

        let results = searchIndex.all;

        // Filter by categories
        if (categories.length > 0) {
            results = results.filter(q => categories.includes(q.category));
        }

        // Filter by difficulties
        if (difficulties.length > 0) {
            results = results.filter(q => difficulties.includes(q.difficulty));
        }

        // Filter by tags
        if (tags.length > 0) {
            results = results.filter(q => 
                tags.some(tag => q.tags.includes(tag))
            );
        }

        // Text search
        if (query && query.trim()) {
            const searchTerm = caseSensitive ? query : query.toLowerCase();
            results = results.filter(q => {
                const questionText = caseSensitive ? q.question : q.question.toLowerCase();
                const answerText = caseSensitive ? q.answer : q.answer.toLowerCase();
                const tagsText = caseSensitive ? q.tags.join(' ') : q.tags.join(' ').toLowerCase();

                return questionText.includes(searchTerm) ||
                       answerText.includes(searchTerm) ||
                       tagsText.includes(searchTerm);
            });
        }

        return results.slice(0, maxResults);
    };

    /**
     * Get questions by category
     */
    const getByCategory = (category) => {
        if (!searchIndex) return [];
        return searchIndex.byCategory[category] || [];
    };

    /**
     * Get questions by difficulty
     */
    const getByDifficulty = (difficulty) => {
        if (!searchIndex) return [];
        return searchIndex.byDifficulty[difficulty] || [];
    };

    /**
     * Get questions by tag
     */
    const getByTag = (tag) => {
        if (!searchIndex) return [];
        return searchIndex.byTag[tag] || [];
    };

    /**
     * Get question by ID
     */
    const getById = (id) => {
        if (!searchIndex) return null;
        return searchIndex.byId[id] || null;
    };

    /**
     * Get statistics about loaded data
     */
    const getStats = () => {
        if (!searchIndex) return null;

        return {
            totalQuestions: searchIndex.all.length,
            categories: Object.keys(searchIndex.byCategory).map(cat => ({
                name: cat,
                count: searchIndex.byCategory[cat].length
            })),
            difficulties: Object.keys(searchIndex.byDifficulty).map(diff => ({
                name: diff,
                count: searchIndex.byDifficulty[diff].length
            })),
            tags: Object.keys(searchIndex.byTag).map(tag => ({
                name: tag,
                count: searchIndex.byTag[tag].length
            })).sort((a, b) => b.count - a.count).slice(0, 50) // Top 50 tags
        };
    };

    /**
     * Clear cache manually
     */
    const invalidateCache = () => {
        clearCache();
        parsedData = null;
        searchIndex = null;
    };

    // Public API
    return {
        loadData,
        search,
        getByCategory,
        getByDifficulty,
        getByTag,
        getById,
        getStats,
        invalidateCache,
        clearCache
    };
})();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DataLoader;
}

// Make available globally
window.DataLoader = DataLoader;
