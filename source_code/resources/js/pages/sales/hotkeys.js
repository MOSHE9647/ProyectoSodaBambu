import hotkeys from "hotkeys-js";
import { SwalModal } from "../../utils/sweetalert.js";

const SHORTCUT_CATEGORY = {
	POSITIVE: "positive",
	CAUTION: "caution",
	DESTRUCTIVE: "destructive",
};

const hotkeyDefinitions = {
	f1: {
		description: "Mostrar accesos directos",
		icon: "bi-shift",
		category: SHORTCUT_CATEGORY.CAUTION,
	},
	f6: {
		description: "Nueva orden",
		icon: "bi-plus-circle",
		category: SHORTCUT_CATEGORY.POSITIVE,
	},
	f10: {
		description: "Buscar producto",
		icon: "bi-search",
		category: SHORTCUT_CATEGORY.POSITIVE,
	},
	f12: {
		description: "Finalizar venta",
		icon: "bi-credit-card",
		category: SHORTCUT_CATEGORY.POSITIVE,
	},
	f5: {
		description: "Limpiar orden",
		icon: "bi-trash",
		category: SHORTCUT_CATEGORY.DESTRUCTIVE,
	},
};

const hotkeyCategoryPresentation = {
	[SHORTCUT_CATEGORY.POSITIVE]: {
		rowClass: "sales-hotkeys-row--positive",
		keyClass: "sales-hotkeys-key--positive",
	},
	[SHORTCUT_CATEGORY.CAUTION]: {
		rowClass: "sales-hotkeys-row--caution",
		keyClass: "sales-hotkeys-key--caution",
	},
	[SHORTCUT_CATEGORY.DESTRUCTIVE]: {
		rowClass: "sales-hotkeys-row--destructive",
		keyClass: "sales-hotkeys-key--destructive",
	},
};

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
 * @property {() => void} f5 Triggers a click on the clear sale button when available.
 * @property {() => void} f12 Triggers a click on the finalize sale button when available.
 */
const hotkeyActions = {
	f1: () => {
		showHotkeysModal();
	},
	f6: () => {
		triggerClickIfExists("#new-order-btn");
	},
	f10: () => {
		focusIfExists("#product-search");
	},
	f12: () => {
		triggerClickIfExists("#finalize-sale-button");
	},
	f5: () => {
		triggerClickIfExists("#clear-sale-btn");
	},
};

/**
 * Registers keyboard shortcuts for the sales page.
 *
 * Shortcuts:
 * - F1: open hotkeys modal
 * - F5: clear current order
 * - F6: create new order tab
 * - F10: focus product search input
 * - F12: trigger finalize sale action
 *
 * @returns {void}
 */
export function initializeHotkeys() {
	hotkeys(Object.keys(hotkeyActions).join(","), (event, handler) => {
		event.preventDefault();
		hotkeyActions[handler.key]?.();
	});

	$('#show-actions').on('click', () => showHotkeysModal());
}

export function showHotkeysModal() {
	const hotkeysRows = Object.entries(hotkeyDefinitions).map(([key, definition]) => {
		const style = hotkeyCategoryPresentation[definition.category];
		const displayKey = key.toUpperCase();

		return `
			<div class="sales-hotkeys-row ${style.rowClass}">
				<span class="sales-hotkeys-key ${style.keyClass}">${displayKey}</span>
				<i class="bi ${definition.icon} sales-hotkeys-icon"></i>
				<span class="sales-hotkeys-label">${definition.description}</span>
			</div>
		`;
	}).join("");

	const modalBodyHtml = `
		<div class="sales-hotkeys-swal-body">
			<p class="sales-hotkeys-subtitle">Usa estas teclas para navegar más rápido</p>
			<div class="sales-hotkeys-list">${hotkeysRows}</div>
			<div class="sales-hotkeys-footer">
				<small class="text-muted">Presiona <span class="sales-hotkeys-key">Esc</span> para cerrar</small>
			</div>
		</div>
	`;

	SwalModal.fire({
		title: '<i class="bi bi-keyboard me-2 text-success"></i><span>Accesos directos</span>',
		html: modalBodyHtml,
		showConfirmButton: false,
		showCloseButton: true,
		customClass: {
			popup: "swal-popup h-auto sales-hotkeys-swal",
			title: "sales-hotkeys-swal-title",
			htmlContainer: "sales-hotkeys-swal-html",
			closeButton: "swal-close-btn fs-3",
		},
	});
}