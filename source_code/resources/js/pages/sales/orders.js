import { SwalModal, SwalNotificationTypes } from "../../utils/sweetalert.js";
import { switchActiveOrder, deleteOrderCart } from "./cart.js";

const TABS_STORAGE_KEY = "pos_orders_tabs_state";

// General state for the orders page
const state = {
	orderCounter: 2, // Starts at 2 because we assume there's already an "ORD-0001" in the HTML on load
};

/**
 * Extracts the numeric order sequence from a tab id.
 *
 * @param {string} [orderId=""]
 * @returns {number|null}
 */
const getOrderNumberFromId = (orderId = "") => {
	const match = String(orderId).match(/^order-tab-(\d{4})$/);
	return match ? Number(match[1]) : null;
};

/**
 * Returns fallback tab label text for a given order id.
 *
 * @param {string} [orderId=""]
 * @returns {string}
 */
const getDefaultTabTextById = (orderId = "") => {
	const orderNumber = getOrderNumberFromId(orderId);
	if (orderNumber === null) {
		return "";
	}

	return `ORD-${orderNumber.toString().padStart(4, "0")}`;
};

/**
 * Computes the next order counter based on existing tab ids.
 *
 * @param {Array<{id?: string}|string>} [tabsOrIds=[]]
 * @returns {number}
 */
const getNextOrderCounter = (tabsOrIds = []) => {
	const tabIds = Array.isArray(tabsOrIds)
		? tabsOrIds.map((tab) => (typeof tab === "string" ? tab : tab?.id || ""))
		: [];

	const maxOrderNumber = tabIds.reduce((currentMax, tabId) => {
		const orderNumber = getOrderNumberFromId(tabId);
		if (orderNumber === null) {
			return currentMax;
		}

		return Math.max(currentMax, orderNumber);
	}, 1);

	return maxOrderNumber + 1;
};

/**
 * Builds and returns the HTML markup for an order tab button item.
 *
 * The generated structure includes:
 * - An optional `id` on the `<button>` element.
 * - A base set of tab/button classes, with optional active state styling.
 * - An optional left icon (custom icon class or default status dot).
 * - Tab title content.
 * - An optional close button.
 *
 * @param {string} [btnID=""] - Optional `id` attribute for the generated button.
 * @param {boolean} [active=false] - Whether the tab should be rendered in its active state.
 * @param {boolean} [showIcon=true] - Whether to display the left-side icon area.
 * @param {string|null} [icon=null] - Icon class name(s) for a custom `<i>` icon; if `null` and `showIcon` is `true`, a default circular dot is rendered.
 * @param {boolean} [showCloseBtn=true] - Whether to render the close control on the right side of the tab.
 * @param {string} [buttonContent=""] - Inner HTML/text for the tab title content.
 * @returns {string} HTML string representing a `<li>` nav item containing the configured tab button.
 */
const createOrderButton = (
	btnID = "",
	active = false,
	showIcon = true,
	icon = null,
	showCloseBtn = true,
	buttonContent = "",
) => {
	let btnClasses =
		"nav-link d-flex align-items-center gap-2 py-1 ps-3 pe-2 border rounded-3 text-nowrap order-tab-btn";
	if (active) btnClasses += " active";

	let leftIcon = "";
	if (showIcon) {
		leftIcon = icon
			? `<i class="tab-btn-icon ${icon} flex-shrink-0"></i>`
			: `<span class="tab-btn-icon rounded-circle flex-shrink-0" style="width: 6px; height: 6px; background-color: currentColor;"></span>`;
	}

	return `
    <li class="nav-item" role="presentation">
        <button ${btnID ? `id="${btnID}"` : ""} class='${btnClasses}' style="font-size: 0.85rem;" type="button" role="tab">
            ${leftIcon}
            <div class="tab-title ${!showCloseBtn ? "pe-2" : ""}">
                ${buttonContent}
            </div>
            ${
				showCloseBtn
					? `<div class="btn-close ms-1 flex-shrink-0 close-tab-btn" style="font-size: 0.60rem;" tabindex="-1"></div>`
					: ""
			}
        </button>
    </li>
    `;
};

/**
 * Persists tab list, active tab, and next counter in LocalStorage.
 *
 * @param {JQuery<HTMLElement>} tabsBtnsContainer
 * @returns {void}
 */
