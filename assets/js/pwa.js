if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        // Jika SW di root: '/service-worker.js'
        // Jika di subfolder: '/presensi/service-worker.js'
        navigator.serviceWorker.register('/service-worker.js', {
            updateViaCache: 'none' // jangan cache file SW saat update
        })
            .then(reg => console.log('SW registered:', reg.scope))
            .catch(err => console.error('SW register failed:', err));
    });
}

let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    const btn = document.getElementById('installRelaylab');
    if (btn) btn.style.display = 'block';
});

document.getElementById('installRelaylab')?.addEventListener('click', async () => {
    if (!deferredPrompt) return;
    await deferredPrompt.prompt();
    deferredPrompt = null;
});