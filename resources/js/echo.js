import Echo from 'laravel-echo';

import Pusher from 'pusher-js';

const pusherKey = String(import.meta.env.VITE_PUSHER_APP_KEY ?? '').trim();
const pusherCluster = String(import.meta.env.VITE_PUSHER_APP_CLUSTER ?? '').trim();
const pusherHost = String(import.meta.env.VITE_PUSHER_HOST ?? '').trim();
const configuredPort = Number(import.meta.env.VITE_PUSHER_PORT ?? 0);

window.Pusher = Pusher;
window.Echo = null;

// Realtime is optional platform infrastructure. A missing or incomplete
// build-time Pusher configuration must never abort the main UI bundle.
if (pusherKey && (pusherCluster || pusherHost)) {
    const echoOptions = {
        broadcaster: 'pusher',
        key: pusherKey,
        forceTLS: true,
        enabledTransports: ['ws', 'wss'],
    };

    if (pusherCluster) {
        echoOptions.cluster = pusherCluster;
    }

    if (pusherHost) {
        echoOptions.wsHost = pusherHost;
    }

    if (Number.isFinite(configuredPort) && configuredPort > 0) {
        echoOptions.wsPort = configuredPort;
        echoOptions.wssPort = configuredPort;
    }

    try {
        window.Echo = new Echo(echoOptions);
    } catch (error) {
        window.Echo = null;
        console.warn('[RCENTZ] Realtime disabled because Pusher could not initialize.', error);
    }
}
