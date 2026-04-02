// ============================================================================
// MARKDOWN PARSER MODULE
// Converts markdown to HTML with code block syntax highlighting
// ============================================================================

const MarkdownParser = (() => {
    const escapeHtml = (text) => {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    };

    const highlightCode = (code, language) => {
        const keywords = {
            javascript: ['const', 'let', 'var', 'function', 'return', 'if', 'else', 'for', 'while', 'class', 'extends', 'import', 'export', 'async', 'await', 'try', 'catch', 'new', 'this', 'super', 'static', 'typeof', 'instanceof'],
            php: ['public', 'private', 'protected', 'function', 'class', 'namespace', 'use', 'return', 'if', 'else', 'foreach', 'while', 'echo', 'print', 'new', 'extends', 'implements', 'trait', 'abstract', 'interface', 'const', 'static', 'self', 'parent', 'try', 'catch', 'throw', 'finally'],
            python: ['def', 'class', 'if', 'else', 'elif', 'for', 'while', 'return', 'import', 'from', 'as', 'try', 'except', 'finally', 'with', 'lambda', 'yield', 'async', 'await', 'pass', 'break', 'continue'],
            java: ['public', 'private', 'protected', 'class', 'interface', 'extends', 'implements', 'new', 'return', 'if', 'else', 'for', 'while', 'try', 'catch', 'finally', 'throw', 'throws', 'static', 'final', 'void', 'int', 'String', 'boolean'],
            sql: ['SELECT', 'FROM', 'WHERE', 'INSERT', 'UPDATE', 'DELETE', 'JOIN', 'LEFT', 'RIGHT', 'INNER', 'OUTER', 'ON', 'GROUP BY', 'ORDER BY', 'HAVING', 'LIMIT', 'OFFSET', 'CREATE', 'TABLE', 'INDEX', 'ALTER', 'DROP']
        };

        const strings = [];
        const comments = [];
        
        let highlighted = code;
        
        // Extract and preserve strings
        highlighted = highlighted.replace(/(["'`])(?:(?=(\\?))\2.)*?\1/g, (match) => {
            strings.push(match);
            return `__STRING_${strings.length - 1}__`;
        });
        
        // Extract and preserve comments
        highlighted = highlighted.replace(/\/\/.*$/gm, (match) => {
            comments.push(match);
            return `__COMMENT_${comments.length - 1}__`;
        });
        highlighted = highlighted.replace(/\/\*[\s\S]*?\*\//g, (match) => {
            comments.push(match);
            return `__COMMENT_${comments.length - 1}__`;
        });
        highlighted = highlighted.replace(/#.*$/gm, (match) => {
            comments.push(match);
            return `__COMMENT_${comments.length - 1}__`;
        });
        
        // Highlight keywords
        const langKeywords = keywords[language] || keywords.javascript;
        langKeywords.forEach(keyword => {
            const regex = new RegExp(`\\b${keyword}\\b`, 'g');
            highlighted = highlighted.replace(regex, `<span class="keyword">${keyword}</span>`);
        });
        
        // Highlight numbers
        highlighted = highlighted.replace(/\b\d+\.?\d*\b/g, '<span class="number">$&</span>');
        
        // Restore comments
        comments.forEach((comment, i) => {
            highlighted = highlighted.replace(`__COMMENT_${i}__`, `<span class="comment">${escapeHtml(comment)}</span>`);
        });
        
        // Restore strings
        strings.forEach((str, i) => {
            highlighted = highlighted.replace(`__STRING_${i}__`, `<span class="string">${escapeHtml(str)}</span>`);
        });
        
        return highlighted;
    };

    const parseInlineElements = (text) => {
        // Bold
        text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        text = text.replace(/__(.+?)__/g, '<strong>$1</strong>');
        
        // Italic
        text = text.replace(/\*(.+?)\*/g, '<em>$1</em>');
        text = text.replace(/_(.+?)_/g, '<em>$1</em>');
        
        // Inline code
        text = text.replace(/`([^`]+)`/g, '<code>$1</code>');
        
        // Links
        text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2">$1</a>');
        
        // Images
        text = text.replace(/!\[([^\]]*)\]\(([^)]+)\)/g, '<img src="$2" alt="$1" />');
        
        return text;
    };

    const parse = (markdown) => {
        if (!markdown) return '';
        
        const lines = markdown.split('\n');
        let html = '';
        let inCodeBlock = false;
        let codeLanguage = '';
        let codeContent = '';
        let inList = false;
        let listItems = [];
        let inOrderedList = false;
        let inBlockquote = false;
        let blockquoteLines = [];
        
        const flushList = () => {
            if (inList || inOrderedList) {
                const tag = inOrderedList ? 'ol' : 'ul';
                html += `<${tag}>`;
                listItems.forEach(item => {
                    html += `<li>${parseInlineElements(item)}</li>`;
                });
                html += `</${tag}>`;
                listItems = [];
                inList = false;
                inOrderedList = false;
            }
        };
        
        const flushBlockquote = () => {
            if (inBlockquote) {
                html += '<blockquote>';
                html += blockquoteLines.map(line => parseInlineElements(line)).join('<br>');
                html += '</blockquote>';
                blockquoteLines = [];
                inBlockquote = false;
            }
        };
        
        for (let i = 0; i < lines.length; i++) {
            let line = lines[i];
            
            // Code blocks
            if (line.trim().startsWith('```')) {
                if (!inCodeBlock) {
                    flushList();
                    flushBlockquote();
                    inCodeBlock = true;
                    codeLanguage = line.trim().substring(3).trim();
                    codeContent = '';
                } else {
                    const highlighted = highlightCode(codeContent, codeLanguage);
                    html += `<pre><code class="language-${codeLanguage}">${highlighted}</code></pre>`;
                    inCodeBlock = false;
                    codeLanguage = '';
                    codeContent = '';
                }
                continue;
            }
            
            if (inCodeBlock) {
                codeContent += line + '\n';
                continue;
            }
            
            // Headers
            const headerMatch = line.match(/^(#{1,6})\s+(.+)$/);
            if (headerMatch) {
                flushList();
                flushBlockquote();
                const level = headerMatch[1].length;
                const text = headerMatch[2];
                const id = text.toLowerCase().replace(/[^\w\s-]/g, '').replace(/\s+/g, '-');
                html += `<h${level} id="${id}">${parseInlineElements(text)}</h${level}>`;
                continue;
            }
            
            // Horizontal rule
            if (line.match(/^(-{3,}|\*{3,}|_{3,})$/)) {
                flushList();
                flushBlockquote();
                html += '<hr>';
                continue;
            }
            
            // Blockquote
            if (line.trim().startsWith('>')) {
                flushList();
                inBlockquote = true;
                blockquoteLines.push(line.trim().substring(1).trim());
                continue;
            } else if (inBlockquote) {
                flushBlockquote();
            }
            
            // Unordered lists
            if (line.match(/^[\s]*[-*+]\s+(.+)$/)) {
                if (inOrderedList) {
                    flushList();
                }
                inList = true;
                const match = line.match(/^[\s]*[-*+]\s+(.+)$/);
                listItems.push(match[1]);
                continue;
            }
            
            // Ordered lists
            if (line.match(/^[\s]*\d+\.\s+(.+)$/)) {
                if (inList && !inOrderedList) {
                    flushList();
                }
                inOrderedList = true;
                const match = line.match(/^[\s]*\d+\.\s+(.+)$/);
                listItems.push(match[1]);
                continue;
            }
            
            // Flush lists if line doesn't match
            if (inList || inOrderedList) {
                if (!line.match(/^[\s]*[-*+\d.]\s+/)) {
                    flushList();
                }
            }
            
            // Empty lines
            if (line.trim() === '') {
                flushList();
                flushBlockquote();
                html += '<br>';
                continue;
            }
            
            // Paragraphs
            if (line.trim()) {
                flushList();
                flushBlockquote();
                html += `<p>${parseInlineElements(line)}</p>`;
            }
        }
        
        flushList();
        flushBlockquote();
        
        return html;
    };

    return {
        parse,
        parseInlineElements,
        highlightCode
    };
})();

