/**
 * RideMax Service Worker
 * Provides offline functionality and caching
 */

const CACHE_NAME = 'ridemax-v1.0.1';
const urlsToCache = [
    '/',
    '/login.php',
    '/register.php',
    '/assets/css/style.css',
    '/assets/js/script.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];

// Install event
self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                console.log('Opened cache');
                return cache.addAll(urlsToCache);
            })
    );
});

// Fetch event - Network first strategy for dynamic content
self.addEventListener('fetch', function(event) {
    // Skip caching for API calls and PHP files
    if (event.request.url.includes('/process/') || 
        event.request.url.includes('.php') ||
        event.request.method !== 'GET') {
        return;
    }
    
    event.respondWith(
        fetch(event.request)
            .then(function(response) {
                // If network succeeds, return fresh content
                return response;
            })
            .catch(function() {
                // If network fails, try cache
                return caches.match(event.request);
            })
    );
});

// Activate event
self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cacheName) {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});