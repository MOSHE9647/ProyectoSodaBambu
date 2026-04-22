import hotkeys from "hotkeys-js";

/**
 * Triggers a click event on the first DOM element that matches the provided selector,
 * if such an element exists in the document.
 *
 * @param {string} selector - A jQuery-compatible selector used to find the target element.
 * @returns {void}
 */
const triggerClickIfExists = (selector) => {
	const element = $(selector);
	if (element.length) {
		element.trigger("click");
	}
};

/**
 * Sets focus on the first DOM element that matches the provided selector, if it exists.
 *
 * @param {string} selector - A jQuery-compatible selector used to locate the target element.
 * @returns {void}
 */
const focusIfExists = (selector) => {
	const element = $(selector);
	if (element.length) {
		element.focus();
	}
};

/**
 * Maps keyboard shortcut keys to their corresponding UI actions in the sales page.
 *
 * @type {Object.<string, () => void>}
 * @property {() => void} f6 Triggers a click on the "new order" button (`#new-order-btn`) if it exists.
 * @property {() => void} f10 Focuses the product search input (`#product-search`) if it exists.
 * @property {() => void} f12 Triggers a click on the finalize sale button when available.
 */
const hotkeyActions = {
	f6: () => {
		triggerClickIfExists("#new-order-btn");
	},
	f10: () => {
		focusIfExists("#product-search");
	},
	f12: () => {
        triggerClickIfExists("#finalize-sale-button");
	},
};

export function initializeHotkeys() {
    hotkeys(Object.keys(hotkeyActions).join(","), (event, handler) => {
		event.preventDefault();
		hotkeyActions[handler.key]?.();
	});
}
