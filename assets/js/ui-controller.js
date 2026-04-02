// ============================================================================
// UI CONTROLLER MODULE
// Comprehensive UI interactions: accordion with localStorage persistence,
// smooth scrolling with URL hash anchors, copy-to-clipboard for code snippets,
// keyboard shortcuts, and loading states with skeleton screens
// ============================================================================

const UIController = (() => {
    // ========================================================================
    // CONFIGURATION
    // ========================================================================
    
    const config = {
        // Accordion settings
        accordion: {
            singleExpandMode: false, // false = multi-expand, true = single-expand
            persistState: true,
            storageKey: 'accordion_expanded_state',
            animationDuration: 300,
            scrollOffset: 100
        },
        
        // Smooth scrolling settings
        smoothScroll: {
            enabled: true,
            duration: 800,
            offset: 80,
            easing: 'easeInOutCubic'
        },
        
        // Copy to clipboard settings
        clipboard: {
            feedbackDuration: 2000,
            buttonText: 'Copy',
            copiedText: '✓ Copied!',
            errorText: '✗ Failed'
        },
        
        // Keyboard shortcuts
        shortcuts: {
            enabled: true,
            searchKey: '/',
            commandPaletteKey: 'k', // Ctrl+K or Cmd+K
            arrowNavigation: true
        },
        
        // Loading states
        loading: {
            skeletonCount: 6,
            minDisplayTime: 300
        }
    };
    
    // ========================================================================
    // STATE MANAGEMENT
    // ========================================================================
    
    let state = {
        accordionItems: [],
        expandedItems: new Set(),
        currentFocusIndex: -1,
        isLoading: false,
        commandPaletteOpen: false
    };
    
    // ========================================================================
    // ACCORDION CONTROLLER WITH LOCALSTORAGE PERSISTENCE
    // ========================================================================
    
    const AccordionManager = {
        /**
         * Initialize accordion functionality
         */
        init() {
            const accordionItems = document.querySelectorAll('.accordion-item');
            
            accordionItems.forEach((item, index) => {
                const header = item.querySelector('.accordion-header');
                const content = item.querySelector('.accordion-content');
                
                if (!header || !content) return;
                
                // Store reference
                state.accordionItems.push({
                    element: item,
                    header,
                    content,
                    index,
                    id: this.generateId(header)
                });
                
                // Set up click handler
                header.addEventListener('click', () => this.toggle(index));
                
                // Set ARIA attributes
                header.setAttribute('role', 'button');
                header.setAttribute('aria-expanded', 'false');
                header.setAttribute('tabindex', '0');
                content.setAttribute('role', 'region');
            });
            
            // Load persisted state
            if (config.accordion.persistState) {
                this.loadState();
            }
            
            // Handle URL hash on load
            if (window.location.hash) {
                this.scrollToHash(window.location.hash);
            }
        },
        
        /**
         * Generate unique ID for accordion item
         */
        generateId(header) {
            const text = header.textContent || header.innerText;
            return text
                .toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/\s+/g, '-')
                .substring(0, 50);
        },
        
        /**
         * Toggle accordion item
         */
        toggle(index) {
            const item = state.accordionItems[index];
            if (!item) return;
            
            const isExpanded = state.expandedItems.has(item.id);
            
            if (config.accordion.singleExpandMode && !isExpanded) {
                // Close all others in single-expand mode
                this.collapseAll();
            }
            
            if (isExpanded) {
                this.collapse(index);
            } else {
                this.expand(index);
            }
            
            // Persist state
            if (config.accordion.persistState) {
                this.saveState();
            }
        },
        
        /**
         * Expand accordion item
         */
        expand(index) {
            const item = state.accordionItems[index];
            if (!item) return;
            
            item.element.classList.add('active');
            item.header.setAttribute('aria-expanded', 'true');
            item.content.style.maxHeight = item.content.scrollHeight + 'px';
            
            state.expandedItems.add(item.id);
            
            // Dispatch event
            item.element.dispatchEvent(new CustomEvent('accordion:expanded', {
                detail: { index, id: item.id }
            }));
        },
        
        /**
         * Collapse accordion item
         */
        collapse(index) {
            const item = state.accordionItems[index];
            if (!item) return;
            
            item.element.classList.remove('active');
            item.header.setAttribute('aria-expanded', 'false');
            item.content.style.maxHeight = '0';
            
            state.expandedItems.delete(item.id);
            
            // Dispatch event
            item.element.dispatchEvent(new CustomEvent('accordion:collapsed', {
                detail: { index, id: item.id }
            }));
        },
        
        /**
         * Collapse all accordion items
         */
        collapseAll() {
            state.accordionItems.forEach((_, index) => this.collapse(index));
        },
        
        /**
         * Expand all accordion items
         */
        expandAll() {
            state.accordionItems.forEach((_, index) => this.expand(index));
        },
        
        /**
         * Save accordion state to localStorage
         */
        saveState() {
            try {
                const stateData = {
                    expandedItems: Array.from(state.expandedItems),
                    mode: config.accordion.singleExpandMode ? 'single' : 'multi',
                    timestamp: Date.now()
                };
                localStorage.setItem(config.accordion.storageKey, JSON.stringify(stateData));
            } catch (error) {
                console.warn('Failed to save accordion state:', error);
            }
        },
        
        /**
         * Load accordion state from localStorage
         */
        loadState() {
            try {
                const savedState = localStorage.getItem(config.accordion.storageKey);
                if (!savedState) return;
                
                const stateData = JSON.parse(savedState);
                
                // Check if state is not too old (24 hours)
                const age = Date.now() - (stateData.timestamp || 0);
                if (age > 24 * 60 * 60 * 1000) {
                    this.clearState();
                    return;
                }
                
                // Restore expanded items
                stateData.expandedItems.forEach(itemId => {
                    const index = state.accordionItems.findIndex(item => item.id === itemId);
                    if (index !== -1) {
                        this.expand(index);
                    }
                });
            } catch (error) {
                console.warn('Failed to load accordion state:', error);
            }
        },
        
        /**
         * Clear persisted state
         */
        clearState() {
            try {
                localStorage.removeItem(config.accordion.storageKey);
            } catch (error) {
                console.warn('Failed to clear accordion state:', error);
            }
        },
        
        /**
         * Toggle between single and multi-expand modes
         */
        toggleMode() {
            config.accordion.singleExpandMode = !config.accordion.singleExpandMode;
            if (config.accordion.singleExpandMode && state.expandedItems.size > 1) {
                // Keep only the first expanded item
                const firstExpanded = Array.from(state.expandedItems)[0];
                this.collapseAll();
                const index = state.accordionItems.findIndex(item => item.id === firstExpanded);
                if (index !== -1) {
                    this.expand(index);
                }
            }
            this.saveState();
            return config.accordion.singleExpandMode ? 'single' : 'multi';
        },
        
        /**
         * Scroll to hash anchor smoothly
         */
        scrollToHash(hash) {
            const targetId = hash.substring(1);
            const targetItem = state.accordionItems.find(item => item.id === targetId);
            
            if (targetItem) {
                // Expand the target item
                this.expand(targetItem.index);
                
                // Smooth scroll to element
                setTimeout(() => {
                    SmoothScroller.scrollToElement(targetItem.element);
                }, config.accordion.animationDuration);
            }
        }
    };
    
    // ========================================================================
    // SMOOTH SCROLLING WITH URL HASH ANCHORS
    // ========================================================================
    
    const SmoothScroller = {
        /**
         * Initialize smooth scrolling for all anchor links
         */
        init() {
            if (!config.smoothScroll.enabled) return;
            
            // Handle all hash links
            document.addEventListener('click', (e) => {
                const anchor = e.target.closest('a[href^="#"]');
                if (!anchor) return;
                
                e.preventDefault();
                const targetId = anchor.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                
                if (targetElement) {
                    this.scrollToElement(targetElement);
                    history.pushState(null, null, `#${targetId}`);
                }
            });
            
            // Handle browser back/forward with hash
            window.addEventListener('hashchange', () => {
                if (window.location.hash) {
                    AccordionManager.scrollToHash(window.location.hash);
                }
            });
            
            // Handle accordion header clicks for hash updates
            state.accordionItems.forEach(item => {
                item.header.addEventListener('click', () => {
                    if (state.expandedItems.has(item.id)) {
                        history.replaceState(null, null, `#${item.id}`);
                    }
                });
            });
        },
        
        /**
         * Scroll to element with smooth animation
         */
        scrollToElement(element) {
            if (!element) return;
            
            const targetPosition = element.getBoundingClientRect().top + window.pageYOffset;
            const startPosition = window.pageYOffset;
            const distance = targetPosition - startPosition - config.smoothScroll.offset;
            const duration = config.smoothScroll.duration;
            let startTime = null;
            
            const easing = this.easingFunctions[config.smoothScroll.easing];
            
            const animation = (currentTime) => {
                if (startTime === null) startTime = currentTime;
                const timeElapsed = currentTime - startTime;
                const progress = Math.min(timeElapsed / duration, 1);
                
                window.scrollTo(0, startPosition + distance * easing(progress));
                
                if (timeElapsed < duration) {
                    requestAnimationFrame(animation);
                }
            };
            
            requestAnimationFrame(animation);
        },
        
        /**
         * Easing functions for smooth animations
         */
        easingFunctions: {
            linear: (t) => t,
            easeInQuad: (t) => t * t,
            easeOutQuad: (t) => t * (2 - t),
            easeInOutQuad: (t) => t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t,
            easeInCubic: (t) => t * t * t,
            easeOutCubic: (t) => (--t) * t * t + 1,
            easeInOutCubic: (t) => t < 0.5 ? 4 * t * t * t : (t - 1) * (2 * t - 2) * (2 * t - 2) + 1
        }
    };
    
    // ========================================================================
    // COPY TO CLIPBOARD FOR CODE SNIPPETS
    // ========================================================================
    
    const ClipboardManager = {
        /**
         * Initialize copy buttons for all code blocks
         */
        init() {
            const codeBlocks = document.querySelectorAll('pre code, .code-block, pre');
            
            codeBlocks.forEach((codeBlock) => {
                const pre = codeBlock.tagName === 'PRE' ? codeBlock : codeBlock.closest('pre');
                if (!pre) return;
                
                // Skip if copy button already exists
                if (pre.querySelector('.copy-button')) return;
                
                // Create copy button
                const button = this.createCopyButton();
                
                // Position button relative to pre element
                pre.style.position = 'relative';
                pre.appendChild(button);
                
                // Add click handler
                button.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const code = codeBlock.textContent || codeBlock.innerText;
                    this.copyToClipboard(code, button);
                });
            });
        },
        
        /**
         * Create copy button element
         */
        createCopyButton() {
            const button = document.createElement('button');
            button.className = 'copy-button';
            button.innerHTML = config.clipboard.buttonText;
            button.setAttribute('aria-label', 'Copy code to clipboard');
            button.style.cssText = `
                position: absolute;
                top: 8px;
                right: 8px;
                padding: 6px 12px;
                background: rgba(255, 255, 255, 0.9);
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 12px;
                font-weight: 500;
                color: #333;
                cursor: pointer;
                transition: all 0.2s ease;
                z-index: 10;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            `;
            
            // Hover effect
            button.addEventListener('mouseenter', () => {
                button.style.background = 'rgba(255, 255, 255, 1)';
                button.style.borderColor = '#e3342f';
            });
            
            button.addEventListener('mouseleave', () => {
                if (!button.classList.contains('copied')) {
                    button.style.background = 'rgba(255, 255, 255, 0.9)';
                    button.style.borderColor = '#ddd';
                }
            });
            
            return button;
        },
        
        /**
         * Copy text to clipboard with visual feedback
         */
        async copyToClipboard(text, button) {
            try {
                // Try modern clipboard API first
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(text);
                    this.showSuccess(button);
                } else {
                    // Fallback for older browsers
                    this.fallbackCopy(text);
                    this.showSuccess(button);
                }
            } catch (error) {
                console.error('Copy failed:', error);
                this.showError(button);
            }
        },
        
        /**
         * Fallback copy method for older browsers
         */
        fallbackCopy(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        },
        
        /**
         * Show success feedback
         */
        showSuccess(button) {
            const originalText = button.innerHTML;
            button.innerHTML = config.clipboard.copiedText;
            button.classList.add('copied');
            button.style.background = '#28a745';
            button.style.color = 'white';
            button.style.borderColor = '#28a745';
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.classList.remove('copied');
                button.style.background = 'rgba(255, 255, 255, 0.9)';
                button.style.color = '#333';
                button.style.borderColor = '#ddd';
            }, config.clipboard.feedbackDuration);
        },
        
        /**
         * Show error feedback
         */
        showError(button) {
            const originalText = button.innerHTML;
            button.innerHTML = config.clipboard.errorText;
            button.style.background = '#dc3545';
            button.style.color = 'white';
            button.style.borderColor = '#dc3545';
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.background = 'rgba(255, 255, 255, 0.9)';
                button.style.color = '#333';
                button.style.borderColor = '#ddd';
            }, config.clipboard.feedbackDuration);
        },
        
        /**
         * Reinitialize for dynamically added code blocks
         */
        refresh() {
            this.init();
        }
    };
    
    // ========================================================================
    // KEYBOARD SHORTCUTS
    // ========================================================================
    
    const KeyboardShortcuts = {
        /**
         * Initialize keyboard shortcuts
         */
        init() {
            if (!config.shortcuts.enabled) return;
            
            document.addEventListener('keydown', (e) => {
                // Ignore shortcuts when typing in input fields
                if (this.isTypingContext(e.target)) {
                    // Exception: allow '/' for search
                    if (e.key !== config.shortcuts.searchKey) {
                        return;
                    }
                }
                
                this.handleKeyPress(e);
            });
        },
        
        /**
         * Check if user is typing in an input context
         */
        isTypingContext(element) {
            const tagName = element.tagName.toLowerCase();
            return tagName === 'input' || tagName === 'textarea' || element.isContentEditable;
        },
        
        /**
         * Handle keyboard shortcuts
         */
        handleKeyPress(e) {
            // Search shortcut: '/'
            if (e.key === config.shortcuts.searchKey && !this.isTypingContext(e.target)) {
                e.preventDefault();
                this.focusSearch();
                return;
            }
            
            // Command palette: Ctrl+K or Cmd+K
            if ((e.ctrlKey || e.metaKey) && e.key === config.shortcuts.commandPaletteKey) {
                e.preventDefault();
                this.toggleCommandPalette();
                return;
            }
            
            // Arrow navigation within accordions
            if (config.shortcuts.arrowNavigation) {
                this.handleArrowNavigation(e);
            }
            
            // Escape key: close command palette or clear search
            if (e.key === 'Escape') {
                if (state.commandPaletteOpen) {
                    this.toggleCommandPalette();
                } else {
                    this.clearSearch();
                }
                return;
            }
        },
        
        /**
         * Focus search input
         */
        focusSearch() {
            const searchInput = document.querySelector('.search-bar input');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        },
        
        /**
         * Clear search input
         */
        clearSearch() {
            const searchInput = document.querySelector('.search-bar input');
            if (searchInput && searchInput.value) {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input', { bubbles: true }));
                searchInput.blur();
            }
        },
        
        /**
         * Handle arrow key navigation
         */
        handleArrowNavigation(e) {
            // Only handle arrows when focused on accordion headers or in command palette
            const focusedElement = document.activeElement;
            const isAccordionHeader = focusedElement.classList.contains('accordion-header');
            
            if (!isAccordionHeader && !state.commandPaletteOpen) return;
            
            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    this.navigateNext();
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    this.navigatePrevious();
                    break;
                case 'Enter':
                case ' ':
                    if (isAccordionHeader) {
                        e.preventDefault();
                        focusedElement.click();
                    }
                    break;
                case 'Home':
                    e.preventDefault();
                    this.navigateFirst();
                    break;
                case 'End':
                    e.preventDefault();
                    this.navigateLast();
                    break;
            }
        },
        
        /**
         * Navigate to next accordion item
         */
        navigateNext() {
            if (state.accordionItems.length === 0) return;
            
            state.currentFocusIndex = (state.currentFocusIndex + 1) % state.accordionItems.length;
            state.accordionItems[state.currentFocusIndex].header.focus();
        },
        
        /**
         * Navigate to previous accordion item
         */
        navigatePrevious() {
            if (state.accordionItems.length === 0) return;
            
            state.currentFocusIndex = state.currentFocusIndex <= 0 
                ? state.accordionItems.length - 1 
                : state.currentFocusIndex - 1;
            state.accordionItems[state.currentFocusIndex].header.focus();
        },
        
        /**
         * Navigate to first accordion item
         */
        navigateFirst() {
            if (state.accordionItems.length === 0) return;
            
            state.currentFocusIndex = 0;
            state.accordionItems[0].header.focus();
        },
        
        /**
         * Navigate to last accordion item
         */
        navigateLast() {
            if (state.accordionItems.length === 0) return;
            
            state.currentFocusIndex = state.accordionItems.length - 1;
            state.accordionItems[state.currentFocusIndex].header.focus();
        },
        
        /**
         * Toggle command palette
         */
        toggleCommandPalette() {
            state.commandPaletteOpen = !state.commandPaletteOpen;
            
            if (state.commandPaletteOpen) {
                this.showCommandPalette();
            } else {
                this.hideCommandPalette();
            }
        },
        
        /**
         * Show command palette
         */
        showCommandPalette() {
            let palette = document.getElementById('command-palette');
            
            if (!palette) {
                palette = this.createCommandPalette();
                document.body.appendChild(palette);
            }
            
            palette.style.display = 'block';
            palette.querySelector('input')?.focus();
            
            // Dispatch event
            document.dispatchEvent(new CustomEvent('commandpalette:opened'));
        },
        
        /**
         * Hide command palette
         */
        hideCommandPalette() {
            const palette = document.getElementById('command-palette');
            if (palette) {
                palette.style.display = 'none';
            }
            
            // Dispatch event
            document.dispatchEvent(new CustomEvent('commandpalette:closed'));
        },
        
        /**
         * Create command palette UI
         */
        createCommandPalette() {
            const palette = document.createElement('div');
            palette.id = 'command-palette';
            palette.style.cssText = `
                position: fixed;
                top: 20%;
                left: 50%;
                transform: translateX(-50%);
                width: 90%;
                max-width: 600px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.3);
                z-index: 10000;
                overflow: hidden;
                display: none;
            `;
            
            palette.innerHTML = `
                <div style="padding: 20px; border-bottom: 1px solid #eee;">
                    <input type="text" placeholder="Type a command or search..." style="
                        width: 100%;
                        padding: 12px;
                        border: 1px solid #ddd;
                        border-radius: 4px;
                        font-size: 16px;
                    ">
                </div>
                <div style="max-height: 400px; overflow-y: auto; padding: 10px;">
                    <div class="command-list">
                        <div class="command-item" data-command="expand-all" style="padding: 12px; cursor: pointer; border-radius: 4px; margin-bottom: 4px;">
                            <strong>Expand All</strong> - Open all accordion items
                        </div>
                        <div class="command-item" data-command="collapse-all" style="padding: 12px; cursor: pointer; border-radius: 4px; margin-bottom: 4px;">
                            <strong>Collapse All</strong> - Close all accordion items
                        </div>
                        <div class="command-item" data-command="toggle-mode" style="padding: 12px; cursor: pointer; border-radius: 4px; margin-bottom: 4px;">
                            <strong>Toggle Mode</strong> - Switch between single/multi-expand
                        </div>
                        <div class="command-item" data-command="clear-state" style="padding: 12px; cursor: pointer; border-radius: 4px; margin-bottom: 4px;">
                            <strong>Clear State</strong> - Reset accordion state
                        </div>
                        <div class="command-item" data-command="focus-search" style="padding: 12px; cursor: pointer; border-radius: 4px; margin-bottom: 4px;">
                            <strong>Focus Search</strong> - Jump to search bar
                        </div>
                    </div>
                </div>
                <div style="padding: 10px; background: #f9f9f9; font-size: 12px; color: #666; border-top: 1px solid #eee;">
                    Press <kbd>Esc</kbd> to close • <kbd>↑↓</kbd> to navigate • <kbd>Enter</kbd> to select
                </div>
            `;
            
            // Add hover effects
            const items = palette.querySelectorAll('.command-item');
            items.forEach(item => {
                item.addEventListener('mouseenter', () => {
                    item.style.background = '#f0f0f0';
                });
                item.addEventListener('mouseleave', () => {
                    item.style.background = 'transparent';
                });
                item.addEventListener('click', () => {
                    this.executeCommand(item.getAttribute('data-command'));
                    this.hideCommandPalette();
                });
            });
            
            // Close on backdrop click
            palette.addEventListener('click', (e) => {
                if (e.target === palette) {
                    this.hideCommandPalette();
                }
            });
            
            return palette;
        },
        
        /**
         * Execute command from palette
         */
        executeCommand(command) {
            switch (command) {
                case 'expand-all':
                    AccordionManager.expandAll();
                    break;
                case 'collapse-all':
                    AccordionManager.collapseAll();
                    break;
                case 'toggle-mode':
                    const mode = AccordionManager.toggleMode();
                    console.log(`Accordion mode: ${mode}`);
                    break;
                case 'clear-state':
                    AccordionManager.clearState();
                    AccordionManager.collapseAll();
                    break;
                case 'focus-search':
                    this.focusSearch();
                    break;
            }
        }
    };
    
    // ========================================================================
    // LOADING STATES WITH SKELETON SCREENS
    // ========================================================================
    
    const LoadingManager = {
        /**
         * Show loading skeleton
         */
        showLoading(container) {
            if (!container) {
                container = document.querySelector('#main-content');
            }
            
            if (!container) return;
            
            state.isLoading = true;
            const loadingStart = Date.now();
            
            // Store original content
            if (!container.dataset.originalContent) {
                container.dataset.originalContent = container.innerHTML;
            }
            
            // Create skeleton
            const skeleton = this.createSkeleton();
            container.innerHTML = skeleton;
            
            // Store start time for minimum display
            container.dataset.loadingStart = loadingStart.toString();
            
            // Dispatch event
            document.dispatchEvent(new CustomEvent('loading:started'));
        },
        
        /**
         * Hide loading skeleton
         */
        async hideLoading(container, newContent = null) {
            if (!container) {
                container = document.querySelector('#main-content');
            }
            
            if (!container || !state.isLoading) return;
            
            // Ensure minimum display time
            const loadingStart = parseInt(container.dataset.loadingStart || '0', 10);
            const elapsed = Date.now() - loadingStart;
            const remainingTime = Math.max(0, config.loading.minDisplayTime - elapsed);
            
            await new Promise(resolve => setTimeout(resolve, remainingTime));
            
            // Restore or set new content
            if (newContent) {
                container.innerHTML = newContent;
            } else if (container.dataset.originalContent) {
                container.innerHTML = container.dataset.originalContent;
                delete container.dataset.originalContent;
            }
            
            delete container.dataset.loadingStart;
            state.isLoading = false;
            
            // Reinitialize UI components for new content
            ClipboardManager.refresh();
            
            // Dispatch event
            document.dispatchEvent(new CustomEvent('loading:completed'));
        },
        
        /**
         * Create skeleton screen HTML
         */
        createSkeleton() {
            const skeletonItems = Array.from({ length: config.loading.skeletonCount }, (_, i) => `
                <div class="skeleton-item" style="
                    margin-bottom: 1rem;
                    border: 1px solid #e0e0e0;
                    border-radius: 8px;
                    overflow: hidden;
                    animation: pulse 1.5s ease-in-out infinite;
                ">
                    <div class="skeleton-header" style="
                        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
                        background-size: 200% 100%;
                        animation: shimmer 2s infinite;
                        padding: 1.5rem;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    ">
                        <div style="
                            height: 20px;
                            width: ${60 + Math.random() * 30}%;
                            background: rgba(255,255,255,0.7);
                            border-radius: 4px;
                        "></div>
                        <div style="
                            height: 20px;
                            width: 20px;
                            background: rgba(255,255,255,0.7);
                            border-radius: 50%;
                        "></div>
                    </div>
                </div>
            `).join('');
            
            const styles = `
                <style>
                    @keyframes shimmer {
                        0% { background-position: -200% 0; }
                        100% { background-position: 200% 0; }
                    }
                    @keyframes pulse {
                        0%, 100% { opacity: 1; }
                        50% { opacity: 0.7; }
                    }
                </style>
            `;
            
            return styles + `
                <div class="skeleton-container">
                    <div class="skeleton-intro" style="margin-bottom: 2rem;">
                        <div style="
                            height: 32px;
                            width: 60%;
                            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
                            background-size: 200% 100%;
                            animation: shimmer 2s infinite;
                            border-radius: 4px;
                            margin-bottom: 1rem;
                        "></div>
                        <div style="
                            height: 16px;
                            width: 100%;
                            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
                            background-size: 200% 100%;
                            animation: shimmer 2s infinite;
                            border-radius: 4px;
                            margin-bottom: 0.5rem;
                        "></div>
                        <div style="
                            height: 16px;
                            width: 80%;
                            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
                            background-size: 200% 100%;
                            animation: shimmer 2s infinite;
                            border-radius: 4px;
                        "></div>
                    </div>
                    ${skeletonItems}
                </div>
            `;
        },
        
        /**
         * Show inline loading spinner
         */
        createSpinner(size = 'medium') {
            const sizes = {
                small: 20,
                medium: 40,
                large: 60
            };
            
            const spinnerSize = sizes[size] || sizes.medium;
            
            const spinner = document.createElement('div');
            spinner.className = 'loading-spinner';
            spinner.style.cssText = `
                display: inline-block;
                width: ${spinnerSize}px;
                height: ${spinnerSize}px;
                border: 3px solid #f3f3f3;
                border-top: 3px solid #e3342f;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            `;
            
            // Add animation if not already defined
            if (!document.getElementById('spinner-animation')) {
                const style = document.createElement('style');
                style.id = 'spinner-animation';
                style.textContent = `
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                `;
                document.head.appendChild(style);
            }
            
            return spinner;
        }
    };
    
    // ========================================================================
    // PUBLIC API & INITIALIZATION
    // ========================================================================
    
    /**
     * Initialize all UI controllers
     */
    const init = (options = {}) => {
        // Merge custom config
        if (options.accordion) Object.assign(config.accordion, options.accordion);
        if (options.smoothScroll) Object.assign(config.smoothScroll, options.smoothScroll);
        if (options.clipboard) Object.assign(config.clipboard, options.clipboard);
        if (options.shortcuts) Object.assign(config.shortcuts, options.shortcuts);
        if (options.loading) Object.assign(config.loading, options.loading);
        
        // Initialize all modules
        AccordionManager.init();
        SmoothScroller.init();
        ClipboardManager.init();
        KeyboardShortcuts.init();
        
        console.log('UI Controller initialized successfully');
        
        // Dispatch ready event
        document.dispatchEvent(new CustomEvent('uicontroller:ready'));
    };
    
    /**
     * Public API
     */
    return {
        init,
        
        // Accordion controls
        accordion: {
            expand: (index) => AccordionManager.expand(index),
            collapse: (index) => AccordionManager.collapse(index),
            toggle: (index) => AccordionManager.toggle(index),
            expandAll: () => AccordionManager.expandAll(),
            collapseAll: () => AccordionManager.collapseAll(),
            toggleMode: () => AccordionManager.toggleMode(),
            clearState: () => AccordionManager.clearState(),
            saveState: () => AccordionManager.saveState(),
            loadState: () => AccordionManager.loadState()
        },
        
        // Smooth scrolling
        scroll: {
            toElement: (element) => SmoothScroller.scrollToElement(element),
            toHash: (hash) => AccordionManager.scrollToHash(hash)
        },
        
        // Clipboard
        clipboard: {
            refresh: () => ClipboardManager.refresh(),
            copy: (text, button) => ClipboardManager.copyToClipboard(text, button)
        },
        
        // Keyboard shortcuts
        shortcuts: {
            toggle: (enabled) => { config.shortcuts.enabled = enabled; },
            focusSearch: () => KeyboardShortcuts.focusSearch(),
            clearSearch: () => KeyboardShortcuts.clearSearch()
        },
        
        // Loading states
        loading: {
            show: (container) => LoadingManager.showLoading(container),
            hide: (container, content) => LoadingManager.hideLoading(container, content),
            createSpinner: (size) => LoadingManager.createSpinner(size)
        },
        
        // Config access
        getConfig: () => ({ ...config }),
        updateConfig: (newConfig) => {
            if (newConfig.accordion) Object.assign(config.accordion, newConfig.accordion);
            if (newConfig.smoothScroll) Object.assign(config.smoothScroll, newConfig.smoothScroll);
            if (newConfig.clipboard) Object.assign(config.clipboard, newConfig.clipboard);
            if (newConfig.shortcuts) Object.assign(config.shortcuts, newConfig.shortcuts);
            if (newConfig.loading) Object.assign(config.loading, newConfig.loading);
        }
    };
})();

// ============================================================================
// AUTO-INITIALIZATION
// ============================================================================

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => UIController.init());
} else {
    UIController.init();
}

// ============================================================================
// EXPORTS
// ============================================================================

// CommonJS
if (typeof module !== 'undefined' && module.exports) {
    module.exports = UIController;
}

// Global
window.UIController = UIController;
