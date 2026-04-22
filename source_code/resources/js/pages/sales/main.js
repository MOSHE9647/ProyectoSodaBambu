import { formatTimeAgo, PaymentMethods, PaymentStatus, processSale, startTimeUpdateInterval } from "./api.js";
import { initializeSalesCart, getActiveSaleData, clearActiveCart } from "./cart.js";
import { initializeSalesProducts } from "./products.js";
import { initializeSalesOrderTabs } from "./orders.js";
import { setLoadingState } from "../../utils/utils.js";
import { initializeHotkeys } from "./hotkeys.js";
import { SwalModal } from "../../utils/sweetalert.js";

/**
 * Mutable sale draft used as payload source when finalizing a sale.
 *
 * @type {{
 *   payment_status: string,
 *   date: string,
 *   total: number,
 *   sale_details: Array<object>,
 *   payment_details: Array<object>
 * }}
 */
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

			// Start periodic updates so relative time stays current.
			startTimeUpdateInterval(lastSaleTimeElement, lastSaleTime);
		}
	}
}

/**
 * Boots all sales page modules and wires primary UI events.
 *
 * @returns {void}
 */
$(() => {
	// Initialize all sales-related modules.
    initializeSalesProducts();
    initializeSalesCart();
	initializeSalesOrderTabs();
	initializeHotkeys();

	// Start current time ticker.
	tickClock();
	setInterval(tickClock, 1000); // Refresh clock every second.

	// Render and start auto-updating the last sale relative time.
	updateLastSaleTime();

	// Handle finalize sale action.
    const finalizeSaleButton = $("#finalize-sale-button");
    if (finalizeSaleButton.length) {
        finalizeSaleButton.on("click", async () => {
			// Populate payload from active cart state.
			SaleData.sale_details = getActiveSaleData().sale_details;
			SaleData.total = Number(getActiveSaleData().total || 0);

			SaleData.payment_details = SaleData.payment_status === PaymentStatus.PAID
				? SaleData.payment_details = [
					{
						method: PaymentMethods.CARD, // Assuming cash payment for simplicity; this can be dynamic based on user input
						amount: SaleData.total, // Full amount paid in cash; adjust if partial payments or multiple methods are implemented
						change_amount: 0, // Assuming no change for simplicity; calculate if needed based on payment amount and total
						reference: String(Math.floor(10000000 + Math.random() * 90000000)), // Numeric 8-digit reference
					},
				]
				: [];

			// Submit sale with selected payment details/status.
			processSale(SaleData.payment_details, SaleData.payment_status);
		});
    }

	const rePrintLastSaleButton = $("#reprint-last-sale");
	if (rePrintLastSaleButton.length) {
		const SweetModalCustomClass = {
			title: "d-flex justify-content-center align-items-center border-bottom pb-3 mb-3",
			popup: "swal-popup w-auto h-auto",
			closeButton: "swal-close-btn fs-3",
			htmlContainer: "w-auto h-auto p-1 overflow-x-hidden",
			confirmButton: "btn btn-primary mx-1",
			cancelButton: "btn btn-danger mx-1",
			icon: "mb-4",
		};

		rePrintLastSaleButton.on("click", () => {
			SwalModal.fire({
				title: "Reimprimir última venta",
				text: "¿Deseas reimprimir el recibo de la última venta?",
				icon: "question",
				showCancelButton: true,
				customClass: SweetModalCustomClass,
				confirmButtonText: "Sí, reimprimir",
				cancelButtonText: "No, cancelar",
			}).then((result) => {
				if (result.isConfirmed) {
					// Placeholder for reprint logic; implement actual reprint functionality here.
					SwalModal.fire({
						title: "Funcionalidad no implementada",
						text: "La función de reimprimir la última venta aún no está implementada.",
						icon: "info",
						customClass: SweetModalCustomClass,
					});
					// Example of triggering a click on the finalize button to simulate reprint; replace with actual reprint logic.
				}
			});
		});
	}
});
