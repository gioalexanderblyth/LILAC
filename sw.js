// LILAC System Service Worker
// Version 1.0.0

const CACHE_NAME = 'lilac-v1.0.1';
const DATA_CACHE_NAME = 'lilac-data-v1.0.1';

// Resources to cache for offline use
const STATIC_CACHE_URLS = [
  '/LILAC/',
  '/LILAC/dashboard.html',
  '/LILAC/awards.html',
  '/LILAC/documents.html',
  '/LILAC/meetings.html',
  '/LILAC/funds.html',
  '/LILAC/mou-moa.html',
  '/LILAC/templates.html',
  '/LILAC/registrar_files.html',
  '/LILAC/styles.css',
  '/LILAC/manifest.json',
  'https://cdn.tailwindcss.com'
];

// API endpoints that should be cached
const API_CACHE_URLS = [
  '/LILAC/api/awards.php',
  '/LILAC/api/documents.php',
  '/LILAC/api/meetings.php',
  '/LILAC/api/funds.php',
  '/LILAC/api/mous.php',
  '/LILAC/api/templates.php',
  '/LILAC/api/registrar_files.php',
  '/LILAC/api/dashboard.php'
];

// Install event - cache static resources
self.addEventListener('install', (event) => {
  console.log('LILAC Service Worker: Installing...');
  
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('LILAC Service Worker: Caching static resources');
        return cache.addAll(STATIC_CACHE_URLS);
      })
      .then(() => {
        console.log('LILAC Service Worker: Installation complete');
        return self.skipWaiting();
      })
      .catch((error) => {
        console.error('LILAC Service Worker: Installation failed', error);
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('LILAC Service Worker: Activating...');
  
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames.map((cacheName) => {
            if (cacheName !== CACHE_NAME && cacheName !== DATA_CACHE_NAME) {
              console.log('LILAC Service Worker: Deleting old cache', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      })
      .then(() => {
        console.log('LILAC Service Worker: Activation complete');
        return self.clients.claim();
      })
  );
});

// Fetch event - handle requests with offline support
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Handle API requests
  if (url.pathname.includes('/api/')) {
    event.respondWith(handleApiRequest(request));
    return;
  }

  // Handle static resources
  if (request.method === 'GET') {
    event.respondWith(handleStaticRequest(request));
    return;
  }
});

// Handle API requests with network-first strategy
async function handleApiRequest(request) {
  try {
    const networkResponse = await fetch(request);
    
    if (networkResponse.ok) {
      const cache = await caches.open(DATA_CACHE_NAME);
      cache.put(request, networkResponse.clone());
      return networkResponse;
    }
    
    throw new Error('Network response not ok');
    
  } catch (error) {
    console.log('LILAC Service Worker: Network failed, trying cache');
    
    if (request.method === 'GET') {
      const cachedResponse = await caches.match(request);
      if (cachedResponse) {
        return cachedResponse;
      }
    }
    
    // For offline POST/PUT/DELETE requests
    if (['POST', 'PUT', 'DELETE'].includes(request.method)) {
      return new Response(
        JSON.stringify({
          success: false,
          offline: true,
          message: 'Request will be synced when online'
        }),
        {
          status: 202,
          headers: { 'Content-Type': 'application/json' }
        }
      );
    }
    
    return new Response(
      JSON.stringify({
        success: false,
        offline: true,
        message: 'No cached data available'
      }),
      {
        status: 503,
        headers: { 'Content-Type': 'application/json' }
      }
    );
  }
}

// Handle static resources with cache-first strategy
async function handleStaticRequest(request) {
  try {
    const url = new URL(request.url);
    const isPHP = url.pathname.endsWith('.php');

    // Never cache dynamic PHP pages; always hit network
    if (isPHP) {
      try {
        const networkResponse = await fetch(request, { cache: 'no-store' });
        return networkResponse;
      } catch (e) {
        // If offline while navigating to a PHP page, fall back to dashboard
        if (request.mode === 'navigate') {
          return caches.match('/LILAC/dashboard.html');
        }
        throw e;
      }
    }

    // Cache-first for true static assets
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }

    const networkResponse = await fetch(request);

    if (networkResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }

    return networkResponse;

  } catch (error) {
    console.log('LILAC Service Worker: Failed to fetch static resource');

    if (request.mode === 'navigate') {
      return caches.match('/LILAC/dashboard.html');
    }

    return new Response('Offline', { status: 503 });
  }
}

