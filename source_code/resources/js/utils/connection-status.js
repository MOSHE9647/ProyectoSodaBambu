import { SwalOfflineToast, SwalNotificationTypes } from './sweetalert.js';

// Messages for different connection states
const MESSAGES = {
    offline: 'No tienes conexión a internet. \nAlgunas características pueden no estar disponibles.',
    serverUnreachable: 'No se pudo conectar al servidor. \nAlgunas características pueden no estar disponibles.',
    serverReachable: 'Conexión al servidor restaurada.'
};

// Endpoint to check server reachability
const ENDPOINT = '/up';

// Store previous connection state to detect changes
let previousConnectionState = {
    online: true,
    serverReachable: true
};

// Prevent multiple simultaneous checks
let isCheckingConnection = false;

/**
 * Update the connection status by checking both internet connectivity and server reachability.
 * Displays appropriate toast notifications on state changes.
 * 
 * Usage: Call this function periodically to monitor connection status.
 * 
 * Example:
 * setInterval(updateConnectionStatus, 5000);
 * 
 * @returns {void}
 */
export async function updateConnectionStatus() {
    if (isCheckingConnection) return; // Prevent overlapping checks
    isCheckingConnection = true;

    const currentOnline = navigator.onLine;

    // Offline handling
    if (!currentOnline) {
        if (previousConnectionState.online) {
            showToast(SwalNotificationTypes.WARNING, MESSAGES.offline);
        }
        previousConnectionState.online = false;
        previousConnectionState.serverReachable = false;
        isCheckingConnection = false;
        return;
    }

    // Online handling
    try {
        // Check server reachability with a timeout
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 seconds timeout

        // Attempt to fetch the '/up' endpoint
        const response = await fetch(`${ENDPOINT}?t=` + Date.now(), {
            cache: 'no-store',
            signal: controller.signal 
        });

        // Clear timeout if fetch completes
        clearTimeout(timeoutId);

        // Determine if server is reachable
        const responsePathname = new URL(response.url, window.location.origin).pathname;
        const currentServerReachable = response.ok && responsePathname === ENDPOINT;

        // Handle server reachability changes
        if (currentServerReachable) {
            if (!previousConnectionState.serverReachable || !previousConnectionState.online) {
                showToast(SwalNotificationTypes.SUCCESS, MESSAGES.serverReachable);
            }
        } else {
            if (previousConnectionState.serverReachable) {
                showToast(SwalNotificationTypes.ERROR, MESSAGES.serverUnreachable);
            }
        }

        // Sets previous state to online and current server status
        previousConnectionState.online = true;
        previousConnectionState.serverReachable = currentServerReachable;

    } catch (error) {
        // Handle fetch errors (network issues, timeouts, etc.)
        if (previousConnectionState.serverReachable) {
            showToast(SwalNotificationTypes.ERROR, MESSAGES.serverUnreachable);
        }
        previousConnectionState.online = true; // Still online, but server unreachable
        previousConnectionState.serverReachable = false;

    } finally {
        isCheckingConnection = false;
    }
}

/**
 * Show a toast notification using SwalOfflineToast.
 * 
 * @param {String} icon 
 * @param {String} title 
 */
function showToast(icon, title) {
    SwalOfflineToast.fire({ icon, title });
}