// ============================================================================
// ACCORDION CONTROLLER MODULE
// Manages expand/collapse with smooth transitions, keyboard navigation, deep linking
// ============================================================================

const AccordionController = (() => {
    let accordions = [];
    const config = {
        animationDuration: 300,
        multiExpand: false,
        keyboardNavigation: true,
        deepLinking: true,
        scrollOffset: 100
    };

    const init = (options = {}) => {
        Object.assign(config, options);
        
        const accordionItems = document.querySelectorAll('.accordion-item');
        
        accordionItems.forEach((item, index) => {
            const header = item.querySelector('.accordion-header');
            const content = item.querySelector('.accordion-content');
            const body = item.querySelector('.accordion-body');
            
            if (!header || !content) return;
            
            const accordionData = {
                item,
                header,
                content,
                body,
                index,
                isOpen: item.classList.contains('active')
            };
            
            accordions.push(accordionData);
            
            // Set ARIA attributes
            const id = `accordion-${index}`;
            const panelId = `accordion-panel-${index}`;
            
            header.setAttribute('id', id);
            header.setAttribute('role', 'button');
            header.setAttribute('aria-expanded', accordionData.isOpen);
            header.setAttribute('aria-controls', panelId);
            header.setAttribute('tabindex', '0');
            
            content.setAttribute('id', panelId);
            content.setAttribute('role', 'region');
            content.setAttribute('aria-labelledby', id);
            
            // Generate hash from header text for deep linking
            if (config.deepLinking) {
                const headerText = header.querySelector('h3')?.textContent || header.textContent;
                const hash = headerText.toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .substring(0, 50);
                accordionData.hash = hash;
                item.setAttribute('data-hash', hash);
            }
            
            // Click event
            header.addEventListener('click', () => toggle(index));
            
            // Keyboard navigation
            if (config.keyboardNavigation) {
                header.addEventListener('keydown', (e) => handleKeyboard(e, index));
            }
            
            // Set initial state
            if (accordionData.isOpen) {
                content.style.maxHeight = content.scrollHeight + 'px';
            }
        });
        
        // Handle deep linking
        if (config.deepLinking && window.location.hash) {
            handleDeepLink();
        }
        
        // Listen for hash changes
        if (config.deepLinking) {
            window.addEventListener('hashchange', handleDeepLink);
        }
    };

    const toggle = (index) => {
        const accordion = accordions[index];
        if (!accordion) return;
        
        const wasOpen = accordion.isOpen;
        
        // Close others if multiExpand is false
        if (!config.multiExpand && !wasOpen) {
            closeAll();
        }
        
        if (wasOpen) {
            close(index);
        } else {
            open(index);
        }
        
        // Update URL hash for deep linking
        if (config.deepLinking && !wasOpen) {
            history.replaceState(null, null, `#${accordion.hash}`);
        } else if (config.deepLinking && wasOpen) {
            history.replaceState(null, null, ' ');
        }
    };

    const open = (index) => {
        const accordion = accordions[index];
        if (!accordion || accordion.isOpen) return;
        
        accordion.item.classList.add('active');
        accordion.isOpen = true;
        accordion.header.setAttribute('aria-expanded', 'true');
        
        // Smooth transition
        const content = accordion.content;
        content.style.maxHeight = content.scrollHeight + 'px';
        
        // Dispatch custom event
        accordion.item.dispatchEvent(new CustomEvent('accordion:opened', {
            detail: { index, accordion }
        }));
    };

    const close = (index) => {
        const accordion = accordions[index];
        if (!accordion || !accordion.isOpen) return;
        
        accordion.item.classList.remove('active');
        accordion.isOpen = false;
        accordion.header.setAttribute('aria-expanded', 'false');
        
        // Smooth transition
        accordion.content.style.maxHeight = '0';
        
        // Dispatch custom event
        accordion.item.dispatchEvent(new CustomEvent('accordion:closed', {
            detail: { index, accordion }
        }));
    };

    const closeAll = () => {
        accordions.forEach((_, index) => close(index));
    };

    const openAll = () => {
        accordions.forEach((_, index) => open(index));
    };

    const handleKeyboard = (e, index) => {
        switch (e.key) {
            case 'Enter':
            case ' ':
                e.preventDefault();
                toggle(index);
                break;
            case 'ArrowDown':
                e.preventDefault();
                focusNext(index);
                break;
            case 'ArrowUp':
                e.preventDefault();
                focusPrevious(index);
                break;
            case 'Home':
                e.preventDefault();
                focusFirst();
                break;
            case 'End':
                e.preventDefault();
                focusLast();
                break;
        }
    };

    const focusNext = (currentIndex) => {
        const nextIndex = (currentIndex + 1) % accordions.length;
        accordions[nextIndex].header.focus();
    };

    const focusPrevious = (currentIndex) => {
        const prevIndex = currentIndex === 0 ? accordions.length - 1 : currentIndex - 1;
        accordions[prevIndex].header.focus();
    };

    const focusFirst = () => {
        if (accordions.length > 0) {
            accordions[0].header.focus();
        }
    };

    const focusLast = () => {
        if (accordions.length > 0) {
            accordions[accordions.length - 1].header.focus();
        }
    };

    const handleDeepLink = () => {
        const hash = window.location.hash.substring(1);
        if (!hash) return;
        
        const accordion = accordions.find(acc => acc.hash === hash);
        if (accordion) {
            closeAll();
            open(accordion.index);
            
            // Scroll to accordion with offset
            setTimeout(() => {
                const top = accordion.item.offsetTop - config.scrollOffset;
                window.scrollTo({ top, behavior: 'smooth' });
            }, config.animationDuration);
        }
    };

    const destroy = () => {
        accordions.forEach(accordion => {
            accordion.header.removeEventListener('click', () => toggle(accordion.index));
            accordion.header.removeEventListener('keydown', handleKeyboard);
        });
        
        if (config.deepLinking) {
            window.removeEventListener('hashchange', handleDeepLink);
        }
        
        accordions = [];
    };

    const getState = () => {
        return accordions.map(acc => ({
            index: acc.index,
            isOpen: acc.isOpen,
            hash: acc.hash
        }));
    };

    return {
        init,
        toggle,
        open,
        close,
        closeAll,
        openAll,
        destroy,
        getState
    };
})();

