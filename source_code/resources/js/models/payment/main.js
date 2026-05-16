import { fetchWithErrorHandling } from "../../utils/error-handling";
import { SwalModal, SwalToast } from "../../utils/sweetalert";
import { enableBootstrapTooltips, formatCurrency } from "../../utils/utils";
import { clearFieldError, showFieldError, validateMultipleOf5 } from "../../utils/validation";

// ==================== Environment Checks ====================

if (typeof $ === "undefined") {
	throw new Error("This script requires jQuery");
}

// ======================== Constants =========================

const PAYMENT_METHODS = {
    CASH: 'cash',
    CARD: 'card',
    SINPE: 'sinpe',
};

// ========================= Helpers ==========================

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
        console.error('Error fetching payment details modal content:', error);
        SwalToast.fire({
            icon: 'error',
            title: error.message || 'Ocurrió un error al cargar el formulario de detalles de pago.',
        });
    }
};

const getPaymentDetailsFromForm = () => {
    const paymentDetails = [];
    const paymentItems = document.querySelectorAll(".payment-item");
    const shouldPrint = document.getElementById("print_receipt_switch").checked;

    paymentItems?.forEach((item) => {
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

    return { paymentDetails, shouldPrint };
};

const getPaymentDetailsFormElements = () => {
    return {
        amount_to_pay: $("#amount_to_pay"),
        reference: $('#reference_number'),
        add_payment_button: $('#add-payment-button'),
        total_paid: $('#total-paid'),
        total_amount: $('#total_amount'),
        change_amount: $('#change_amount'),
        payment_methods: $('#payment-methods'),
        payment_details: $('#payment-details'),
    };
};

const toggleNoPaymentsMessage = () => {
    const $paymentItems = $("#payment-details .payment-item");
    $("#no-payments-message").toggleClass("d-none", $paymentItems.length > 0);
};

const toggleReferenceInput = (paymentMethod) => {
    const $referenceInput = $('#reference_number');
    const isCash = paymentMethod === PAYMENT_METHODS.CASH;
    $referenceInput.prop('disabled', isCash).prop('readonly', isCash);
};

const togglePaymentMethodCheckedState = (selectedInput = null) => {
    if (!selectedInput) return;
    
    if (selectedInput.value === PAYMENT_METHODS.CASH) {
        clearFieldError("reference_number");
    } else {
        const referenceValue = getPaymentDetailsFormElements().reference.val();
        if (referenceValue !== "") {
            validatePaymentField("reference_number", referenceValue, selectedInput.value);
        }
    }
    
    const $radioButtons = getPaymentDetailsFormElements().payment_methods.find('input[name="payment_method"]');
    $radioButtons.each(function () {
        const isSelected = this === selectedInput;
        $(this).closest('.radio-button').find('.checked').toggleClass('d-none', !isSelected);
    });
};

/**
 * Synchronizes and calculates the transaction financial data on the UI
 */
const recalculateTotals = () => {
    const elements = getPaymentDetailsFormElements();
    const totalInvoice = parseInt(elements.total_amount.text().trim().replace(/[^0-9,-]+/g, "")) || 0;
    
    let totalPaid = 0;
    $(".payment-item-amount").each(function () {
        totalPaid += parseInt($(this).text().trim().replace(/[^0-9,-]+/g, "")) || 0;
    });
    
    elements.total_paid.text(formatCurrency(totalPaid, false));
    
    const change = totalPaid > totalInvoice ? totalPaid - totalInvoice : 0;
    elements.change_amount.text(formatCurrency(change, false));

    const remaining = totalInvoice - totalPaid;
    elements.amount_to_pay.val(remaining > 0 ? remaining : 0);
};

const renderPaymentItemDOM = (payment) => {
    const $paymentDetailsContainer = $("#payment-details");
    if (!$paymentDetailsContainer.length) return;

    const referenceHtml = payment.reference ? `
        <span class="text-muted" style="font-size: 0.75rem;">
            Referencia: <span class="payment-item-reference">${payment.reference}</span>
        </span>
    ` : '';

    const html = `
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

    $paymentDetailsContainer.append(html);
    toggleNoPaymentsMessage();

    const $newButton = $paymentDetailsContainer.children().last().find(".remove-payment-btn");
    if ($newButton.length && typeof enableBootstrapTooltips === "function") {
        enableBootstrapTooltips($newButton[0]);
    }
};

// ==================== Validation Helpers ====================

const baseFieldValidators = {
	amount_to_pay: {
		validate: (v) => validateMultipleOf5(v) && parseInt(v) > 0,
		message: "El monto debe ser un número entero múltiplo de 5.",
	},
	reference_number: {
		validate: (v) => {
			const val = String(v || "").trim();
			if (val === "") return true;
			return val.length >= 4 && val.length <= 12;
		},
		message: "La referencia debe tener entre 4 y 12 caracteres.",
	},
};

const getActiveFieldValidators = (selectedPaymentMethod) => {
    if (selectedPaymentMethod === PAYMENT_METHODS.CASH) {
        return { amount_to_pay: baseFieldValidators.amount_to_pay };
    }
    return {
        amount_to_pay: baseFieldValidators.amount_to_pay,
        reference_number: baseFieldValidators.reference_number,
    };
};

const validatePaymentField = (fieldId, value, selectedPaymentMethod) => {
    const validators = getActiveFieldValidators(selectedPaymentMethod);
    const validator = validators[fieldId];

    if (!validator) return true;

    const isValid = validator.validate(value);
    if (!isValid) {
        showFieldError(fieldId, validator.message);
    } else {
        clearFieldError(fieldId);
    }

    return isValid;
};

const validatePaymentForm = () => {
    const values = {
        amount_to_pay: getPaymentDetailsFormElements().amount_to_pay.val(),
        reference_number: getPaymentDetailsFormElements().reference.val(),
    };
    const selectedPaymentMethod = getPaymentDetailsFormElements().payment_methods.find('input[name="payment_method"]:checked').val();
    const fieldsToValidate = getActiveFieldValidators(selectedPaymentMethod);

    let isFormValid = true;
    for (const [fieldId, validator] of Object.entries(fieldsToValidate)) {
        const isFieldValid = validatePaymentField(fieldId, values[fieldId], selectedPaymentMethod);
        if (!isFieldValid) isFormValid = false;
    }
    return isFormValid;
};

// =============== Real-Time Validation Handler ===============

function bindRealTimeValidation() {
    const refs = getPaymentDetailsFormElements();

    refs.amount_to_pay.off("input").on("input", function () {
        validatePaymentField(
			"amount_to_pay",
			refs.amount_to_pay.val(),
			refs.payment_methods.find('input[name="payment_method"]:checked').val()
		);
    });

    refs.reference.off("input").on("input", function () {
        const selectedPaymentMethod = refs.payment_methods.find('input[name="payment_method"]:checked').val();

        if (selectedPaymentMethod !== PAYMENT_METHODS.CASH) {
            validatePaymentField("reference_number", refs.reference.val(), selectedPaymentMethod);
        } else {
            clearFieldError("reference_number");
        }
    });
}

// ===================== Event Listeners ======================

function bindEventListeners() {
    const $elements = getPaymentDetailsFormElements();

    $elements.payment_methods.find('input[name="payment_method"]').off("change").on("change", function () {
        const selectedPaymentMethod = $(this).val();
        toggleReferenceInput(selectedPaymentMethod);
        togglePaymentMethodCheckedState(this);
    });

    $elements.add_payment_button.off("click").on("click", () => {
        if (validatePaymentForm()) {
            const selectedPaymentMethod = $elements.payment_methods.find('input[name="payment_method"]:checked').val();
            const amount = parseInt($elements.amount_to_pay.val()) || 0;
            const reference = $elements.reference.val().trim();

            const paymentData = {
                type: selectedPaymentMethod,
                label: selectedPaymentMethod === PAYMENT_METHODS.CASH ? "Efectivo" : selectedPaymentMethod === PAYMENT_METHODS.CARD ? "Tarjeta" : "SINPE Móvil",
                amount,
                reference: selectedPaymentMethod !== PAYMENT_METHODS.CASH ? reference : null,
            };

            renderPaymentItemDOM(paymentData);

            $elements.amount_to_pay.val("");
            $elements.reference.val("");
            
            recalculateTotals();
        }
    });

    /**
     * Using event delegation for dynamically rendered element item cleanups
     */
    $elements.payment_details.off("click", ".remove-payment-btn").on("click", ".remove-payment-btn", function () {
        $(this).closest(".payment-item").remove();
        toggleNoPaymentsMessage();
        recalculateTotals();
    });

    /**
     * Triggers form submit explicitly to prevent button type specification conflicts
     */
    $("#payment-button").off("click").on("click", function (e) {
        e.preventDefault();
        $("#payment-details-form").submit();
    });
}

// ====================== Initialization ======================

export async function showPaymentDetailsFormModal(purchaseTotalAmount) {
    const html = await fetchPaymentDetailsModalContent(purchaseTotalAmount);
    if (html) {
        const modal = SwalModal.fire({
			title: "Procesar Pago",
			html: `
            <div id="payment-details-modal" class="d-flex flex-column flex-grow-1 text-start" style="min-width: 50rem !important; max-width: 50rem; width: 100%;">
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
				confirmButton: "btn btn-primary mx-1",
				cancelButton: "btn btn-danger mx-1",
				icon: "mb-4",
			},
		});
        
        bindEventListeners();
        bindRealTimeValidation();
        recalculateTotals();
            
        const paymentModalContainer = document.getElementById("payment-details-modal");
        enableBootstrapTooltips(paymentModalContainer);

        let paymentDetails = null;
        let shouldPrint = false;

        $(document)
            .off("submit", "#payment-details-form")
            .on("submit", "#payment-details-form", function (e) {
                e.preventDefault();
                const result = getPaymentDetailsFromForm();
                paymentDetails = result.paymentDetails;
                shouldPrint = result.shouldPrint;
            });
        
        return new Promise((resolve) => {
            const checkPaymentDetailsInterval = setInterval(() => {
                if (paymentDetails) {
                    modal.close();
                    clearInterval(checkPaymentDetailsInterval);
                    resolve({ paymentDetails, shouldPrint });
                }
            }, 500);
        });
    }
}