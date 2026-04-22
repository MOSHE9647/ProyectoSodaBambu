import { setLoadingState } from "../../utils/utils.js";
import {
	getActiveSaleData,
	clearActiveCart,
	syncFinalizeSaleButtonState,
	formatCurrency,
} from "./cart.js";
import { SwalNotificationTypes, SwalToast } from "../../utils/sweetalert.js";

/**
 * Available payment methods used in the sales flow.
 *
 * @readonly
 * @enum {string}
 * @property {string} CASH - Payment made with cash.
 * @property {string} CARD - Payment made with card.
 * @property {string} SINPE - Payment made using SINPE.
 */
export const PaymentMethods = {
	CASH: "cash",
	CARD: "card",
	SINPE: "sinpe",
};

/**
 * Human-readable labels for each payment method.
 *
 * @readonly
 * @enum {string}
 */
export const PaymentMethodLabels = {
	[PaymentMethods.CASH]: "Efectivo",
	[PaymentMethods.CARD]: "Tarjeta",
	[PaymentMethods.SINPE]: "SINPE",
};

/**
 * Returns the display text for a payment method.
 * @param {string} paymentMethod - The payment method value.
 * @returns {string} The text to show in the UI.
 */
export const getPaymentMethodLabel = (paymentMethod) =>
	PaymentMethodLabels[paymentMethod] || paymentMethod || "Desconocido";

/**
 * Represents the possible payment states for a sale transaction.
 *
 * @readonly
 * @enum {string}
 * @property {string} PAID - Indicates that the payment has been completed.
 * @property {string} PENDING - Indicates that the payment is still awaiting completion.
 */
export const PaymentStatus = {
	PAID: "paid",
	PENDING: "pending",
};

let timeUpdateInterval = null;

/**
 * Formats a date string into a relative "time ago" format.
 * @param {string} dateString - The ISO date string to compare.
 * @returns {string} Relative time string (e.g., "hace 5 min").
 */
export const formatTimeAgo = (dateString) => {
	if (!dateString) return "Fecha desconocida";

	const saleDate = new Date(dateString);
	const timeDifference = Math.floor((new Date() - saleDate) / 60000); // Difference in minutes

	if (timeDifference < 1) return "hace unos segundos";
	if (timeDifference < 60) return `hace ${timeDifference} min`;

	const hoursDifference = Math.floor(timeDifference / 60);
	if (hoursDifference < 24) return `hace ${hoursDifference} hora(s)`;

	const daysDifference = Math.floor(hoursDifference / 24);
	return `hace ${daysDifference} día(s)`;
};

/**
 * Starts an interval to dynamically update the time UI element.
 * @param {jQuery} element - The jQuery element to update.
 * @param {string} dateString - The ISO date string of the sale.
 */
export const startTimeUpdateInterval = (element, dateString) => {
	// Clear any existing interval to prevent overlapping timers
	if (timeUpdateInterval) clearInterval(timeUpdateInterval);
	if (!dateString) return;

	// Update the DOM element every 60 seconds (60000ms)
	timeUpdateInterval = setInterval(() => {
		element.text(formatTimeAgo(dateString));
	}, 60000);
};

/**
 * Updates the Payment Status UI in the Status Bar.
 * @param {Object} saleData - The payload containing the latest sale information.
 */
const updatePaymentStatusUI = (saleData) => {
	console.log("Updating payment status UI with sale data:", saleData);

    const lastSaleOrderIdElement = $("#last-sale-order-id");
    const lastSalePaymentMethodElement = $("#last-sale-payment-method");
    const lastSalePaymentAmountElement = $("#last-sale-payment-amount");
    const lastSaleItemsElement = $("#last-sale-items");
    const lastSaleTimeElement = $("#last-sale-time");

    if (
        !lastSaleOrderIdElement.length ||
        !lastSalePaymentMethodElement.length ||
        !lastSalePaymentAmountElement.length ||
        !lastSaleItemsElement.length ||
        !lastSaleTimeElement.length
    ) {
        console.error("One or more required elements for updating payment status UI not found.");
        return;
    }

    // Update general shared details (Items and Time)
    const totalItems = saleData.sale_details ? saleData.sale_details.length : 0;
    lastSaleItemsElement.text(`${totalItems} ítem(s)`);
    
    // Set initial time and start dynamic updater
    const timeString = saleData.payment_status === PaymentStatus.PENDING && !saleData.date 
        ? "Pago pendiente" 
        : formatTimeAgo(saleData.date);
        
    lastSaleTimeElement.text(timeString);
    startTimeUpdateInterval(lastSaleTimeElement, saleData.date);

    // Handle specific UI for Paid Status
    lastSaleOrderIdElement.text(saleData.invoice_number || "N/A");
	lastSalePaymentAmountElement.text(formatCurrency(saleData.total));

    // Handle specific UI for Pending Status
    if (saleData.payment_status !== PaymentStatus.PAID) {
        lastSalePaymentMethodElement.html(`
			<i class="bi bi-hourglass-split text-warning"></i>
			<span class="text-warning">Pago Pendiente</span>
			<span class="text-muted">·</span>
		`);
		lastSalePaymentMethodElement.attr("title", "Pago Pendiente");
		return;
	}
    
    if (saleData.payments && saleData.payments.length > 0) {
        const paymentMethod = saleData.payments[0].method;
        const paymentAmount = saleData.payments[0].amount || 0;

        const methodIcons = {
            [PaymentMethods.CASH]: '<i class="bi bi-cash-coin text-success"></i>',
            [PaymentMethods.CARD]: '<i class="bi bi-credit-card text-primary"></i>',
            [PaymentMethods.SINPE]: '<x-icons.sinpe-movil width="28" height="18" />'
        };

        lastSalePaymentMethodElement.html(methodIcons[paymentMethod] || '<i class="bi bi-question-circle text-muted"></i>');
		lastSalePaymentMethodElement.attr("title", `Pago vía ${getPaymentMethodLabel(paymentMethod)}`);

        lastSalePaymentAmountElement.text(formatCurrency(paymentAmount));
    } else {
        lastSalePaymentMethodElement.html('<i class="bi bi-x-circle text-danger"></i>');
		lastSalePaymentMethodElement.attr("title", "Sin detalles de pago");

        lastSalePaymentAmountElement.text(formatCurrency(0));
    }
};