// ============================================================================
// SEARCH ENGINE MODULE
// Fuzzy matching across questions and answers
// ============================================================================

const SearchEngine = (() => {
    let searchIndex = [];
    let config = {
        threshold: 0.4,
        caseSensitive: false,
        searchFields: ['title', 'content', 'tags', 'topic', 'difficulty'],
        minQueryLength: 2,
        maxResults: 50
    };

    const init = (options = {}) => {
        Object.assign(config, options);
        buildSearchIndex();
    };

    const buildSearchIndex = () => {
        searchIndex = [];
        
        const accordionItems = document.querySelectorAll('.accordion-item');
        
        accordionItems.forEach((item, index) => {
            const header = item.querySelector('.accordion-header h3');
            const body = item.querySelector('.accordion-body');
            const badges = item.querySelectorAll('.badge');
            
            const title = header?.textContent.trim() || '';
            const content = body?.textContent.trim() || '';
            const tags = Array.from(badges).map(b => b.textContent.trim());
            const topic = item.getAttribute('data-topic') || '';
            const difficulty = item.getAttribute('data-difficulty') || '';
            const hash = item.getAttribute('data-hash') || '';
            
            searchIndex.push({
                index,
                element: item,
                title,
                content,
                tags,
                topic,
                difficulty,
                hash,
                searchableText: [title, content, ...tags, topic, difficulty].join(' ')
            });
        });
    };

    const calculateScore = (query, text) => {
        if (!text || !query) return 0;
        
        if (!config.caseSensitive) {
            query = query.toLowerCase();
            text = text.toLowerCase();
        }
        
        // Exact match
        if (text === query) return 1.0;
        
        // Contains exact query
        if (text.includes(query)) {
            return 0.9 - (text.indexOf(query) / text.length) * 0.2;
        }
        
        // Fuzzy matching using Levenshtein distance
        const words = text.split(/\s+/);
        let maxScore = 0;
        
        words.forEach(word => {
            const distance = levenshteinDistance(query, word);
            const similarity = 1 - distance / Math.max(query.length, word.length);
            maxScore = Math.max(maxScore, similarity);
        });
        
        // Check if all query words exist in text
        const queryWords = query.split(/\s+/);
        const matchedWords = queryWords.filter(qw => {
            return words.some(w => w.includes(qw) || qw.includes(w));
        });
        
        const wordMatchRatio = matchedWords.length / queryWords.length;
        maxScore = Math.max(maxScore, wordMatchRatio * 0.7);
        
        return maxScore;
    };

    const levenshteinDistance = (str1, str2) => {
        const matrix = [];
        
        for (let i = 0; i <= str2.length; i++) {
            matrix[i] = [i];
        }
        
        for (let j = 0; j <= str1.length; j++) {
            matrix[0][j] = j;
        }
        
        for (let i = 1; i <= str2.length; i++) {
            for (let j = 1; j <= str1.length; j++) {
                if (str2.charAt(i - 1) === str1.charAt(j - 1)) {
                    matrix[i][j] = matrix[i - 1][j - 1];
                } else {
                    matrix[i][j] = Math.min(
                        matrix[i - 1][j - 1] + 1,
                        matrix[i][j - 1] + 1,
                        matrix[i - 1][j] + 1
                    );
                }
            }
        }
        
        return matrix[str2.length][str1.length];
    };

    const search = (query) => {
        if (!query || query.length < config.minQueryLength) {
            return [];
        }
        
        const results = [];
        
        searchIndex.forEach(item => {
            let maxScore = 0;
            
            config.searchFields.forEach(field => {
                const fieldValue = item[field];
                if (!fieldValue) return;
                
                let score = 0;
                if (Array.isArray(fieldValue)) {
                    fieldValue.forEach(val => {
                        score = Math.max(score, calculateScore(query, val));
                    });
                } else {
                    score = calculateScore(query, fieldValue);
                }
                
                // Weight different fields
                const weights = {
                    title: 1.5,
                    tags: 1.3,
                    topic: 1.2,
                    difficulty: 1.0,
                    content: 0.8
                };
                
                score *= (weights[field] || 1.0);
                maxScore = Math.max(maxScore, score);
            });
            
            if (maxScore >= config.threshold) {
                results.push({
                    ...item,
                    score: maxScore
                });
            }
        });
        
        // Sort by score descending
        results.sort((a, b) => b.score - a.score);
        
        // Limit results
        return results.slice(0, config.maxResults);
    };

    const highlight = (text, query) => {
        if (!query || !text) return text;
        
        if (!config.caseSensitive) {
            const regex = new RegExp(`(${query})`, 'gi');
            return text.replace(regex, '<mark>$1</mark>');
        } else {
            const regex = new RegExp(`(${query})`, 'g');
            return text.replace(regex, '<mark>$1</mark>');
        }
    };

    const updateIndex = () => {
        buildSearchIndex();
    };

    return {
        init,
        search,
        highlight,
        updateIndex,
        getIndex: () => searchIndex
    };
})();

