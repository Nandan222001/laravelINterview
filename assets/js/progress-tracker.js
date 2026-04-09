const ProgressTracker = (() => {
    const STORAGE_KEY = 'interview_progress';
    const VERSION = '1.0.0';
    
    let progressData = {
        version: VERSION,
        questions: {},
        lastUpdated: null
    };

    const QuestionStatus = {
        VIEWED: 'viewed',
        COMPLETED: 'completed',
        INCORRECT: 'incorrect'
    };

    const init = () => {
        loadProgress();
        attachEventListeners();
        updateAllVisualIndicators();
    };

    const loadProgress = () => {
        try {
            const stored = localStorage.getItem(STORAGE_KEY);
            if (stored) {
                const parsed = JSON.parse(stored);
                if (parsed.version === VERSION) {
                    progressData = parsed;
                } else {
                    migrateProgress(parsed);
                }
            }
        } catch (error) {
            console.error('Error loading progress:', error);
            progressData = {
                version: VERSION,
                questions: {},
                lastUpdated: null
            };
        }
    };

    const saveProgress = () => {
        try {
            progressData.lastUpdated = new Date().toISOString();
            localStorage.setItem(STORAGE_KEY, JSON.stringify(progressData));
            dispatchProgressEvent('saved');
        } catch (error) {
            console.error('Error saving progress:', error);
        }
    };

    const migrateProgress = (oldData) => {
        progressData = {
            version: VERSION,
            questions: oldData.questions || {},
            lastUpdated: oldData.lastUpdated || null
        };
        saveProgress();
    };

    const markViewed = (questionId, metadata = {}) => {
        if (!progressData.questions[questionId]) {
            progressData.questions[questionId] = {
                id: questionId,
                status: QuestionStatus.VIEWED,
                viewedAt: new Date().toISOString(),
                viewCount: 1,
                metadata: metadata
            };
        } else {
            progressData.questions[questionId].viewCount = 
                (progressData.questions[questionId].viewCount || 0) + 1;
            progressData.questions[questionId].lastViewedAt = new Date().toISOString();
        }
        saveProgress();
        updateQuestionVisualIndicator(questionId);
    };

    const markComplete = (questionId, metadata = {}) => {
        if (!progressData.questions[questionId]) {
            progressData.questions[questionId] = {
                id: questionId,
                status: QuestionStatus.COMPLETED,
                viewedAt: new Date().toISOString(),
                completedAt: new Date().toISOString(),
                viewCount: 1,
                metadata: metadata
            };
        } else {
            progressData.questions[questionId].status = QuestionStatus.COMPLETED;
            progressData.questions[questionId].completedAt = new Date().toISOString();
            if (metadata) {
                progressData.questions[questionId].metadata = {
                    ...progressData.questions[questionId].metadata,
                    ...metadata
                };
            }
        }
        saveProgress();
        updateQuestionVisualIndicator(questionId);
        dispatchProgressEvent('questionCompleted', { questionId });
    };

    const markIncorrect = (questionId, metadata = {}) => {
        if (!progressData.questions[questionId]) {
            progressData.questions[questionId] = {
                id: questionId,
                status: QuestionStatus.INCORRECT,
                viewedAt: new Date().toISOString(),
                incorrectAt: new Date().toISOString(),
                viewCount: 1,
                incorrectCount: 1,
                metadata: metadata
            };
        } else {
            progressData.questions[questionId].status = QuestionStatus.INCORRECT;
            progressData.questions[questionId].incorrectAt = new Date().toISOString();
            progressData.questions[questionId].incorrectCount = 
                (progressData.questions[questionId].incorrectCount || 0) + 1;
            if (metadata) {
                progressData.questions[questionId].metadata = {
                    ...progressData.questions[questionId].metadata,
                    ...metadata
                };
            }
        }
        saveProgress();
        updateQuestionVisualIndicator(questionId);
        dispatchProgressEvent('questionIncorrect', { questionId });
    };

    const getProgress = (category = null, difficulty = null) => {
        const questions = Object.values(progressData.questions);
        
        let filtered = questions;
        if (category) {
            filtered = filtered.filter(q => 
                q.metadata && q.metadata.category === category
            );
        }
        if (difficulty) {
            filtered = filtered.filter(q => 
                q.metadata && q.metadata.difficulty === difficulty
            );
        }

        const total = filtered.length;
        const viewed = filtered.filter(q => q.status === QuestionStatus.VIEWED).length;
        const completed = filtered.filter(q => q.status === QuestionStatus.COMPLETED).length;
        const incorrect = filtered.filter(q => q.status === QuestionStatus.INCORRECT).length;
        
        return {
            total,
            viewed,
            completed,
            incorrect,
            completionPercentage: total > 0 ? Math.round((completed / total) * 100) : 0,
            viewedPercentage: total > 0 ? Math.round((viewed / total) * 100) : 0,
            incorrectPercentage: total > 0 ? Math.round((incorrect / total) * 100) : 0
        };
    };

    const getStatistics = () => {
        const allQuestions = Object.values(progressData.questions);
        const categories = {};
        const difficulties = {};

        allQuestions.forEach(q => {
            if (q.metadata) {
                if (q.metadata.category) {
                    if (!categories[q.metadata.category]) {
                        categories[q.metadata.category] = {
                            total: 0,
                            viewed: 0,
                            completed: 0,
                            incorrect: 0
                        };
                    }
                    categories[q.metadata.category].total++;
                    if (q.status === QuestionStatus.VIEWED) categories[q.metadata.category].viewed++;
                    if (q.status === QuestionStatus.COMPLETED) categories[q.metadata.category].completed++;
                    if (q.status === QuestionStatus.INCORRECT) categories[q.metadata.category].incorrect++;
                }

                if (q.metadata.difficulty) {
                    if (!difficulties[q.metadata.difficulty]) {
                        difficulties[q.metadata.difficulty] = {
                            total: 0,
                            viewed: 0,
                            completed: 0,
                            incorrect: 0
                        };
                    }
                    difficulties[q.metadata.difficulty].total++;
                    if (q.status === QuestionStatus.VIEWED) difficulties[q.metadata.difficulty].viewed++;
                    if (q.status === QuestionStatus.COMPLETED) difficulties[q.metadata.difficulty].completed++;
                    if (q.status === QuestionStatus.INCORRECT) difficulties[q.metadata.difficulty].incorrect++;
                }
            }
        });

        Object.keys(categories).forEach(cat => {
            const stats = categories[cat];
            stats.completionPercentage = Math.round((stats.completed / stats.total) * 100);
        });

        Object.keys(difficulties).forEach(diff => {
            const stats = difficulties[diff];
            stats.completionPercentage = Math.round((stats.completed / stats.total) * 100);
        });

        const totalQuestions = allQuestions.length;
        const completedCount = allQuestions.filter(q => q.status === QuestionStatus.COMPLETED).length;
        const viewedCount = allQuestions.filter(q => q.status === QuestionStatus.VIEWED).length;
        const incorrectCount = allQuestions.filter(q => q.status === QuestionStatus.INCORRECT).length;

        return {
            overall: {
                total: totalQuestions,
                viewed: viewedCount,
                completed: completedCount,
                incorrect: incorrectCount,
                completionPercentage: totalQuestions > 0 ? Math.round((completedCount / totalQuestions) * 100) : 0
            },
            byCategory: categories,
            byDifficulty: difficulties,
            lastUpdated: progressData.lastUpdated,
            version: progressData.version
        };
    };

    const resetProgress = () => {
        if (confirm('Are you sure you want to reset all progress? This action cannot be undone.')) {
            progressData = {
                version: VERSION,
                questions: {},
                lastUpdated: new Date().toISOString()
            };
            saveProgress();
            updateAllVisualIndicators();
            dispatchProgressEvent('reset');
            return true;
        }
        return false;
    };

    const exportProgress = () => {
        const dataStr = JSON.stringify(progressData, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(dataBlob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `interview-progress-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
        dispatchProgressEvent('exported');
    };

    const importProgress = (file) => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const imported = JSON.parse(e.target.result);
                    if (imported.version && imported.questions) {
                        if (confirm('Import progress? This will overwrite your current progress.')) {
                            progressData = imported;
                            saveProgress();
                            updateAllVisualIndicators();
                            dispatchProgressEvent('imported');
                            resolve(true);
                        } else {
                            resolve(false);
                        }
                    } else {
                        reject(new Error('Invalid progress file format'));
                    }
                } catch (error) {
                    reject(error);
                }
            };
            reader.onerror = () => reject(new Error('Failed to read file'));
            reader.readAsText(file);
        });
    };

    const mergeProgress = (file) => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const imported = JSON.parse(e.target.result);
                    if (imported.version && imported.questions) {
                        Object.keys(imported.questions).forEach(questionId => {
                            const importedQ = imported.questions[questionId];
                            const existingQ = progressData.questions[questionId];
                            
                            if (!existingQ || new Date(importedQ.completedAt || importedQ.viewedAt) > 
                                new Date(existingQ.completedAt || existingQ.viewedAt)) {
                                progressData.questions[questionId] = importedQ;
                            }
                        });
                        saveProgress();
                        updateAllVisualIndicators();
                        dispatchProgressEvent('merged');
                        resolve(true);
                    } else {
                        reject(new Error('Invalid progress file format'));
                    }
                } catch (error) {
                    reject(error);
                }
            };
            reader.onerror = () => reject(new Error('Failed to read file'));
            reader.readAsText(file);
        });
    };

    const updateQuestionVisualIndicator = (questionId) => {
        const questionElement = document.querySelector(`[data-question-id="${questionId}"]`);
        if (!questionElement) return;

        const question = progressData.questions[questionId];
        removeAllStatusClasses(questionElement);

        if (question) {
            questionElement.classList.add(`status-${question.status}`);
            
            let indicatorContainer = questionElement.querySelector('.progress-indicator');
            if (!indicatorContainer) {
                indicatorContainer = document.createElement('div');
                indicatorContainer.className = 'progress-indicator';
                const header = questionElement.querySelector('.question-header') || 
                              questionElement.querySelector('.question') ||
                              questionElement;
                header.appendChild(indicatorContainer);
            }

            indicatorContainer.innerHTML = getStatusIcon(question.status);
        }
    };

    const updateAllVisualIndicators = () => {
        Object.keys(progressData.questions).forEach(questionId => {
            updateQuestionVisualIndicator(questionId);
        });
        updateProgressBars();
    };

    const removeAllStatusClasses = (element) => {
        element.classList.remove('status-viewed', 'status-completed', 'status-incorrect');
    };

    const getStatusIcon = (status) => {
        const icons = {
            [QuestionStatus.VIEWED]: '<span class="status-icon viewed" title="Viewed">👁️</span>',
            [QuestionStatus.COMPLETED]: '<span class="status-icon completed" title="Completed">✓</span>',
            [QuestionStatus.INCORRECT]: '<span class="status-icon incorrect" title="Needs Review">✗</span>'
        };
        return icons[status] || '';
    };

    const updateProgressBars = () => {
        const stats = getStatistics();
        
        const progressContainer = document.getElementById('progress-stats-container');
        if (!progressContainer) return;

        const overallProgress = stats.overall;
        
        let html = `
            <div class="progress-summary">
                <h3>Overall Progress</h3>
                <div class="progress-bar-container">
                    <div class="progress-bar">
                        <div class="progress-fill completed" style="width: ${overallProgress.completionPercentage}%"></div>
                    </div>
                    <span class="progress-text">${overallProgress.completed}/${overallProgress.total} Completed (${overallProgress.completionPercentage}%)</span>
                </div>
                <div class="progress-details">
                    <div class="detail-item">
                        <span class="detail-icon viewed">👁️</span>
                        <span>Viewed: ${overallProgress.viewed}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-icon completed">✓</span>
                        <span>Completed: ${overallProgress.completed}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-icon incorrect">✗</span>
                        <span>Needs Review: ${overallProgress.incorrect}</span>
                    </div>
                </div>
            </div>
        `;

        if (Object.keys(stats.byCategory).length > 0) {
            html += '<div class="category-progress"><h4>Progress by Category</h4>';
            Object.keys(stats.byCategory).forEach(category => {
                const cat = stats.byCategory[category];
                html += `
                    <div class="category-item">
                        <div class="category-header">
                            <span class="category-name">${category}</span>
                            <span class="category-stats">${cat.completed}/${cat.total}</span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar small">
                                <div class="progress-fill completed" style="width: ${cat.completionPercentage}%"></div>
                            </div>
                            <span class="progress-text small">${cat.completionPercentage}%</span>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
        }

        if (Object.keys(stats.byDifficulty).length > 0) {
            html += '<div class="difficulty-progress"><h4>Progress by Difficulty</h4>';
            Object.keys(stats.byDifficulty).forEach(difficulty => {
                const diff = stats.byDifficulty[difficulty];
                html += `
                    <div class="difficulty-item">
                        <div class="difficulty-header">
                            <span class="difficulty-name">${difficulty}</span>
                            <span class="difficulty-stats">${diff.completed}/${diff.total}</span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar small">
                                <div class="progress-fill completed" style="width: ${diff.completionPercentage}%"></div>
                            </div>
                            <span class="progress-text small">${diff.completionPercentage}%</span>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
        }

        progressContainer.innerHTML = html;
    };

    const attachEventListeners = () => {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('mark-complete-btn')) {
                const questionId = e.target.dataset.questionId;
                const metadata = JSON.parse(e.target.dataset.metadata || '{}');
                markComplete(questionId, metadata);
            }
            
            if (e.target.classList.contains('mark-incorrect-btn')) {
                const questionId = e.target.dataset.questionId;
                const metadata = JSON.parse(e.target.dataset.metadata || '{}');
                markIncorrect(questionId, metadata);
            }

            if (e.target.classList.contains('reset-progress-btn')) {
                resetProgress();
            }

            if (e.target.classList.contains('export-progress-btn')) {
                exportProgress();
            }
        });

        const importInput = document.getElementById('import-progress-input');
        if (importInput) {
            importInput.addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (file) {
                    try {
                        await importProgress(file);
                        alert('Progress imported successfully!');
                    } catch (error) {
                        alert('Error importing progress: ' + error.message);
                    }
                    e.target.value = '';
                }
            });
        }

        document.addEventListener('questionExpanded', (e) => {
            const questionId = e.detail.questionId;
            const metadata = e.detail.metadata || {};
            markViewed(questionId, metadata);
        });
    };

    const dispatchProgressEvent = (eventType, detail = {}) => {
        const event = new CustomEvent(`progress${eventType.charAt(0).toUpperCase() + eventType.slice(1)}`, {
            detail: {
                ...detail,
                timestamp: new Date().toISOString()
            }
        });
        document.dispatchEvent(event);
    };

    const createProgressControls = () => {
        return `
            <div class="progress-controls">
                <button class="export-progress-btn" title="Export Progress">
                    📥 Export Progress
                </button>
                <label class="import-progress-btn" title="Import Progress">
                    📤 Import Progress
                    <input type="file" id="import-progress-input" accept=".json" style="display: none;">
                </label>
                <button class="reset-progress-btn" title="Reset Progress">
                    🔄 Reset Progress
                </button>
            </div>
        `;
    };

    const createQuestionControls = (questionId, metadata = {}) => {
        return `
            <div class="question-progress-controls">
                <button class="mark-complete-btn" 
                        data-question-id="${questionId}" 
                        data-metadata='${JSON.stringify(metadata)}'
                        title="Mark as Completed">
                    ✓ Complete
                </button>
                <button class="mark-incorrect-btn" 
                        data-question-id="${questionId}" 
                        data-metadata='${JSON.stringify(metadata)}'
                        title="Mark as Needs Review">
                    ✗ Review
                </button>
            </div>
        `;
    };

    const getQuestionStatus = (questionId) => {
        return progressData.questions[questionId] || null;
    };

    const injectStyles = () => {
        if (document.getElementById('progress-tracker-styles')) return;
        
        const styles = `
            <style id="progress-tracker-styles">
                .progress-indicator {
                    margin-left: auto;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .status-icon {
                    font-size: 1.2rem;
                    display: inline-block;
                }

                .status-icon.viewed {
                    color: #3498db;
                }

                .status-icon.completed {
                    color: #27ae60;
                    font-weight: bold;
                }

                .status-icon.incorrect {
                    color: #e74c3c;
                    font-weight: bold;
                }

                .status-viewed {
                    border-left-color: #3498db !important;
                }

                .status-completed {
                    border-left-color: #27ae60 !important;
                    background-color: #f0fdf4 !important;
                }

                .status-incorrect {
                    border-left-color: #e74c3c !important;
                    background-color: #fef2f2 !important;
                }

                .progress-controls {
                    display: flex;
                    gap: 10px;
                    margin: 20px 0;
                    flex-wrap: wrap;
                }

                .progress-controls button,
                .progress-controls label.import-progress-btn {
                    padding: 10px 20px;
                    border: none;
                    border-radius: 6px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    cursor: pointer;
                    font-weight: 600;
                    transition: all 0.3s;
                    font-size: 0.9rem;
                }

                .progress-controls button:hover,
                .progress-controls label.import-progress-btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
                }

                .progress-controls .reset-progress-btn {
                    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
                }

                .question-progress-controls {
                    display: flex;
                    gap: 8px;
                    margin-top: 15px;
                }

                .question-progress-controls button {
                    padding: 6px 12px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 0.85rem;
                    font-weight: 600;
                    transition: all 0.3s;
                }

                .mark-complete-btn {
                    background-color: #27ae60;
                    color: white;
                }

                .mark-complete-btn:hover {
                    background-color: #229954;
                    transform: scale(1.05);
                }

                .mark-incorrect-btn {
                    background-color: #e74c3c;
                    color: white;
                }

                .mark-incorrect-btn:hover {
                    background-color: #c0392b;
                    transform: scale(1.05);
                }

                #progress-stats-container {
                    background: white;
                    border-radius: 8px;
                    padding: 20px;
                    margin: 20px 0;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                }

                .progress-summary h3 {
                    margin-bottom: 15px;
                    color: #2d3748;
                }

                .progress-bar-container {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    margin: 10px 0;
                }

                .progress-bar {
                    flex: 1;
                    height: 24px;
                    background-color: #e9ecef;
                    border-radius: 12px;
                    overflow: hidden;
                    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
                }

                .progress-bar.small {
                    height: 16px;
                    border-radius: 8px;
                }

                .progress-fill {
                    height: 100%;
                    transition: width 0.5s ease;
                    border-radius: 12px;
                }

                .progress-fill.completed {
                    background: linear-gradient(90deg, #27ae60 0%, #229954 100%);
                }

                .progress-fill.viewed {
                    background: linear-gradient(90deg, #3498db 0%, #2980b9 100%);
                }

                .progress-text {
                    font-weight: 600;
                    color: #495057;
                    min-width: 80px;
                    text-align: right;
                    font-size: 0.9rem;
                }

                .progress-text.small {
                    font-size: 0.8rem;
                    min-width: 50px;
                }

                .progress-details {
                    display: flex;
                    gap: 20px;
                    margin-top: 15px;
                    flex-wrap: wrap;
                }

                .detail-item {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    padding: 8px 12px;
                    background-color: #f8f9fa;
                    border-radius: 6px;
                }

                .detail-icon {
                    font-size: 1.1rem;
                }

                .category-progress,
                .difficulty-progress {
                    margin-top: 25px;
                }

                .category-progress h4,
                .difficulty-progress h4 {
                    margin-bottom: 12px;
                    color: #495057;
                }

                .category-item,
                .difficulty-item {
                    margin-bottom: 12px;
                }

                .category-header,
                .difficulty-header {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 5px;
                }

                .category-name,
                .difficulty-name {
                    font-weight: 600;
                    color: #2d3748;
                    font-size: 0.9rem;
                }

                .category-stats,
                .difficulty-stats {
                    color: #6c757d;
                    font-size: 0.85rem;
                }

                @media (max-width: 768px) {
                    .progress-controls {
                        flex-direction: column;
                    }

                    .progress-controls button,
                    .progress-controls label.import-progress-btn {
                        width: 100%;
                    }

                    .progress-details {
                        flex-direction: column;
                        gap: 10px;
                    }
                }
            </style>
        `;
        document.head.insertAdjacentHTML('beforeend', styles);
    };

    return {
        init,
        markViewed,
        markComplete,
        markIncorrect,
        getProgress,
        getStatistics,
        resetProgress,
        exportProgress,
        importProgress,
        mergeProgress,
        createProgressControls,
        createQuestionControls,
        getQuestionStatus,
        updateQuestionVisualIndicator,
        updateAllVisualIndicators,
        injectStyles,
        QuestionStatus
    };
})();

if (typeof module !== 'undefined' && module.exports) {
    module.exports = ProgressTracker;
}