// Store offline requests for later synchronization
async function storeOfflineRequest(request) {
  const requestData = {
    url: request.url,
    method: request.method,
    headers: Object.fromEntries(request.headers.entries()),
    body: request.method !== 'GET' ? await request.text() : null,
    timestamp: new Date().toISOString()
  };
  
  // Store in IndexedDB (implement IndexedDB helper)
  const db = await openOfflineDB();
  const transaction = db.transaction(['requests'], 'readwrite');
  const store = transaction.objectStore('requests');
  await store.add(requestData);
  
  console.log('LILAC Service Worker: Stored offline request', requestData);
}

// IndexedDB helper for offline storage
function openOfflineDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('LILACOfflineDB', 1);
    
    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);
    
    request.onupgradeneeded = (event) => {
      const db = event.target.result;
      
      // Create object stores
      if (!db.objectStoreNames.contains('requests')) {
        const requestStore = db.createObjectStore('requests', { 
          keyPath: 'id', 
          autoIncrement: true 
        });
        requestStore.createIndex('timestamp', 'timestamp', { unique: false });
      }
      
      if (!db.objectStoreNames.contains('data')) {
        const dataStore = db.createObjectStore('data', { keyPath: 'key' });
        dataStore.createIndex('timestamp', 'timestamp', { unique: false });
      }
    };
  });
}

// Background sync for offline requests
self.addEventListener('sync', (event) => {
  console.log('LILAC Service Worker: Background sync triggered', event.tag);
  
  if (event.tag === 'lilac-background-sync') {
    event.waitUntil(syncOfflineRequests());
  }
});

// Sync offline requests when back online
async function syncOfflineRequests() {
  try {
    const db = await openOfflineDB();
    const transaction = db.transaction(['requests'], 'readonly');
    const store = transaction.objectStore('requests');
    const requests = await getAllRecords(store);
    
    console.log(`LILAC Service Worker: Syncing ${requests.length} offline requests`);
    
    for (const requestData of requests) {
      try {
        const response = await fetch(requestData.url, {
          method: requestData.method,
          headers: requestData.headers,
          body: requestData.body
        });
        
        if (response.ok) {
          // Remove successfully synced request
          const deleteTransaction = db.transaction(['requests'], 'readwrite');
          const deleteStore = deleteTransaction.objectStore('requests');
          await deleteStore.delete(requestData.id);
          
          console.log('LILAC Service Worker: Successfully synced request', requestData.id);
        }
      } catch (error) {
        console.error('LILAC Service Worker: Failed to sync request', requestData.id, error);
      }
    }
    
    // Notify clients about sync completion
    notifyClients({ 
      type: 'SYNC_COMPLETE', 
      data: `Synced ${requests.length} offline requests` 
    });
    
  } catch (error) {
    console.error('LILAC Service Worker: Background sync failed', error);
  }
}

// Helper function to get all records from IndexedDB store
function getAllRecords(store) {
  return new Promise((resolve, reject) => {
    const request = store.getAll();
    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);
  });
}

// Notify all clients about service worker events
function notifyClients(message) {
  self.clients.matchAll().then(clients => {
    clients.forEach(client => {
      client.postMessage(message);
    });
  });
}

// Handle push notifications (for future enhancement)
self.addEventListener('push', (event) => {
  console.log('LILAC Service Worker: Push notification received');
  
  const options = {
    body: event.data ? event.data.text() : 'LILAC System notification',
    icon: '/LILAC/icons/icon-192x192.png',
    badge: '/LILAC/icons/badge-72x72.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'explore',
        title: 'Open LILAC',
        icon: '/LILAC/icons/checkmark.png'
      },
      {
        action: 'close',
        title: 'Close',
        icon: '/LILAC/icons/xmark.png'
      }
    ]
  };
  
  event.waitUntil(
    self.registration.showNotification('LILAC System', options)
  );
});

// Handle notification clicks
self.addEventListener('notificationclick', (event) => {
  console.log('LILAC Service Worker: Notification clicked');
  
  event.notification.close();
  
  if (event.action === 'explore') {
    event.waitUntil(
      clients.openWindow('/LILAC/dashboard.html')
    );
  }
});

console.log('LILAC Service Worker: Loaded successfully'); 