// ============================================================================
// FILTER MANAGER MODULE
// Multi-select filtering by topic, difficulty, and tags
// ============================================================================

const FilterManager = (() => {
    let filters = {
        topics: new Set(),
        difficulties: new Set(),
        tags: new Set()
    };
    
    let items = [];
    let config = {
        animationDuration: 300,
        showEmptyMessage: true,
        emptyMessage: 'No items match your filters. Try adjusting your selections.',
        combineMode: 'AND' // 'AND' or 'OR'
    };

    const init = (options = {}) => {
        Object.assign(config, options);
        
        // Gather all filterable items
        const accordionItems = document.querySelectorAll('.accordion-item');
        
        accordionItems.forEach((item, index) => {
            const topic = item.getAttribute('data-topic') || '';
            const difficulty = item.getAttribute('data-difficulty') || '';
            const tagsAttr = item.getAttribute('data-tags') || '';
            const tags = tagsAttr.split(',').map(t => t.trim()).filter(t => t);
            
            items.push({
                element: item,
                index,
                topic,
                difficulty,
                tags,
                visible: true
            });
        });
        
        // Set up filter controls
        setupFilterControls();
    };

    const setupFilterControls = () => {
        // Topic filters
        const topicCheckboxes = document.querySelectorAll('input[name="topic"]');
        topicCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                if (e.target.checked) {
                    filters.topics.add(e.target.value);
                } else {
                    filters.topics.delete(e.target.value);
                }
                applyFilters();
            });
        });
        
        // Difficulty filters
        const difficultyCheckboxes = document.querySelectorAll('input[name="difficulty"]');
        difficultyCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                if (e.target.checked) {
                    filters.difficulties.add(e.target.value);
                } else {
                    filters.difficulties.delete(e.target.value);
                }
                applyFilters();
            });
        });
        
        // Tag filters (if any)
        const tagCheckboxes = document.querySelectorAll('input[name="tag"]');
        tagCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                if (e.target.checked) {
                    filters.tags.add(e.target.value);
                } else {
                    filters.tags.delete(e.target.value);
                }
                applyFilters();
            });
        });
    };

    const applyFilters = () => {
        let visibleCount = 0;
        
        items.forEach(item => {
            const matchesTopic = filters.topics.size === 0 || 
                Array.from(filters.topics).some(topic => {
                    return item.topic === topic || 
                           item.topic.includes(topic) ||
                           item.tags.includes(topic);
                });
            
            const matchesDifficulty = filters.difficulties.size === 0 || 
                filters.difficulties.has(item.difficulty);
            
            const matchesTags = filters.tags.size === 0 || 
                Array.from(filters.tags).some(tag => item.tags.includes(tag));
            
            let isVisible;
            if (config.combineMode === 'AND') {
                isVisible = matchesTopic && matchesDifficulty && matchesTags;
            } else {
                isVisible = matchesTopic || matchesDifficulty || matchesTags;
            }
            
            // Handle case when no filters are selected
            if (filters.topics.size === 0 && filters.difficulties.size === 0 && filters.tags.size === 0) {
                isVisible = true;
            }
            
            if (isVisible) {
                showItem(item);
                visibleCount++;
            } else {
                hideItem(item);
            }
        });
        
        // Show/hide empty message
        updateEmptyMessage(visibleCount === 0);
        
        // Dispatch filter event
        document.dispatchEvent(new CustomEvent('filters:applied', {
            detail: {
                filters: getActiveFilters(),
                visibleCount,
                totalCount: items.length
            }
        }));
    };

    const showItem = (item) => {
        if (item.visible) return;
        
        item.element.style.display = '';
        item.element.style.opacity = '0';
        
        setTimeout(() => {
            item.element.style.transition = `opacity ${config.animationDuration}ms ease`;
            item.element.style.opacity = '1';
        }, 10);
        
        item.visible = true;
    };

    const hideItem = (item) => {
        if (!item.visible) return;
        
        item.element.style.transition = `opacity ${config.animationDuration}ms ease`;
        item.element.style.opacity = '0';
        
        setTimeout(() => {
            item.element.style.display = 'none';
        }, config.animationDuration);
        
        item.visible = false;
    };

    const updateEmptyMessage = (show) => {
        if (!config.showEmptyMessage) return;
        
        let emptyMessageEl = document.querySelector('.filter-empty-message');
        
        if (show && !emptyMessageEl) {
            emptyMessageEl = document.createElement('div');
            emptyMessageEl.className = 'filter-empty-message';
            emptyMessageEl.textContent = config.emptyMessage;
            emptyMessageEl.style.cssText = `
                padding: 2rem;
                text-align: center;
                color: #666;
                font-size: 1.1rem;
                background: #f9f9f9;
                border-radius: 8px;
                margin: 2rem 0;
            `;
            
            const accordion = document.querySelector('.accordion');
            if (accordion) {
                accordion.appendChild(emptyMessageEl);
            }
        } else if (!show && emptyMessageEl) {
            emptyMessageEl.remove();
        }
    };

    const clearFilters = () => {
        filters.topics.clear();
        filters.difficulties.clear();
        filters.tags.clear();
        
        // Uncheck all checkboxes
        document.querySelectorAll('input[type="checkbox"][name^="topic"], input[type="checkbox"][name^="difficulty"], input[type="checkbox"][name^="tag"]')
            .forEach(cb => cb.checked = false);
        
        applyFilters();
    };

    const setFilter = (type, values) => {
        if (!filters[type]) return;
        
        filters[type] = new Set(Array.isArray(values) ? values : [values]);
        applyFilters();
    };

    const getActiveFilters = () => {
        return {
            topics: Array.from(filters.topics),
            difficulties: Array.from(filters.difficulties),
            tags: Array.from(filters.tags)
        };
    };

    const getFilterStats = () => {
        const stats = {
            topics: {},
            difficulties: {},
            tags: {}
        };
        
        items.forEach(item => {
            // Count topics
            if (item.topic) {
                stats.topics[item.topic] = (stats.topics[item.topic] || 0) + 1;
            }
            
            // Count difficulties
            if (item.difficulty) {
                stats.difficulties[item.difficulty] = (stats.difficulties[item.difficulty] || 0) + 1;
            }
            
            // Count tags
            item.tags.forEach(tag => {
                stats.tags[tag] = (stats.tags[tag] || 0) + 1;
            });
        });
        
        return stats;
    };

    const toggleCombineMode = () => {
        config.combineMode = config.combineMode === 'AND' ? 'OR' : 'AND';
        applyFilters();
        return config.combineMode;
    };

    return {
        init,
        applyFilters,
        clearFilters,
        setFilter,
        getActiveFilters,
        getFilterStats,
        toggleCombineMode
    };
})();

