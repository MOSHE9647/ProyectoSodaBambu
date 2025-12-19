/**
 * Check if a URL should be cached
 * @param { string } url
 * @returns { boolean }
 */
const shouldCache = function (url) {
	// Don't cache '/up' endpoint
	if (url.endsWith('/up')) {
		return false;
	}
	// Don't cache URLs that are not http(s)
	if (!url.startsWith('http')) {
		return false;
	}
	return true;
};

/**
 * Preloads the necessary files for offline use.
 * @returns { Promise<void> }
 */
const preLoad = async function () {
	const response = await fetch('./cache.json');
	const filesToCache = await response.json();
	return caches.open("offline").then(function (cache) {
		// caching index and important routes
		const promises = filesToCache
			.filter(url => shouldCache(url)) // Filter URLs that shouldn't be cached
			.map(url => cache.add(url).catch(error => console.error(`Error caching ${url}:`, error)));
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
	// Always try to fetch from network and return the response if the server responds (regardless of status)
	return fetch(request);
};

/**
 * Add a request to the cache
 * @param { Request } request
 * @returns { Promise<void> }
 */
const addToCache = async function (request) {
	// Only cache http(s) requests
	if (!shouldCache(request.url)) {
		return Promise.resolve();
	}
	const cache = await caches.open("offline");
	const response = await fetch(request);
	if (response.ok) {
		return cache.put(request, response.clone());
	}
	return response;
};

/**
 * Return a response from the cache or the offline page
 * @param { Request } request
 * @returns { Promise<Response> }
 */
const returnFromCache = async function (request) {
	const cache = await caches.open("offline");
	const matching = await cache.match(request);
	if (!matching || matching.status === 404) {
		return cache.match("offline.html");
	} else {
		return matching;
	}
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
	if (shouldCache(event.request.url)) {
		event.waitUntil(addToCache(event.request));
	}
});
