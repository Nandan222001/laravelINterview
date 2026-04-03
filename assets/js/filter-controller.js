class FilterController {
    constructor() {
        this.selectedDifficulties = new Set();
        this.selectedTags = new Set();
        this.searchQuery = '';
        this.init();
    }

    init() {
        this.loadFiltersFromHash();
        this.attachEventListeners();
        this.applyFilters();
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
            });
        });

        const searchInput = document.querySelector('[data-search-input]');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.searchQuery = e.target.value.toLowerCase();
                this.applyFilters();
                this.updateHash();
            });
        }

        const resetButton = document.querySelector('[data-reset-filters]');
        if (resetButton) {
            resetButton.addEventListener('click', () => {
                this.resetFilters();
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
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = FilterController;
}
