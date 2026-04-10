// ============================================================================
// SERVICE WORKER FOR PWA OFFLINE SUPPORT
// Implements cache-first strategy for all HTML/CSS/JS files
// ============================================================================

const CACHE_VERSION = 'v2';
const STATIC_CACHE_NAME = `laravel-interview-bank-static-${CACHE_VERSION}`;
const DATA_CACHE_NAME = `laravel-interview-bank-data-${CACHE_VERSION}`;
const RUNTIME_CACHE_NAME = `laravel-interview-bank-runtime-${CACHE_VERSION}`;

// Core static assets to cache on install
const STATIC_ASSETS = [
    '/',
    '/index.html',
    '/answers-index.html',
    '/code-examples.html',
    '/manifest.json',
    '/assets/css/styles.css',
    '/assets/css/enhanced-ui.css',
    '/assets/css/question-pages.css',
    '/assets/css/mock-interview.css',
    '/assets/css/syntax-highlighting.css',
    '/assets/css/print.css',
    '/assets/css/answer-diagrams.css',
    '/assets/js/app.js',
    '/assets/js/offline-support.js',
    '/assets/js/enhancements.js',
    '/assets/js/markdown-renderer.js',
    '/assets/js/search.js',
    '/assets/js/search-engine.js',
    '/assets/js/ui-controller.js',
    '/assets/js/data-loader.js',
    '/assets/js/theme-manager.js',
    '/assets/js/bookmark-system.js',
    '/assets/js/progress-tracker.js',
    '/assets/js/export-import.js',
    '/assets/js/filter-controller.js',
    '/assets/js/mock-interview.js'
];

// Maximum age for cached items (in milliseconds)
const MAX_AGE = {
    static: 30 * 24 * 60 * 60 * 1000,  // 30 days
    data: 24 * 60 * 60 * 1000,          // 1 day
    runtime: 7 * 24 * 60 * 60 * 1000    // 7 days for HTML/CSS/JS
};

// ============================================================================
// INSTALL EVENT
// Cache static assets when service worker is installed
// ============================================================================

self.addEventListener('install', (event) => {
    console.log('[ServiceWorker] Installing version', CACHE_VERSION);
    
    event.waitUntil(
        caches.open(STATIC_CACHE_NAME)
            .then((cache) => {
                console.log('[ServiceWorker] Caching static assets');
                // Try to cache all assets, but don't fail if some are missing
                return Promise.allSettled(
                    STATIC_ASSETS.map(url => 
                        cache.add(new Request(url, { cache: 'reload' }))
                            .catch(error => {
                                console.warn('[ServiceWorker] Failed to cache:', url, error);
                            })
                    )
                );
            })
            .then(() => {
                console.log('[ServiceWorker] Static assets cached');
                return self.skipWaiting();
            })
            .catch((error) => {
                console.error('[ServiceWorker] Installation failed:', error);
            })
    );
});

// ============================================================================
// ACTIVATE EVENT
// Clean up old caches
// ============================================================================

