self.addEventListener('push', function(event) {
    const data = event.data.json();
    const options = {
        body: data.body,
        icon: data.icon || 'assets/images/logo.png',
        badge: data.badge || 'assets/images/p-icon.png'
    };
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});