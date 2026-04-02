// ============================================================================
// MARKDOWN RENDERER MODULE
// Full-featured markdown-to-HTML converter with marked.js integration,
// Prism.js syntax highlighting, Mermaid.js diagram rendering,
// and custom rendering for special elements
// ============================================================================

const MarkdownRenderer = (() => {
    'use strict';

    // ========================================================================
    // Configuration & Settings
    // ========================================================================
    const config = {
        // Marked.js options
        marked: {
            gfm: true,              // GitHub Flavored Markdown
            breaks: true,           // Convert \n to <br>
            pedantic: false,        // Don't use original markdown.pl behavior
            sanitize: false,        // Allow HTML (we'll sanitize custom elements)
            smartLists: true,       // Use smarter list behavior
            smartypants: true,      // Use "smart" typographic punctuation
            xhtml: false,           // Generate XHTML output
            headerIds: true,        // Add IDs to headers
            headerPrefix: '',       // Prefix for header IDs
            mangle: false           // Don't mangle email addresses
        },
        
        // Prism.js language support
        prismLanguages: ['php', 'javascript', 'js', 'sql', 'bash', 'shell', 'yaml', 'yml', 'json', 'css', 'html', 'xml', 'typescript', 'ts', 'python', 'py', 'java', 'c', 'cpp', 'csharp', 'cs', 'go', 'rust', 'ruby', 'swift'],
        
        // Mermaid.js options
        mermaid: {
            theme: 'default',
            themeVariables: {
                primaryColor: '#3b82f6',
                primaryTextColor: '#fff',
                primaryBorderColor: '#2563eb',
                lineColor: '#6b7280',
                secondaryColor: '#8b5cf6',
                tertiaryColor: '#10b981',
                background: '#ffffff',
                mainBkg: '#f9fafb',
                secondBkg: '#f3f4f6',
                border1: '#e5e7eb',
                border2: '#d1d5db'
            },
            flowchart: {
                curve: 'basis',
                htmlLabels: true,
                nodeSpacing: 50,
                rankSpacing: 50
            },
            sequence: {
                diagramMarginX: 50,
                diagramMarginY: 10,
                actorMargin: 50,
                width: 150,
                height: 65,
                boxMargin: 10,
                boxTextMargin: 5,
                noteMargin: 10,
                messageMargin: 35
            },
            gantt: {
                titleTopMargin: 25,
                barHeight: 20,
                barGap: 4,
                topPadding: 50,
                leftPadding: 75,
                gridLineStartPadding: 35,
                fontSize: 11,
                numberSectionStyles: 4,
                axisFormat: '%Y-%m-%d'
            }
        },
        
        // Custom rendering options
        customElements: {
            admonitions: true,      // Support for note/warning/tip/danger callouts
            badges: true,           // Support for difficulty/tag badges
            metadataBlocks: true,   // Support for frontmatter-style metadata
            expandableBlocks: true, // Support for collapsible sections
            copyButtons: true,      // Add copy buttons to code blocks
            lineNumbers: false      // Show line numbers in code blocks
        }
    };

    // ========================================================================
    // Marked.js Integration & Configuration
    // ========================================================================
    
    /**
     * Initialize marked.js with custom renderer
     */
    const initializeMarked = () => {
        if (typeof marked === 'undefined') {
            console.warn('marked.js is not loaded. Using fallback parser.');
            return false;
        }

        // Configure marked options
        marked.setOptions(config.marked);

        // Custom renderer extensions
        const renderer = new marked.Renderer();

        // Custom heading renderer with IDs
        renderer.heading = (text, level, raw) => {
            const id = generateId(raw);
            return `<h${level} id="${id}" class="markdown-heading markdown-heading-${level}">
                <a href="#${id}" class="heading-anchor" aria-label="Permalink">#</a>
                ${text}
            </h${level}>`;
        };

        // Custom code block renderer for Prism.js and Mermaid.js
        renderer.code = (code, language, isEscaped) => {
            const lang = (language || '').toLowerCase().trim();
            
            // Check if it's a Mermaid diagram
            if (lang === 'mermaid') {
                return renderMermaidDiagram(code);
            }
            
            // Check for custom elements (admonitions)
            if (lang === 'admonition' || lang === 'callout') {
                return renderAdmonition(code);
            }
            
            // Regular code block with Prism.js
            return renderCodeBlock(code, lang, isEscaped);
        };

        // Custom blockquote renderer for admonitions
        renderer.blockquote = (quote) => {
            // Check if it's an admonition-style blockquote
            const admonitionMatch = quote.match(/^<p>\[!(NOTE|TIP|WARNING|DANGER|INFO)\](.+?)<\/p>/is);
            if (admonitionMatch) {
                const type = admonitionMatch[1].toLowerCase();
                const content = admonitionMatch[2];
                return renderAdmonition(`[${type}]\n${content}`, type);
            }
            return `<blockquote class="markdown-blockquote">${quote}</blockquote>`;
        };

        // Custom table renderer with classes
        renderer.table = (header, body) => {
            return `<div class="markdown-table-wrapper">
                <table class="markdown-table">
                    <thead>${header}</thead>
                    <tbody>${body}</tbody>
                </table>
            </div>`;
        };

        // Custom list renderer
        renderer.list = (body, ordered, start) => {
            const type = ordered ? 'ol' : 'ul';
            const startAttr = ordered && start !== 1 ? ` start="${start}"` : '';
            return `<${type} class="markdown-list markdown-list-${ordered ? 'ordered' : 'unordered'}"${startAttr}>${body}</${type}>`;
        };

        // Custom link renderer with security
        renderer.link = (href, title, text) => {
            const cleanHref = sanitizeUrl(href);
            const titleAttr = title ? ` title="${escapeHtml(title)}"` : '';
            const external = cleanHref.startsWith('http') ? ' target="_blank" rel="noopener noreferrer"' : '';
            return `<a href="${cleanHref}"${titleAttr}${external} class="markdown-link">${text}</a>`;
        };

        // Custom image renderer with lazy loading
        renderer.image = (href, title, text) => {
            const cleanHref = sanitizeUrl(href);
            const titleAttr = title ? ` title="${escapeHtml(title)}"` : '';
            const altAttr = text ? ` alt="${escapeHtml(text)}"` : ' alt=""';
            return `<img src="${cleanHref}"${altAttr}${titleAttr} class="markdown-image" loading="lazy">`;
        };

        marked.use({ renderer });

        return true;
    };

    // ========================================================================
    // Prism.js Syntax Highlighting Integration
    // ========================================================================

    /**
     * Render code block with Prism.js syntax highlighting
     */
    const renderCodeBlock = (code, language, isEscaped) => {
        const lang = normalizeLanguage(language);
        const escapedCode = isEscaped ? code : escapeHtml(code);
        
        // Check if Prism is available
        if (typeof Prism !== 'undefined' && Prism.languages[lang]) {
            try {
                const highlighted = Prism.highlight(code, Prism.languages[lang], lang);
                return buildCodeBlockHTML(highlighted, lang, code);
            } catch (error) {
                console.warn(`Prism highlighting failed for language "${lang}":`, error);
            }
        }
        
        // Fallback to basic highlighting
        return buildCodeBlockHTML(escapedCode, lang, code);
    };

    /**
     * Build complete code block HTML structure
     */
    const buildCodeBlockHTML = (highlightedCode, language, originalCode) => {
        const displayLang = getLanguageDisplayName(language);
        const copyBtn = config.customElements.copyButtons 
            ? `<button class="code-copy-btn" data-code="${escapeHtml(originalCode)}" aria-label="Copy code">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5.5 2.5h-2a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M6.5 1.5h6a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1h-6a1 1 0 0 1-1-1v-6a1 1 0 0 1 1-1z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="copy-text">Copy</span>
            </button>`
            : '';

        return `<div class="code-block-wrapper" data-language="${language}">
            <div class="code-block-header">
                <span class="code-language">${displayLang}</span>
                ${copyBtn}
            </div>
            <pre class="language-${language}"><code class="language-${language}">${highlightedCode}</code></pre>
        </div>`;
    };

    /**
     * Normalize language names for Prism
     */
    const normalizeLanguage = (lang) => {
        const langMap = {
            'js': 'javascript',
            'ts': 'typescript',
            'py': 'python',
            'rb': 'ruby',
            'yml': 'yaml',
            'sh': 'bash',
            'shell': 'bash',
            'cs': 'csharp'
        };
        return langMap[lang] || lang || 'plaintext';
    };

    /**
     * Get display name for language
     */
    const getLanguageDisplayName = (lang) => {
        const displayNames = {
            'javascript': 'JavaScript',
            'typescript': 'TypeScript',
            'php': 'PHP',
            'python': 'Python',
            'java': 'Java',
            'csharp': 'C#',
            'sql': 'SQL',
            'bash': 'Bash',
            'yaml': 'YAML',
            'json': 'JSON',
            'css': 'CSS',
            'html': 'HTML',
            'xml': 'XML',
            'markdown': 'Markdown',
            'plaintext': 'Plain Text'
        };
        return displayNames[lang] || lang.toUpperCase();
    };

    // ========================================================================
    // Mermaid.js Diagram Rendering
    // ========================================================================

    /**
     * Render Mermaid diagram
     */
    const renderMermaidDiagram = (code) => {
        if (typeof mermaid === 'undefined') {
            console.warn('Mermaid.js is not loaded. Displaying diagram code instead.');
            return `<div class="mermaid-fallback">
                <pre>${escapeHtml(code)}</pre>
                <p class="mermaid-error">Mermaid.js is required to render this diagram.</p>
            </div>`;
        }

        const diagramId = `mermaid-${generateRandomId()}`;
        
        return `<div class="mermaid-diagram-wrapper">
            <div id="${diagramId}" class="mermaid-diagram">${escapeHtml(code)}</div>
        </div>`;
    };

    /**
     * Initialize Mermaid.js if available
     */
    const initializeMermaid = () => {
        if (typeof mermaid !== 'undefined') {
            mermaid.initialize(config.mermaid);
            return true;
        }
        return false;
    };

    /**
     * Render all Mermaid diagrams in the document
     */
    const renderMermaidDiagrams = () => {
        if (typeof mermaid === 'undefined') {
            return;
        }

        const diagrams = document.querySelectorAll('.mermaid-diagram');
        diagrams.forEach((diagram) => {
            if (!diagram.hasAttribute('data-processed')) {
                try {
                    mermaid.init(undefined, diagram);
                    diagram.setAttribute('data-processed', 'true');
                } catch (error) {
                    console.error('Mermaid rendering error:', error);
                    diagram.innerHTML = `<div class="mermaid-error">
                        Failed to render diagram: ${error.message}
                    </div>`;
                }
            }
        });
    };

    // ========================================================================
    // Custom Element Renderers (Admonitions, Callouts, Badges)
    // ========================================================================

    /**
     * Render admonition/callout blocks
     */
    const renderAdmonition = (content, type = null) => {
        // Parse admonition syntax: [!TYPE] Title\nContent
        const match = content.match(/^\[!?(NOTE|TIP|WARNING|DANGER|INFO)\]\s*(.+?)[\n\r]+([\s\S]*)/i);
        
        if (!match && !type) {
            return `<blockquote class="markdown-blockquote">${content}</blockquote>`;
        }

        const admonitionType = (type || match[1]).toLowerCase();
        const title = match ? match[2].trim() : getAdmonitionDefaultTitle(admonitionType);
        const body = match ? match[3].trim() : content;

        const icon = getAdmonitionIcon(admonitionType);
        const className = `admonition admonition-${admonitionType}`;

        return `<div class="${className}" role="note" aria-label="${admonitionType}">
            <div class="admonition-header">
                <span class="admonition-icon">${icon}</span>
                <span class="admonition-title">${escapeHtml(title)}</span>
            </div>
            <div class="admonition-content">
                ${marked.parse(body)}
            </div>
        </div>`;
    };

    /**
     * Get default title for admonition type
     */
    const getAdmonitionDefaultTitle = (type) => {
        const defaults = {
            'note': 'Note',
            'tip': 'Tip',
            'warning': 'Warning',
            'danger': 'Danger',
            'info': 'Information'
        };
        return defaults[type] || 'Note';
    };

    /**
     * Get icon for admonition type
     */
    const getAdmonitionIcon = (type) => {
        const icons = {
            'note': '📝',
            'tip': '💡',
            'warning': '⚠️',
            'danger': '🚨',
            'info': 'ℹ️'
        };
        return icons[type] || '📝';
    };

    /**
     * Render metadata badges (difficulty, tags)
     */
    const renderMetadataBadges = (metadata) => {
        if (!config.customElements.badges || !metadata) {
            return '';
        }

        let badges = [];

        // Difficulty badge
        if (metadata.difficulty) {
            const difficulty = metadata.difficulty.toLowerCase();
            const difficultyClass = `difficulty-badge difficulty-${difficulty}`;
            badges.push(`<span class="${difficultyClass}">${escapeHtml(metadata.difficulty)}</span>`);
        }

        // Tags badges
        if (metadata.tags && Array.isArray(metadata.tags)) {
            metadata.tags.forEach(tag => {
                badges.push(`<span class="tag-badge">${escapeHtml(tag)}</span>`);
            });
        }

        // Category badge
        if (metadata.category) {
            badges.push(`<span class="category-badge">${escapeHtml(metadata.category)}</span>`);
        }

        // Time estimate badge
        if (metadata.timeEstimate) {
            badges.push(`<span class="time-badge">⏱️ ${escapeHtml(metadata.timeEstimate)}</span>`);
        }

        if (badges.length === 0) {
            return '';
        }

        return `<div class="metadata-badges">${badges.join('')}</div>`;
    };

    /**
     * Parse frontmatter-style metadata from markdown
     */
    const parseMetadata = (markdown) => {
        const frontmatterMatch = markdown.match(/^---\s*\n([\s\S]*?)\n---\s*\n/);
        
        if (!frontmatterMatch) {
            return { metadata: null, content: markdown };
        }

        const frontmatter = frontmatterMatch[1];
        const content = markdown.slice(frontmatterMatch[0].length);
        const metadata = {};

        // Parse YAML-like frontmatter
        frontmatter.split('\n').forEach(line => {
            const colonIndex = line.indexOf(':');
            if (colonIndex === -1) return;

            const key = line.slice(0, colonIndex).trim();
            let value = line.slice(colonIndex + 1).trim();

            // Handle arrays (tags: [tag1, tag2])
            if (value.startsWith('[') && value.endsWith(']')) {
                value = value.slice(1, -1).split(',').map(v => v.trim().replace(/^["']|["']$/g, ''));
            } else {
                // Remove quotes
                value = value.replace(/^["']|["']$/g, '');
            }

            metadata[key] = value;
        });

        return { metadata, content };
    };

    // ========================================================================
    // Utility Functions
    // ========================================================================

    /**
     * Generate ID from text
     */
    const generateId = (text) => {
        return text
            .toLowerCase()
            .replace(/<[^>]*>/g, '') // Remove HTML tags
            .replace(/[^\w\s-]/g, '') // Remove special characters
            .replace(/\s+/g, '-') // Replace spaces with hyphens
            .replace(/--+/g, '-') // Replace multiple hyphens with single
            .replace(/^-+|-+$/g, ''); // Remove leading/trailing hyphens
    };

    /**
     * Generate random ID
     */
    const generateRandomId = () => {
        return `id-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    };

    /**
     * Escape HTML special characters
     */
    const escapeHtml = (text) => {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    };

    /**
     * Sanitize URL to prevent XSS
     */
    const sanitizeUrl = (url) => {
        const dangerous = /^(javascript|data|vbscript):/i;
        if (dangerous.test(url)) {
            return '#';
        }
        return url;
    };

    /**
     * Add copy functionality to code blocks
     */
    const setupCopyButtons = () => {
        if (!config.customElements.copyButtons) return;

        document.addEventListener('click', (e) => {
            const copyBtn = e.target.closest('.code-copy-btn');
            if (!copyBtn) return;

            const codeBlock = copyBtn.closest('.code-block-wrapper');
            const code = codeBlock.querySelector('code').textContent;

            navigator.clipboard.writeText(code).then(() => {
                const originalText = copyBtn.querySelector('.copy-text').textContent;
                copyBtn.querySelector('.copy-text').textContent = 'Copied!';
                copyBtn.classList.add('copied');

                setTimeout(() => {
                    copyBtn.querySelector('.copy-text').textContent = originalText;
                    copyBtn.classList.remove('copied');
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy code:', err);
                copyBtn.querySelector('.copy-text').textContent = 'Failed';
                setTimeout(() => {
                    copyBtn.querySelector('.copy-text').textContent = 'Copy';
                }, 2000);
            });
        });
    };

    // ========================================================================
    // Main Rendering Function
    // ========================================================================

    /**
     * Render markdown to HTML
     */
    const render = (markdown, options = {}) => {
        if (!markdown || typeof markdown !== 'string') {
            return '';
        }

        // Merge options with config
        const renderOptions = { ...config, ...options };

        // Parse metadata if enabled
        const { metadata, content } = config.customElements.metadataBlocks 
            ? parseMetadata(markdown) 
            : { metadata: null, content: markdown };

        // Pre-process markdown for custom syntax
        let processedContent = content;

        // Process admonitions in blockquote style (GitHub-style)
        processedContent = processedContent.replace(
            /^>\s*\[!(NOTE|TIP|WARNING|DANGER|INFO)\]\s*(.+?)$([\s\S]*?)(?=\n\n|\n$|$)/gim,
            (match, type, title, body) => {
                return `\`\`\`admonition\n[${type}] ${title}\n${body.replace(/^>\s*/gm, '').trim()}\n\`\`\``;
            }
        );

        // Render with marked.js or fallback
        let html;
        if (typeof marked !== 'undefined') {
            html = marked.parse(processedContent);
        } else {
            console.warn('marked.js not available, using basic parser');
            html = basicMarkdownParse(processedContent);
        }

        // Add metadata badges if present
        if (metadata && config.customElements.badges) {
            const badges = renderMetadataBadges(metadata);
            if (badges) {
                html = badges + html;
            }
        }

        // Wrap in container
        html = `<div class="markdown-content">${html}</div>`;

        return html;
    };

    /**
     * Fallback basic markdown parser (when marked.js is not available)
     */
    const basicMarkdownParse = (markdown) => {
        let html = escapeHtml(markdown);

        // Headers
        html = html.replace(/^### (.+)$/gm, '<h3>$1</h3>');
        html = html.replace(/^## (.+)$/gm, '<h2>$1</h2>');
        html = html.replace(/^# (.+)$/gm, '<h1>$1</h1>');

        // Bold
        html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/__(.+?)__/g, '<strong>$1</strong>');

        // Italic
        html = html.replace(/\*(.+?)\*/g, '<em>$1</em>');
        html = html.replace(/_(.+?)_/g, '<em>$1</em>');

        // Code
        html = html.replace(/`(.+?)`/g, '<code>$1</code>');

        // Links
        html = html.replace(/\[(.+?)\]\((.+?)\)/g, '<a href="$2">$1</a>');

        // Paragraphs
        html = html.replace(/\n\n/g, '</p><p>');
        html = '<p>' + html + '</p>';

        return html;
    };

    /**
     * Render markdown into a specific element
     */
    const renderInto = (element, markdown, options = {}) => {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }

        if (!element) {
            console.error('Target element not found');
            return;
        }

        const html = render(markdown, options);
        element.innerHTML = html;

        // Render Mermaid diagrams after DOM update
        setTimeout(() => {
            renderMermaidDiagrams();
        }, 100);
    };

    /**
     * Initialize the renderer
     */
    const init = (options = {}) => {
        // Merge user options with defaults
        Object.assign(config, options);

        // Initialize marked.js
        const markedLoaded = initializeMarked();

        // Initialize Mermaid.js
        const mermaidLoaded = initializeMermaid();

        // Setup copy buttons
        setupCopyButtons();

        // Auto-render elements with data-markdown attribute
        document.querySelectorAll('[data-markdown]').forEach(element => {
            const markdown = element.getAttribute('data-markdown') || element.textContent;
            renderInto(element, markdown);
        });

        console.log('MarkdownRenderer initialized', {
            marked: markedLoaded,
            mermaid: mermaidLoaded,
            prism: typeof Prism !== 'undefined'
        });

        return {
            marked: markedLoaded,
            mermaid: mermaidLoaded,
            prism: typeof Prism !== 'undefined'
        };
    };

    /**
     * Update configuration
     */
    const configure = (options) => {
        Object.assign(config, options);
        if (typeof marked !== 'undefined') {
            initializeMarked();
        }
        if (typeof mermaid !== 'undefined') {
            initializeMermaid();
        }
    };

    // ========================================================================
    // Public API
    // ========================================================================

    return {
        init,
        render,
        renderInto,
        configure,
        renderMermaidDiagrams,
        parseMetadata,
        renderMetadataBadges,
        renderAdmonition,
        renderCodeBlock,
        escapeHtml,
        generateId,
        version: '1.0.0'
    };
})();

// ========================================================================
// Auto-initialization
// ========================================================================

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            MarkdownRenderer.init();
        });
    } else {
        MarkdownRenderer.init();
    }
}

// ========================================================================
// Module Exports
// ========================================================================

if (typeof module !== 'undefined' && module.exports) {
    module.exports = MarkdownRenderer;
}

if (typeof window !== 'undefined') {
    window.MarkdownRenderer = MarkdownRenderer;
}
