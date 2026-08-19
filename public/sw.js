// RacePack Pro Service Worker
const CACHE_NAME = 'racepack-pro-v1';
const ASSETS_TO_CACHE = [
  '/',
  '/manifest.webmanifest',
  '/images/logo-indomaret-funrun.png',
  '/images/logo-indomaret.png',
  '/images/header-event-yogyakarta.png',
  '/images/tagline-catch-the-fun.png',
  '/images/media-partner.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE).catch(() => {});
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;
  // Network first with cache fallback for HTML/API navigation
  event.respondWith(
    fetch(event.request).catch(() => {
      return caches.match(event.request);
    })
  );
});
