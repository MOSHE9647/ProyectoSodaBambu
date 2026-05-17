import { fetchWithErrorHandling } from "../../utils/error-handling";
import { SwalModal, SwalNotificationTypes, SwalToast } from "../../utils/sweetalert";
import { enableBootstrapTooltips, formatCurrency, setLoadingState } from "../../utils/utils";
import { clearFieldError, showFieldError, validateMultipleOf5 } from "../../utils/validation";

// ==================== Environment Checks ====================

if (typeof $ === "undefined") {
    throw new Error("This script requires jQuery");
}

// ======================== Constants =========================

/**
 * Payment method constants.
 * @constant {Object}
 * @property {string} CASH - Cash payment method ('cash')
 * @property {string} CARD - Card payment method ('card')
 * @property {string} SINPE - SINPE payment method ('sinpe')
 */
const PAYMENT_METHODS = {
    CASH: 'cash',
    CARD: 'card',
    SINPE: 'sinpe',
};

// ===================== DOM Selectors ========================

/**
 * Centralized DOM element retrieval to ensure fresh references
 * 
 * @returns {Object} An object containing jQuery references to various DOM elements.
 */
const getDOM = () => ({
    amountToPay: $("#amount_to_pay"),
    reference: $('#reference_number'),
    addPaymentBtn: $('#add-payment-button'),
    completePaymentBtn: $('#payment-button'),
    totalPaid: $('#total-paid'),
    totalAmount: $('#total_amount'),
    changeAmount: $('#change_amount'),
    paymentMethods: $('#payment-methods'),
    paymentDetails: $('#payment-details'),
    form: $("#payment-details-form"),
    printSwitch: $("#print_receipt_switch")
});

// ===================== API / Fetching =======================

/**
 * Fetches the HTML content for the payment details modal from the server.
 * 
 * @async
 * @param {number|string} purchaseTotalAmount - The total amount of the purchase to be sent to the server.
 * @returns {Promise<string|undefined>} The HTML content of the modal, or undefined if an error occurs.
 */
const fetchPaymentDetailsModalContent = async (purchaseTotalAmount) => {
    try {
        const url = route('receipts.payment-modal', { paymentTotal: purchaseTotalAmount });
        const response = await fetchWithErrorHandling(url, {}, 
            'Error al cargar el formulario de detalles de pago. Por favor, inténtelo de nuevo.'
        );

        const html = await response.text();
        if (!html) throw new Error('La respuesta del servidor está vacía.');

        return html;
    } catch (error) {
        console.error('Modal fetch error:', error);
        SwalToast.fire({ icon: SwalNotificationTypes.ERROR, title: error.message || 'Error al cargar detalles de pago.' });
    }
};

// ==================== State Extraction ======================

/**
 * Extracts and compiles the current payment details and settings from the DOM.
 * 
 * @returns {Object} An object containing:
 * - {Array<Object>} paymentDetails - List of extracted payment items (method, amount, reference, change_amount).
 * - {boolean} shouldPrint - Whether the receipt should be printed.
 */
const getExtractedPaymentDetails = () => {
    const dom = getDOM();
    const paymentDetails = [];
    const shouldPrint = dom.printSwitch.is(':checked');
    const changeAmount = parseInt(dom.changeAmount.text().replace(/[^0-9,]+/g, "")) || 0;

    dom.paymentDetails.find(".payment-item").each((_, item) => {
        const type = item.getAttribute("data-payment-type");
        const amount = parseInt($(item).find(".payment-item-amount").text().replace(/[^0-9,-]+/g, "")) || 0;
        const reference = $(item).find(".payment-item-reference").text().trim() || '';

        paymentDetails.push({
            method: type,
            amount,
            reference: type !== PAYMENT_METHODS.CASH ? reference : '',
            change_amount: type === PAYMENT_METHODS.CASH ? changeAmount : 0,
        });
    });

    return { paymentDetails, shouldPrint };
};

// ===================== UI Updaters ==========================

/**
 * Toggles the visibility of the "No payments added" message based on the presence of payment items.
 */
const toggleNoPaymentsMessage = () => {
    const hasPayments = getDOM().paymentDetails.find(".payment-item").length > 0;
    $("#no-payments-message").toggleClass("d-none", hasPayments);
};

const toggleCompleteButtonState = () => {
    const hasPayments = getDOM().paymentDetails.find(".payment-item").length > 0;
    getDOM().completePaymentBtn.prop("disabled", !hasPayments);
};