/**
 * Processes the current active sale by validating cart data, sending the sale payload
 * to the backend, and handling UI feedback throughout the request lifecycle.
 *
 * @async
 * @function processSale
 * @param {Object|Array} paymentDetails - Payment information to be included in the sale
 * payload (for example, payment method, reference, or transaction metadata).
 * @param {string} [paymentStatus=PaymentStatus.PAID] - Payment status to assign to the sale
 * (e.g., `PaymentStatus.PAID` or `PaymentStatus.PENDING`).
 * @returns {Promise<boolean>} Resolves to `true` when the sale is successfully created;
 * otherwise resolves to `false`.
 *
 * @description
 * - Validates that an active cart exists and contains at least one sale item.
 * - Builds a payload including payment status, timestamp, total amount, sale details,
 *   and payment details.
 * - Sends a `POST` request to `/sales` with JSON content and CSRF token headers.
 * - Shows success/error toast notifications depending on the outcome.
 * - Clears the active cart after a successful sale.
 * - Toggles the `"finalize-sale"` loading state before and after the request.
 */
export const processSale = async (
	paymentDetails,
	paymentStatus = PaymentStatus.PAID,
) => {
	const cartData = getActiveSaleData();

	// Ensure we have cart data and at least one item in the sale details before proceeding
	if (!cartData || cartData.sale_details.length === 0) {
		SwalToast.fire({
			icon: SwalNotificationTypes.ERROR,
			title: "El carrito está vacío.",
		});
		return false;
	}

	// Build the payload to send to the server
	const payload = {
		payment_status: paymentStatus,
		date: new Date().toISOString(),
		total: cartData.total,
		sale_details: cartData.sale_details,
		payment_details: paymentDetails,
	};

	// Set loading state to true while processing the sale
	setLoadingState("finalize-sale", true);

	try {
		const url = route("sales.store");
		const response = await fetch(url, {
			method: "POST",
			headers: {
				"Content-Type": "application/json",
				"X-Requested-With": "XMLHttpRequest",
				"X-CSRF-TOKEN":
					typeof csrfToken !== "undefined" ? csrfToken : "",
			},
			body: JSON.stringify(payload),
		});

		if (response.ok) {
			const responseData = await response.json();
			console.log("Sale processed successfully:", responseData);

			SwalToast.fire({
				icon: SwalNotificationTypes.SUCCESS,
				title: responseData.message || "Venta registrada con éxito.",
			});

			// Clear the active cart after a successful sale
			clearActiveCart();

			// Update the payment status UI with the new sale data
			updatePaymentStatusUI(responseData.data);

			// Dispatch a custom event to notify other parts of the application about the completed sale
			window.dispatchEvent(
				new CustomEvent("sales:refresh-products-after-sale", {
					detail: {
						sale: responseData?.data ?? null,
					},
				}),
			);

			return true; // Indicate success to the caller (SweetAlert Modal)
		} else {
			const errorData = await response.json();
			SwalToast.fire({
				icon: SwalNotificationTypes.ERROR,
				title: `Error ${response.status}: ${errorData.message || "Error al procesar el pago"}`,
				timer: 15000, // Extend timer for error messages
			});
			console.error("Error response from server:", errorData);
			return false;
		}
	} catch (error) {
		console.error("Error durante el flujo de venta:", error);
		SwalToast.fire({
			icon: SwalNotificationTypes.ERROR,
			title: "Error de conexión con el servidor.",
			timer: 15000, // Extend timer for error messages
		});
		return false;
	} finally {
		setLoadingState("finalize-sale", false);
		syncFinalizeSaleButtonState();
	}
};