// ============================================================================
// APP INITIALIZATION
// ============================================================================

const App = (() => {
    const init = () => {
        // Initialize Accordion Controller
        AccordionController.init({
            animationDuration: 300,
            multiExpand: false,
            keyboardNavigation: true,
            deepLinking: true,
            scrollOffset: 100
        });

        // Initialize Search Engine
        SearchEngine.init({
            threshold: 0.3,
            caseSensitive: false,
            minQueryLength: 2,
            maxResults: 50
        });

        // Initialize Filter Manager
        FilterManager.init({
            animationDuration: 300,
            showEmptyMessage: true,
            combineMode: 'AND'
        });

        // Set up search UI
        setupSearchUI();
        
        // Set up mobile menu
        setupMobileMenu();
        
        // Set up markdown rendering (if needed for dynamic content)
        setupMarkdownRendering();
        
        console.log('Laravel Interview Bank App initialized successfully');
    };

    const setupSearchUI = () => {
        const searchInput = document.querySelector('.search-bar input');
        const searchButton = document.querySelector('.search-bar button');
        
        if (!searchInput || !searchButton) return;
        
        let searchTimeout;
        
        const performSearch = () => {
            const query = searchInput.value.trim();
            
            if (query.length < 2) {
                // Reset to show all items
                FilterManager.clearFilters();
                highlightSearchResults([]);
                return;
            }
            
            const results = SearchEngine.search(query);
            highlightSearchResults(results);
            filterBySearchResults(results);
        };
        
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearch, 300);
        });
        
        searchButton.addEventListener('click', performSearch);
        
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        });
    };

    const highlightSearchResults = (results) => {
        // Remove existing highlights
        document.querySelectorAll('.accordion-item mark').forEach(mark => {
            const parent = mark.parentNode;
            parent.replaceChild(document.createTextNode(mark.textContent), mark);
        });
        
        // Add new highlights
        if (results.length === 0) return;
        
        const query = document.querySelector('.search-bar input').value.trim();
        
        results.forEach(result => {
            const header = result.element.querySelector('.accordion-header h3');
            if (header && query) {
                header.innerHTML = SearchEngine.highlight(header.textContent, query);
            }
        });
    };

    const filterBySearchResults = (results) => {
        const allItems = document.querySelectorAll('.accordion-item');
        
        if (results.length === 0 && document.querySelector('.search-bar input').value.trim().length >= 2) {
            // Hide all items when no results
            allItems.forEach(item => {
                item.style.opacity = '0.3';
                item.style.pointerEvents = 'none';
            });
            
            // Show no results message
            let noResults = document.querySelector('.search-no-results');
            if (!noResults) {
                noResults = document.createElement('div');
                noResults.className = 'search-no-results';
                noResults.textContent = 'No results found. Try a different search term.';
                noResults.style.cssText = `
                    padding: 2rem;
                    text-align: center;
                    color: #999;
                    font-size: 1.1rem;
                `;
                document.querySelector('.accordion')?.prepend(noResults);
            }
        } else if (results.length > 0) {
            // Hide no results message
            document.querySelector('.search-no-results')?.remove();
            
            // Show matching items, hide others
            const matchingElements = new Set(results.map(r => r.element));
            
            allItems.forEach(item => {
                if (matchingElements.has(item)) {
                    item.style.opacity = '1';
                    item.style.pointerEvents = 'auto';
                } else {
                    item.style.opacity = '0.3';
                    item.style.pointerEvents = 'none';
                }
            });
        } else {
            // Reset all items
            document.querySelector('.search-no-results')?.remove();
            allItems.forEach(item => {
                item.style.opacity = '1';
                item.style.pointerEvents = 'auto';
            });
        }
    };

    const setupMobileMenu = () => {
        const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
        const mainNav = document.getElementById('main-nav');
        
        if (!mobileMenuToggle || !mainNav) return;
        
        mobileMenuToggle.addEventListener('click', () => {
            mainNav.classList.toggle('hidden');
        });
    };

    const setupMarkdownRendering = () => {
        // Find all elements with markdown content (if any have data-markdown attribute)
        const markdownElements = document.querySelectorAll('[data-markdown]');
        
        markdownElements.forEach(element => {
            const markdown = element.getAttribute('data-markdown') || element.textContent;
            const html = MarkdownParser.parse(markdown);
            element.innerHTML = html;
        });
    };

    return {
        init,
        MarkdownParser,
        AccordionController,
        SearchEngine,
        FilterManager
    };
})();

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', App.init);
} else {
    App.init();
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = App;
}

// Make available globally
window.InterviewBankApp = App;
