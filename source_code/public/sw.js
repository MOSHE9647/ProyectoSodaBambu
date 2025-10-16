/**
 * Preloads the necessary files for offline use.
 * @returns { Promise<void> }
 */
const preLoad = async function () {
	const response = await fetch('./cache.json');
	const filesToCache = await response.json();
	return caches.open("offline").then(function (cache) {
		// caching index and important routes
		const promises = filesToCache.map(url => cache.add(url).catch(error => console.error(`Error caching ${url}:`, error)));
		return Promise.all(promises);
	});
};

/**
 * Install Service Worker and pre-load important offline files
 */
self.addEventListener("install", function (event) {
	event.waitUntil(preLoad());
});

/**
 * Check the network response for a request
 * @param { Request } request
 * @returns { Promise<Response> }
 */
const checkResponse = function (request) {
	// Simplify: always try to fetch from network and return the response if the server responds (regardless of status)
	return fetch(request);
};

/**
 * Add a request to the cache
 * @param { Request } request
 * @returns { Promise<void> }
 */
const addToCache = function (request) {
	// Only cache http(s) requests
	if (!request.url.startsWith('http')) {
		return Promise.resolve();
	}
	return caches.open("offline").then(function (cache) {
		return fetch(request).then(function (response) {
			return cache.put(request, response);
		});
	});
};

/**
 * Return a response from the cache or the offline page
 * @param { Request } request
 * @returns { Promise<Response> }
 */
const returnFromCache = function (request) {
	return caches.open("offline").then(function (cache) {
		return cache.match(request).then(function (matching) {
			if (!matching || matching.status === 404) {
				return cache.match("offline.html");
			} else {
				return matching;
			}
		});
	});
};

/**
 * Fetch event handler - tries network first, then cache, then offline page
 */
self.addEventListener("fetch", function (event) {
	event.respondWith(
		checkResponse(event.request).catch(function () {
			// Solo si hay error de red (network error), intenta devolver del cache o offline.html
			return returnFromCache(event.request);
		})
	);
	if (event.request.url.startsWith('http')) {
		event.waitUntil(addToCache(event.request));
	}
});
