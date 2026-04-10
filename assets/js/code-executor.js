// ============================================================================
// CODE EXECUTOR MODULE
// Interactive code examples with "Try it" button, modal editor, validation,
// copy-to-clipboard, formatting, and local storage for snippets
// ============================================================================

const CodeExecutor = (() => {
    let config = {
        editorTheme: 'monokai',
        fontSize: 14,
        tabSize: 4,
        autoFormat: true,
        showLineNumbers: true,
        enableValidation: true,
        storageKey: 'code-executor-snippets',
        maxStoredSnippets: 50
    };

    let currentEditor = null;
    let currentCodeBlock = null;
    let aceEditor = null;
    let codeMirrorEditor = null;
    let editorLibrary = null; // 'ace' or 'codemirror'

    // Initialize the module
    const init = (options = {}) => {
        Object.assign(config, options);
        
        // Detect which editor library is available
        detectEditorLibrary();
        
        // Add "Try it" buttons to PHP code blocks
        addTryItButtons();
        
        // Create modal structure
        createModal();
        
        // Load saved snippets from localStorage
        loadStoredSnippets();
        
        console.log('CodeExecutor initialized with', editorLibrary || 'no editor library');
    };

    // Detect available editor library
    const detectEditorLibrary = () => {
        if (typeof ace !== 'undefined') {
            editorLibrary = 'ace';
        } else if (typeof CodeMirror !== 'undefined') {
            editorLibrary = 'codemirror';
        } else {
            console.warn('No code editor library detected. Basic textarea will be used.');
        }
    };

    // Add "Try it" buttons to all PHP code blocks
    const addTryItButtons = () => {
        const codeBlocks = document.querySelectorAll('pre code[class*="language-php"], pre code.php, pre code[class*="language-javascript"], pre code.js');
        
        codeBlocks.forEach((codeBlock) => {
            const pre = codeBlock.parentElement;
            if (!pre || pre.querySelector('.code-try-btn')) return;

            // Get language from class
            const language = getLanguageFromClass(codeBlock.className);
            
            // Create wrapper if not exists
            let wrapper = pre.closest('.code-block-wrapper');
            if (!wrapper) {
                wrapper = document.createElement('div');
                wrapper.className = 'code-block-wrapper';
                pre.parentNode.insertBefore(wrapper, pre);
                wrapper.appendChild(pre);
            }

            // Create or get header
            let header = wrapper.querySelector('.code-block-header');
            if (!header) {
                header = document.createElement('div');
                header.className = 'code-block-header';
                wrapper.insertBefore(header, pre);
                
                const langLabel = document.createElement('span');
                langLabel.className = 'code-language';
                langLabel.textContent = language.toUpperCase();
                header.appendChild(langLabel);
            }

            // Create actions container
            let actionsContainer = header.querySelector('.code-actions');
            if (!actionsContainer) {
                actionsContainer = document.createElement('div');
                actionsContainer.className = 'code-actions';
                header.appendChild(actionsContainer);
            }

            // Add Try It button
            const tryItBtn = createButton('Try it', 'code-try-btn', () => {
                openCodeEditor(codeBlock, language);
            });
            actionsContainer.insertBefore(tryItBtn, actionsContainer.firstChild);

            // Add Copy button with animation
            const copyBtn = createCopyButton(codeBlock);
            actionsContainer.appendChild(copyBtn);

            // Add Format button
            const formatBtn = createButton('Format', 'code-format-btn', () => {
                formatCode(codeBlock, language);
            });
            actionsContainer.appendChild(formatBtn);
        });
    };

    // Get language from class name
    const getLanguageFromClass = (className) => {
        if (!className) return 'text';
        
        const match = className.match(/language-(\w+)|^(\w+)$/);
        if (match) {
            return match[1] || match[2] || 'text';
        }
        
        return 'text';
    };

    // Create a button element
    const createButton = (text, className, onClick) => {
        const button = document.createElement('button');
        button.className = `code-action-btn ${className}`;
        button.innerHTML = `<span>${text}</span>`;
        button.addEventListener('click', onClick);
        return button;
    };

    // Create copy button with success animation
    const createCopyButton = (codeBlock) => {
        const button = document.createElement('button');
        button.className = 'code-action-btn code-copy-btn';
        button.innerHTML = `
            <svg class="copy-icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M5 2h8v8M2 5h8v8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <span>Copy</span>
        `;
        
        button.addEventListener('click', async () => {
            const code = codeBlock.textContent;
            
            try {
                await navigator.clipboard.writeText(code);
                
                // Success animation
                button.classList.add('copied');
                button.innerHTML = `
                    <svg class="check-icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M3 8l3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <span>Copied!</span>
                `;
                
                setTimeout(() => {
                    button.classList.remove('copied');
                    button.innerHTML = `
                        <svg class="copy-icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M5 2h8v8M2 5h8v8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                        <span>Copy</span>
                    `;
                }, 2000);
            } catch (err) {
                console.error('Failed to copy code:', err);
                showNotification('Failed to copy code', 'error');
            }
        });
        
        return button;
    };

    // Format code using js-beautify
    const formatCode = async (codeBlock, language) => {
        const code = codeBlock.textContent;
        let formatted = code;

        try {
            if (typeof js_beautify !== 'undefined') {
                if (language === 'javascript' || language === 'js') {
                    formatted = js_beautify(code, {
                        indent_size: config.tabSize,
                        space_in_empty_paren: true
                    });
                } else if (language === 'html') {
                    formatted = html_beautify(code, {
                        indent_size: config.tabSize
                    });
                } else if (language === 'css') {
                    formatted = css_beautify(code, {
                        indent_size: config.tabSize
                    });
                } else if (language === 'php') {
                    // Basic PHP formatting (js-beautify doesn't support PHP natively)
                    formatted = formatPHP(code);
                }

                codeBlock.textContent = formatted;
                showNotification('Code formatted successfully', 'success');
            } else {
                // Fallback to basic formatting
                formatted = basicFormat(code, language);
                codeBlock.textContent = formatted;
                showNotification('Code formatted (basic)', 'success');
            }
        } catch (err) {
            console.error('Error formatting code:', err);
            showNotification('Error formatting code', 'error');
        }
    };

    // Basic PHP formatter
    const formatPHP = (code) => {
        // Simple indentation based on braces
        let formatted = '';
        let indentLevel = 0;
        const lines = code.split('\n');
        
        lines.forEach(line => {
            const trimmed = line.trim();
            
            // Decrease indent for closing braces
            if (trimmed.startsWith('}')) {
                indentLevel = Math.max(0, indentLevel - 1);
            }
            
            // Add indentation
            formatted += '    '.repeat(indentLevel) + trimmed + '\n';
            
            // Increase indent for opening braces
            if (trimmed.endsWith('{')) {
                indentLevel++;
            }
        });
        
        return formatted.trim();
    };

    // Basic formatter fallback
    const basicFormat = (code, language) => {
        // Simple whitespace cleanup
        return code
            .split('\n')
            .map(line => line.trimRight())
            .join('\n')
            .trim();
    };

    // Create modal for code editor
    const createModal = () => {
        if (document.getElementById('code-executor-modal')) return;

        const modal = document.createElement('div');
        modal.id = 'code-executor-modal';
        modal.className = 'code-modal';
        modal.innerHTML = `
            <div class="code-modal-overlay"></div>
            <div class="code-modal-content">
                <div class="code-modal-header">
                    <h3 class="code-modal-title">Interactive Code Editor</h3>
                    <div class="code-modal-actions">
                        <button class="code-modal-btn code-modal-save" title="Save to localStorage">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M12 2H4a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2V4a2 2 0 00-2-2z" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M10 2v4H6V2M8 10v4" stroke="currentColor" stroke-width="1.5"/>
                            </svg>
                            Save
                        </button>
                        <button class="code-modal-btn code-modal-format" title="Format code">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M2 4h12M2 8h12M2 12h12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            Format
                        </button>
                        <button class="code-modal-btn code-modal-validate" title="Validate syntax">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M3 8l3 3 7-7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            Validate
                        </button>
                        <button class="code-modal-btn code-modal-close" title="Close">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M3 3l10 10M13 3L3 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            Close
                        </button>
                    </div>
                </div>
                <div class="code-modal-body">
                    <div class="code-editor-container">
                        <div id="code-editor"></div>
                    </div>
                    <div class="code-sidebar">
                        <div class="code-sidebar-section">
                            <h4>Validation</h4>
                            <div id="validation-output" class="validation-output">
                                <p class="validation-placeholder">Click "Validate" to check syntax</p>
                            </div>
                        </div>
                        <div class="code-sidebar-section">
                            <h4>Example Output</h4>
                            <div id="example-output" class="example-output">
                                <pre><code>Output will appear here...</code></pre>
                            </div>
                        </div>
                        <div class="code-sidebar-section">
                            <h4>Personal Notes</h4>
                            <textarea id="personal-notes" class="personal-notes" placeholder="Add your notes here..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Add styles
        addModalStyles();

        // Set up event listeners
        const closeBtn = modal.querySelector('.code-modal-close');
        const overlay = modal.querySelector('.code-modal-overlay');
        const saveBtn = modal.querySelector('.code-modal-save');
        const formatBtn = modal.querySelector('.code-modal-format');
        const validateBtn = modal.querySelector('.code-modal-validate');

        closeBtn.addEventListener('click', closeModal);
        overlay.addEventListener('click', closeModal);
        saveBtn.addEventListener('click', saveCurrentSnippet);
        formatBtn.addEventListener('click', formatCurrentCode);
        validateBtn.addEventListener('click', validateCurrentCode);

        // Keyboard shortcuts
        modal.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeModal();
            } else if (e.ctrlKey || e.metaKey) {
                if (e.key === 's') {
                    e.preventDefault();
                    saveCurrentSnippet();
                } else if (e.key === 'f') {
                    e.preventDefault();
                    formatCurrentCode();
                }
            }
        });
    };

    // Add modal styles
    const addModalStyles = () => {
        if (document.getElementById('code-executor-styles')) return;

        const styles = document.createElement('style');
        styles.id = 'code-executor-styles';
        styles.textContent = `
            .code-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 10000;
            }

            .code-modal.active {
                display: block;
            }

            .code-modal-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.7);
                backdrop-filter: blur(4px);
            }

            .code-modal-content {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 90vw;
                max-width: 1400px;
                height: 85vh;
                background: var(--bg-primary, #ffffff);
                border-radius: 12px;
                box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }

            .code-modal-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 1rem 1.5rem;
                background: var(--bg-secondary, #f9fafb);
                border-bottom: 1px solid var(--border-color, #e5e7eb);
            }

            .code-modal-title {
                margin: 0;
                font-size: 1.25rem;
                font-weight: 600;
                color: var(--text-primary, #111827);
            }

            .code-modal-actions {
                display: flex;
                gap: 0.5rem;
            }

            .code-modal-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.5rem 1rem;
                background: var(--bg-primary, #ffffff);
                color: var(--text-primary, #111827);
                border: 1px solid var(--border-color, #e5e7eb);
                border-radius: 6px;
                font-size: 0.875rem;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s;
            }

            .code-modal-btn:hover {
                background: var(--bg-hover, #f3f4f6);
                transform: translateY(-1px);
            }

            .code-modal-btn:active {
                transform: translateY(0);
            }

            .code-modal-btn svg {
                width: 16px;
                height: 16px;
            }

            .code-modal-btn.code-modal-save {
                background: var(--color-primary, #3b82f6);
                color: white;
                border-color: var(--color-primary, #3b82f6);
            }

            .code-modal-btn.code-modal-save:hover {
                background: var(--color-primary-dark, #2563eb);
            }

            .code-modal-body {
                flex: 1;
                display: flex;
                overflow: hidden;
            }

            .code-editor-container {
                flex: 2;
                display: flex;
                flex-direction: column;
                overflow: hidden;
                border-right: 1px solid var(--border-color, #e5e7eb);
            }

            #code-editor {
                flex: 1;
                font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace;
                font-size: 14px;
                line-height: 1.6;
            }

            #code-editor textarea {
                width: 100%;
                height: 100%;
                padding: 1rem;
                border: none;
                font-family: inherit;
                font-size: inherit;
                line-height: inherit;
                resize: none;
                outline: none;
                background: var(--syntax-bg, #f8fafc);
                color: var(--syntax-text, #1e293b);
            }

            .code-sidebar {
                flex: 1;
                display: flex;
                flex-direction: column;
                overflow-y: auto;
                background: var(--bg-secondary, #f9fafb);
            }

            .code-sidebar-section {
                padding: 1.5rem;
                border-bottom: 1px solid var(--border-color, #e5e7eb);
            }

            .code-sidebar-section:last-child {
                border-bottom: none;
                flex: 1;
            }

            .code-sidebar-section h4 {
                margin: 0 0 1rem 0;
                font-size: 0.875rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: var(--text-secondary, #6b7280);
            }

            .validation-output {
                padding: 0.75rem;
                background: var(--bg-primary, #ffffff);
                border: 1px solid var(--border-color, #e5e7eb);
                border-radius: 6px;
                font-size: 0.875rem;
                max-height: 200px;
                overflow-y: auto;
            }

            .validation-placeholder {
                color: var(--text-tertiary, #9ca3af);
                font-style: italic;
                margin: 0;
            }

            .validation-error {
                color: var(--color-error, #ef4444);
                margin: 0.5rem 0;
                padding: 0.5rem;
                background: rgba(239, 68, 68, 0.1);
                border-left: 3px solid var(--color-error, #ef4444);
                border-radius: 4px;
            }

            .validation-success {
                color: var(--color-success, #10b981);
                margin: 0;
                padding: 0.5rem;
                background: rgba(16, 185, 129, 0.1);
                border-left: 3px solid var(--color-success, #10b981);
                border-radius: 4px;
            }

            .example-output {
                background: var(--bg-primary, #ffffff);
                border: 1px solid var(--border-color, #e5e7eb);
                border-radius: 6px;
                overflow: hidden;
            }

            .example-output pre {
                margin: 0;
                padding: 0.75rem;
                overflow-x: auto;
                font-size: 0.875rem;
                background: transparent;
            }

            .example-output code {
                font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace;
                color: var(--syntax-text, #1e293b);
            }

            .personal-notes {
                width: 100%;
                min-height: 150px;
                padding: 0.75rem;
                border: 1px solid var(--border-color, #e5e7eb);
                border-radius: 6px;
                font-size: 0.875rem;
                font-family: inherit;
                line-height: 1.6;
                resize: vertical;
                background: var(--bg-primary, #ffffff);
                color: var(--text-primary, #111827);
            }

            .personal-notes:focus {
                outline: none;
                border-color: var(--color-primary, #3b82f6);
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            }

            .code-action-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.375rem;
                padding: 0.375rem 0.75rem;
                background: var(--bg-secondary, #f9fafb);
                color: var(--text-secondary, #6b7280);
                border: 1px solid var(--border-color, #e5e7eb);
                border-radius: 6px;
                font-size: 0.75rem;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s;
            }

            .code-action-btn:hover {
                background: var(--bg-hover, #f3f4f6);
                color: var(--text-primary, #111827);
                transform: translateY(-1px);
            }

            .code-action-btn:active {
                transform: translateY(0);
            }

            .code-action-btn.copied {
                background: var(--color-success, #10b981);
                color: white;
                border-color: var(--color-success, #10b981);
            }

            .code-actions {
                display: flex;
                gap: 0.5rem;
                align-items: center;
            }

            .notification {
                position: fixed;
                top: 2rem;
                right: 2rem;
                padding: 1rem 1.5rem;
                background: var(--bg-primary, #ffffff);
                border: 1px solid var(--border-color, #e5e7eb);
                border-radius: 8px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
                z-index: 10001;
                animation: slideInRight 0.3s ease;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                max-width: 400px;
            }

            .notification.success {
                border-left: 4px solid var(--color-success, #10b981);
            }

            .notification.error {
                border-left: 4px solid var(--color-error, #ef4444);
            }

            .notification.info {
                border-left: 4px solid var(--color-info, #06b6d4);
            }

            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }

            @media (max-width: 768px) {
                .code-modal-content {
                    width: 95vw;
                    height: 95vh;
                }

                .code-modal-body {
                    flex-direction: column;
                }

                .code-editor-container {
                    border-right: none;
                    border-bottom: 1px solid var(--border-color, #e5e7eb);
                }

                .code-sidebar {
                    max-height: 40vh;
                }

                .notification {
                    left: 1rem;
                    right: 1rem;
                    top: auto;
                    bottom: 1rem;
                }
            }
        `;

        document.head.appendChild(styles);
    };

    // Open code editor modal
    const openCodeEditor = (codeBlock, language) => {
        const modal = document.getElementById('code-executor-modal');
        if (!modal) return;

        currentCodeBlock = codeBlock;
        const code = codeBlock.textContent;

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Initialize appropriate editor
        setTimeout(() => {
            if (editorLibrary === 'ace') {
                initAceEditor(code, language);
            } else if (editorLibrary === 'codemirror') {
                initCodeMirrorEditor(code, language);
            } else {
                initBasicEditor(code, language);
            }

            // Load notes if exists
            loadNotesForSnippet(code);

            // Show example output
            showExampleOutput(language);
        }, 100);
    };

    // Initialize Ace Editor
    const initAceEditor = (code, language) => {
        const editorEl = document.getElementById('code-editor');
        editorEl.innerHTML = '';

        aceEditor = ace.edit('code-editor');
        aceEditor.setTheme(`ace/theme/${config.editorTheme}`);
        aceEditor.session.setMode(`ace/mode/${mapLanguageToMode(language)}`);
        aceEditor.setValue(code, -1);
        aceEditor.setOptions({
            fontSize: config.fontSize,
            showLineNumbers: config.showLineNumbers,
            showGutter: config.showLineNumbers,
            tabSize: config.tabSize,
            useSoftTabs: true,
            wrap: true
        });

        currentEditor = aceEditor;
    };

    // Initialize CodeMirror Editor
    const initCodeMirrorEditor = (code, language) => {
        const editorEl = document.getElementById('code-editor');
        editorEl.innerHTML = '';

        codeMirrorEditor = CodeMirror(editorEl, {
            value: code,
            mode: mapLanguageToMode(language),
            theme: config.editorTheme,
            lineNumbers: config.showLineNumbers,
            tabSize: config.tabSize,
            indentUnit: config.tabSize,
            lineWrapping: true,
            autoCloseBrackets: true,
            matchBrackets: true
        });

        currentEditor = codeMirrorEditor;
    };

    // Initialize basic textarea editor
    const initBasicEditor = (code, language) => {
        const editorEl = document.getElementById('code-editor');
        editorEl.innerHTML = `<textarea>${code}</textarea>`;
        currentEditor = editorEl.querySelector('textarea');
    };

    // Map language to editor mode
    const mapLanguageToMode = (language) => {
        const modeMap = {
            'php': 'php',
            'javascript': 'javascript',
            'js': 'javascript',
            'html': 'html',
            'css': 'css',
            'python': 'python',
            'sql': 'sql',
            'json': 'json',
            'xml': 'xml',
            'markdown': 'markdown',
            'yaml': 'yaml'
        };

        return modeMap[language.toLowerCase()] || 'text';
    };

    // Get current editor value
    const getEditorValue = () => {
        if (!currentEditor) return '';

        if (editorLibrary === 'ace') {
            return aceEditor.getValue();
        } else if (editorLibrary === 'codemirror') {
            return codeMirrorEditor.getValue();
        } else {
            return currentEditor.value;
        }
    };

    // Set editor value
    const setEditorValue = (value) => {
        if (!currentEditor) return;

        if (editorLibrary === 'ace') {
            aceEditor.setValue(value, -1);
        } else if (editorLibrary === 'codemirror') {
            codeMirrorEditor.setValue(value);
        } else {
            currentEditor.value = value;
        }
    };

    // Close modal
    const closeModal = () => {
        const modal = document.getElementById('code-executor-modal');
        if (!modal) return;

        modal.classList.remove('active');
        document.body.style.overflow = '';

        // Cleanup editors
        if (aceEditor) {
            aceEditor.destroy();
            aceEditor = null;
        }
        if (codeMirrorEditor) {
            codeMirrorEditor.toTextArea();
            codeMirrorEditor = null;
        }

        currentEditor = null;
        currentCodeBlock = null;
    };

    // Format current code in editor
    const formatCurrentCode = () => {
        const code = getEditorValue();
        const language = getLanguageFromClass(currentCodeBlock.className);
        
        let formatted = code;

        try {
            if (typeof js_beautify !== 'undefined') {
                if (language === 'javascript' || language === 'js') {
                    formatted = js_beautify(code, {
                        indent_size: config.tabSize,
                        space_in_empty_paren: true
                    });
                } else if (language === 'html') {
                    formatted = html_beautify(code, {
                        indent_size: config.tabSize
                    });
                } else if (language === 'css') {
                    formatted = css_beautify(code, {
                        indent_size: config.tabSize
                    });
                } else if (language === 'php') {
                    formatted = formatPHP(code);
                }
            } else {
                formatted = formatPHP(code);
            }

            setEditorValue(formatted);
            showNotification('Code formatted successfully', 'success');
        } catch (err) {
            console.error('Error formatting code:', err);
            showNotification('Error formatting code', 'error');
        }
    };

    // Validate current code
    const validateCurrentCode = () => {
        const code = getEditorValue();
        const language = getLanguageFromClass(currentCodeBlock.className);
        const output = document.getElementById('validation-output');

        if (!output) return;

        if (language === 'php') {
            validatePHP(code, output);
        } else if (language === 'javascript' || language === 'js') {
            validateJavaScript(code, output);
        } else if (language === 'json') {
            validateJSON(code, output);
        } else {
            output.innerHTML = '<p class="validation-placeholder">Validation not available for this language</p>';
        }
    };

    // Validate PHP syntax (client-side basic validation)
    const validatePHP = (code, outputEl) => {
        const errors = [];

        // Check for matching braces
        const openBraces = (code.match(/{/g) || []).length;
        const closeBraces = (code.match(/}/g) || []).length;
        if (openBraces !== closeBraces) {
            errors.push('Mismatched curly braces');
        }

        // Check for matching parentheses
        const openParens = (code.match(/\(/g) || []).length;
        const closeParens = (code.match(/\)/g) || []).length;
        if (openParens !== closeParens) {
            errors.push('Mismatched parentheses');
        }

        // Check for PHP opening tag
        if (!code.includes('<?php') && !code.includes('<?=')) {
            errors.push('Missing PHP opening tag (<?php)');
        }

        // Check for common syntax errors
        const lines = code.split('\n');
        lines.forEach((line, index) => {
            const trimmed = line.trim();
            
            // Check for statements that should end with semicolon
            if (trimmed && 
                !trimmed.startsWith('//') && 
                !trimmed.startsWith('/*') && 
                !trimmed.startsWith('*') &&
                !trimmed.endsWith('{') && 
                !trimmed.endsWith('}') && 
                !trimmed.endsWith(';') &&
                !trimmed.endsWith(':') &&
                !trimmed.startsWith('<?php') &&
                !trimmed.startsWith('namespace') &&
                !trimmed.startsWith('use') &&
                trimmed !== '<?php'
            ) {
                if (/^(echo|return|throw|include|require|print|var_dump|\$\w+\s*=)/.test(trimmed)) {
                    errors.push(`Line ${index + 1}: Missing semicolon`);
                }
            }
        });

        // Display results
        if (errors.length === 0) {
            outputEl.innerHTML = '<p class="validation-success">✓ No syntax errors found (basic validation)</p>';
            showNotification('Validation passed', 'success');
        } else {
            const errorList = errors.map(err => `<p class="validation-error">✗ ${err}</p>`).join('');
            outputEl.innerHTML = errorList;
            showNotification(`Found ${errors.length} issue(s)`, 'error');
        }
    };

    // Validate JavaScript syntax
    const validateJavaScript = (code, outputEl) => {
        try {
            new Function(code);
            outputEl.innerHTML = '<p class="validation-success">✓ JavaScript syntax is valid</p>';
            showNotification('Validation passed', 'success');
        } catch (err) {
            outputEl.innerHTML = `<p class="validation-error">✗ ${err.message}</p>`;
            showNotification('Syntax error found', 'error');
        }
    };

    // Validate JSON syntax
    const validateJSON = (code, outputEl) => {
        try {
            JSON.parse(code);
            outputEl.innerHTML = '<p class="validation-success">✓ Valid JSON</p>';
            showNotification('Validation passed', 'success');
        } catch (err) {
            outputEl.innerHTML = `<p class="validation-error">✗ ${err.message}</p>`;
            showNotification('Invalid JSON', 'error');
        }
    };

    // Show example output based on language
    const showExampleOutput = (language) => {
        const outputEl = document.getElementById('example-output');
        if (!outputEl) return;

        const examples = {
            php: '<?php\n// Example output:\necho "Hello, World!";\n// Output: Hello, World!',
            javascript: '// Example output:\nconsole.log("Hello, World!");\n// Console: Hello, World!',
            html: '<!-- Example output will be rendered HTML -->',
            css: '/* Apply styles to preview changes */',
            python: '# Example output:\nprint("Hello, World!")\n# Output: Hello, World!'
        };

        const example = examples[language] || 'Execute your code to see output...';
        outputEl.innerHTML = `<pre><code>${example}</code></pre>`;
    };

    // Save current snippet to localStorage
    const saveCurrentSnippet = () => {
        const code = getEditorValue();
        const notes = document.getElementById('personal-notes').value;
        const language = getLanguageFromClass(currentCodeBlock.className);

        const snippet = {
            id: Date.now(),
            code,
            notes,
            language,
            timestamp: new Date().toISOString(),
            title: code.substring(0, 50) + (code.length > 50 ? '...' : '')
        };

        const snippets = getStoredSnippets();
        snippets.unshift(snippet);

        // Keep only max number of snippets
        const trimmed = snippets.slice(0, config.maxStoredSnippets);
        
        localStorage.setItem(config.storageKey, JSON.stringify(trimmed));
        showNotification('Snippet saved successfully', 'success');
    };

    // Get stored snippets from localStorage
    const getStoredSnippets = () => {
        try {
            const stored = localStorage.getItem(config.storageKey);
            return stored ? JSON.parse(stored) : [];
        } catch (err) {
            console.error('Error reading stored snippets:', err);
            return [];
        }
    };

    // Load stored snippets
    const loadStoredSnippets = () => {
        const snippets = getStoredSnippets();
        console.log(`Loaded ${snippets.length} stored code snippets`);
        return snippets;
    };

    // Load notes for a snippet
    const loadNotesForSnippet = (code) => {
        const snippets = getStoredSnippets();
        const snippet = snippets.find(s => s.code === code);
        
        const notesEl = document.getElementById('personal-notes');
        if (notesEl && snippet) {
            notesEl.value = snippet.notes || '';
        }
    };

    // Show notification
    const showNotification = (message, type = 'info') => {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        
        const icons = {
            success: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M5 10l3 3 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
            error: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M5 5l10 10M15 5L5 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
            info: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="2"/><path d="M10 10v4M10 6v1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
        };

        notification.innerHTML = `
            <div style="color: var(--text-primary);">${icons[type] || icons.info}</div>
            <div style="color: var(--text-primary);">${message}</div>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease forwards';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    };

    // Public API
    return {
        init,
        openCodeEditor,
        getStoredSnippets,
        saveSnippet: saveCurrentSnippet,
        showNotification
    };
})();

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => CodeExecutor.init());
} else {
    CodeExecutor.init();
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CodeExecutor;
}

// Make available globally
window.CodeExecutor = CodeExecutor;
