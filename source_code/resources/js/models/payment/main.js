import { fetchWithErrorHandling } from "../../utils/error-handling";
import { SwalModal, SwalToast } from "../../utils/sweetalert";

/**
 * Defines the available payment methods in the system.
 * This constant can be used throughout the application to ensure consistency when referring to payment methods.
 * Each key represents a payment method type, and its value is the corresponding string identifier.
 */
const PAYMENT_METHODS = {
    CASH: 'cash',
    CARD: 'card',
    SINPE: 'sinpe',
};

/**
 * Fetches the HTML content for the payment details modal from the server.
 *
 * @param {number|string} purchaseTotalAmount - The total amount of the purchase to be sent as a parameter.
 * @returns {Promise<string|undefined>} The HTML content for the modal if successful, otherwise undefined.
 *
 * This function constructs the URL for the payment modal using the provided purchase total amount,
 * then fetches the modal content from the server using fetchWithErrorHandling. If the response is empty
 * or an error occurs, it displays an error toast using SwalToast and logs the error to the console.
 */
const fetchPaymentDetailsModalContent = async (purchaseTotalAmount) => {
    try {
        const url = route('payment-modal', { paymentTotal: purchaseTotalAmount });
        const response = await fetchWithErrorHandling(url, {}, 
            'Error al cargar el formulario de detalles de pago. Por favor, inténtelo de nuevo.'
        );

        const html = await response.text();
        if (!html) throw new Error('La respuesta del servidor está vacía.');

        return html;
    } catch (error) {
        console.error('Error fetching payment details modal content:', error);
        SwalToast.fire({
            icon: 'error',
            title: error.message || 'Ocurrió un error al cargar el formulario de detalles de pago.',
        });
    }
};

/**
 * Extracts payment details from the payment form in the DOM.
 *
 * This function queries all elements with the class ".payment-item" and retrieves
 * their payment type, amount, reference (if applicable), and change amount (if cash).
 *
 * @returns {Array<Object>} An array of payment detail objects, each containing:
 *   - method: The payment method type (e.g., 'cash', 'card').
 *   - amount: The payment amount as a string.
 *   - reference: The payment reference if not cash, otherwise null.
 *   - changeAmount: The change amount if cash, otherwise null.
 */
const getPaymentDetailsFromForm = () => {
    const paymentDetails = [];
    const paymentItems = document.querySelectorAll(".payment-item");

    paymentItems.forEach((item) => {
        const type = item.getAttribute("data-payment-type");
        const amount = parseInt(item.querySelector(".payment-item-amount").textContent.trim().replace(/[^0-9,-]+/g, "")) || 0;
        const reference = item.querySelector(".payment-item-reference")
            ? item.querySelector(".payment-item-reference").textContent.trim() || ""
            : null;
        const changeAmount = parseInt(document.getElementById("change_amount").textContent.trim().replace(/[^0-9,]+/g, "")) || 0;

        paymentDetails.push({
            method: type,
            amount: amount,
            reference: type !== PAYMENT_METHODS.CASH ? reference : '',
            change_amount: type === PAYMENT_METHODS.CASH ? changeAmount : 0,
        });
    });

    return paymentDetails;
};

/**
 * Displays the payment details form inside a modal and returns the collected payment details.
 *
 * This function fetches the payment details HTML for the provided purchase total amount,
 * opens a SweetAlert modal containing the form, and listens for the form's submit event.
 * When the form is submitted, it collects payment information from the DOM and resolves
 * a Promise with an array of payment detail objects.
 *
 * @param {number|string} purchaseTotalAmount - The total amount of the purchase to display in the form.
 * @returns {Promise<Array<Object>|undefined>} A Promise that resolves to an array of payment detail objects
 *   when the form is submitted, or undefined if the modal content could not be loaded.
 */
export async function showPaymentDetailsFormModal(purchaseTotalAmount) {
    const html = await fetchPaymentDetailsModalContent(purchaseTotalAmount);
    if (html) {
        const modal = SwalModal.fire({
			title: "Procesar Pago",
			html: html,
            showCloseButton: true,
			showCancelButton: false,
			showConfirmButton: false,
			allowOutsideClick: false,
			allowEscapeKey: false,
            customClass: {
                popup: 'swal-popup w-auto h-auto',
                title: 'd-flex justify-content-start align-items-center border-bottom pb-3 mb-3',
                closeButton: 'swal-close-btn fs-3',
                htmlContainer: 'pb-0 overflow-x-hidden text-start',
                confirmButton: 'btn btn-primary mx-1',
                cancelButton: 'btn btn-danger mx-1',
                icon: 'mb-4',
            },
		});

        let paymentDetails = null;

        // Handle form submission within the modal
        $(document)
            .off("submit", "#payment-details-form")
            .on("submit", "#payment-details-form", function (e) {
                e.preventDefault();
                paymentDetails = getPaymentDetailsFromForm();
            });
        
        return new Promise((resolve) => {
            const checkPaymentDetailsInterval = setInterval(() => {
                if (paymentDetails) {
                    modal.close();
                    clearInterval(checkPaymentDetailsInterval);
                    resolve(paymentDetails);
                }
            }, 500);
        });
    }
}