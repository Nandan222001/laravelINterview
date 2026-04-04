class FilterController {
    constructor() {
        this.selectedDifficulties = new Set();
        this.selectedTags = new Set();
        this.searchQuery = '';
        this.collapsedAnswers = this.loadCollapsedState();
        this.diagramZoomLevels = new Map();
        this.init();
    }

    init() {
        this.loadFiltersFromHash();
        this.attachEventListeners();
        this.applyFilters();
        this.initializeAnswerSections();
        this.initializeCopyCodeButtons();
        this.initializeDiagramZoomControls();
        this.restoreCollapsedState();
    }

    attachEventListeners() {
        document.querySelectorAll('[data-filter-difficulty]').forEach(button => {
            button.addEventListener('click', (e) => {
                const difficulty = e.target.dataset.filterDifficulty;
                this.toggleDifficulty(difficulty);
            });
        });

        document.querySelectorAll('[data-filter-tag]').forEach(button => {
            button.addEventListener('click', (e) => {
                const tag = e.target.dataset.filterTag;
                this.toggleTag(tag);
                this.scrollToFirstVisibleQuestion();
            });
        });

        const searchInput = document.querySelector('[data-search-input]');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.searchQuery = e.target.value.toLowerCase();
                this.applyFilters();
                this.updateHash();
                this.highlightSearchTerms();
            });
        }

        const resetButton = document.querySelector('[data-reset-filters]');
        if (resetButton) {
            resetButton.addEventListener('click', () => {
                this.resetFilters();
            });
        }

        const expandAllButton = document.querySelector('[data-expand-all]');
        if (expandAllButton) {
            expandAllButton.addEventListener('click', () => {
                this.expandAllAnswers();
            });
        }

        const collapseAllButton = document.querySelector('[data-collapse-all]');
        if (collapseAllButton) {
            collapseAllButton.addEventListener('click', () => {
                this.collapseAllAnswers();
            });
        }

        window.addEventListener('hashchange', () => {
            this.loadFiltersFromHash();
            this.applyFilters();
        });
    }

    toggleDifficulty(difficulty) {
        const button = document.querySelector(`[data-filter-difficulty="${difficulty}"]`);
        
        if (this.selectedDifficulties.has(difficulty)) {
            this.selectedDifficulties.delete(difficulty);
            button?.classList.remove('active');
        } else {
            this.selectedDifficulties.add(difficulty);
            button?.classList.add('active');
        }

        this.applyFilters();
        this.updateHash();
        this.scrollToFirstVisibleQuestion();
    }

    toggleTag(tag) {
        const button = document.querySelector(`[data-filter-tag="${tag}"]`);
        
        if (this.selectedTags.has(tag)) {
            this.selectedTags.delete(tag);
            button?.classList.remove('active');
        } else {
            this.selectedTags.add(tag);
            button?.classList.add('active');
        }

        this.applyFilters();
        this.updateHash();
    }

    applyFilters() {
        const questions = document.querySelectorAll('[data-difficulty]');
        let visibleCount = 0;

        questions.forEach(question => {
            const difficulty = question.dataset.difficulty;
            const tags = question.dataset.tags ? question.dataset.tags.split(',').map(t => t.trim()) : [];
            const textContent = question.textContent.toLowerCase();

            let isVisible = true;

            if (this.selectedDifficulties.size > 0) {
                isVisible = isVisible && this.selectedDifficulties.has(difficulty);
            }

            if (this.selectedTags.size > 0) {
                isVisible = isVisible && tags.some(tag => this.selectedTags.has(tag));
            }

            if (this.searchQuery.trim() !== '') {
                isVisible = isVisible && textContent.includes(this.searchQuery);
            }

            if (isVisible) {
                question.style.display = '';
                visibleCount++;
            } else {
                question.style.display = 'none';
            }
        });

        this.updateResultCount(visibleCount, questions.length);
        this.highlightSearchTerms();
    }

    updateResultCount(visible, total) {
        const countElement = document.querySelector('[data-result-count]');
        if (countElement) {
            countElement.textContent = `Showing ${visible} of ${total} questions`;
        }
    }

    resetFilters() {
        this.selectedDifficulties.clear();
        this.selectedTags.clear();
        this.searchQuery = '';

        document.querySelectorAll('[data-filter-difficulty]').forEach(button => {
            button.classList.remove('active');
        });

        document.querySelectorAll('[data-filter-tag]').forEach(button => {
            button.classList.remove('active');
        });

        const searchInput = document.querySelector('[data-search-input]');
        if (searchInput) {
            searchInput.value = '';
        }

        this.applyFilters();
        this.updateHash();
        this.clearSearchHighlights();
    }

    updateHash() {
        const params = new URLSearchParams();

        if (this.selectedDifficulties.size > 0) {
            params.set('difficulties', Array.from(this.selectedDifficulties).join(','));
        }

        if (this.selectedTags.size > 0) {
            params.set('tags', Array.from(this.selectedTags).join(','));
        }

        if (this.searchQuery.trim() !== '') {
            params.set('search', this.searchQuery);
        }

        const hashString = params.toString();
        window.location.hash = hashString ? `#${hashString}` : '';
    }

    loadFiltersFromHash() {
        const hash = window.location.hash.substring(1);
        if (!hash) {
            return;
        }

        const params = new URLSearchParams(hash);

        this.selectedDifficulties.clear();
        const difficulties = params.get('difficulties');
        if (difficulties) {
            difficulties.split(',').forEach(d => {
                this.selectedDifficulties.add(d);
                const button = document.querySelector(`[data-filter-difficulty="${d}"]`);
                button?.classList.add('active');
            });
        }

        this.selectedTags.clear();
        const tags = params.get('tags');
        if (tags) {
            tags.split(',').forEach(t => {
                this.selectedTags.add(t);
                const button = document.querySelector(`[data-filter-tag="${t}"]`);
                button?.classList.add('active');
            });
        }

        const search = params.get('search');
        if (search) {
            this.searchQuery = search.toLowerCase();
            const searchInput = document.querySelector('[data-search-input]');
            if (searchInput) {
                searchInput.value = search;
            }
        }
    }

    initializeAnswerSections() {
        const answerSections = document.querySelectorAll('[data-answer-section]');
        
        answerSections.forEach(section => {
            const header = section.querySelector('[data-answer-header]');
            const content = section.querySelector('[data-answer-content]');
            
            if (header && content) {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => {
                    this.toggleAnswerSection(section, content);
                });
            }
        });
    }

    toggleAnswerSection(section, content) {
        const sectionId = section.dataset.answerSection;
        const isCollapsed = content.style.display === 'none';
        
        if (isCollapsed) {
            content.style.display = '';
            section.classList.remove('collapsed');
            this.collapsedAnswers.delete(sectionId);
        } else {
            content.style.display = 'none';
            section.classList.add('collapsed');
            this.collapsedAnswers.add(sectionId);
        }
        
        this.saveCollapsedState();
    }

    expandAllAnswers() {
        const answerSections = document.querySelectorAll('[data-answer-section]');
        
        answerSections.forEach(section => {
            const content = section.querySelector('[data-answer-content]');
            if (content) {
                content.style.display = '';
                section.classList.remove('collapsed');
            }
        });
        
        this.collapsedAnswers.clear();
        this.saveCollapsedState();
    }

    collapseAllAnswers() {
        const answerSections = document.querySelectorAll('[data-answer-section]');
        
        answerSections.forEach(section => {
            const content = section.querySelector('[data-answer-content]');
            const sectionId = section.dataset.answerSection;
            if (content) {
                content.style.display = 'none';
                section.classList.add('collapsed');
                this.collapsedAnswers.add(sectionId);
            }
        });
        
        this.saveCollapsedState();
    }

    restoreCollapsedState() {
        const answerSections = document.querySelectorAll('[data-answer-section]');
        
        answerSections.forEach(section => {
            const sectionId = section.dataset.answerSection;
            const content = section.querySelector('[data-answer-content]');
            
            if (content && this.collapsedAnswers.has(sectionId)) {
                content.style.display = 'none';
                section.classList.add('collapsed');
            }
        });
    }

    saveCollapsedState() {
        try {
            localStorage.setItem('collapsedAnswers', JSON.stringify(Array.from(this.collapsedAnswers)));
        } catch (e) {
            console.warn('Failed to save collapsed state to localStorage:', e);
        }
    }

    loadCollapsedState() {
        try {
            const saved = localStorage.getItem('collapsedAnswers');
            return saved ? new Set(JSON.parse(saved)) : new Set();
        } catch (e) {
            console.warn('Failed to load collapsed state from localStorage:', e);
            return new Set();
        }
    }

    scrollToFirstVisibleQuestion() {
        setTimeout(() => {
            const firstVisible = document.querySelector('[data-difficulty]:not([style*="display: none"])');
            if (firstVisible) {
                firstVisible.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 100);
    }

    initializeCopyCodeButtons() {
        const codeBlocks = document.querySelectorAll('pre code');
        
        codeBlocks.forEach(codeBlock => {
            if (codeBlock.parentElement.querySelector('[data-copy-code]')) {
                return;
            }
            
            const button = document.createElement('button');
            button.textContent = 'Copy';
            button.setAttribute('data-copy-code', '');
            button.style.position = 'absolute';
            button.style.top = '5px';
            button.style.right = '5px';
            button.style.padding = '4px 8px';
            button.style.fontSize = '12px';
            button.style.cursor = 'pointer';
            
            codeBlock.parentElement.style.position = 'relative';
            
            button.addEventListener('click', async () => {
                await this.copyCodeToClipboard(codeBlock, button);
            });
            
            codeBlock.parentElement.appendChild(button);
        });
    }

    async copyCodeToClipboard(codeBlock, button) {
        const code = codeBlock.textContent;
        const originalText = button.textContent;
        
        try {
            await navigator.clipboard.writeText(code);
            button.textContent = 'Copied!';
            button.style.backgroundColor = '#4CAF50';
            button.style.color = 'white';
            
            setTimeout(() => {
                button.textContent = originalText;
                button.style.backgroundColor = '';
                button.style.color = '';
            }, 2000);
        } catch (err) {
            console.error('Failed to copy code:', err);
            button.textContent = 'Failed';
            
            setTimeout(() => {
                button.textContent = originalText;
            }, 2000);
        }
    }

    initializeDiagramZoomControls() {
        const diagrams = document.querySelectorAll('svg[data-diagram]');
        
        diagrams.forEach((diagram, index) => {
            const diagramId = diagram.dataset.diagram || `diagram-${index}`;
            this.diagramZoomLevels.set(diagramId, 1);
            
            if (diagram.parentElement.querySelector('[data-zoom-controls]')) {
                return;
            }
            
            const controlsContainer = document.createElement('div');
            controlsContainer.setAttribute('data-zoom-controls', '');
            controlsContainer.style.marginTop = '10px';
            controlsContainer.style.display = 'flex';
            controlsContainer.style.gap = '8px';
            controlsContainer.style.alignItems = 'center';
            
            const zoomInButton = document.createElement('button');
            zoomInButton.textContent = 'Zoom In';
            zoomInButton.style.padding = '4px 12px';
            zoomInButton.style.cursor = 'pointer';
            
            const zoomOutButton = document.createElement('button');
            zoomOutButton.textContent = 'Zoom Out';
            zoomOutButton.style.padding = '4px 12px';
            zoomOutButton.style.cursor = 'pointer';
            
            const resetButton = document.createElement('button');
            resetButton.textContent = 'Reset';
            resetButton.style.padding = '4px 12px';
            resetButton.style.cursor = 'pointer';
            
            const zoomLabel = document.createElement('span');
            zoomLabel.textContent = '100%';
            zoomLabel.style.fontSize = '14px';
            zoomLabel.style.minWidth = '50px';
            
            zoomInButton.addEventListener('click', () => {
                this.zoomDiagram(diagram, diagramId, 0.2, zoomLabel);
            });
            
            zoomOutButton.addEventListener('click', () => {
                this.zoomDiagram(diagram, diagramId, -0.2, zoomLabel);
            });
            
            resetButton.addEventListener('click', () => {
                this.resetDiagramZoom(diagram, diagramId, zoomLabel);
            });
            
            controlsContainer.appendChild(zoomOutButton);
            controlsContainer.appendChild(zoomInButton);
            controlsContainer.appendChild(resetButton);
            controlsContainer.appendChild(zoomLabel);
            
            diagram.parentElement.appendChild(controlsContainer);
            
            diagram.style.transformOrigin = 'top left';
            diagram.style.transition = 'transform 0.3s ease';
        });
    }

    zoomDiagram(diagram, diagramId, delta, zoomLabel) {
        let currentZoom = this.diagramZoomLevels.get(diagramId) || 1;
        currentZoom = Math.max(0.5, Math.min(3, currentZoom + delta));
        
        this.diagramZoomLevels.set(diagramId, currentZoom);
        diagram.style.transform = `scale(${currentZoom})`;
        zoomLabel.textContent = `${Math.round(currentZoom * 100)}%`;
    }

    resetDiagramZoom(diagram, diagramId, zoomLabel) {
        this.diagramZoomLevels.set(diagramId, 1);
        diagram.style.transform = 'scale(1)';
        zoomLabel.textContent = '100%';
    }

    highlightSearchTerms() {
        this.clearSearchHighlights();
        
        if (!this.searchQuery.trim()) {
            return;
        }
        
        const answers = document.querySelectorAll('[data-answer-content]');
        const searchTerms = this.searchQuery.trim().split(/\s+/);
        
        answers.forEach(answer => {
            this.highlightInElement(answer, searchTerms);
        });
    }

    highlightInElement(element, searchTerms) {
        const walker = document.createTreeWalker(
            element,
            NodeFilter.SHOW_TEXT,
            {
                acceptNode: (node) => {
                    if (node.parentElement.tagName === 'SCRIPT' || 
                        node.parentElement.tagName === 'STYLE' ||
                        node.parentElement.tagName === 'MARK') {
                        return NodeFilter.FILTER_REJECT;
                    }
                    return NodeFilter.FILTER_ACCEPT;
                }
            }
        );
        
        const nodesToReplace = [];
        let node;
        
        while (node = walker.nextNode()) {
            const text = node.textContent;
            let modified = false;
            let newHTML = text;
            
            searchTerms.forEach(term => {
                if (term.length > 0) {
                    const regex = new RegExp(`(${this.escapeRegExp(term)})`, 'gi');
                    if (regex.test(newHTML)) {
                        newHTML = newHTML.replace(regex, '<mark>$1</mark>');
                        modified = true;
                    }
                }
            });
            
            if (modified) {
                nodesToReplace.push({ node, newHTML });
            }
        }
        
        nodesToReplace.forEach(({ node, newHTML }) => {
            const span = document.createElement('span');
            span.innerHTML = newHTML;
            span.setAttribute('data-search-highlight', '');
            node.parentNode.replaceChild(span, node);
        });
    }

    clearSearchHighlights() {
        const highlights = document.querySelectorAll('[data-search-highlight]');
        
        highlights.forEach(highlight => {
            const text = highlight.textContent;
            const textNode = document.createTextNode(text);
            highlight.parentNode.replaceChild(textNode, highlight);
        });
        
        document.querySelectorAll('mark').forEach(mark => {
            const text = mark.textContent;
            const textNode = document.createTextNode(text);
            mark.parentNode.replaceChild(textNode, mark);
        });
    }

    escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = FilterController;
}
