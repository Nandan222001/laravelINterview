// ============================================================================
// EXPORT-IMPORT MODULE
// Data portability with export/import capabilities and browser sync
// ============================================================================

const ExportImportManager = (() => {
    // ========================================================================
    // CONFIGURATION
    // ========================================================================
    
    const config = {
        version: '1.0.0',
        storagePrefix: 'interview_app_',
        syncKey: 'sync_session_',
        syncInterval: 2000,
        exportFilename: 'interview-data-export',
        pdfFilename: 'study-report',
        ankiFilename: 'flashcard-deck'
    };

    // ========================================================================
    // STATE MANAGEMENT
    // ========================================================================
    
    let state = {
        syncEnabled: false,
        syncSessionId: null,
        syncInterval: null,
        lastSyncTimestamp: null
    };

    // ========================================================================
    // DATA COLLECTION
    // ========================================================================

    /**
     * Collect all user data from various modules
     * @returns {Object} Complete user data object
     */
    function collectUserData() {
        const data = {
            version: config.version,
            exportDate: new Date().toISOString(),
            progress: collectProgressData(),
            bookmarks: collectBookmarksData(),
            notes: collectNotesData(),
            quizResults: collectQuizResults(),
            settings: collectSettings(),
            metadata: {
                browser: navigator.userAgent,
                platform: navigator.platform,
                language: navigator.language
            }
        };

        return data;
    }

    /**
     * Collect progress tracking data
     */
    function collectProgressData() {
        try {
            const stored = localStorage.getItem('interview_progress');
            if (stored) {
                return JSON.parse(stored);
            }
        } catch (error) {
            console.error('Error collecting progress data:', error);
        }
        return null;
    }

    /**
     * Collect bookmarks data
     */
    function collectBookmarksData() {
        try {
            const stored = localStorage.getItem('interview_bookmarks');
            if (stored) {
                const bookmarksArray = JSON.parse(stored);
                return {
                    bookmarks: bookmarksArray,
                    tags: JSON.parse(localStorage.getItem('bookmark_tags') || '[]')
                };
            }
        } catch (error) {
            console.error('Error collecting bookmarks data:', error);
        }
        return { bookmarks: [], tags: [] };
    }

    /**
     * Collect notes data
     */
    function collectNotesData() {
        const notes = {};
        try {
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key && key.startsWith('note_')) {
                    notes[key] = localStorage.getItem(key);
                }
            }
        } catch (error) {
            console.error('Error collecting notes data:', error);
        }
        return notes;
    }

    /**
     * Collect quiz results
     */
    function collectQuizResults() {
        const results = [];
        try {
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key && key.startsWith('quiz_result_')) {
                    const data = localStorage.getItem(key);
                    if (data) {
                        results.push(JSON.parse(data));
                    }
                }
            }
        } catch (error) {
            console.error('Error collecting quiz results:', error);
        }
        return results;
    }

    /**
     * Collect application settings
     */
    function collectSettings() {
        const settings = {};
        try {
            const themeKey = 'theme_preference';
            if (localStorage.getItem(themeKey)) {
                settings.theme = localStorage.getItem(themeKey);
            }

            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key && key.startsWith('setting_')) {
                    settings[key] = localStorage.getItem(key);
                }
            }
        } catch (error) {
            console.error('Error collecting settings:', error);
        }
        return settings;
    }

    // ========================================================================
    // EXPORT FUNCTIONS
    // ========================================================================

    /**
     * Export user data as JSON file
     * @param {boolean} downloadImmediately - Whether to trigger download immediately
     * @returns {string} JSON string of exported data
     */
    function exportUserData(downloadImmediately = true) {
        try {
            const userData = collectUserData();
            const jsonString = JSON.stringify(userData, null, 2);

            if (downloadImmediately) {
                downloadJSON(jsonString, `${config.exportFilename}-${getDateString()}.json`);
                
                dispatchEvent('dataExported', {
                    size: jsonString.length,
                    timestamp: userData.exportDate
                });
            }

            return jsonString;
        } catch (error) {
            console.error('Error exporting user data:', error);
            throw new Error('Failed to export user data: ' + error.message);
        }
    }

    /**
     * Export study report as PDF
     * @param {Object} options - Export options
     */
    function exportStudyReportPDF(options = {}) {
        if (typeof window.jspdf === 'undefined' || !window.jspdf.jsPDF) {
            throw new Error('jsPDF library not loaded. Please include jsPDF in your HTML.');
        }

        try {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            const userData = collectUserData();
            const progressData = userData.progress;
            const bookmarksData = userData.bookmarks;
            
            let yPos = 20;
            const lineHeight = 7;
            const pageHeight = doc.internal.pageSize.height;
            const pageWidth = doc.internal.pageSize.width;
            const margin = 20;
            const contentWidth = pageWidth - (margin * 2);

            // Helper function to check if new page is needed
            const checkNewPage = (spaceNeeded = 20) => {
                if (yPos > pageHeight - spaceNeeded) {
                    doc.addPage();
                    yPos = 20;
                    return true;
                }
                return false;
            };

            // Title
            doc.setFontSize(24);
            doc.setFont(undefined, 'bold');
            doc.text('Study Progress Report', margin, yPos);
            yPos += lineHeight * 2;

            // Date and metadata
            doc.setFontSize(11);
            doc.setFont(undefined, 'normal');
            doc.text(`Generated: ${new Date().toLocaleString()}`, margin, yPos);
            yPos += lineHeight;
            doc.text(`Report Period: All time`, margin, yPos);
            yPos += lineHeight * 2;

            // Overall Statistics
            checkNewPage(40);
            doc.setFontSize(16);
            doc.setFont(undefined, 'bold');
            doc.text('Overall Statistics', margin, yPos);
            yPos += lineHeight * 1.5;

            doc.setFontSize(11);
            doc.setFont(undefined, 'normal');

            if (progressData && progressData.questions) {
                const questions = Object.values(progressData.questions);
                const totalQuestions = questions.length;
                const completed = questions.filter(q => q.status === 'completed').length;
                const viewed = questions.filter(q => q.status === 'viewed').length;
                const incorrect = questions.filter(q => q.status === 'incorrect').length;
                const completionRate = totalQuestions > 0 ? Math.round((completed / totalQuestions) * 100) : 0;

                doc.text(`Total Questions Tracked: ${totalQuestions}`, margin + 5, yPos);
                yPos += lineHeight;
                doc.text(`Completed: ${completed} (${completionRate}%)`, margin + 5, yPos);
                yPos += lineHeight;
                doc.text(`Viewed: ${viewed}`, margin + 5, yPos);
                yPos += lineHeight;
                doc.text(`Needs Review: ${incorrect}`, margin + 5, yPos);
                yPos += lineHeight * 2;
            } else {
                doc.text('No progress data available', margin + 5, yPos);
                yPos += lineHeight * 2;
            }

            // Progress by Category
            if (progressData && progressData.questions) {
                checkNewPage(40);
                doc.setFontSize(16);
                doc.setFont(undefined, 'bold');
                doc.text('Progress by Category', margin, yPos);
                yPos += lineHeight * 1.5;

                doc.setFontSize(11);
                doc.setFont(undefined, 'normal');

                const categories = {};
                Object.values(progressData.questions).forEach(q => {
                    if (q.metadata && q.metadata.category) {
                        const cat = q.metadata.category;
                        if (!categories[cat]) {
                            categories[cat] = { total: 0, completed: 0, viewed: 0, incorrect: 0 };
                        }
                        categories[cat].total++;
                        if (q.status === 'completed') categories[cat].completed++;
                        if (q.status === 'viewed') categories[cat].viewed++;
                        if (q.status === 'incorrect') categories[cat].incorrect++;
                    }
                });

                Object.entries(categories).forEach(([category, stats]) => {
                    checkNewPage(30);
                    const rate = stats.total > 0 ? Math.round((stats.completed / stats.total) * 100) : 0;
                    doc.setFont(undefined, 'bold');
                    doc.text(`${category}:`, margin + 5, yPos);
                    yPos += lineHeight;
                    doc.setFont(undefined, 'normal');
                    doc.text(`  Completed: ${stats.completed}/${stats.total} (${rate}%)`, margin + 10, yPos);
                    yPos += lineHeight;
                });
                yPos += lineHeight;
            }

            // Bookmarked Questions
            if (bookmarksData && bookmarksData.bookmarks && bookmarksData.bookmarks.length > 0) {
                checkNewPage(40);
                doc.setFontSize(16);
                doc.setFont(undefined, 'bold');
                doc.text('Bookmarked Questions', margin, yPos);
                yPos += lineHeight * 1.5;

                doc.setFontSize(10);
                doc.setFont(undefined, 'normal');

                const bookmarks = Array.isArray(bookmarksData.bookmarks[0]) 
                    ? bookmarksData.bookmarks.map(b => b[1]) 
                    : bookmarksData.bookmarks;

                bookmarks.forEach((bookmark, index) => {
                    checkNewPage(40);

                    doc.setFont(undefined, 'bold');
                    doc.text(`${index + 1}. ${bookmark.questionText || 'Untitled'}`, margin + 5, yPos);
                    yPos += lineHeight;

                    doc.setFont(undefined, 'normal');
                    if (bookmark.difficulty) {
                        doc.text(`   Difficulty: ${bookmark.difficulty}`, margin + 10, yPos);
                        yPos += lineHeight;
                    }
                    if (bookmark.category) {
                        doc.text(`   Category: ${bookmark.category}`, margin + 10, yPos);
                        yPos += lineHeight;
                    }
                    if (bookmark.tags && bookmark.tags.length > 0) {
                        doc.text(`   Tags: ${bookmark.tags.join(', ')}`, margin + 10, yPos);
                        yPos += lineHeight;
                    }
                    if (bookmark.notes && bookmark.notes.trim().length > 0) {
                        const noteLines = doc.splitTextToSize(`   Notes: ${bookmark.notes}`, contentWidth - 10);
                        noteLines.forEach(line => {
                            checkNewPage();
                            doc.text(line, margin + 10, yPos);
                            yPos += lineHeight;
                        });
                    }
                    if (bookmark.dateAdded) {
                        doc.text(`   Added: ${new Date(bookmark.dateAdded).toLocaleDateString()}`, margin + 10, yPos);
                        yPos += lineHeight;
                    }
                    yPos += lineHeight / 2;
                });
            }

            // Quiz Results
            if (userData.quizResults && userData.quizResults.length > 0) {
                checkNewPage(40);
                doc.setFontSize(16);
                doc.setFont(undefined, 'bold');
                doc.text('Quiz Results Summary', margin, yPos);
                yPos += lineHeight * 1.5;

                doc.setFontSize(11);
                doc.setFont(undefined, 'normal');

                userData.quizResults.forEach((result, index) => {
                    checkNewPage(25);
                    doc.text(`Quiz ${index + 1}:`, margin + 5, yPos);
                    yPos += lineHeight;
                    if (result.score !== undefined) {
                        doc.text(`  Score: ${result.score}%`, margin + 10, yPos);
                        yPos += lineHeight;
                    }
                    if (result.date) {
                        doc.text(`  Date: ${new Date(result.date).toLocaleDateString()}`, margin + 10, yPos);
                        yPos += lineHeight;
                    }
                });
            }

            // Footer on last page
            doc.setFontSize(9);
            doc.setFont(undefined, 'italic');
            const footerText = 'Generated by Interview Preparation App';
            doc.text(footerText, margin, pageHeight - 10);

            // Save the PDF
            const filename = `${config.pdfFilename}-${getDateString()}.pdf`;
            doc.save(filename);

            dispatchEvent('pdfExported', { filename });

            return doc;
        } catch (error) {
            console.error('Error exporting PDF:', error);
            throw new Error('Failed to export PDF: ' + error.message);
        }
    }

    /**
     * Export flashcard deck as Anki-compatible format
     * @returns {string} Anki-compatible CSV content
     */
    function exportFlashcardDeck() {
        try {
            const userData = collectUserData();
            const bookmarks = userData.bookmarks.bookmarks || [];
            const progressQuestions = userData.progress?.questions || {};

            // Convert bookmarks to flashcards
            const flashcards = [];

            // Add bookmarked questions
            const bookmarkArray = Array.isArray(bookmarks[0]) 
                ? bookmarks.map(b => b[1]) 
                : bookmarks;

            bookmarkArray.forEach(bookmark => {
                flashcards.push({
                    front: bookmark.questionText,
                    back: bookmark.notes || 'No notes available',
                    tags: [...(bookmark.tags || []), bookmark.difficulty, bookmark.category].filter(Boolean),
                    difficulty: bookmark.difficulty || ''
                });
            });

            // Add questions that need review
            Object.values(progressQuestions).forEach(q => {
                if (q.status === 'incorrect') {
                    const existingCard = flashcards.find(card => 
                        card.front.includes(q.id) || card.front.includes(q.metadata?.questionText)
                    );
                    
                    if (!existingCard) {
                        flashcards.push({
                            front: q.metadata?.questionText || `Question ${q.id}`,
                            back: 'Review needed - marked as incorrect',
                            tags: [q.metadata?.category, q.metadata?.difficulty, 'review-needed'].filter(Boolean),
                            difficulty: q.metadata?.difficulty || ''
                        });
                    }
                }
            });

            // Generate Anki-compatible CSV
            // Format: Front, Back, Tags
            let csv = '';
            flashcards.forEach(card => {
                const front = escapeCSV(card.front);
                const back = escapeCSV(card.back);
                const tags = card.tags.join(' ');
                csv += `"${front}","${back}","${tags}"\n`;
            });

            // Add metadata header (as a comment)
            const header = `# Anki Flashcard Deck Export\n# Generated: ${new Date().toISOString()}\n# Total Cards: ${flashcards.length}\n`;
            const fullContent = header + csv;

            // Download the file
            downloadText(fullContent, `${config.ankiFilename}-${getDateString()}.txt`);

            dispatchEvent('ankiExported', { 
                cardCount: flashcards.length,
                filename: `${config.ankiFilename}-${getDateString()}.txt`
            });

            return fullContent;
        } catch (error) {
            console.error('Error exporting Anki deck:', error);
            throw new Error('Failed to export Anki deck: ' + error.message);
        }
    }

    // ========================================================================
    // IMPORT FUNCTIONS
    // ========================================================================

    /**
     * Import user data from JSON file
     * @param {File} file - File object to import
     * @param {string} mergeMode - 'replace' or 'merge'
     * @returns {Promise<Object>} Import result
     */
    function importUserData(file, mergeMode = 'replace') {
        return new Promise((resolve, reject) => {
            if (!file) {
                reject(new Error('No file provided'));
                return;
            }

            const reader = new FileReader();
            
            reader.onload = (e) => {
                try {
                    const jsonString = e.target.result;
                    const importedData = JSON.parse(jsonString);

                    // Validate data structure
                    const validation = validateImportData(importedData);
                    if (!validation.valid) {
                        reject(new Error('Invalid data format: ' + validation.error));
                        return;
                    }

                    // Perform import based on merge mode
                    const result = mergeMode === 'replace' 
                        ? replaceUserData(importedData)
                        : mergeUserData(importedData);

                    dispatchEvent('dataImported', {
                        mergeMode,
                        itemsImported: result.itemsImported,
                        timestamp: new Date().toISOString()
                    });

                    resolve(result);
                } catch (error) {
                    reject(new Error('Failed to parse import file: ' + error.message));
                }
            };

            reader.onerror = () => {
                reject(new Error('Failed to read file'));
            };

            reader.readAsText(file);
        });
    }

    /**
     * Validate imported data structure
     */
    function validateImportData(data) {
        if (!data || typeof data !== 'object') {
            return { valid: false, error: 'Data must be an object' };
        }

        if (!data.version) {
            return { valid: false, error: 'Missing version information' };
        }

        if (!data.exportDate) {
            return { valid: false, error: 'Missing export date' };
        }

        // Check for at least one data category
        const hasData = data.progress || data.bookmarks || data.notes || 
                       data.quizResults || data.settings;
        
        if (!hasData) {
            return { valid: false, error: 'No valid data categories found' };
        }

        return { valid: true };
    }

    /**
     * Replace all user data with imported data
     */
    function replaceUserData(importedData) {
        let itemsImported = 0;

        try {
            // Replace progress data
            if (importedData.progress) {
                localStorage.setItem('interview_progress', JSON.stringify(importedData.progress));
                itemsImported++;
            }

            // Replace bookmarks data
            if (importedData.bookmarks) {
                if (importedData.bookmarks.bookmarks) {
                    localStorage.setItem('interview_bookmarks', JSON.stringify(importedData.bookmarks.bookmarks));
                    itemsImported++;
                }
                if (importedData.bookmarks.tags) {
                    localStorage.setItem('bookmark_tags', JSON.stringify(importedData.bookmarks.tags));
                }
            }

            // Replace notes
            if (importedData.notes) {
                Object.entries(importedData.notes).forEach(([key, value]) => {
                    localStorage.setItem(key, value);
                    itemsImported++;
                });
            }

            // Replace quiz results
            if (importedData.quizResults) {
                importedData.quizResults.forEach((result, index) => {
                    const key = `quiz_result_${Date.now()}_${index}`;
                    localStorage.setItem(key, JSON.stringify(result));
                    itemsImported++;
                });
            }

            // Replace settings
            if (importedData.settings) {
                Object.entries(importedData.settings).forEach(([key, value]) => {
                    localStorage.setItem(key, value);
                    itemsImported++;
                });
            }

            // Trigger refresh events
            triggerDataRefresh();

            return {
                success: true,
                mode: 'replace',
                itemsImported,
                message: `Successfully imported ${itemsImported} items`
            };
        } catch (error) {
            throw new Error('Failed to replace data: ' + error.message);
        }
    }

    /**
     * Merge imported data with existing data
     */
    function mergeUserData(importedData) {
        let itemsImported = 0;

        try {
            // Merge progress data
            if (importedData.progress) {
                const existing = collectProgressData() || { version: '1.0.0', questions: {}, lastUpdated: null };
                
                // Merge questions, keeping the most recent update
                Object.entries(importedData.progress.questions || {}).forEach(([qId, qData]) => {
                    const existingQ = existing.questions[qId];
                    
                    if (!existingQ) {
                        existing.questions[qId] = qData;
                        itemsImported++;
                    } else {
                        // Keep the more recent version
                        const importDate = new Date(qData.completedAt || qData.viewedAt || 0);
                        const existingDate = new Date(existingQ.completedAt || existingQ.viewedAt || 0);
                        
                        if (importDate > existingDate) {
                            existing.questions[qId] = qData;
                            itemsImported++;
                        }
                    }
                });

                existing.lastUpdated = new Date().toISOString();
                localStorage.setItem('interview_progress', JSON.stringify(existing));
            }

            // Merge bookmarks data
            if (importedData.bookmarks && importedData.bookmarks.bookmarks) {
                const existingBookmarks = collectBookmarksData();
                const existingMap = new Map(existingBookmarks.bookmarks || []);
                
                const importedBookmarks = Array.isArray(importedData.bookmarks.bookmarks[0])
                    ? importedData.bookmarks.bookmarks
                    : importedData.bookmarks.bookmarks.map(b => [b.id, b]);

                importedBookmarks.forEach(([id, bookmark]) => {
                    const existing = existingMap.get(id);
                    
                    if (!existing) {
                        existingMap.set(id, bookmark);
                        itemsImported++;
                    } else {
                        // Keep the more recently modified bookmark
                        const importDate = new Date(bookmark.lastModified || bookmark.dateAdded || 0);
                        const existingDate = new Date(existing.lastModified || existing.dateAdded || 0);
                        
                        if (importDate > existingDate) {
                            existingMap.set(id, bookmark);
                            itemsImported++;
                        }
                    }
                });

                localStorage.setItem('interview_bookmarks', JSON.stringify(Array.from(existingMap.entries())));

                // Merge tags
                if (importedData.bookmarks.tags) {
                    const existingTags = new Set(existingBookmarks.tags || []);
                    importedData.bookmarks.tags.forEach(tag => existingTags.add(tag));
                    localStorage.setItem('bookmark_tags', JSON.stringify(Array.from(existingTags)));
                }
            }

            // Merge notes (keep newer versions)
            if (importedData.notes) {
                Object.entries(importedData.notes).forEach(([key, value]) => {
                    const existing = localStorage.getItem(key);
                    if (!existing || value.length > existing.length) {
                        localStorage.setItem(key, value);
                        itemsImported++;
                    }
                });
            }

            // Merge quiz results
            if (importedData.quizResults) {
                importedData.quizResults.forEach((result, index) => {
                    const key = `quiz_result_${Date.now()}_${index}`;
                    localStorage.setItem(key, JSON.stringify(result));
                    itemsImported++;
                });
            }

            // Merge settings (imported settings take precedence)
            if (importedData.settings) {
                Object.entries(importedData.settings).forEach(([key, value]) => {
                    localStorage.setItem(key, value);
                    itemsImported++;
                });
            }

            // Trigger refresh events
            triggerDataRefresh();

            return {
                success: true,
                mode: 'merge',
                itemsImported,
                message: `Successfully merged ${itemsImported} items`
            };
        } catch (error) {
            throw new Error('Failed to merge data: ' + error.message);
        }
    }

    // ========================================================================
    // BROWSER SYNC FUNCTIONS
    // ========================================================================

    /**
     * Enable browser sync across tabs
     * @param {string} sessionId - Optional session ID (generated if not provided)
     */
    function enableBrowserSync(sessionId = null) {
        if (state.syncEnabled) {
            console.warn('Browser sync already enabled');
            return;
        }

        state.syncSessionId = sessionId || generateSessionId();
        state.syncEnabled = true;

        // Store session ID
        localStorage.setItem(config.syncKey + 'id', state.syncSessionId);
        localStorage.setItem(config.syncKey + 'enabled', 'true');

        // Listen for storage changes from other tabs
        window.addEventListener('storage', handleStorageChange);

        // Set up periodic sync broadcast
        state.syncInterval = setInterval(broadcastSync, config.syncInterval);

        // Initial sync broadcast
        broadcastSync();

        dispatchEvent('syncEnabled', { sessionId: state.syncSessionId });

        console.log('Browser sync enabled with session ID:', state.syncSessionId);
    }

    /**
     * Disable browser sync
     */
    function disableBrowserSync() {
        if (!state.syncEnabled) {
            return;
        }

        state.syncEnabled = false;

        // Remove storage listener
        window.removeEventListener('storage', handleStorageChange);

        // Clear sync interval
        if (state.syncInterval) {
            clearInterval(state.syncInterval);
            state.syncInterval = null;
        }

        // Clear session data
        localStorage.removeItem(config.syncKey + 'id');
        localStorage.removeItem(config.syncKey + 'enabled');
        localStorage.removeItem(config.syncKey + 'data');

        dispatchEvent('syncDisabled', { sessionId: state.syncSessionId });

        state.syncSessionId = null;

        console.log('Browser sync disabled');
    }

    /**
     * Broadcast sync data to other tabs
     */
    function broadcastSync() {
        if (!state.syncEnabled) {
            return;
        }

        try {
            const syncData = {
                sessionId: state.syncSessionId,
                timestamp: Date.now(),
                data: collectUserData()
            };

            localStorage.setItem(config.syncKey + 'data', JSON.stringify(syncData));
            state.lastSyncTimestamp = syncData.timestamp;
        } catch (error) {
            console.error('Error broadcasting sync data:', error);
        }
    }

    /**
     * Handle storage changes from other tabs
     */
    function handleStorageChange(e) {
        if (!state.syncEnabled) {
            return;
        }

        // Check if this is a sync data change
        if (e.key === config.syncKey + 'data' && e.newValue) {
            try {
                const syncData = JSON.parse(e.newValue);

                // Ignore our own broadcasts
                if (syncData.sessionId === state.syncSessionId) {
                    return;
                }

                // Ignore older sync data
                if (state.lastSyncTimestamp && syncData.timestamp <= state.lastSyncTimestamp) {
                    return;
                }

                // Merge the synced data
                mergeUserData(syncData.data);
                state.lastSyncTimestamp = syncData.timestamp;

                dispatchEvent('syncReceived', {
                    sessionId: syncData.sessionId,
                    timestamp: syncData.timestamp
                });

                console.log('Received sync data from session:', syncData.sessionId);
            } catch (error) {
                console.error('Error handling sync data:', error);
            }
        }

        // Handle regular localStorage changes for real-time sync
        if (e.key && e.key.startsWith(config.storagePrefix)) {
            dispatchEvent('storageChanged', {
                key: e.key,
                oldValue: e.oldValue,
                newValue: e.newValue
            });
        }
    }

    /**
     * Get current sync status
     */
    function getSyncStatus() {
        return {
            enabled: state.syncEnabled,
            sessionId: state.syncSessionId,
            lastSyncTimestamp: state.lastSyncTimestamp,
            lastSyncDate: state.lastSyncTimestamp ? new Date(state.lastSyncTimestamp).toISOString() : null
        };
    }

    // ========================================================================
    // UTILITY FUNCTIONS
    // ========================================================================

    /**
     * Generate unique session ID
     */
    function generateSessionId() {
        return 'session_' + Date.now() + '_' + Math.random().toString(36).substring(2, 15);
    }

    /**
     * Get formatted date string for filenames
     */
    function getDateString() {
        const now = new Date();
        return now.toISOString().split('T')[0];
    }

    /**
     * Download JSON data as file
     */
    function downloadJSON(jsonString, filename) {
        const blob = new Blob([jsonString], { type: 'application/json' });
        downloadBlob(blob, filename);
    }

    /**
     * Download text data as file
     */
    function downloadText(text, filename) {
        const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
        downloadBlob(blob, filename);
    }

    /**
     * Download blob as file
     */
    function downloadBlob(blob, filename) {
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    /**
     * Escape CSV values
     */
    function escapeCSV(value) {
        if (value === null || value === undefined) {
            return '';
        }
        const stringValue = String(value);
        // Escape double quotes by doubling them
        return stringValue.replace(/"/g, '""');
    }

    /**
     * Trigger data refresh in other modules
     */
    function triggerDataRefresh() {
        // Dispatch events to notify other modules of data changes
        dispatchEvent('dataRefreshRequired');
        
        // Trigger specific module refresh events
        if (typeof window.ProgressTracker !== 'undefined' && window.ProgressTracker.updateAllVisualIndicators) {
            window.ProgressTracker.updateAllVisualIndicators();
        }
        
        if (typeof window.BookmarkSystem !== 'undefined' && window.BookmarkSystem.init) {
            // Reload bookmarks system data
            const event = new CustomEvent('bookmarks:reload');
            document.dispatchEvent(event);
        }
    }

    /**
     * Dispatch custom event
     */
    function dispatchEvent(eventName, detail = {}) {
        const event = new CustomEvent(`exportImport:${eventName}`, {
            detail: {
                ...detail,
                timestamp: new Date().toISOString()
            }
        });
        document.dispatchEvent(event);
    }

    // ========================================================================
    // UI HELPER FUNCTIONS
    // ========================================================================

    /**
     * Create export/import UI controls
     */
    function createUIControls() {
        return `
            <div class="export-import-controls">
                <h3>Data Management</h3>
                
                <div class="control-section">
                    <h4>Export Options</h4>
                    <div class="button-group">
                        <button id="export-all-data-btn" class="btn btn-primary">
                            📥 Export All Data (JSON)
                        </button>
                        <button id="export-pdf-report-btn" class="btn btn-primary">
                            📄 Export Study Report (PDF)
                        </button>
                        <button id="export-anki-deck-btn" class="btn btn-primary">
                            🗂️ Export Flashcards (Anki)
                        </button>
                    </div>
                </div>

                <div class="control-section">
                    <h4>Import Options</h4>
                    <div class="import-controls">
                        <label class="file-input-label">
                            <input type="file" id="import-data-file" accept=".json" style="display: none;">
                            📤 Choose File to Import
                        </label>
                        <select id="import-merge-mode" class="merge-mode-select">
                            <option value="replace">Replace All Data</option>
                            <option value="merge" selected>Merge with Existing</option>
                        </select>
                        <button id="import-data-btn" class="btn btn-secondary" disabled>
                            Import Data
                        </button>
                    </div>
                    <p class="help-text">
                        <strong>Replace:</strong> Overwrites all existing data<br>
                        <strong>Merge:</strong> Combines imported data with existing (keeps newer versions)
                    </p>
                </div>

                <div class="control-section">
                    <h4>Browser Sync</h4>
                    <div class="sync-controls">
                        <label class="sync-toggle">
                            <input type="checkbox" id="sync-enabled-toggle">
                            <span>Enable sync across browser tabs</span>
                        </label>
                        <div id="sync-status" class="sync-status">
                            <span class="status-indicator"></span>
                            <span class="status-text">Sync disabled</span>
                        </div>
                        <p class="help-text">
                            When enabled, changes are automatically synchronized across all open tabs in this browser.
                        </p>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Initialize UI event listeners
     */
    function initUIEventListeners() {
        // Export all data button
        const exportAllBtn = document.getElementById('export-all-data-btn');
        if (exportAllBtn) {
            exportAllBtn.addEventListener('click', () => {
                try {
                    exportUserData();
                    showNotification('Data exported successfully!', 'success');
                } catch (error) {
                    showNotification('Failed to export data: ' + error.message, 'error');
                }
            });
        }

        // Export PDF report button
        const exportPDFBtn = document.getElementById('export-pdf-report-btn');
        if (exportPDFBtn) {
            exportPDFBtn.addEventListener('click', () => {
                try {
                    exportStudyReportPDF();
                    showNotification('PDF report exported successfully!', 'success');
                } catch (error) {
                    showNotification('Failed to export PDF: ' + error.message, 'error');
                }
            });
        }

        // Export Anki deck button
        const exportAnkiBtn = document.getElementById('export-anki-deck-btn');
        if (exportAnkiBtn) {
            exportAnkiBtn.addEventListener('click', () => {
                try {
                    exportFlashcardDeck();
                    showNotification('Anki deck exported successfully!', 'success');
                } catch (error) {
                    showNotification('Failed to export Anki deck: ' + error.message, 'error');
                }
            });
        }

        // Import file selection
        const importFileInput = document.getElementById('import-data-file');
        const importBtn = document.getElementById('import-data-btn');
        
        if (importFileInput) {
            importFileInput.addEventListener('change', (e) => {
                if (importBtn) {
                    importBtn.disabled = !e.target.files || e.target.files.length === 0;
                }
            });
        }

        // Import data button
        if (importBtn) {
            importBtn.addEventListener('click', async () => {
                const file = importFileInput?.files[0];
                if (!file) {
                    showNotification('Please select a file to import', 'warning');
                    return;
                }

                const mergeMode = document.getElementById('import-merge-mode')?.value || 'merge';
                const confirmMessage = mergeMode === 'replace'
                    ? 'This will REPLACE all your existing data. Are you sure?'
                    : 'This will merge the imported data with your existing data. Continue?';

                if (!confirm(confirmMessage)) {
                    return;
                }

                try {
                    const result = await importUserData(file, mergeMode);
                    showNotification(result.message, 'success');
                    
                    // Clear file input
                    if (importFileInput) {
                        importFileInput.value = '';
                        importBtn.disabled = true;
                    }
                } catch (error) {
                    showNotification('Failed to import data: ' + error.message, 'error');
                }
            });
        }

        // Sync toggle
        const syncToggle = document.getElementById('sync-enabled-toggle');
        if (syncToggle) {
            // Initialize toggle state
            syncToggle.checked = localStorage.getItem(config.syncKey + 'enabled') === 'true';
            if (syncToggle.checked) {
                const sessionId = localStorage.getItem(config.syncKey + 'id');
                enableBrowserSync(sessionId);
            }

            syncToggle.addEventListener('change', (e) => {
                if (e.target.checked) {
                    enableBrowserSync();
                    updateSyncStatus(true);
                    showNotification('Browser sync enabled', 'success');
                } else {
                    disableBrowserSync();
                    updateSyncStatus(false);
                    showNotification('Browser sync disabled', 'info');
                }
            });
        }

        // Update sync status display
        updateSyncStatus(syncToggle?.checked || false);
    }

    /**
     * Update sync status display
     */
    function updateSyncStatus(enabled) {
        const statusEl = document.getElementById('sync-status');
        if (!statusEl) return;

        const indicator = statusEl.querySelector('.status-indicator');
        const text = statusEl.querySelector('.status-text');

        if (enabled) {
            indicator.classList.add('active');
            text.textContent = `Sync enabled (Session: ${state.syncSessionId?.substring(0, 12)}...)`;
        } else {
            indicator.classList.remove('active');
            text.textContent = 'Sync disabled';
        }
    }

    /**
     * Show notification message
     */
    function showNotification(message, type = 'info') {
        // Create notification element if it doesn't exist
        let notification = document.getElementById('export-import-notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'export-import-notification';
            document.body.appendChild(notification);
            injectNotificationStyles();
        }

        // Set message and type
        notification.textContent = message;
        notification.className = `show ${type}`;

        // Hide after 4 seconds
        setTimeout(() => {
            notification.classList.remove('show');
        }, 4000);
    }

    /**
     * Inject notification styles
     */
    function injectNotificationStyles() {
        if (document.getElementById('export-import-notification-styles')) return;

        const style = document.createElement('style');
        style.id = 'export-import-notification-styles';
        style.textContent = `
            #export-import-notification {
                position: fixed;
                bottom: 30px;
                right: 30px;
                padding: 15px 25px;
                border-radius: 8px;
                color: white;
                font-size: 0.95rem;
                font-weight: 500;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                z-index: 10002;
                opacity: 0;
                transform: translateY(20px);
                transition: all 0.3s ease;
                pointer-events: none;
                max-width: 400px;
            }
            
            #export-import-notification.show {
                opacity: 1;
                transform: translateY(0);
            }
            
            #export-import-notification.success {
                background: #48bb78;
            }
            
            #export-import-notification.error {
                background: #e53e3e;
            }
            
            #export-import-notification.warning {
                background: #ed8936;
            }
            
            #export-import-notification.info {
                background: #4299e1;
            }

            .export-import-controls {
                background: white;
                border-radius: 12px;
                padding: 25px;
                margin: 20px 0;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }

            .export-import-controls h3 {
                margin-top: 0;
                margin-bottom: 20px;
                color: #2d3748;
                font-size: 1.5rem;
            }

            .export-import-controls h4 {
                margin-top: 0;
                margin-bottom: 12px;
                color: #4a5568;
                font-size: 1.1rem;
            }

            .control-section {
                margin-bottom: 25px;
                padding-bottom: 25px;
                border-bottom: 1px solid #e2e8f0;
            }

            .control-section:last-child {
                border-bottom: none;
                margin-bottom: 0;
                padding-bottom: 0;
            }

            .button-group {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }

            .export-import-controls .btn {
                padding: 10px 20px;
                border: none;
                border-radius: 6px;
                font-size: 0.95rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s;
            }

            .export-import-controls .btn-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }

            .export-import-controls .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            }

            .export-import-controls .btn-secondary {
                background: #48bb78;
                color: white;
            }

            .export-import-controls .btn-secondary:hover {
                background: #38a169;
                transform: translateY(-2px);
            }

            .export-import-controls .btn:disabled {
                opacity: 0.5;
                cursor: not-allowed;
                transform: none !important;
            }

            .import-controls {
                display: flex;
                gap: 10px;
                align-items: center;
                flex-wrap: wrap;
            }

            .file-input-label {
                padding: 10px 20px;
                background: #edf2f7;
                color: #2d3748;
                border-radius: 6px;
                font-size: 0.95rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s;
            }

            .file-input-label:hover {
                background: #e2e8f0;
            }

            .merge-mode-select {
                padding: 10px 15px;
                border: 2px solid #e2e8f0;
                border-radius: 6px;
                font-size: 0.95rem;
                background: white;
                cursor: pointer;
            }

            .help-text {
                margin-top: 10px;
                font-size: 0.85rem;
                color: #718096;
                line-height: 1.6;
            }

            .sync-controls {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .sync-toggle {
                display: flex;
                align-items: center;
                gap: 10px;
                cursor: pointer;
            }

            .sync-toggle input[type="checkbox"] {
                width: 20px;
                height: 20px;
                cursor: pointer;
            }

            .sync-status {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 10px 15px;
                background: #f7fafc;
                border-radius: 6px;
                font-size: 0.9rem;
            }

            .status-indicator {
                width: 12px;
                height: 12px;
                border-radius: 50%;
                background: #cbd5e0;
            }

            .status-indicator.active {
                background: #48bb78;
                animation: pulse 2s infinite;
            }

            @keyframes pulse {
                0%, 100% {
                    opacity: 1;
                }
                50% {
                    opacity: 0.5;
                }
            }

            @media (max-width: 768px) {
                .button-group {
                    flex-direction: column;
                }

                .export-import-controls .btn {
                    width: 100%;
                }

                .import-controls {
                    flex-direction: column;
                    align-items: stretch;
                }

                .file-input-label,
                .merge-mode-select {
                    width: 100%;
                }
            }
        `;
        document.head.appendChild(style);
    }

    // ========================================================================
    // INITIALIZATION
    // ========================================================================

    /**
     * Initialize the module
     */
    function init() {
        // Check if sync was previously enabled
        const syncEnabled = localStorage.getItem(config.syncKey + 'enabled') === 'true';
        if (syncEnabled) {
            const sessionId = localStorage.getItem(config.syncKey + 'id');
            enableBrowserSync(sessionId);
        }

        // Initialize UI if controls exist in DOM
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initUIEventListeners);
        } else {
            initUIEventListeners();
        }

        console.log('ExportImportManager initialized');
    }

    // ========================================================================
    // PUBLIC API
    // ========================================================================

    return {
        init,
        
        // Export functions
        exportUserData,
        exportStudyReportPDF,
        exportFlashcardDeck,
        
        // Import functions
        importUserData,
        
        // Sync functions
        enableBrowserSync,
        disableBrowserSync,
        getSyncStatus,
        
        // Data collection
        collectUserData,
        
        // UI helpers
        createUIControls,
        showNotification,
        
        // Getters
        getConfig: () => ({ ...config }),
        getState: () => ({ ...state })
    };
})();

// ============================================================================
// AUTO-INITIALIZATION
// ============================================================================

if (typeof window !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => ExportImportManager.init());
    } else {
        ExportImportManager.init();
    }
}

// ============================================================================
// EXPORTS
// ============================================================================

// CommonJS
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ExportImportManager;
}

// Global
if (typeof window !== 'undefined') {
    window.ExportImportManager = ExportImportManager;
}
