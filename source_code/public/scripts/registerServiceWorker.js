if ("serviceWorker" in navigator) {
	// Check for existing service worker with the same script name
	navigator.serviceWorker.getRegistrations().then((registrations) => {
		const existing = registrations.find((reg) => reg.active && reg.active.scriptURL.includes('/sw.js'));
		if (existing) {
			// Unregister the existing service worker
			existing.unregister().then(() => {
				console.log("Unregistered existing service worker.");
				// Register the new service worker
				navigator.serviceWorker.register("../sw.js").then(
					(registration) => {
						console.log("Service worker registration succeeded:", registration);
					},
					(error) => {
						console.error(`Service worker registration failed: ${error}`);
					},
				);
			}).catch((error) => {
				console.error(`Error unregistering service worker: ${error}`);
			});
		} else {
			// Register if none exists
			navigator.serviceWorker.register("../sw.js").then(
				(registration) => {
					console.log("Service worker registration succeeded:", registration);
				},
				(error) => {
					console.error(`Service worker registration failed: ${error}`);
				},
			);
		}
	}).catch((error) => {
		console.error(`Error checking service worker registrations: ${error}`);
	});
} else {
	console.error("Service workers are not supported.");
}