/**
 * Updates the UI state of the form based on the selected payment method.
 * Disables/enables reference input and handles visual selection state.
 * 
 * @param {HTMLElement} selectedInput - The clicked/selected radio button element.
 */
const updateUIFormState = (selectedInput) => {
    if (!selectedInput) return;
    const dom = getDOM();
    const type = selectedInput.value;
    const isCash = type === PAYMENT_METHODS.CASH;

    // Toggle reference input state
    dom.reference.prop('disabled', isCash).prop('readonly', isCash);
    
    // Clear or validate reference based on selection
    if (isCash) {
        clearFieldError("reference_number");
    } else {
        validatePaymentField("reference_number", dom.reference.val(), type);
    }

    // Update radio button visuals
    dom.paymentMethods.find('input[name="payment_method"]').each(function () {
        const isSelected = this === selectedInput;
        $(this).closest('.radio-button').find('.checked').toggleClass('d-none', !isSelected);
    });
};

/**
 * Recalculates the total paid amount, change, and remaining amount to pay,
 * then updates the respective DOM elements with formatted currency values.
 */
const recalculateTotals = () => {
    const dom = getDOM();
    const totalInvoice = parseInt(dom.totalAmount.text().replace(/[^0-9,-]+/g, "")) || 0;
    
    let totalPaid = 0;
    $(".payment-item-amount").each(function () {
        totalPaid += parseInt($(this).text().replace(/[^0-9,-]+/g, "")) || 0;
    });
    
    dom.totalPaid.text(formatCurrency(totalPaid, false));
    
    const change = Math.max(totalPaid - totalInvoice, 0);
    dom.changeAmount.text(formatCurrency(change, false));

    const remaining = Math.max(totalInvoice - totalPaid, 0);
    dom.amountToPay.val(remaining);
};

// =================== Template Rendering =====================

/**
 * Generates the HTML string for a single payment item row.
 * 
 * @param {Object} payment - The payment data object.
 * @param {string} payment.type - The payment method type.
 * @param {string} payment.label - The display label for the payment method.
 * @param {string} [payment.reference] - The transaction reference (optional).
 * @param {number} payment.amount - The payment amount.
 * @returns {string} The generated HTML string for the payment item.
 */
const getPaymentItemHTML = (payment) => {
    const referenceHtml = payment.reference ? `
        <span class="text-muted" style="font-size: 0.75rem;">
            Referencia: <span class="payment-item-reference">${payment.reference}</span>
        </span>
    ` : '';

    return `
        <div class="payment-item d-flex align-items-center justify-content-between text-start border border-1 border-secondary-subtle rounded-3 p-2" style="background-color: rgba(0, 0, 0, 0.05);" data-payment-type="${payment.type}">
            <div class="d-flex flex-column align-items-start">
                <span class="fw-bold" style="font-size: 1rem;">${payment.label}</span>
                ${referenceHtml}
            </div>
            <div class="d-flex align-items-center justify-content-end gap-3">
                <span class="d-flex fw-bolder text-success align-items-center justify-content-center" style="font-size: 1rem;">
                    <svg class="me-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 7.010000228881836 31.639999389648438 40.98999786376953" width="14" height="14" fill="currentColor">
                        <path d="M31.64 33.91Q30.20 39.60 26.44 42.70Q22.58 45.92 16.82 45.87L16.31 45.87L15.70 48.00L12.18 48.00L12.89 45.51Q11.16 45.17 9.67 44.58L8.72 48.00L5.20 48.00L6.64 42.87Q0 37.99 0 27.10Q0 19.19 4.27 14.23Q8.67 9.11 16.21 8.86L16.75 7.01L20.26 7.01L19.68 9.06Q21.29 9.30 22.90 9.94L23.73 7.01L27.25 7.01L25.95 11.60Q29.59 14.31 31.03 19.29L26.37 20.39Q25.63 18.09 24.56 16.58L17.48 41.77Q25.07 41.16 26.90 32.71L31.64 33.91M21.75 14.04Q20.39 13.28 18.58 13.04L10.84 40.48Q12.28 41.28 13.99 41.60L21.75 14.04M15.06 13.01Q9.86 13.60 7.23 17.72Q4.88 21.36 4.88 27.08Q4.88 34.11 8.01 38.04L15.06 13.01Z"/>
                    </svg>
                    <span class="payment-item-amount">${formatCurrency(payment.amount, false)}</span>
                </span>
                <button type="button" class="btn remove-payment-btn btn-sm btn-outline-danger pt-2" data-bs-toggle="tooltip" data-bs-title="Eliminar este pago del resumen." style="font-size: 0.75rem;">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="bi bi-trash"></i>
                    </div>
                </button>
            </div>
        </div>
    `;
};

