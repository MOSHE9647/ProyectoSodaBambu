let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;

    const modal = new bootstrap.Modal(document.getElementById('pwaInstallModal'));
    modal.show();
});

document.addEventListener('DOMContentLoaded', () => {
    const installBtn = document.getElementById('pwa-install-btn');

    if (installBtn) {
        installBtn.addEventListener('click', async () => {
            if (!deferredPrompt) return;

            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;

            console.log(`User response to the install prompt: ${outcome}`);

            installBtn.style.display = 'none';
            deferredPrompt = null;
        });
    }
});

window.addEventListener('appinstalled', () => {
    console.log('PWA was installed');
    // your logic to handle the PWA installation
});