self.addEventListener('activate', (event) => {
    console.log('[ServiceWorker] Activating version', CACHE_VERSION);
    
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((cacheName) => {
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
// Intercept network requests with cache-first strategy for static assets
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
    
    // Skip external domains
    if (url.origin !== location.origin) {
        return;
    }
    
    // Handle different types of requests
    if (isStaticAsset(url)) {
        // Cache-first strategy for HTML, CSS, JS
        event.respondWith(cacheFirstStrategy(request, STATIC_CACHE_NAME, MAX_AGE.static));
    } else if (isDataRequest(url)) {
        // Network-first for data/API requests
        event.respondWith(networkFirstStrategy(request, DATA_CACHE_NAME));
    } else {
        // Cache-first for other resources (images, fonts, etc.)
        event.respondWith(cacheFirstStrategy(request, RUNTIME_CACHE_NAME, MAX_AGE.runtime));
    }
});

// ============================================================================
// CACHING STRATEGIES
// ============================================================================

/**
 * Cache-first strategy: Try cache first, fallback to network
 * Perfect for static assets (HTML, CSS, JS)
 */
async function cacheFirstStrategy(request, cacheName, maxAge) {
    try {
        const cache = await caches.open(cacheName);
        const cachedResponse = await cache.match(request);
        
        if (cachedResponse) {
            // Check if cache is still fresh
            const cacheDate = new Date(cachedResponse.headers.get('date') || 0);
            const now = new Date();
            const age = now - cacheDate;
            
            if (age < maxAge) {
                console.log('[ServiceWorker] Serving from cache:', request.url);
                
                // Update cache in background if it's getting old (> 50% of maxAge)
                if (age > maxAge * 0.5) {
                    updateCacheInBackground(request, cache);
                }
                
                return cachedResponse;
            } else {
                console.log('[ServiceWorker] Cache is stale, fetching fresh:', request.url);
            }
        }
        
        // Fetch from network
        const networkResponse = await fetch(request);
        
        if (networkResponse && networkResponse.status === 200) {
            // Clone the response before caching
            const responseToCache = networkResponse.clone();
            cache.put(request, responseToCache);
            console.log('[ServiceWorker] Cached from network:', request.url);
        }
        
        return networkResponse;
    } catch (error) {
        console.error('[ServiceWorker] Network failed, trying cache:', error);
        
        // Network failed, return stale cache if available
        const cache = await caches.open(cacheName);
        const cachedResponse = await cache.match(request);
        
        if (cachedResponse) {
            console.log('[ServiceWorker] Serving stale cache (offline):', request.url);
            return cachedResponse;
        }
        
        // Return offline page for HTML requests
        if (request.headers.get('accept')?.includes('text/html')) {
            return createOfflinePage();
        }
        
        // Return error response for other requests
        return new Response('Offline - Resource not available', {
            status: 503,
            statusText: 'Service Unavailable',
            headers: new Headers({
                'Content-Type': 'text/plain'
            })
        });
    }
}

/**
 * Network-first strategy: Try network first, fallback to cache
 * Good for data/API requests
 */
async function networkFirstStrategy(request, cacheName) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse && networkResponse.status === 200) {
            const cache = await caches.open(cacheName);
            const responseToCache = networkResponse.clone();
            cache.put(request, responseToCache);
            console.log('[ServiceWorker] Updated cache from network:', request.url);
        }
        
        return networkResponse;
    } catch (error) {
        console.error('[ServiceWorker] Network failed, trying cache:', error);
        
        const cache = await caches.open(cacheName);
        const cachedResponse = await cache.match(request);
        
        if (cachedResponse) {
            console.log('[ServiceWorker] Serving from cache (offline):', request.url);
            return cachedResponse;
        }
        
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
 * Update cache in background without blocking the response
 */
function updateCacheInBackground(request, cache) {
    fetch(request)
        .then(response => {
            if (response && response.status === 200) {
                cache.put(request, response);
                console.log('[ServiceWorker] Background cache update:', request.url);
            }
        })
        .catch(error => {
            console.warn('[ServiceWorker] Background update failed:', error);
        });
}

/**
 * Create offline fallback page
 */
function createOfflinePage() {
    const html = `
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Offline - Laravel Interview Bank</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    text-align: center;
                    padding: 2rem;
                }
                .container {
                    max-width: 500px;
                }
                .icon {
                    font-size: 5rem;
                    margin-bottom: 1rem;
                }
                h1 {
                    font-size: 2rem;
                    margin-bottom: 1rem;
                }
                p {
                    font-size: 1.1rem;
                    margin-bottom: 2rem;
                    opacity: 0.95;
                }
                button {
                    padding: 1rem 2rem;
                    font-size: 1rem;
                    background: white;
                    color: #667eea;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: transform 0.2s;
                }
                button:hover {
                    transform: translateY(-2px);
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="icon">📡</div>
                <h1>You're Offline</h1>
                <p>This page isn't available offline. Please check your internet connection and try again.</p>
                <button onclick="location.reload()">Retry</button>
                <br><br>
                <button onclick="location.href='/'">Go Home</button>
            </div>
        </body>
        </html>
    `;
    
    return new Response(html, {
        status: 503,
        statusText: 'Service Unavailable',
        headers: new Headers({
            'Content-Type': 'text/html'
        })
    });
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Check if URL is a static asset (HTML, CSS, JS)
 */
function isStaticAsset(url) {
    const pathname = url.pathname;
    
    return pathname.endsWith('.html') ||
           pathname.endsWith('.css') ||
           pathname.endsWith('.js') ||
           pathname === '/' ||
           pathname.endsWith('/');
}

/**
 * Check if URL is a data/API request
 */
function isDataRequest(url) {
    const pathname = url.pathname;
    
    return pathname.includes('/api/') ||
           pathname.endsWith('.json') ||
           pathname.includes('/data/');
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
        cleanupOldCaches();
    }
});

