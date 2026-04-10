// ============================================================================
// PWA OFFLINE SUPPORT MODULE
// Handles service worker registration, offline detection, localStorage queue,
// and offline mode indicators
// ============================================================================

class OfflineSupport {
    constructor() {
        this.isOnline = navigator.onLine;
        this.serviceWorkerRegistration = null;
        this.offlineQueue = [];
        this.syncInterval = null;
        this.STORAGE_KEY = 'offline_queue';
        this.SYNC_INTERVAL = 30000; // 30 seconds
        
        this.init();
    }

    // ========================================================================
    // INITIALIZATION
    // ========================================================================

    async init() {
        console.log('[OfflineSupport] Initializing...');
        
        // Register service worker
        await this.registerServiceWorker();
        
        // Setup offline detection
        this.setupOfflineDetection();
        
        // Load queued operations
        this.loadQueue();
        
        // Setup background sync simulation
        this.startBackgroundSync();
        
        // Add offline indicator to navigation
        this.addOfflineIndicator();
        
        // Initial status check
        this.updateOnlineStatus();
        
        console.log('[OfflineSupport] Initialization complete');
    }

    // ========================================================================
    // SERVICE WORKER REGISTRATION
    // ========================================================================

    async registerServiceWorker() {
        if (!('serviceWorker' in navigator)) {
            console.warn('[OfflineSupport] Service Workers not supported');
            return;
        }

        try {
            const registration = await navigator.serviceWorker.register('/service-worker.js', {
                scope: '/'
            });

            this.serviceWorkerRegistration = registration;

            console.log('[OfflineSupport] Service Worker registered:', registration.scope);

            // Handle updates
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                console.log('[OfflineSupport] New Service Worker installing...');

                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        console.log('[OfflineSupport] New Service Worker available');
                        this.showUpdateNotification();
                    }
                });
            });

            // Check for updates periodically
            setInterval(() => {
                registration.update();
            }, 60000); // Check every minute

        } catch (error) {
            console.error('[OfflineSupport] Service Worker registration failed:', error);
        }
    }

    showUpdateNotification() {
        const notification = document.createElement('div');
        notification.id = 'sw-update-notification';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 10000;
            max-width: 400px;
            animation: slideInRight 0.3s ease-out;
        `;
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="flex: 1;">
                    <strong>Update Available</strong>
                    <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">A new version is available. Refresh to update.</p>
                </div>
                <button onclick="location.reload()" style="
                    background: white;
                    color: #667eea;
                    border: none;
                    padding: 0.5rem 1rem;
                    border-radius: 4px;
                    cursor: pointer;
                    font-weight: 600;
                ">Refresh</button>
            </div>
        `;
        document.body.appendChild(notification);
    }

    // ========================================================================
    // OFFLINE DETECTION
    // ========================================================================

    setupOfflineDetection() {
        window.addEventListener('online', () => this.handleOnline());
        window.addEventListener('offline', () => this.handleOffline());
        
        // Also poll connection status
        setInterval(() => this.checkConnection(), 10000); // Check every 10 seconds
    }

    async checkConnection() {
        try {
            // Try to fetch a small resource
            const response = await fetch('/index.html', { 
                method: 'HEAD',
                cache: 'no-cache'
            });
            
            if (!this.isOnline && response.ok) {
                this.handleOnline();
            }
        } catch (error) {
            if (this.isOnline) {
                this.handleOffline();
            }
        }
    }

    handleOnline() {
        console.log('[OfflineSupport] Connection restored');
        this.isOnline = true;
        this.updateOnlineStatus();
        this.hideOfflineBanner();
        this.processQueue();
    }

    handleOffline() {
        console.log('[OfflineSupport] Connection lost');
        this.isOnline = false;
        this.updateOnlineStatus();
        this.showOfflineBanner();
    }

    updateOnlineStatus() {
        const indicator = document.getElementById('offline-indicator');
        if (!indicator) return;

        if (this.isOnline) {
            indicator.style.background = 'linear-gradient(135deg, #48bb78 0%, #38a169 100%)';
            indicator.innerHTML = '🟢 Online';
            indicator.title = 'You are online';
        } else {
            indicator.style.background = 'linear-gradient(135deg, #f56565 0%, #e53e3e 100%)';
            indicator.innerHTML = '🔴 Offline';
            indicator.title = 'You are offline - using cached data';
        }
    }

    // ========================================================================
    // OFFLINE BANNER
    // ========================================================================

    showOfflineBanner() {
        // Remove existing banner if any
        const existing = document.getElementById('offline-banner');
        if (existing) existing.remove();

        const banner = document.createElement('div');
        banner.id = 'offline-banner';
        banner.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
            color: white;
            padding: 1rem;
            text-align: center;
            z-index: 9999;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            animation: slideDown 0.3s ease-out;
        `;
        banner.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: center; gap: 1rem;">
                <span style="font-size: 1.5rem;">📡</span>
                <div>
                    <strong>You are offline</strong>
                    <p style="margin: 0.25rem 0 0 0; font-size: 0.9rem; opacity: 0.95;">
                        Don't worry! You can still browse cached content. Your changes will sync when you're back online.
                    </p>
                </div>
            </div>
        `;

        // Add animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideDown {
                from {
                    transform: translateY(-100%);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
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
        `;
        document.head.appendChild(style);

        document.body.insertBefore(banner, document.body.firstChild);

        // Adjust body padding to account for banner
        document.body.style.paddingTop = `${banner.offsetHeight}px`;
    }

    hideOfflineBanner() {
        const banner = document.getElementById('offline-banner');
        if (!banner) return;

        banner.style.animation = 'slideDown 0.3s ease-out reverse';
        setTimeout(() => {
            banner.remove();
            document.body.style.paddingTop = '0';
        }, 300);
    }

    // ========================================================================
    // OFFLINE INDICATOR IN NAVIGATION
    // ========================================================================

    addOfflineIndicator() {
        const header = document.querySelector('header nav');
        if (!header) return;

        const indicator = document.createElement('div');
        indicator.id = 'offline-indicator';
        indicator.style.cssText = `
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
            cursor: help;
            transition: all 0.3s;
            white-space: nowrap;
        `;

        header.appendChild(indicator);
        this.updateOnlineStatus();
    }

    // ========================================================================
    // LOCALSTORAGE QUEUE MANAGEMENT
    // ========================================================================

    loadQueue() {
        try {
            const stored = localStorage.getItem(this.STORAGE_KEY);
            this.offlineQueue = stored ? JSON.parse(stored) : [];
            console.log(`[OfflineSupport] Loaded ${this.offlineQueue.length} queued operations`);
        } catch (error) {
            console.error('[OfflineSupport] Failed to load queue:', error);
            this.offlineQueue = [];
        }
    }

    saveQueue() {
        try {
            localStorage.setItem(this.STORAGE_KEY, JSON.stringify(this.offlineQueue));
        } catch (error) {
            console.error('[OfflineSupport] Failed to save queue:', error);
        }
    }

    queueOperation(operation) {
        const queuedOp = {
            id: Date.now() + Math.random(),
            timestamp: new Date().toISOString(),
            operation: operation.type,
            data: operation.data,
            retries: 0,
            maxRetries: 3
        };

        this.offlineQueue.push(queuedOp);
        this.saveQueue();

        console.log('[OfflineSupport] Operation queued:', queuedOp);

        if (this.isOnline) {
            this.processQueue();
        }

        return queuedOp.id;
    }

    async processQueue() {
        if (!this.isOnline || this.offlineQueue.length === 0) {
            return;
        }

        console.log(`[OfflineSupport] Processing ${this.offlineQueue.length} queued operations`);

        const pendingOperations = [...this.offlineQueue];
        this.offlineQueue = [];

        for (const operation of pendingOperations) {
            try {
                await this.executeOperation(operation);
                console.log('[OfflineSupport] Operation executed:', operation);
            } catch (error) {
                console.error('[OfflineSupport] Operation failed:', operation, error);
                
                // Retry logic
                operation.retries++;
                if (operation.retries < operation.maxRetries) {
                    this.offlineQueue.push(operation);
                } else {
                    console.error('[OfflineSupport] Operation failed after max retries:', operation);
                }
            }
        }

        this.saveQueue();

        if (this.offlineQueue.length > 0) {
            console.log(`[OfflineSupport] ${this.offlineQueue.length} operations remaining in queue`);
        } else {
            console.log('[OfflineSupport] All operations processed successfully');
            this.showSyncSuccessNotification();
        }
    }

    async executeOperation(operation) {
        // Execute different types of operations
        switch (operation.operation) {
            case 'save_progress':
                return this.syncProgress(operation.data);
            case 'save_bookmark':
                return this.syncBookmark(operation.data);
            case 'save_note':
                return this.syncNote(operation.data);
            case 'api_request':
                return this.executeApiRequest(operation.data);
            default:
                console.warn('[OfflineSupport] Unknown operation type:', operation.operation);
        }
    }

    async syncProgress(data) {
        // Simulate syncing progress to server
        console.log('[OfflineSupport] Syncing progress:', data);
        // In a real app, this would make an API call
        await new Promise(resolve => setTimeout(resolve, 100));
    }

    async syncBookmark(data) {
        console.log('[OfflineSupport] Syncing bookmark:', data);
        await new Promise(resolve => setTimeout(resolve, 100));
    }

    async syncNote(data) {
        console.log('[OfflineSupport] Syncing note:', data);
        await new Promise(resolve => setTimeout(resolve, 100));
    }

    async executeApiRequest(data) {
        console.log('[OfflineSupport] Executing API request:', data);
        const response = await fetch(data.url, {
            method: data.method || 'POST',
            headers: data.headers || { 'Content-Type': 'application/json' },
            body: data.body ? JSON.stringify(data.body) : undefined
        });
        
        if (!response.ok) {
            throw new Error(`API request failed: ${response.status}`);
        }
        
        return response.json();
    }

    showSyncSuccessNotification() {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 10000;
            animation: slideInRight 0.3s ease-out;
        `;
        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <span style="font-size: 1.5rem;">✅</span>
                <strong>All changes synced!</strong>
            </div>
        `;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideInRight 0.3s ease-out reverse';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // ========================================================================
    // BACKGROUND SYNC SIMULATION
    // ========================================================================

    startBackgroundSync() {
        // Simulate background sync with setInterval
        this.syncInterval = setInterval(() => {
            this.checkConnection();
            
            if (this.isOnline && this.offlineQueue.length > 0) {
                console.log('[OfflineSupport] Background sync triggered');
                this.processQueue();
            }
        }, this.SYNC_INTERVAL);

        console.log('[OfflineSupport] Background sync started');
    }

    stopBackgroundSync() {
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
            this.syncInterval = null;
            console.log('[OfflineSupport] Background sync stopped');
        }
    }

    // ========================================================================
    // PUBLIC API
    // ========================================================================

    // Queue a localStorage operation for sync
    queueLocalStorageOperation(type, data) {
        return this.queueOperation({ type, data });
    }

    // Manually trigger sync
    async sync() {
        console.log('[OfflineSupport] Manual sync triggered');
        await this.processQueue();
    }

    // Get queue status
    getQueueStatus() {
        return {
            isOnline: this.isOnline,
            queueLength: this.offlineQueue.length,
            operations: this.offlineQueue.map(op => ({
                operation: op.operation,
                timestamp: op.timestamp,
                retries: op.retries
            }))
        };
    }

    // Clear the queue (use with caution)
    clearQueue() {
        this.offlineQueue = [];
        this.saveQueue();
        console.log('[OfflineSupport] Queue cleared');
    }

    // Cache specific URLs
    cacheUrls(urls) {
        if (this.serviceWorkerRegistration && this.serviceWorkerRegistration.active) {
            this.serviceWorkerRegistration.active.postMessage({
                type: 'CACHE_URLS',
                urls: urls
            });
            console.log('[OfflineSupport] Requested caching of URLs:', urls);
        }
    }

    // Clear all caches
    clearCache() {
        if (this.serviceWorkerRegistration && this.serviceWorkerRegistration.active) {
            this.serviceWorkerRegistration.active.postMessage({
                type: 'CLEAR_CACHE'
            });
            console.log('[OfflineSupport] Requested cache clear');
        }
    }
}

// ============================================================================
// INTEGRATION WITH EXISTING STORAGE OPERATIONS
// ============================================================================

// Wrapper for localStorage with offline queue support
class OfflineStorage {
    constructor(offlineSupport) {
        this.offlineSupport = offlineSupport;
    }

    setItem(key, value) {
        try {
            localStorage.setItem(key, value);
            
            // Queue for sync if offline
            if (!this.offlineSupport.isOnline) {
                this.offlineSupport.queueOperation({
                    type: 'save_progress',
                    data: { key, value, timestamp: new Date().toISOString() }
                });
            }
        } catch (error) {
            console.error('[OfflineStorage] setItem failed:', error);
        }
    }

    getItem(key) {
        try {
            return localStorage.getItem(key);
        } catch (error) {
            console.error('[OfflineStorage] getItem failed:', error);
            return null;
        }
    }

    removeItem(key) {
        try {
            localStorage.removeItem(key);
            
            if (!this.offlineSupport.isOnline) {
                this.offlineSupport.queueOperation({
                    type: 'save_progress',
                    data: { key, value: null, timestamp: new Date().toISOString() }
                });
            }
        } catch (error) {
            console.error('[OfflineStorage] removeItem failed:', error);
        }
    }
}

// ============================================================================
// INITIALIZE AND EXPORT
// ============================================================================

// Auto-initialize when script loads
let offlineSupport;
let offlineStorage;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initOfflineSupport);
} else {
    initOfflineSupport();
}

function initOfflineSupport() {
    offlineSupport = new OfflineSupport();
    offlineStorage = new OfflineStorage(offlineSupport);
    
    // Expose globally for use in other scripts
    window.offlineSupport = offlineSupport;
    window.offlineStorage = offlineStorage;
    
    console.log('[OfflineSupport] Available globally as window.offlineSupport');
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { OfflineSupport, OfflineStorage };
}
