export function initializePageLoadingProgressBar() {
	const loader = document.getElementById("page-loading-progress");
	const bar = loader?.querySelector(".progress-bar");
	if (!loader || !bar) {
		return;
	}

	let intervalId = null;
	let currentWidth = 0;

	// Contadores independientes para evitar cierres prematuros
	let activeFetchRequests = 0;
	let activeJqueryRequests = 0;

	const setProgress = (value) => {
		currentWidth = Math.max(0, Math.min(100, value));
		loader.setAttribute("aria-valuenow", String(Math.round(currentWidth)));
		bar.style.width = `${currentWidth}%`;
	};

	const startLoader = () => {
		if (loader.classList.contains("is-active")) {
			return;
		}

		loader.classList.add("is-active");
		setProgress(8);

		if (intervalId !== null) window.clearInterval(intervalId);

		intervalId = window.setInterval(() => {
			if (currentWidth < 88) {
				setProgress(
					currentWidth + Math.max(1, (88 - currentWidth) * 0.12),
				);
			}
		}, 120);
	};

	const finishLoader = () => {
		// Evitar cerrar si hay peticiones válidas corriendo en Fetch o jQuery
		if (activeFetchRequests > 0 || activeJqueryRequests > 0) return;

		if (intervalId !== null) {
			window.clearInterval(intervalId);
			intervalId = null;
		}

		setProgress(100);
		window.setTimeout(() => {
			loader.classList.remove("is-active");
			setProgress(0);
		}, 180);
	};

	// --- FUNCIÓN PARA IGNORAR RUTAS ---
	const isIgnoredUrl = (url) => {
		if (!url) return false;
		// Ignora si la ruta incluye el endpoint exacto o con el parámetro de tiempo
		return url.includes("/up?t=") || url.endsWith("/up");
	};

	// --- INTERCEPTORES PARA AJAX EN SEGUNDO PLANO ---

	// 1. Interceptar Fetch API
	const originalFetch = window.fetch;
	window.fetch = async function (...args) {
		// Extraemos la URL (Fetch puede recibir un String, un objeto URL o un Request)
		let url = "";
		if (typeof args[0] === "string") {
			url = args[0];
		} else if (args[0] instanceof Request) {
			url = args[0].url;
		} else if (args[0] instanceof URL) {
			url = args[0].toString();
		}

		const ignore = isIgnoredUrl(url);

		if (!ignore) {
			activeFetchRequests++;
			startLoader();
		}

		try {
			const response = await originalFetch.apply(this, args);
			return response;
		} finally {
			if (!ignore) {
				activeFetchRequests--;
				finishLoader();
			}
		}
	};

	// 2. Eventos Globales de jQuery (Modificados para leer la URL)
	if (window.jQuery) {
		window.jQuery(document).ajaxSend((event, jqXHR, settings) => {
			// settings.url contiene la ruta de la petición de jQuery
			if (!isIgnoredUrl(settings.url)) {
				activeJqueryRequests++;
				startLoader();
			}
		});

		window.jQuery(document).ajaxComplete((event, jqXHR, settings) => {
			if (!isIgnoredUrl(settings.url)) {
				activeJqueryRequests--;
				finishLoader();
			}
		});
	}

	// --- EVENTOS DE NAVEGACIÓN Y FORMULARIOS ORIGINALES ---

	document.addEventListener("click", (event) => {
		const target = event.target;
		if (!(target instanceof Element)) {
			return;
		}

		const anchor = target.closest("a[href]");
		if (!anchor) {
			return;
		}

		if (anchor.target === "_blank" || anchor.hasAttribute("download")) {
			return;
		}

		const href = anchor.getAttribute("href") ?? "";
		if (href.startsWith("#") || href.startsWith("javascript:")) {
			return;
		}

		const destination = new URL(anchor.href, window.location.origin);
		if (destination.origin !== window.location.origin) {
			return;
		}

		if (
			destination.pathname === window.location.pathname &&
			destination.search === window.location.search
		) {
			return;
		}

		startLoader();
	});

	document.addEventListener("submit", (event) => {
		if (event.target instanceof HTMLFormElement) {
			startLoader();
		}
	});

	window.addEventListener("beforeunload", startLoader);

	window.addEventListener("pageshow", () => {
		activeFetchRequests = 0;
		activeJqueryRequests = 0;
		finishLoader();
	});

	window.addEventListener("load", () => {
		activeFetchRequests = 0;
		activeJqueryRequests = 0;
		finishLoader();
	});
}