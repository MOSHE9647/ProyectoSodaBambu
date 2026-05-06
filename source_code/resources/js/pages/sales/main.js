import { formatTimeAgo, PaymentMethods, PaymentStatus, processSale, startTimeUpdateInterval } from "./api.js";
import { initializeSalesCart, getActiveSaleData, clearActiveCart } from "./cart.js";
import { initializeCashRegister } from "./cash-register.js";
import { initializeSalesProducts } from "./products.js";
import { initializeSalesOrderTabs } from "./orders.js";
import { setLoadingState } from "../../utils/utils.js";
import { initializeHotkeys } from "./hotkeys.js";
import { openPaymentModal } from "./payment.js";
import { SwalModal } from "../../utils/sweetalert.js";

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
	// Initialize all sales-related components
	initializeCashRegister();
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
			const saleData = getActiveSaleData();

			openPaymentModal({
				total: saleData.total,
				title: "Procesar Venta",
				loadingId: "finalize-sale",
				onComplete: async (paymentDetails, totalTendered) => {
					SwalModal.showLoading();
					const saleResult = await processSale(paymentDetails);
					if (saleResult?.success) {
						SwalModal.close();
				
						await SwalModal.fire({
							title: "",
							html: getSaleSuccessSummaryHtml(saleResult),
							width: 760,
							background: "#ffffff",
							color: "#1f1f1f",
							showConfirmButton: true,
							showCancelButton: false,
							confirmButtonText: "Cerrar",
							allowEscapeKey: true,
							allowOutsideClick: true,
							customClass: {
								popup: "swal-popup w-auto h-auto",
								title: "d-flex justify-content-start align-items-center border-bottom pb-3 mb-3",
								closeButton: "swal-close-btn fs-3",
								htmlContainer: "w-auto h-auto p-1 overflow-x-hidden",
								confirmButton: "btn btn-success mx-1",
								cancelButton: "btn btn-outline-secondary mx-1",
								icon: "mb-4",
							},
						});
					} else {
						SwalModal.hideLoading();
					}
				}
			});
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
