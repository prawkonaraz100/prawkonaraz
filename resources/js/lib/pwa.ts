const SERVICE_WORKER_URL = '/service-worker.js';

export const registerPwaServiceWorker = () => {
    if (!('serviceWorker' in navigator) || !window.isSecureContext) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register(SERVICE_WORKER_URL, { scope: '/' })
            .then((registration) => registration.update())
            .catch(() => {
                // Installability should fail silently; the app remains fully usable online.
            });
    }, { once: true });
};
