import { SwalOfflineToast } from './sweetalert.js';

let previousConnectionState = {
    online: true,
    serverReachable: true
};

export function updateConnectionStatus() {
    const currentOnline = navigator.onLine;

    if (currentOnline) {
        fetch('/up')
            .then(response => {
                const currentServerReachable = response.ok;

                // Solo mostrar mensaje si el estado cambió
                if (!previousConnectionState.serverReachable && currentServerReachable) {
                    // Conexión al servidor recuperada
                    SwalOfflineToast.fire({
                        icon: 'success',
                        title: 'Conexión al servidor restaurada.'
                    });
                } else if (previousConnectionState.serverReachable && !currentServerReachable) {
                    // Conexión al servidor perdida
                    SwalOfflineToast.fire({
                        icon: 'error',
                        title: 'No se pudo conectar al servidor. Algunas características pueden no estar disponibles.'
                    });
                }

                previousConnectionState.serverReachable = currentServerReachable;
            })
            .catch((error) => {
                console.error('Error al verificar el estado de la conexión:', error);

                // Solo mostrar mensaje si antes estaba conectado al servidor
                if (previousConnectionState.serverReachable) {
                    SwalOfflineToast.fire({
                        icon: 'error',
                        title: 'No se pudo conectar al servidor. Algunas características pueden no estar disponibles.'
                    });
                }

                previousConnectionState.serverReachable = false;
            });

        // Si antes no había internet y ahora sí
        if (!previousConnectionState.online) {
            SwalOfflineToast.fire({
                icon: 'success',
                title: 'Conexión a internet restaurada.'
            });
        }

        previousConnectionState.online = true;
    } else {
        // Solo mostrar mensaje si antes estaba online
        if (previousConnectionState.online) {
            SwalOfflineToast.fire({
                icon: 'warning',
                title: 'No tienes conexión a internet. Algunas características pueden no estar disponibles.'
            });
        }

        previousConnectionState.online = false;
        previousConnectionState.serverReachable = false;
    }
}