/**
 * Renders a new payment item to the DOM or updates an existing cash payment item.
 * 
 * @param {Object} payment - The payment data object to render or update.
 */
const renderOrUpdatePaymentItem = (payment) => {
    const dom = getDOM();
    const isCash = payment.type === PAYMENT_METHODS.CASH;
    const $existingCashItem = dom.paymentDetails.find(`.payment-item[data-payment-type="${PAYMENT_METHODS.CASH}"]`);

    // Only update existing row IF it is a Cash payment. Otherwise, always add a new row.
    if (isCash && $existingCashItem.length) {
        $existingCashItem.find('.payment-item-amount').text(formatCurrency(payment.amount, false));
    } else {
        dom.paymentDetails.append(getPaymentItemHTML(payment));
        const $newBtn = dom.paymentDetails.children().last().find(".remove-payment-btn")[0];
        if ($newBtn && typeof enableBootstrapTooltips === "function") {
            enableBootstrapTooltips($newBtn);
        }
    }

    toggleNoPaymentsMessage();
};

// ==================== Validation Logic ======================

/**
 * Configuration object containing validation rules and messages for form fields.
 * @type {Object}
 */
const validatorsConfig = {
    amount_to_pay: {
        validate: (v) => validateMultipleOf5(v) && parseInt(v) > 0,
        message: () => "El monto debe ser múltiplo de 5 y mayor a 0.",
    },
    reference_number: {
        validate: (v, paymentMethod) => {
            const val = String(v || "").trim();
            if (val === "") return true; // Only validate if there's a value - reference is optional for non-cash methods
            if (paymentMethod === PAYMENT_METHODS.CASH) return true; // No validation for cash reference since it's not required
            if (paymentMethod === PAYMENT_METHODS.CARD) return val.length >= 4 && val.length <= 12; // For card payments, require 4-12 characters (e.g., last 4 digits or transaction ID)
            if (paymentMethod === PAYMENT_METHODS.SINPE) return val.length >= 8 && val.length <= 12; // For SINPE, require 8-12 characters (e.g., phone number or transaction ID)
        },
        message: (paymentMethod) => {
            if (paymentMethod === PAYMENT_METHODS.CARD) return "El número de referencia debe tener entre 4 y 12 caracteres.";
            if (paymentMethod === PAYMENT_METHODS.SINPE) return "El número de referencia debe tener entre 8 y 12 caracteres.";
            return "Número de referencia inválido.";
        },
    },
};

/**
 * Validates a specific payment form field.
 * 
 * @param {string} fieldId - The ID of the field to validate. Maps to keys in validatorsConfig.
 * @param {string|number} value - The input value to validate.
 * @param {string} paymentMethod - The currently selected payment method.
 * @returns {boolean} True if the field is valid, false otherwise.
 */
const validatePaymentField = (fieldId, value, paymentMethod) => {
    // Reference is only required/validated if method is not CASH
    if (fieldId === 'reference_number' && paymentMethod === PAYMENT_METHODS.CASH) {
        clearFieldError(fieldId);
        return true;
    }

    const validator = validatorsConfig[fieldId];
    if (!validator) return true;

    const isValid = validator.validate(value, paymentMethod);
    isValid ? clearFieldError(fieldId) : showFieldError(fieldId, validator.message(paymentMethod));
    
    return isValid;
};

/**
 * Validates the entire payment form based on the currently selected payment method.
 * 
 * @returns {boolean} True if all required fields are valid, false otherwise.
 */
const validatePaymentForm = () => {
    const dom = getDOM();
    const method = dom.paymentMethods.find('input[name="payment_method"]:checked').val();
    
    const isAmountValid = validatePaymentField("amount_to_pay", dom.amountToPay.val(), method);
    const isRefValid = validatePaymentField("reference_number", dom.reference.val(), method);

    return isAmountValid && isRefValid;
};

/**
 * Binds input events to trigger real-time validation on form fields.
 */
