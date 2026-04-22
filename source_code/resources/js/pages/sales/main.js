import { initializeSalesCart } from "./cart.js";
import { initializeSalesProducts } from "./products.js";
import { initializeSalesOrderTabs } from "./orders.js";
import { showPaymentModal } from "./payment.js";

$(() => {
	// Initialize all sales-related components
    initializeSalesProducts();
    initializeSalesCart();
	initializeSalesOrderTabs();

	// Handle finalize sale button click
    const finalizeSaleButton = $("#finalize-sale-button");
    if (finalizeSaleButton.length) {
        finalizeSaleButton.on("click", async () => {
			showPaymentModal();
		});
    }
});
