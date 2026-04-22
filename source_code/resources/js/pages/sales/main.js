import { initializeSalesCart, getActiveSaleData, clearActiveCart } from "./cart.js";
import { initializeSalesProducts } from "./products.js";
import { initializeSalesOrderTabs } from "./orders.js";
import { setLoadingState } from "../../utils/utils.js";
import { formatTimeAgo, PaymentStatus, processSale, startTimeUpdateInterval } from "./api.js";
import { initializeHotkeys } from "./hotkeys.js";

// Initial sale data structure
const SaleData = {
	payment_status: PaymentStatus.PAID, // Default to pending; this can be updated based on user input
	date: new Date().toISOString(),
	total: 8500,
	sale_details: [],
	payment_details: [],
};

/**
 * Updates the sales page clock element with the current local time.
 * If the target element does not exist, no update is performed.
 */
const tickClock = () => {
	const currentTimeElement = $("#current-time");
	if (currentTimeElement.length) {
		const now = new Date();
		const formattedTime = now.toLocaleTimeString();
		currentTimeElement.text(formattedTime);
	}
};

/**
 * Updates the "last sale" label with a relative timestamp (e.g., "2 minutes ago").
 *
 * Reads the original sale timestamp from the `data-sale-time` attribute in
 * `#last-sale-time`, renders the relative text once, and then starts a periodic
 * updater so the displayed value stays current over time.
 *
 * If the target element or timestamp is missing, no action is performed.
 */
const updateLastSaleTime = () => {
	const lastSaleTimeElement = $("#last-sale-time");
	if (lastSaleTimeElement.length) {
		const lastSaleTime = lastSaleTimeElement.data("sale-time");
		if (lastSaleTime) {
			const relativeTime = formatTimeAgo(lastSaleTime);
			lastSaleTimeElement.text(relativeTime);

			// Start interval to update the time every minute
			startTimeUpdateInterval(lastSaleTimeElement, lastSaleTime);
		}
	}
}

$(() => {
	// Initialize all sales-related components
    initializeSalesProducts();
    initializeSalesCart();
	initializeSalesOrderTabs();
	initializeHotkeys();

	// Start the clock
	tickClock();
	setInterval(tickClock, 1000); // Update the clock every second

	// Update the last sale time display
	updateLastSaleTime();

	// Handle finalize sale button click
    const finalizeSaleButton = $("#finalize-sale-button");
    if (finalizeSaleButton.length) {
        finalizeSaleButton.on("click", async () => {
			// Set SaleData with current cart data
			SaleData.sale_details = getActiveSaleData().sale_details;
			SaleData.total = Number(getActiveSaleData().total || 0);

			SaleData.payment_details = SaleData.payment_status === PaymentStatus.PAID
				? SaleData.payment_details = [
					{
						method: "cash", // Assuming cash payment for simplicity; this can be dynamic based on user input
						amount: SaleData.total, // Full amount paid in cash; adjust if partial payments or multiple methods are implemented
						change_amount: 0, // Assuming no change for simplicity; calculate if needed based on payment amount and total
					},
				]
				: [];

			// Process the sale with current payment details and status
			processSale(SaleData.payment_details, SaleData.payment_status);
		});
    }
});
