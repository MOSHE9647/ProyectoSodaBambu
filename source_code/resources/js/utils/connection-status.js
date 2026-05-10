import { SwalOfflineToast, SwalNotificationTypes } from './sweetalert.js';

const MESSAGES = {
    offline: 'No tienes conexión a internet. \nAlgunas características pueden no estar disponibles.',
    online: 'Conexión a internet restaurada.'
};

let previousOnlineState = navigator.onLine;

/**
 * Inicializa los listeners nativos del navegador para detectar 
 * cambios físicos en la red (Wi-Fi, Ethernet).
 */
export function checkConnectionStatus() {
    // Validar estado inicial por si el usuario carga la página sin internet
    if (!navigator.onLine) {
        SwalOfflineToast.fire({ icon: SwalNotificationTypes.WARNING, title: MESSAGES.offline });
        previousOnlineState = false;
    }

    // Escuchar cuando se pierde el internet
    window.addEventListener('offline', () => {
        if (previousOnlineState) {
            SwalOfflineToast.fire({ icon: SwalNotificationTypes.WARNING, title: MESSAGES.offline });
            previousOnlineState = false;
        }
    });

    // Escuchar cuando regresa el internet
    window.addEventListener('online', () => {
        if (!previousOnlineState) {
            SwalOfflineToast.fire({ icon: SwalNotificationTypes.SUCCESS, title: MESSAGES.online });
            previousOnlineState = true;
        }
    });
}