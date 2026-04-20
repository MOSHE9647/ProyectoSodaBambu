import { switchActiveOrder, deleteOrderCart } from "./cart.js";

// General state for the orders page
const state = {
	orderCounter: 2, // Starts at 2 because we assume there's already an "ORD-0001" in the HTML on load
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

	// Close button visibility logic: show close buttons only if there's more than one tab
	const updateCloseButtons = () => {
		const allTabs = tabsBtnsContainer.find(".nav-item");
		if (allTabs.length === 1) {
			// Hide close button if it's the only tab
			allTabs.find(".close-tab-btn").hide();
			allTabs.find(".tab-title").removeClass("pe-0").addClass("pe-2");
		} else {
			// Show close buttons if there's more than one tab
			allTabs.find(".close-tab-btn").show();
			allTabs.find(".tab-title").removeClass("pe-2");
		}
	};

	// Auxiliary function to deactivate all tabs before activating a new one
	const deactivateAllTabs = () => {
		const activeTabs = tabsBtnsContainer.find(".order-tab-btn.active");
		activeTabs.removeClass("active");
		activeTabs.find(".tab-btn-icon").remove(); // Remove the active indicator icon from all tabs
	};

	// Auxiliary function to scroll the tab container to the end (used after adding a new tab)
	const scrollTabsToEnd = () => {
		const container = tabsBtnsContainer.get(0);
		if (!container) return;

		container.scrollTo({
			left: container.scrollWidth,
			behavior: "smooth",
		});
	};

	// Add new tab on "new order" button click
	if (newOrderBtn.length) {
		newOrderBtn.on("click", function () {
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
			state.orderCounter++;

			switchActiveOrder(newTabBtnId); // Change active order/cart in the system to match the newly created tab
		});
	}

	// Switch active tab on click (Event Delegation)
	tabsBtnsContainer.on("click", ".order-tab-btn", function (e) {
		// Ignore clicks on the close button within the tab to prevent interference with tab activation logic
		if ($(e.target).hasClass("close-tab-btn")) return;

		// Ignore if the clicked tab is already active to prevent unnecessary re-rendering and state changes
		if ($(this).hasClass("active")) return;

		deactivateAllTabs();
		$(this).addClass("active");

		// Render the active indicator icon for the newly active tab
		if (!$(this).find(".tab-btn-icon").length) {
			$(this).prepend(
				`<span class="tab-btn-icon rounded-circle flex-shrink-0" style="width: 6px; height: 6px; background-color: currentColor;"></span>`,
			);
		}

		const orderId = $(this).attr("id");
		console.log("Switching to order:", orderId);
		
        switchActiveOrder(orderId);
	});

	// Close tab on close button click (Event Delegation)
	tabsBtnsContainer.on("click", ".close-tab-btn", function (e) {
		e.stopPropagation(); // Prevent the click from bubbling up to the tab button's click handler which would activate the tab

		// Safety check to prevent removing the last remaining tab
		const totalTabs = tabsBtnsContainer.find(".nav-item").length;
		if (totalTabs <= 1) return; // Double-check to ensure we don't remove the last tab, which would break the UI

		const tabLi = $(this).closest(".nav-item");
		const tabBtn = tabLi.find(".order-tab-btn");
		const wasActive = tabBtn.hasClass("active");

		tabLi.remove();

		// If we remove the active tab, force the activation of the last remaining tab
		if (wasActive) {
			const lastTab = tabsBtnsContainer.find(".order-tab-btn").last();
			lastTab.click(); // Simulate the click to execute the activation logic
		}

		updateCloseButtons();
        deleteOrderCart(tabBtn.attr("id")); // Delete the order/cart in the system that corresponds to the closed tab
	});

	// Initial call to set the correct visibility of close buttons based on the initial number of tabs
	updateCloseButtons();
}
