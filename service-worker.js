// ============================================================================
// SERVICE WORKER FOR OFFLINE SUPPORT
// Caches static assets and question data for offline access
// ============================================================================

const CACHE_VERSION = 'v1';
const STATIC_CACHE_NAME = `laravel-interview-bank-static-${CACHE_VERSION}`;
const DATA_CACHE_NAME = `laravel-interview-bank-data-${CACHE_VERSION}`;
const RUNTIME_CACHE_NAME = `laravel-interview-bank-runtime-${CACHE_VERSION}`;

// Static assets to cache on install
const STATIC_ASSETS = [
    '/',
    '/index.html',
    '/assets/css/styles.css',
    '/assets/js/app.js',
    '/assets/js/enhancements.js',
    '/assets/js/markdown-renderer.js',
    '/assets/js/search.js',
    '/assets/js/ui-controller.js',
    '/assets/js/data-loader.js',
    '/favicon.png'
];

// API endpoints to cache
const API_ENDPOINTS = [
    '/api/questions',
    '/data/questions.json'
];

// Maximum age for cached items (in milliseconds)
const MAX_AGE = {
    static: 30 * 24 * 60 * 60 * 1000,  // 30 days
    data: 24 * 60 * 60 * 1000,          // 1 day
    runtime: 60 * 60 * 1000             // 1 hour
};

// ============================================================================
// INSTALL EVENT
// Cache static assets when service worker is installed
// ============================================================================

self.addEventListener('install', (event) => {
    console.log('[ServiceWorker] Installing...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE_NAME)
            .then((cache) => {
                console.log('[ServiceWorker] Caching static assets');
                return cache.addAll(STATIC_ASSETS.map(url => new Request(url, { cache: 'reload' })));
            })
            .then(() => {
                console.log('[ServiceWorker] Static assets cached');
                return self.skipWaiting();
            })
            .catch((error) => {
                console.error('[ServiceWorker] Failed to cache static assets:', error);
            })
    );
});

// ============================================================================
// ACTIVATE EVENT
// Clean up old caches
// ============================================================================

self.addEventListener('activate', (event) => {
    console.log('[ServiceWorker] Activating...');
    
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((cacheName) => {
                            // Delete old versions of caches
                            return cacheName.startsWith('laravel-interview-bank-') &&
                                   cacheName !== STATIC_CACHE_NAME &&
                                   cacheName !== DATA_CACHE_NAME &&
                                   cacheName !== RUNTIME_CACHE_NAME;
                        })
                        .map((cacheName) => {
                            console.log('[ServiceWorker] Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        })
                );
            })
            .then(() => {
                console.log('[ServiceWorker] Activated');
                return self.clients.claim();
            })
    );
});

// ============================================================================
// FETCH EVENT
// Intercept network requests and serve from cache when offline
// ============================================================================

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Skip chrome extensions and other protocols
    if (!url.protocol.startsWith('http')) {
        return;
    }
    
    // Handle different types of requests
    if (isStaticAsset(url)) {
        event.respondWith(handleStaticAssetRequest(request));
    } else if (isDataRequest(url)) {
        event.respondWith(handleDataRequest(request));
    } else {
        event.respondWith(handleRuntimeRequest(request));
    }
});

// ============================================================================
// REQUEST HANDLERS
// ============================================================================

/**
 * Handle static asset requests (Cache First strategy)
 */
async function handleStaticAssetRequest(request) {
    try {
        const cache = await caches.open(STATIC_CACHE_NAME);
        const cachedResponse = await cache.match(request);
        
        if (cachedResponse) {
            // Check if cache is stale
            const cacheDate = new Date(cachedResponse.headers.get('date'));
            const now = new Date();
            const age = now - cacheDate;
            
            if (age < MAX_AGE.static) {
                return cachedResponse;
            }
        }
        
        // Fetch from network and update cache
        const networkResponse = await fetch(request);
        
        if (networkResponse && networkResponse.status === 200) {
            const responseToCache = networkResponse.clone();
            cache.put(request, responseToCache);
        }
        
        return networkResponse;
    } catch (error) {
        // Network failed, return cached version even if stale
        const cache = await caches.open(STATIC_CACHE_NAME);
        const cachedResponse = await cache.match(request);
        
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Return offline page or error response
        return new Response('Offline - Asset not available', {
            status: 503,
            statusText: 'Service Unavailable',
            headers: new Headers({
                'Content-Type': 'text/plain'
            })
        });
    }
}

/**
 * Handle data requests (Network First with cache fallback)
 */
async function handleDataRequest(request) {
    try {
        // Try network first
        const networkResponse = await fetch(request);
        
        if (networkResponse && networkResponse.status === 200) {
            // Cache the response
            const cache = await caches.open(DATA_CACHE_NAME);
            const responseToCache = networkResponse.clone();
            cache.put(request, responseToCache);
            
            return networkResponse;
        }
        
        throw new Error('Network response not ok');
    } catch (error) {
        // Network failed, try cache
        const cache = await caches.open(DATA_CACHE_NAME);
        const cachedResponse = await cache.match(request);
        
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // No cache available
        return new Response(JSON.stringify({
            error: 'Offline - Data not available',
            offline: true
        }), {
            status: 503,
            statusText: 'Service Unavailable',
            headers: new Headers({
                'Content-Type': 'application/json'
            })
        });
    }
}

/**
 * Handle runtime requests (Network First with cache fallback)
 */