const saveTabsState = (tabsBtnsContainer) => {
	const tabs = tabsBtnsContainer
		.find(".order-tab-btn")
		.map(function () {
			const tabButton = $(this);
			const tabId = tabButton.attr("id") || "";
			const tabText = tabButton.find(".tab-title").text().trim();

			return {
				id: tabId,
				text: tabText || getDefaultTabTextById(tabId),
			};
		})
		.get()
		.filter((tab) => tab.id);

	if (!tabs.length) {
		return;
	}

	const activeOrderId =
		tabsBtnsContainer.find(".order-tab-btn.active").attr("id") ||
		tabs[0].id;

	const nextOrderCounter = getNextOrderCounter(tabs);
	state.orderCounter = nextOrderCounter;

	localStorage.setItem(
		TABS_STORAGE_KEY,
		JSON.stringify({
			tabs,
			activeOrderId,
			orderCounter: nextOrderCounter,
		}),
	);
};

/**
 * Restores tabs from LocalStorage and rebuilds tab markup.
 *
 * @param {JQuery<HTMLElement>} tabsBtnsContainer
 * @returns {string|null} Active order id restored from storage or null.
 */
const loadTabsState = (tabsBtnsContainer) => {
	const savedTabsState = localStorage.getItem(TABS_STORAGE_KEY);
	if (!savedTabsState) {
		return null;
	}

	try {
		const parsedState = JSON.parse(savedTabsState);
		const tabs = Array.isArray(parsedState?.tabs)
			? parsedState.tabs.filter((tab) => tab?.id)
			: [];

		if (!tabs.length) {
			return null;
		}

		tabsBtnsContainer.empty();

		const fallbackActiveOrderId = tabs[0].id;
		const activeOrderId =
			tabs.find((tab) => tab.id === parsedState?.activeOrderId)?.id ||
			fallbackActiveOrderId;

		tabs.forEach((tab) => {
			const isActive = tab.id === activeOrderId;
			const buttonText = tab.text || getDefaultTabTextById(tab.id);

			tabsBtnsContainer.append(
				createOrderButton(
					tab.id,
					isActive,
					isActive,
					null,
					true,
					buttonText,
				),
			);
		});

		state.orderCounter = getNextOrderCounter(tabs);

		return activeOrderId;
	} catch (error) {
		console.error(
			"No se pudo restaurar el estado de pestañas de órdenes:",
			error,
		);
		return null;
	}
};

/**
 * Initializes dynamic behavior for sales order tabs in the UI.
 *
 * This function wires up all tab-related interactions for the sales order screen:
 * - Handles creation of new order tabs from the "new order" button.
 * - Ensures only one tab is active at a time and updates the active indicator.
 * - Enables tab switching through delegated click events.
 * - Manages tab closing with safety checks to prevent removing the last tab.
 * - Automatically reactivates a remaining tab when the active one is closed.
 * - Shows or hides close buttons depending on total tab count.
 * - Scrolls the tab container to the end when a new tab is added.
 * - Synchronizes external order/cart state through `switchActiveOrder` and `deleteOrderCart`.
 *
 * Guard clauses prevent initialization when the tab container is missing.
 *
 * @function initializeSalesOrderTabs
 * @returns {void} This function does not return a value; it attaches event handlers and mutates DOM/state.
 */
