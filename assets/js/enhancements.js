// ============================================================================
// PROGRESSIVE ENHANCEMENTS MODULE
// Print styles, PDF export, dark mode, offline support, and share functionality
// ============================================================================

const ProgressiveEnhancements = (() => {
    // ========================================================================
    // DARK MODE CONTROLLER
    // System preference detection and manual toggle
    // ========================================================================
    
    const DarkMode = (() => {
        const STORAGE_KEY = 'theme-preference';
        const THEME_ATTR = 'data-theme';
        let currentTheme = 'light';
        
        const init = () => {
            // Load saved preference or detect system preference
            const savedTheme = localStorage.getItem(STORAGE_KEY);
            
            if (savedTheme) {
                currentTheme = savedTheme;
            } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                currentTheme = 'dark';
            }
            
            applyTheme(currentTheme);
            setupToggleButton();
            setupSystemPreferenceListener();
        };
        
        const applyTheme = (theme) => {
            document.documentElement.setAttribute(THEME_ATTR, theme);
            currentTheme = theme;
            localStorage.setItem(STORAGE_KEY, theme);
            
            // Update toggle button if exists
            updateToggleButton();
            
            // Dispatch custom event
            document.dispatchEvent(new CustomEvent('theme:changed', {
                detail: { theme }
            }));
        };
        
        const toggle = () => {
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            applyTheme(newTheme);
        };
        
        const setupToggleButton = () => {
            let toggleBtn = document.getElementById('theme-toggle');
            
            if (!toggleBtn) {
                // Create toggle button if it doesn't exist
                toggleBtn = document.createElement('button');
                toggleBtn.id = 'theme-toggle';
                toggleBtn.className = 'theme-toggle';
                toggleBtn.setAttribute('aria-label', 'Toggle dark mode');
                toggleBtn.innerHTML = getToggleIcon();
                
                // Insert in header nav
                const headerNav = document.querySelector('.header-nav') || 
                                 document.querySelector('nav') || 
                                 document.querySelector('header');
                
                if (headerNav) {
                    headerNav.appendChild(toggleBtn);
                }
            }
            
            toggleBtn.addEventListener('click', toggle);
        };
        
        const updateToggleButton = () => {
            const toggleBtn = document.getElementById('theme-toggle');
            if (toggleBtn) {
                toggleBtn.innerHTML = getToggleIcon();
                toggleBtn.setAttribute('aria-label', 
                    currentTheme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'
                );
            }
        };
        
        const getToggleIcon = () => {
            if (currentTheme === 'dark') {
                return `<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"/>
                </svg>`;
            } else {
                return `<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                </svg>`;
            }
        };
        
        const setupSystemPreferenceListener = () => {
            if (window.matchMedia) {
                const darkModeQuery = window.matchMedia('(prefers-color-scheme: dark)');
                
                // Modern browsers
                if (darkModeQuery.addEventListener) {
                    darkModeQuery.addEventListener('change', (e) => {
                        // Only auto-switch if user hasn't manually set a preference
                        if (!localStorage.getItem(STORAGE_KEY)) {
                            applyTheme(e.matches ? 'dark' : 'light');
                        }
                    });
                } 
                // Older browsers
                else if (darkModeQuery.addListener) {
                    darkModeQuery.addListener((e) => {
                        if (!localStorage.getItem(STORAGE_KEY)) {
                            applyTheme(e.matches ? 'dark' : 'light');
                        }
                    });
                }
            }
        };
        
        const getTheme = () => currentTheme;
        
        return {
            init,
            toggle,
            getTheme,
            applyTheme
        };
    })();
    
    // ========================================================================
    // PRINT STYLES CONTROLLER
    // Clean question/answer printing
    // ========================================================================
    
    const PrintStyles = (() => {
        const init = () => {
            injectPrintStyles();
            setupPrintButton();
            setupPrintEventListeners();
        };
        
        const injectPrintStyles = () => {
            const styleId = 'print-enhancement-styles';
            
            if (document.getElementById(styleId)) {
                return; // Already injected
            }
            
            const printStyles = `
                @media print {
                    /* Hide non-printable elements */
                    header, 
                    .app-header,
                    nav, 
                    .header-nav,
                    aside, 
                    .app-sidebar,
                    footer, 
                    .app-footer,
                    .search-bar,
                    .search-container,
                    .filter-section,
                    .theme-toggle,
                    .mobile-menu-toggle,
                    .question-actions,
                    .code-copy-btn,
                    .print-button,
                    .share-button,
                    .export-pdf-button,
                    button:not(.no-print-hide) {
                        display: none !important;
                    }
                    
                    /* Reset layout for print */
                    body {
                        background: white !important;
                        color: black !important;
                        font-size: 12pt;
                        line-height: 1.5;
                    }
                    
                    .container,
                    .app-container {
                        display: block !important;
                        max-width: 100% !important;
                        margin: 0 !important;
                        padding: 0 !important;
                    }
                    
                    .main-wrapper,
                    .app-main {
                        display: block !important;
                        max-width: 100% !important;
                        margin: 0 !important;
                        padding: 0 !important;
                    }
                    
                    main,
                    .app-content {
                        padding: 0 !important;
                        background: white !important;
                        box-shadow: none !important;
                        border: none !important;
                    }
                    
                    /* Print header */
                    @page {
                        margin: 1.5cm;
                        size: A4;
                        
                        @top-center {
                            content: "Laravel Interview Question Bank";
                            font-size: 10pt;
                            color: #666;
                        }
                        
                        @bottom-right {
                            content: "Page " counter(page) " of " counter(pages);
                            font-size: 9pt;
                            color: #666;
                        }
                    }
                    
                    /* Headings */
                    h1, h2, h3, h4, h5, h6 {
                        page-break-after: avoid;
                        break-after: avoid;
                        color: black !important;
                    }
                    
                    h1 {
                        font-size: 18pt;
                        margin-top: 0;
                        border-bottom: 2px solid #000;
                        padding-bottom: 8pt;
                    }
                    
                    h2 {
                        font-size: 16pt;
                        margin-top: 12pt;
                        border-bottom: 1px solid #666;
                        padding-bottom: 6pt;
                    }
                    
                    h3 {
                        font-size: 14pt;
                        margin-top: 10pt;
                    }
                    
                    /* Accordion items */
                    .accordion-item {
                        page-break-inside: avoid;
                        break-inside: avoid;
                        border: 1px solid #000 !important;
                        border-radius: 4px;
                        margin-bottom: 12pt !important;
                        padding: 8pt !important;
                        background: white !important;
                        box-shadow: none !important;
                    }
                    
                    .accordion-header {
                        background: white !important;
                        border: none !important;
                        padding: 0 0 8pt 0 !important;
                        margin-bottom: 8pt !important;
                        border-bottom: 1px solid #ccc !important;
                    }
                    
                    .accordion-header h3,
                    .accordion-title {
                        font-size: 14pt !important;
                        color: black !important;
                        margin: 0 !important;
                    }
                    
                    .accordion-icon,
                    .accordion-toggle-icon,
                    .badge {
                        display: none !important;
                    }
                    
                    /* Always show accordion content when printing */
                    .accordion-content {
                        display: block !important;
                        max-height: none !important;
                        overflow: visible !important;
                        padding: 0 !important;
                    }
                    
                    .accordion-body,
                    .accordion-content-inner {
                        padding: 0 !important;
                    }
                    
                    /* Question items */
                    .question-item {
                        page-break-inside: avoid;
                        break-inside: avoid;
                        margin-bottom: 10pt !important;
                        padding: 8pt !important;
                        border: 1px solid #ddd !important;
                        border-radius: 3px;
                        background: white !important;
                    }
                    
                    .question-header {
                        margin-bottom: 6pt !important;
                    }
                    
                    .question-title-text {
                        font-size: 12pt !important;
                        font-weight: bold !important;
                        color: black !important;
                    }
                    
                    .question-tags {
                        margin-bottom: 6pt !important;
                    }
                    
                    .question-tag {
                        display: inline-block;
                        padding: 2pt 6pt;
                        border: 1px solid #999;
                        border-radius: 3px;
                        font-size: 8pt;
                        margin-right: 4pt;
                        background: #f5f5f5 !important;
                        color: black !important;
                    }
                    
                    .question-answer {
                        font-size: 11pt;
                        line-height: 1.6;
                        color: black !important;
                    }
                    
                    /* Code blocks */
                    pre {
                        page-break-inside: avoid;
                        break-inside: avoid;
                        border: 1px solid #000 !important;
                        background: #f8f8f8 !important;
                        padding: 8pt !important;
                        font-size: 9pt !important;
                        line-height: 1.4 !important;
                        overflow: visible !important;
                        white-space: pre-wrap !important;
                        word-wrap: break-word !important;
                        margin: 8pt 0 !important;
                    }
                    
                    code {
                        font-family: 'Courier New', monospace !important;
                        font-size: 9pt !important;
                        background: #f8f8f8 !important;
                        color: black !important;
                    }
                    
                    .code-block-wrapper {
                        page-break-inside: avoid;
                        break-inside: avoid;
                        margin: 8pt 0 !important;
                    }
                    
                    .code-block-header {
                        background: #e8e8e8 !important;
                        border-bottom: 1px solid #999 !important;
                        padding: 4pt 8pt !important;
                    }
                    
                    .code-language {
                        font-size: 8pt !important;
                        color: #666 !important;
                    }
                    
                    /* Lists */
                    ul, ol {
                        padding-left: 20pt;
                        margin: 6pt 0;
                    }
                    
                    li {
                        margin-bottom: 3pt;
                        page-break-inside: avoid;
                    }
                    
                    /* Links */
                    a {
                        color: black !important;
                        text-decoration: underline;
                    }
                    
                    a[href^="http"]:after {
                        content: " (" attr(href) ")";
                        font-size: 9pt;
                        color: #666;
                    }
                    
                    /* Tables */
                    table {
                        page-break-inside: avoid;
                        border-collapse: collapse;
                        width: 100%;
                        margin: 8pt 0;
                    }
                    
                    th, td {
                        border: 1px solid #000;
                        padding: 4pt 6pt;
                        text-align: left;
                    }
                    
                    th {
                        background: #e8e8e8 !important;
                        font-weight: bold;
                    }
                    
                    /* Stats and metadata */
                    .stats,
                    .stats-bar,
                    .stat-card,
                    .metadata-badges {
                        display: none !important;
                    }
                    
                    /* Images */
                    img {
                        max-width: 100% !important;
                        page-break-inside: avoid;
                    }
                    
                    /* Paragraphs */
                    p {
                        orphans: 3;
                        widows: 3;
                        margin-bottom: 6pt;
                    }
                    
                    /* Print-specific utilities */
                    .print-only {
                        display: block !important;
                    }
                    
                    .no-print {
                        display: none !important;
                    }
                    
                    /* Add printed date/time footer */
                    .print-footer {
                        display: block;
                        margin-top: 20pt;
                        padding-top: 10pt;
                        border-top: 1px solid #ccc;
                        font-size: 9pt;
                        color: #666;
                        text-align: center;
                    }
                }
            `;
            
            const styleElement = document.createElement('style');
            styleElement.id = styleId;
            styleElement.textContent = printStyles;
            document.head.appendChild(styleElement);
        };
        
        const setupPrintButton = () => {
            const printButtons = document.querySelectorAll('.print-button');
            
            printButtons.forEach(btn => {
                btn.addEventListener('click', handlePrint);
            });
            
            // Add global print shortcut (Ctrl+P / Cmd+P)
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                    e.preventDefault();
                    handlePrint();
                }
            });
        };
        
        const setupPrintEventListeners = () => {
            window.addEventListener('beforeprint', () => {
                // Expand all accordions before printing
                document.querySelectorAll('.accordion-item').forEach(item => {
                    item.classList.add('active');
                    const content = item.querySelector('.accordion-content');
                    if (content) {
                        content.style.maxHeight = 'none';
                    }
                });
                
                // Add print footer with timestamp
                addPrintFooter();
            });
            
            window.addEventListener('afterprint', () => {
                // Optionally collapse accordions after printing
                removePrintFooter();
            });
        };
        
        const addPrintFooter = () => {
            const existingFooter = document.querySelector('.print-footer');
            if (existingFooter) {
                existingFooter.remove();
            }
            
            const footer = document.createElement('div');
            footer.className = 'print-footer print-only';
            footer.style.display = 'none';
            
            const now = new Date();
            const dateStr = now.toLocaleDateString();
            const timeStr = now.toLocaleTimeString();
            
            footer.innerHTML = `
                <p>Printed from Laravel Interview Question Bank</p>
                <p>Date: ${dateStr} at ${timeStr}</p>
                <p>URL: ${window.location.href}</p>
            `;
            
            const mainContent = document.querySelector('main') || 
                               document.querySelector('.app-content') || 
                               document.body;
            mainContent.appendChild(footer);
        };
        
        const removePrintFooter = () => {
            const footer = document.querySelector('.print-footer');
            if (footer) {
                footer.remove();
            }
        };
        
        const handlePrint = () => {
            window.print();
        };
        
        const printSelectedQuestions = (questionIds) => {
            // Hide all questions first
            document.querySelectorAll('.question-item').forEach(q => {
                q.style.display = 'none';
            });
            
            // Show only selected questions
            questionIds.forEach(id => {
                const question = document.querySelector(`[data-question-id="${id}"]`);
                if (question) {
                    question.style.display = 'block';
                }
            });
            
            // Print
            window.print();
            
            // Restore all questions
            document.querySelectorAll('.question-item').forEach(q => {
                q.style.display = '';
            });
        };
        
        return {
            init,
            handlePrint,
            printSelectedQuestions
        };
    })();
    
    // ========================================================================
    // PDF EXPORT CONTROLLER
    // Export selected questions to PDF using jsPDF
    // ========================================================================
    
    const PDFExport = (() => {
        let jsPDFLoaded = false;
        
        const init = () => {
            loadJsPDF();
            setupExportButtons();
        };
        
        const loadJsPDF = () => {
            if (window.jspdf || window.jsPDF) {
                jsPDFLoaded = true;
                return Promise.resolve();
            }
            
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
                script.onload = () => {
                    jsPDFLoaded = true;
                    resolve();
                };
                script.onerror = reject;
                document.head.appendChild(script);
            });
        };
        
        const setupExportButtons = () => {
            document.addEventListener('click', (e) => {
                if (e.target.matches('.export-pdf-button') || 
                    e.target.closest('.export-pdf-button')) {
                    e.preventDefault();
                    handleExportPDF();
                }
            });
        };
        
        const handleExportPDF = async () => {
            try {
                await loadJsPDF();
                
                const selectedQuestions = getSelectedQuestions();
                
                if (selectedQuestions.length === 0) {
                    alert('Please select questions to export');
                    return;
                }
                
                await exportToPDF(selectedQuestions);
            } catch (error) {
                console.error('PDF export failed:', error);
                alert('Failed to export PDF. Please try again.');
            }
        };
        
        const getSelectedQuestions = () => {
            // Check for selected checkboxes
            const selected = [];
            document.querySelectorAll('.question-select-checkbox:checked').forEach(cb => {
                const questionItem = cb.closest('.question-item');
                if (questionItem) {
                    selected.push(questionItem);
                }
            });
            
            // If no questions selected, export all visible questions
            if (selected.length === 0) {
                return Array.from(document.querySelectorAll('.question-item:not([style*="display: none"])'));
            }
            
            return selected;
        };
        
        const exportToPDF = async (questions) => {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
            const margin = 20;
            const maxWidth = pageWidth - (margin * 2);
            let yPosition = margin;
            
            // Title
            doc.setFontSize(20);
            doc.setFont(undefined, 'bold');
            doc.text('Laravel Interview Questions', margin, yPosition);
            yPosition += 10;
            
            // Date
            doc.setFontSize(10);
            doc.setFont(undefined, 'normal');
            doc.setTextColor(100);
            const now = new Date();
            doc.text(`Generated: ${now.toLocaleDateString()} ${now.toLocaleTimeString()}`, margin, yPosition);
            yPosition += 15;
            doc.setTextColor(0);
            
            // Process each question
            for (let i = 0; i < questions.length; i++) {
                const question = questions[i];
                
                // Check if we need a new page
                if (yPosition > pageHeight - 40) {
                    doc.addPage();
                    yPosition = margin;
                }
                
                // Question number
                doc.setFontSize(12);
                doc.setFont(undefined, 'bold');
                doc.text(`Question ${i + 1}`, margin, yPosition);
                yPosition += 7;
                
                // Question title
                const titleElement = question.querySelector('.question-title-text');
                if (titleElement) {
                    const title = titleElement.textContent.trim();
                    const titleLines = doc.splitTextToSize(title, maxWidth);
                    doc.setFontSize(11);
                    doc.setFont(undefined, 'bold');
                    titleLines.forEach(line => {
                        if (yPosition > pageHeight - 20) {
                            doc.addPage();
                            yPosition = margin;
                        }
                        doc.text(line, margin, yPosition);
                        yPosition += 6;
                    });
                    yPosition += 3;
                }
                
                // Tags
                const tags = question.querySelectorAll('.question-tag');
                if (tags.length > 0) {
                    doc.setFontSize(8);
                    doc.setFont(undefined, 'normal');
                    doc.setTextColor(100);
                    const tagText = Array.from(tags).map(t => t.textContent.trim()).join(', ');
                    doc.text(`Tags: ${tagText}`, margin, yPosition);
                    yPosition += 6;
                    doc.setTextColor(0);
                }
                
                // Answer
                const answerElement = question.querySelector('.question-answer');
                if (answerElement) {
                    doc.setFontSize(10);
                    doc.setFont(undefined, 'normal');
                    
                    // Get text content, handling code blocks
                    const answerText = extractTextContent(answerElement);
                    const answerLines = doc.splitTextToSize(answerText, maxWidth);
                    
                    answerLines.forEach(line => {
                        if (yPosition > pageHeight - 20) {
                            doc.addPage();
                            yPosition = margin;
                        }
                        doc.text(line, margin, yPosition);
                        yPosition += 5;
                    });
                }
                
                // Add spacing between questions
                yPosition += 10;
                
                // Add separator line
                if (i < questions.length - 1) {
                    doc.setDrawColor(200);
                    doc.line(margin, yPosition, pageWidth - margin, yPosition);
                    yPosition += 10;
                }
            }
            
            // Save the PDF
            const fileName = `laravel-interview-questions-${Date.now()}.pdf`;
            doc.save(fileName);
            
            // Show success message
            showNotification('PDF exported successfully!', 'success');
        };
        
        const extractTextContent = (element) => {
            let text = '';
            
            element.childNodes.forEach(node => {
                if (node.nodeType === Node.TEXT_NODE) {
                    text += node.textContent;
                } else if (node.nodeType === Node.ELEMENT_NODE) {
                    if (node.tagName === 'CODE' || node.tagName === 'PRE') {
                        text += '\n[Code Block]\n' + node.textContent + '\n';
                    } else if (node.tagName === 'BR') {
                        text += '\n';
                    } else if (node.tagName === 'P') {
                        text += '\n' + extractTextContent(node) + '\n';
                    } else if (node.tagName === 'LI') {
                        text += '\n• ' + extractTextContent(node);
                    } else {
                        text += extractTextContent(node);
                    }
                }
            });
            
            return text;
        };
        
        const showNotification = (message, type = 'info') => {
            const notification = document.createElement('div');
            notification.className = `pdf-export-notification ${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 20px;
                background: ${type === 'success' ? '#10b981' : '#3b82f6'};
                color: white;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                z-index: 10000;
                animation: slideIn 0.3s ease;
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        };
        
        return {
            init,
            exportToPDF: handleExportPDF
        };
    })();
    
    // ========================================================================
    // OFFLINE SUPPORT CONTROLLER
    // Service worker for caching static assets and question data
    // ========================================================================
    
    const OfflineSupport = (() => {
        const init = () => {
            if ('serviceWorker' in navigator) {
                registerServiceWorker();
                setupOfflineIndicator();
                setupConnectionListeners();
            }
        };
        
        const registerServiceWorker = async () => {
            try {
                const registration = await navigator.serviceWorker.register('/service-worker.js', {
                    scope: '/'
                });
                
                console.log('Service Worker registered:', registration);
                
                // Check for updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            showUpdateNotification();
                        }
                    });
                });
            } catch (error) {
                console.error('Service Worker registration failed:', error);
            }
        };
        
        const setupOfflineIndicator = () => {
            const indicator = document.createElement('div');
            indicator.id = 'offline-indicator';
            indicator.className = 'offline-indicator';
            indicator.textContent = 'You are offline';
            indicator.style.cssText = `
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%) translateY(100px);
                padding: 12px 24px;
                background: #ef4444;
                color: white;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                z-index: 10000;
                transition: transform 0.3s ease;
                font-weight: 500;
            `;
            
            document.body.appendChild(indicator);
            
            updateOnlineStatus();
        };
        
        const setupConnectionListeners = () => {
            window.addEventListener('online', updateOnlineStatus);
            window.addEventListener('offline', updateOnlineStatus);
        };
        
        const updateOnlineStatus = () => {
            const indicator = document.getElementById('offline-indicator');
            
            if (!indicator) return;
            
            if (navigator.onLine) {
                indicator.style.transform = 'translateX(-50%) translateY(100px)';
            } else {
                indicator.style.transform = 'translateX(-50%) translateY(0)';
            }
        };
        
        const showUpdateNotification = () => {
            const notification = document.createElement('div');
            notification.className = 'update-notification';
            notification.innerHTML = `
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                    <span>A new version is available!</span>
                    <button onclick="location.reload()" style="
                        padding: 6px 12px;
                        background: white;
                        color: #3b82f6;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        font-weight: 500;
                    ">Refresh</button>
                </div>
            `;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 16px 20px;
                background: #3b82f6;
                color: white;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                z-index: 10000;
            `;
            
            document.body.appendChild(notification);
        };
        
        const cacheQuestionData = async (data) => {
            if ('caches' in window) {
                try {
                    const cache = await caches.open('question-data-v1');
                    const response = new Response(JSON.stringify(data));
                    await cache.put('/api/questions', response);
                    console.log('Question data cached');
                } catch (error) {
                    console.error('Failed to cache question data:', error);
                }
            }
        };
        
        const getCachedQuestionData = async () => {
            if ('caches' in window) {
                try {
                    const cache = await caches.open('question-data-v1');
                    const response = await cache.match('/api/questions');
                    if (response) {
                        return await response.json();
                    }
                } catch (error) {
                    console.error('Failed to get cached question data:', error);
                }
            }
            return null;
        };
        
        return {
            init,
            cacheQuestionData,
            getCachedQuestionData
        };
    })();
    
    // ========================================================================
    // SHARE FUNCTIONALITY CONTROLLER
    // Generate shareable links with question ID in URL hash
    // ========================================================================
    
    const ShareFunctionality = (() => {
        const init = () => {
            setupShareButtons();
            handleIncomingShare();
            setupNativeShare();
        };
        
        const setupShareButtons = () => {
            document.addEventListener('click', (e) => {
                if (e.target.matches('.share-button') || 
                    e.target.closest('.share-button')) {
                    e.preventDefault();
                    const button = e.target.closest('.share-button');
                    const questionId = button?.dataset?.questionId;
                    
                    if (questionId) {
                        shareQuestion(questionId);
                    } else {
                        shareCurrentPage();
                    }
                }
            });
        };
        
        const shareQuestion = async (questionId) => {
            const url = generateQuestionUrl(questionId);
            const title = getQuestionTitle(questionId);
            
            // Try native share API first (mobile)
            if (navigator.share) {
                try {
                    await navigator.share({
                        title: title || 'Laravel Interview Question',
                        text: 'Check out this interview question',
                        url: url
                    });
                    return;
                } catch (error) {
                    // User cancelled or error occurred, fall through to copy
                    if (error.name !== 'AbortError') {
                        console.error('Share failed:', error);
                    }
                }
            }
            
            // Fallback to copy to clipboard
            copyToClipboard(url);
            showShareNotification(url);
        };
        
        const shareCurrentPage = async () => {
            const url = window.location.href;
            const title = document.title;
            
            if (navigator.share) {
                try {
                    await navigator.share({
                        title: title,
                        url: url
                    });
                    return;
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error('Share failed:', error);
                    }
                }
            }
            
            copyToClipboard(url);
            showShareNotification(url);
        };
        
        const generateQuestionUrl = (questionId) => {
            const baseUrl = window.location.origin + window.location.pathname;
            return `${baseUrl}#question-${questionId}`;
        };
        
        const getQuestionTitle = (questionId) => {
            const questionElement = document.querySelector(`[data-question-id="${questionId}"]`);
            if (questionElement) {
                const titleElement = questionElement.querySelector('.question-title-text');
                return titleElement?.textContent?.trim() || null;
            }
            return null;
        };
        
        const copyToClipboard = async (text) => {
            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(text);
                } else {
                    // Fallback for older browsers
                    const textarea = document.createElement('textarea');
                    textarea.value = text;
                    textarea.style.position = 'fixed';
                    textarea.style.opacity = '0';
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                }
            } catch (error) {
                console.error('Failed to copy to clipboard:', error);
            }
        };
        
        const showShareNotification = (url) => {
            const notification = document.createElement('div');
            notification.className = 'share-notification';
            notification.innerHTML = `
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <strong>Link copied to clipboard!</strong>
                    <code style="
                        font-size: 12px;
                        padding: 4px 8px;
                        background: rgba(255,255,255,0.2);
                        border-radius: 4px;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                        max-width: 300px;
                    ">${url}</code>
                </div>
            `;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 16px 20px;
                background: #10b981;
                color: white;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                z-index: 10000;
                animation: slideIn 0.3s ease;
                max-width: 400px;
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        };
        
        const handleIncomingShare = () => {
            // Check URL hash for shared question
            const hash = window.location.hash;
            
            if (hash && hash.startsWith('#question-')) {
                const questionId = hash.replace('#question-', '');
                scrollToQuestion(questionId);
                highlightQuestion(questionId);
            }
            
            // Listen for hash changes
            window.addEventListener('hashchange', () => {
                const newHash = window.location.hash;
                if (newHash && newHash.startsWith('#question-')) {
                    const questionId = newHash.replace('#question-', '');
                    scrollToQuestion(questionId);
                    highlightQuestion(questionId);
                }
            });
        };
        
        const scrollToQuestion = (questionId) => {
            const questionElement = document.querySelector(`[data-question-id="${questionId}"]`);
            
            if (questionElement) {
                // Expand parent accordion if needed
                const accordionItem = questionElement.closest('.accordion-item');
                if (accordionItem && !accordionItem.classList.contains('active')) {
                    accordionItem.classList.add('active');
                    const content = accordionItem.querySelector('.accordion-content');
                    if (content) {
                        content.style.maxHeight = content.scrollHeight + 'px';
                    }
                }
                
                // Scroll to question with offset
                setTimeout(() => {
                    const offset = 100;
                    const elementPosition = questionElement.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - offset;
                    
                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }, 100);
            }
        };
        
        const highlightQuestion = (questionId) => {
            const questionElement = document.querySelector(`[data-question-id="${questionId}"]`);
            
            if (questionElement) {
                // Add highlight class
                questionElement.classList.add('shared-question-highlight');
                
                // Add inline styles for animation
                questionElement.style.animation = 'highlightPulse 2s ease';
                
                // Remove highlight after animation
                setTimeout(() => {
                    questionElement.classList.remove('shared-question-highlight');
                    questionElement.style.animation = '';
                }, 2000);
            }
        };
        
        const setupNativeShare = () => {
            // Add share button styling
            const shareStyles = `
                .share-button {
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                    padding: 6px 12px;
                    background: transparent;
                    border: 1px solid var(--border-color, #e5e7eb);
                    border-radius: 6px;
                    cursor: pointer;
                    transition: all 0.2s;
                    font-size: 14px;
                }
                
                .share-button:hover {
                    background: var(--bg-hover, #f3f4f6);
                    transform: translateY(-1px);
                }
                
                .shared-question-highlight {
                    position: relative;
                }
                
                @keyframes highlightPulse {
                    0%, 100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
                    50% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0.3); }
                }
                
                @keyframes slideIn {
                    from {
                        transform: translateX(400px);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                
                @keyframes slideOut {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(400px);
                        opacity: 0;
                    }
                }
            `;
            
            const styleElement = document.createElement('style');
            styleElement.textContent = shareStyles;
            document.head.appendChild(styleElement);
        };
        
        return {
            init,
            shareQuestion,
            shareCurrentPage,
            generateQuestionUrl
        };
    })();
    
    // ========================================================================
    // MAIN INITIALIZATION
    // ========================================================================
    
    const init = () => {
        // Initialize all enhancement modules
        DarkMode.init();
        PrintStyles.init();
        PDFExport.init();
        OfflineSupport.init();
        ShareFunctionality.init();
        
        console.log('Progressive Enhancements initialized');
    };
    
    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Public API
    return {
        init,
        DarkMode,
        PrintStyles,
        PDFExport,
        OfflineSupport,
        ShareFunctionality
    };
})();

// Make available globally
window.ProgressiveEnhancements = ProgressiveEnhancements;
