const CACHE_NAME = 'dekap-cache-v1';
const urlsToCache = [
  './index.html',
  './manifest.json',
  // Sebaiknya tidak memasukkan CDN eksternal langsung ke install cache
  // 'https://cdn.tailwindcss.com',
  // 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];

// Install Service Worker & Cache File Utama
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
      .then(() => self.skipWaiting()) // Memaksa service worker baru untuk langsung aktif
  );
});

// Activate Event - membersihkan cache lama jika ada versi baru
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cache => {
          if (cache !== CACHE_NAME) {
            return caches.delete(cache);
          }
        })
      );
    })
  );
});

// Ambil dari Cache dulu kalau offline, dengan strategi "Cache First, falling back to Network"
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Return file dari cache jika ada
        if (response) {
            return response;
        }

        // Kalau tidak ada di cache, ambil dari jaringan
        // Penting: clone request karena request adalah stream dan hanya bisa dibaca sekali
        const fetchRequest = event.request.clone();

        return fetch(fetchRequest).then(
          response => {
            // Cek apakah response valid (status 200) dan tipe basic (bukan opaque dari CDN cross-origin)
            // CDN cross-origin seringkali mengembalikan response opaque (status 0) yang tidak bisa asal dicache dengan baik
            if(!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }

            // Clone response karena akan disimpan di cache dan di-return ke browser
            const responseToCache = response.clone();

            caches.open(CACHE_NAME)
              .then(cache => {
                // Hanya cache request dengan scheme http/https (menghindari error cache extension browser)
                if (event.request.url.startsWith('http')) {
                   cache.put(event.request, responseToCache);
                }
              });

            return response;
          }
        ).catch(() => {
             // Jika gagal fetch (sedang offline) dan tidak ada di cache,
             // Anda bisa menambahkan halaman fallback offline khusus di sini jika mau
             console.log('Fetch failed, offline mode.');
        });
      })
  );
});

// Ambil dari Cache dulu kalau offline
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Return file dari cache jika ada, kalau tidak ambil dari jaringan
        return response || fetch(event.request);
      })
  );
});