async function handleRuntimeRequest(request) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse && networkResponse.status === 200) {
            // Cache successful responses
            const cache = await caches.open(RUNTIME_CACHE_NAME);
            const responseToCache = networkResponse.clone();
            
            // Don't cache if response is too large
            const contentLength = networkResponse.headers.get('content-length');
            if (!contentLength || parseInt(contentLength) < 5 * 1024 * 1024) { // 5MB limit
                cache.put(request, responseToCache);
            }
        }
        
        return networkResponse;
    } catch (error) {
        // Try cache
        const cache = await caches.open(RUNTIME_CACHE_NAME);
        const cachedResponse = await cache.match(request);
        
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Return offline fallback
        return new Response('Offline', {
            status: 503,
            statusText: 'Service Unavailable'
        });
    }
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Check if URL is a static asset
 */
function isStaticAsset(url) {
    const pathname = url.pathname;
    
    return pathname.endsWith('.css') ||
           pathname.endsWith('.js') ||
           pathname.endsWith('.png') ||
           pathname.endsWith('.jpg') ||
           pathname.endsWith('.jpeg') ||
           pathname.endsWith('.gif') ||
           pathname.endsWith('.svg') ||
           pathname.endsWith('.ico') ||
           pathname.endsWith('.woff') ||
           pathname.endsWith('.woff2') ||
           pathname.endsWith('.ttf') ||
           pathname.endsWith('.eot') ||
           pathname === '/' ||
           pathname.endsWith('.html');
}

/**
 * Check if URL is a data request
 */
function isDataRequest(url) {
    const pathname = url.pathname;
    
    return pathname.includes('/api/') ||
           pathname.endsWith('.json') ||
           pathname.includes('/data/');
}

/**
 * Clean up old cache entries
 */
async function cleanupOldCaches(cacheName, maxAge) {
    try {
        const cache = await caches.open(cacheName);
        const requests = await cache.keys();
        const now = new Date();
        
        const deletePromises = requests.map(async (request) => {
            const response = await cache.match(request);
            if (response) {
                const cacheDate = new Date(response.headers.get('date'));
                const age = now - cacheDate;
                
                if (age > maxAge) {
                    console.log('[ServiceWorker] Deleting stale cache entry:', request.url);
                    return cache.delete(request);
                }
            }
        });
        
        await Promise.all(deletePromises);
    } catch (error) {
        console.error('[ServiceWorker] Error cleaning up caches:', error);
    }
}

// ============================================================================
// MESSAGE HANDLER
// Handle messages from the main application
// ============================================================================

self.addEventListener('message', (event) => {
    console.log('[ServiceWorker] Message received:', event.data);
    
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CACHE_URLS') {
        const urls = event.data.urls || [];
        cacheUrls(urls);
    }
    
    if (event.data && event.data.type === 'CLEAR_CACHE') {
        clearAllCaches();
    }
    
    if (event.data && event.data.type === 'CLEANUP') {
        cleanupOldCaches(STATIC_CACHE_NAME, MAX_AGE.static);
        cleanupOldCaches(DATA_CACHE_NAME, MAX_AGE.data);
        cleanupOldCaches(RUNTIME_CACHE_NAME, MAX_AGE.runtime);
    }
});

/**
 * Cache specific URLs on demand
 */
async function cacheUrls(urls) {
    try {
        const cache = await caches.open(RUNTIME_CACHE_NAME);
        await cache.addAll(urls);
        console.log('[ServiceWorker] URLs cached:', urls);
    } catch (error) {
        console.error('[ServiceWorker] Failed to cache URLs:', error);
    }
}

/**
 * Clear all caches
 */
async function clearAllCaches() {
    try {
        const cacheNames = await caches.keys();
        await Promise.all(
            cacheNames.map(cacheName => caches.delete(cacheName))
        );
        console.log('[ServiceWorker] All caches cleared');
    } catch (error) {
        console.error('[ServiceWorker] Failed to clear caches:', error);
    }
}

// ============================================================================
// BACKGROUND SYNC
// Sync data when connection is restored
// ============================================================================

self.addEventListener('sync', (event) => {
    console.log('[ServiceWorker] Background sync:', event.tag);
    
    if (event.tag === 'sync-questions') {
        event.waitUntil(syncQuestions());
    }
});

/**
 * Sync question data in the background
 */
async function syncQuestions() {
    try {
        const response = await fetch('/api/questions');
        
        if (response && response.status === 200) {
            const cache = await caches.open(DATA_CACHE_NAME);
            cache.put('/api/questions', response);
            console.log('[ServiceWorker] Questions synced');
        }
    } catch (error) {
        console.error('[ServiceWorker] Failed to sync questions:', error);
    }
}

// ============================================================================
// PUSH NOTIFICATIONS (Optional - for future enhancements)
// ============================================================================

self.addEventListener('push', (event) => {
    console.log('[ServiceWorker] Push notification received');
    
    const options = {
        body: event.data ? event.data.text() : 'New content available!',
        icon: '/favicon.png',
        badge: '/favicon.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        }
    };
    
    event.waitUntil(
        self.registration.showNotification('Laravel Interview Bank', options)
    );
});

self.addEventListener('notificationclick', (event) => {
    console.log('[ServiceWorker] Notification clicked');
    
    event.notification.close();
    
    event.waitUntil(
        clients.openWindow('/')
    );
});

console.log('[ServiceWorker] Service Worker loaded');
