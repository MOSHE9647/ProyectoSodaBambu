import { setLoadingState } from "../../utils/utils.js";
import {
	getActiveSaleData,
	clearActiveCart,
	syncFinalizeSaleButtonState,
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
export const processSale = async (paymentDetails, paymentStatus = PaymentStatus.PAID) => {
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
				"X-CSRF-TOKEN": typeof csrfToken !== 'undefined' ? csrfToken : '',
			},
			body: JSON.stringify(payload),
		});

		if (response.ok) {
			const responseData = await response.json();

			// TODO: Remove the following line after confirming the response structure and content
			alert(JSON.stringify(responseData, null, 2)); // Temporary alert for testing purposes

			SwalToast.fire({
				icon: SwalNotificationTypes.SUCCESS,
				title: responseData.message || "Venta registrada con éxito.",
				// TODO: Delete the following line
				text: responseData.data.payment_status
					? `Estado de pago: ${responseData.data.payment_status}`
					: "",
			});

			// Clear the active cart after a successful sale
			clearActiveCart();

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