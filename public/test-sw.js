// Service Worker para recibir notificaciones push
self.addEventListener('push', function(event) {
    console.log('Push recibido:', event);

    const data = event.data ? event.data.json() : {
        title: 'Nueva notificación',
        body: 'Tienes una nueva notificación',
        icon: '/icon.png'
    };

    const options = {
        body: data.body,
        icon: data.icon || '/icon.png',
        badge: data.badge || '/badge.png',
        data: data.data || {},
        vibrate: [200, 100, 200],
        requireInteraction: false
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Manejar clic en notificación
self.addEventListener('notificationclick', function(event) {
    console.log('Notificación clickeada:', event);
    
    event.notification.close();

    // Abrir la aplicación o enfocarse en ella
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
            // Si hay una ventana abierta, enfocarse en ella
            for (let i = 0; i < clientList.length; i++) {
                let client = clientList[i];
                if (client.url.includes('test-push.html') && 'focus' in client) {
                    return client.focus();
                }
            }
            // Si no hay ventana abierta, abrir una nueva
            if (clients.openWindow) {
                return clients.openWindow('/test-push.html');
            }
        })
    );
});

// Instalación del Service Worker
self.addEventListener('install', function(event) {
    console.log('Service Worker instalado');
    self.skipWaiting();
});

// Activación del Service Worker
self.addEventListener('activate', function(event) {
    console.log('Service Worker activado');
    event.waitUntil(clients.claim());
});