export function initializeSalesOrderTabs() {
	const tabsBtnsContainer = $("#order-tabs-container");
	const newOrderBtn = $("#new-order-btn");

	if (!tabsBtnsContainer.length) return;

	// Show close buttons only when there is more than one tab.
	const updateCloseButtons = () => {
		const allTabs = tabsBtnsContainer.find(".nav-item");
		if (allTabs.length === 1) {
			// Hide close button when it is the only tab.
			allTabs.find(".close-tab-btn").hide();
			allTabs.find(".tab-title").removeClass("pe-0").addClass("pe-2");
		} else {
			// Show close buttons when there is more than one tab.
			allTabs.find(".close-tab-btn").show();
			allTabs.find(".tab-title").removeClass("pe-2");
		}
	};

	// Deactivate all tabs before activating a new one.
	const deactivateAllTabs = () => {
		const activeTabs = tabsBtnsContainer.find(".order-tab-btn.active");
		activeTabs.removeClass("active");
		activeTabs.find(".tab-btn-icon").remove(); // Remove active indicator icon from all tabs.
	};

	// Scroll tab container to the end after adding a new tab.
	const scrollTabsToEnd = () => {
		const container = tabsBtnsContainer.get(0);
		if (!container) return;

		container.scrollTo({
			left: container.scrollWidth,
			behavior: "smooth",
		});
	};

	// Create a new order tab from the "new order" button.
	if (newOrderBtn.length) {
		newOrderBtn.on("click", function () {
			state.orderCounter = getNextOrderCounter(
				tabsBtnsContainer
					.find(".order-tab-btn")
					.map(function () {
						return $(this).attr("id") || "";
					})
					.get(),
			);

			const tabBtnId = state.orderCounter.toString().padStart(4, "0");
			const newTabBtnId = `order-tab-${tabBtnId}`;
			const newTabBtnContent = `ORD-${tabBtnId}`;

			const newTabHTML = createOrderButton(
				newTabBtnId,
				true,
				true,
				null,
				true,
				newTabBtnContent,
			);

			deactivateAllTabs();
			tabsBtnsContainer.append(newTabHTML);

			scrollTabsToEnd();
			updateCloseButtons();

			switchActiveOrder(newTabBtnId); // Sync active order/cart with the new tab.
			saveTabsState(tabsBtnsContainer);
		});
	}

	// Switch active tab on click using event delegation.
	tabsBtnsContainer.on("click", ".order-tab-btn", function (e) {
		// Ignore close button clicks to avoid interfering with tab activation.
		if ($(e.target).hasClass("close-tab-btn")) return;

		// Ignore clicks on the already active tab.
		if ($(this).hasClass("active")) return;

		deactivateAllTabs();
		$(this).addClass("active");

		// Render active indicator icon for the newly active tab.
		if (!$(this).find(".tab-btn-icon").length) {
			$(this).prepend(
				`<span class="tab-btn-icon rounded-circle flex-shrink-0" style="width: 6px; height: 6px; background-color: currentColor;"></span>`,
			);
		}

		const orderId = $(this).attr("id");
		console.log("Switching to order:", orderId);

		switchActiveOrder(orderId);
		saveTabsState(tabsBtnsContainer);
	});

	// Close tab on close button click using event delegation.
	tabsBtnsContainer.on("click", ".close-tab-btn", function (e) {
		SwalModal.fire({
			title: "¿Estás seguro de querer eliminar esta orden?",
			text: "Esta acción no se puede deshacer.",
			icon: SwalNotificationTypes.WARNING,
			showCancelButton: true,
			confirmButtonText: "Sí, eliminar",
			cancelButtonText: "Cancelar",
		}).then((result) => {
			// If confirmed, close the tab.
			if (result.isConfirmed) {
				e.stopPropagation(); // Prevent bubbling to tab activation handler.

				// Safety check to prevent removing the last remaining tab.
				const totalTabs = tabsBtnsContainer.find(".nav-item").length;
				if (totalTabs <= 1) return; // Keep at least one tab to avoid breaking the UI.

				const tabLi = $(this).closest(".nav-item");
				const tabBtn = tabLi.find(".order-tab-btn");
				const wasActive = tabBtn.hasClass("active");

				tabLi.remove();

				// If active tab is removed, activate the last remaining tab.
				if (wasActive) {
					const lastTab = tabsBtnsContainer
						.find(".order-tab-btn")
						.last();
					lastTab.click(); // Reuse existing activation logic.
				}

				updateCloseButtons();
				deleteOrderCart(tabBtn.attr("id")); // Remove corresponding cart state.
				saveTabsState(tabsBtnsContainer);
			} else {
				return false;
			}
		});
	});

	const restoredActiveOrderId = loadTabsState(tabsBtnsContainer);

	if (!restoredActiveOrderId) {
		state.orderCounter = getNextOrderCounter(
			tabsBtnsContainer
			.find(".order-tab-btn")
			.map(function () {
				return $(this).attr("id") || "";
			})
			.get()
			.filter(Boolean),
		);
	}

	// Initial close-button visibility based on initial tab count.
	updateCloseButtons();

	const initialActiveOrderId =
		restoredActiveOrderId ||
		tabsBtnsContainer.find(".order-tab-btn.active").attr("id") ||
		tabsBtnsContainer.find(".order-tab-btn").first().attr("id");

	if (initialActiveOrderId) {
		switchActiveOrder(initialActiveOrderId);
	}

	saveTabsState(tabsBtnsContainer);
}