const bindRealTimeValidation = () => {
    const dom = getDOM();

    dom.amountToPay.on("input", function () {
        const method = dom.paymentMethods.find('input[name="payment_method"]:checked').val();
        validatePaymentField("amount_to_pay", $(this).val(), method);
    });

    dom.reference.on("input", function () {
        const method = dom.paymentMethods.find('input[name="payment_method"]:checked').val();
        validatePaymentField("reference_number", $(this).val(), method);
    });
};

// ===================== Event Bindings =======================

/**
 * Binds click and change events for the payment details modal UI.
 * Handles adding, removing payments, and form submission.
 */
const bindEvents = () => {
    const dom = getDOM();

    dom.paymentMethods.find('input[name="payment_method"]').on("change", function () {
        updateUIFormState(this);
    });

    dom.addPaymentBtn.on("click", (e) => {
        e.preventDefault();

        if (!validatePaymentForm()) {
            return SwalToast.fire({
                icon: 'warning',
                title: 'Verifique los campos requeridos antes de agregar el pago.'
            });
        }

        const method = dom.paymentMethods.find('input[name="payment_method"]:checked').val();
        const paymentData = {
            type: method,
            label: method === PAYMENT_METHODS.CASH ? "Efectivo" : method === PAYMENT_METHODS.CARD ? "Tarjeta" : "SINPE Móvil",
            amount: parseInt(dom.amountToPay.val()) || 0,
            reference: method !== PAYMENT_METHODS.CASH ? dom.reference.val().trim() : null,
        };

        renderOrUpdatePaymentItem(paymentData);
        dom.amountToPay.val("");
        dom.reference.val("");
        recalculateTotals();
        toggleCompleteButtonState();
    });

    dom.paymentDetails.on("click", ".remove-payment-btn", function () {
        $(this).closest(".payment-item").remove();
        toggleNoPaymentsMessage();
        recalculateTotals();
        toggleCompleteButtonState();
    });

    dom.completePaymentBtn.on("click", function (e) {
        e.preventDefault();
        
        const hasPayments = dom.paymentDetails.find(".payment-item").length > 0;
        if (!hasPayments) {
            return SwalToast.fire({
                icon: 'warning',
                title: 'Debe agregar al menos un pago para completar la venta.'
            });
        }

        dom.form.submit();
    });
};

// ===================== Initialization =======================

/**
 * Initializes and displays the payment details form modal.
 * Sets up event listeners, validation, and handles the modal lifecycle.
 * 
 * @async
 * @param {number|string} purchaseTotalAmount - The total invoice amount to initialize the form.
 * @param {string} loadingButtonId - The DOM ID of the button that triggered the modal, to manage its loading state.
 * @returns {Promise<Object>} A promise resolving to the final payment details extracted upon form submission.
 */
export async function showPaymentDetailsFormModal(purchaseTotalAmount, loadingButtonId) {
    setLoadingState(loadingButtonId, true);
    const html = await fetchPaymentDetailsModalContent(purchaseTotalAmount);
    if (!html) return;

    const modal = SwalModal.fire({
        title: "Procesar Pago",
        html: `
        <div id="payment-details-modal" class="d-flex flex-column flex-grow-1 text-start" style="min-width: 50rem; max-width: 50rem; width: 100%;">
            ${html}
        </div>
        `,
        showCloseButton: true,
        showCancelButton: false,
        showConfirmButton: false,
        allowOutsideClick: false,
        allowEscapeKey: false,
        customClass: {
            popup: "swal-popup w-auto h-auto",
            title: "d-flex justify-content-start align-items-center border-bottom pb-3 mb-3",
            closeButton: "swal-close-btn fs-3",
            htmlContainer: "pb-0 overflow-x-hidden text-start",
        },
        didClose: () => { 
            setLoadingState(loadingButtonId, false);
        },
    });
    
    bindEvents();
    bindRealTimeValidation();
    recalculateTotals();
        
    const container = document.getElementById("payment-details-modal");
    enableBootstrapTooltips(container);

    let finalDetails = null;

    $(document).off("submit", "#payment-details-form").on("submit", "#payment-details-form", function (e) {
        e.preventDefault();
        finalDetails = getExtractedPaymentDetails();
    });
    
    return new Promise((resolve) => {
        const interval = setInterval(() => {
            if (finalDetails) {
                modal.close();
                setLoadingState(loadingButtonId, false);
                clearInterval(interval);
                resolve(finalDetails);
            }
        }, 300);
    });
}