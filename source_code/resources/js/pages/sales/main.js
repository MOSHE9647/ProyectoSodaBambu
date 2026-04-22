import { initializeSalesCart, getActiveSaleData, clearActiveCart } from "./cart.js";
import { initializeSalesProducts } from "./products.js";
import { initializeSalesOrderTabs } from "./orders.js";
import { setLoadingState } from "../../utils/utils.js";
import { PaymentStatus, processSale } from "./api.js";
import { initializeHotkeys } from "./hotkeys.js";

// Initial sale data structure
const SaleData = {
	payment_status: PaymentStatus.PENDING, // Default to pending; this can be updated based on user input
	date: new Date().toISOString(),
	total: 8500,
	sale_details: [],
	payment_details: [],
};

$(() => {
	// Initialize all sales-related components
    initializeSalesProducts();
    initializeSalesCart();
	initializeSalesOrderTabs();
	initializeHotkeys();

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
