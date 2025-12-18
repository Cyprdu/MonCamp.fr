// Ce code permet de mettre en cache les fichiers pour que l'app soit rapide
// et qu'elle soit considérée comme "installable" par Chrome/Android.

const CACHE_NAME = 'colomap-v1';
const urlsToCache = [
  '/',
  '/index.php',
  '/css/style.css', // Si tu as un fichier CSS externe
  '/favico.png',
  '/icon-192.png',
  '/icon-512.png'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        return response || fetch(event.request);
      })
  );
});