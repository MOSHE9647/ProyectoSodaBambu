/**
 * Get the current theme from localStorage or system preference.
 * @returns {"dark" | "light"}
 */
function getTheme() {
	const stored = localStorage.getItem('theme');
	if (stored === 'dark' || stored === 'light') return stored;
	return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
}

/**
 * Set the theme to a specific value or toggle if none is provided.
 * Persist to localStorage when explicitly set via UI.
 * @param {"dark"|"light"|undefined} explicitTheme
 * @param {boolean} persist
 * @returns {void}
 */
function setTheme(explicitTheme, persist = false) {
	const current = getTheme();
	const newTheme = explicitTheme ?? (current === "dark" ? "light" : "dark");
	document.documentElement.setAttribute('data-bs-theme', newTheme);
	if (persist) localStorage.setItem('theme', newTheme);
}

/**
 * Initialize theme from user preference or system setting.
 */
function initTheme() {
	const theme = getTheme();
	document.documentElement.setAttribute('data-bs-theme', theme);
}

/**
 * Apply theme toggling functionality to a button.
 * @param {string} togglerId CSS selector for the theme toggle button.
 * @returns {void}
 */
export function applyTheme(togglerId) {
	initTheme();

	// Get the theme toggle button using jQuery
	const $btn = $(togglerId);
	if (!$btn || $btn.length === 0) {
		console.warn("Theme toggle button not found:", togglerId);
	} else {
		// Click toggles and persists user preference
		$btn.on('click', () => setTheme(undefined, true));
	}

	// System preference changes: only apply if user has not overridden
	const media = window.matchMedia('(prefers-color-scheme: dark)');
	const onSystemChange = (e) => {
		const hasOverride = localStorage.getItem('theme') === 'dark' || localStorage.getItem('theme') === 'light';
		if (!hasOverride) setTheme(e.matches ? 'dark' : 'light', false);
	};
	media.addEventListener('change', onSystemChange);
}