/**
 * Cache specific URLs on demand
 */
async function cacheUrls(urls) {
    try {
        const cache = await caches.open(RUNTIME_CACHE_NAME);
        await Promise.allSettled(
            urls.map(url => 
                cache.add(url).catch(error => {
                    console.warn('[ServiceWorker] Failed to cache URL:', url, error);
                })
            )
        );
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

/**
 * Clean up old cache entries
 */
async function cleanupOldCaches() {
    const cacheNamesList = [STATIC_CACHE_NAME, DATA_CACHE_NAME, RUNTIME_CACHE_NAME];
    
    for (const cacheName of cacheNamesList) {
        try {
            const cache = await caches.open(cacheName);
            const requests = await cache.keys();
            const now = new Date();
            
            const maxAge = cacheName.includes('static') ? MAX_AGE.static :
                          cacheName.includes('data') ? MAX_AGE.data :
                          MAX_AGE.runtime;
            
            for (const request of requests) {
                const response = await cache.match(request);
                if (response) {
                    const cacheDate = new Date(response.headers.get('date') || 0);
                    const age = now - cacheDate;
                    
                    if (age > maxAge) {
                        console.log('[ServiceWorker] Deleting stale cache entry:', request.url);
                        await cache.delete(request);
                    }
                }
            }
        } catch (error) {
            console.error('[ServiceWorker] Error cleaning cache:', cacheName, error);
        }
    }
}

// ============================================================================
// BACKGROUND SYNC
// Sync data when connection is restored
// ============================================================================

self.addEventListener('sync', (event) => {
    console.log('[ServiceWorker] Background sync:', event.tag);
    
    if (event.tag === 'sync-data') {
        event.waitUntil(syncData());
    }
});

/**
 * Sync data in the background
 */
async function syncData() {
    try {
        // This would sync queued operations from localStorage
        // For now, just refresh critical caches
        const cache = await caches.open(DATA_CACHE_NAME);
        
        // Re-cache critical pages
        const criticalUrls = ['/index.html', '/answers-index.html'];
        await Promise.allSettled(
            criticalUrls.map(url => fetch(url).then(response => {
                if (response.ok) {
                    cache.put(url, response);
                }
            }))
        );
        
        console.log('[ServiceWorker] Data synced');
    } catch (error) {
        console.error('[ServiceWorker] Failed to sync data:', error);
    }
}

// ============================================================================
// PERIODIC CACHE CLEANUP
// Run cleanup every hour
// ============================================================================

// Clean up stale caches periodically
setInterval(() => {
    cleanupOldCaches();
}, 60 * 60 * 1000); // Every hour

console.log('[ServiceWorker] Service Worker loaded - Cache-first strategy enabled');
