export function initializePageLoadingProgressBar() {
    const loader = document.getElementById("page-loading-progress");
	const bar = loader?.querySelector(".progress-bar");
	if (!loader || !bar) {
		return;
	}

	let intervalId = null;
	let currentWidth = 0;

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

		intervalId = window.setInterval(() => {
			if (currentWidth < 88) {
				setProgress(
					currentWidth + Math.max(1, (88 - currentWidth) * 0.12),
				);
			}
		}, 120);
	};

	const finishLoader = () => {
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
	window.addEventListener("pageshow", finishLoader);
	window.addEventListener("load", finishLoader);
}