import { clearAllFieldErrors, clearFieldError, showFieldError } from "../../utils/validation";
import { setLoadingState } from "../../utils/utils";
import { SwalNotificationTypes, SwalToast } from "../../utils/sweetalert.js";
import { bindPurchaseFormEvents } from "./items.js";
import { bindOffcanvasEvents } from "./offcanvas.js";

// ==================== Environment Checks ====================
if (typeof $ === 'undefined') {
    throw new Error('This script requires jQuery');
}

// ======================== Constants =========================

const PURCHASE_DATA = window.purchaseFormData || [];
const IS_EDITING = document.querySelector('form[id^="edit-"]') !== null;
const FORM_ID = IS_EDITING ? 'edit-purchase-form' : 'create-purchase-form';
const PAYMENT_METHODS = { CASH: 'cash', CARD: 'card', SINPE: 'sinpe' };

// =========================== Helpers ==========================

const parseFormattedNumber = (text) => {
	if (!text) return 0;
	return parseFloat(text.replace(/[^0-9,-]+/g, "").replace(",", ".")) || 0;
};

function showFieldErrorInAlert(fieldId, errorMessage) {
	const $alert = $("#form-error-alert");
	$alert.removeClass("d-none").html(`
        <i class="bi bi-exclamation-triangle me-2"></i>
        <span>${errorMessage}</span>
    `);

	if (fieldId) {
		$(`#${fieldId}`).focus();
	}

	window.scrollTo({ top: $alert.offset()?.top - 100, behavior: "smooth" });
}

function clearFieldErrorInAlert() {
    const $alert = $("#form-error-alert");
    $alert.addClass("d-none").html("");
}

function getLaravelFirstError(errorData) {
    let firstField = null;
	let firstMessage = null;
	if (errorData.errors && typeof errorData.errors === "object") {
		const keys = Object.keys(errorData.errors);
		if (keys.length > 0) {
			firstField = keys[0];
			const messages = errorData.errors[firstField];
			if (Array.isArray(messages) && messages.length > 0) {
				firstMessage = messages[0];
			} else if (typeof messages === "string") {
				firstMessage = messages;
			}
		}
	}
	return { field: firstField, message: firstMessage };
}

function getFormFields() {
    const purchase = {
		invoice_number: $("#invoice_number").val()?.trim() || '',
		supplier_id: $("#supplier_id").val(),
		payment_status: $("#payment_status").val(),
		date: $("#date").val(),
        total: parseFormattedNumber($("#total").text()),
		notes: $("#notes").val()?.trim() || '',
	};

    purchase.purchase_details = $("#purchase-details-table")
		.find("tbody tr:not(#empty-row)")
		.map((_, row) => {
			const $row = $(row);
			return {
				id: $row.data("id") || null,
				quantity: $row.find('[name="quantity"]').val(),
				unit_price: $row.find('[name="unit-price"]').val(),
				sub_total: parseFormattedNumber($row.find(".sub-total").text()),
				purchasable_id: $row.find('[name="purchasable_id"]').val(),
				purchasable_type: $row.data("purchasable-type"),
			};
		})
		.get();

	if ($("#payment_status").val() === "paid") {
		purchase.payment_details = [
			{
				method: PAYMENT_METHODS.CASH || "cash",
				change_amount: 0,
				amount: purchase.total / 3,
			},
			{
				method: PAYMENT_METHODS.CARD || "card",
				reference: Math.random().toString(36).substring(2, 10),
				amount: purchase.total / 3,
			},
			{
				method: PAYMENT_METHODS.SINPE || "sinpe",
				reference: Math.random().toString(36).substring(2, 10),
				amount: purchase.total / 3,
			},
		];
	}

    return purchase;
}

// ===================== Validation Helpers =====================
const rules = {
	isNum: (v) => v !== "" && !isNaN(v) && v !== null,
	isInt: (v) => /^\d+$/.test(v),
	isString: (v) => typeof v === "string" && v.trim().length > 0,
	isValidId: (v) => /^\d+$/.test(v) && v !== "-1",
    inList: (v, list, key = 'value') => list.some(item => String(item[key]) === String(v))
};

// ===================== Field Validators =====================
const baseFieldValidators = {
	supplier_id: {
		validate: (v) => rules.isValidId(v),
		message: "Debe seleccionar un proveedor válido.",
	},
	invoice_number: {
		validate: (v) => rules.isString(v) && v.length >= 2 && v.length <= 255,
		message: "El número de factura es obligatorio (2-255 caracteres).",
	},
	payment_status: {
		validate: (v) => rules.inList(v, PURCHASE_DATA.paymentStatuses || []),
		message: "Debe seleccionar un estado de pago válido.",
	},
	date: {
		validate: (v) => {
			if (!/^\d{4}-\d{2}-\d{2}$/.test(v)) return false;
			const date = new Date(v);
			return !isNaN(date) && date <= new Date().setHours(23, 59, 59);
		},
		message: "La fecha es obligatoria y no puede ser futura.",
	},
	total: {
		validate: (v) => rules.isNum(v) && parseFloat(v) >= 0,
		message: "El total de la compra no puede ser negativo.",
	},
	purchase_details: {
		validate: (arr) => {
			if (!Array.isArray(arr) || arr.length === 0) return false;
			return arr.every(
				(i) =>
					rules.isValidId(i.purchasable_id) &&
					i.quantity > 0 &&
					i.unit_price >= 0,
			);
		},
		message:
			"Debe agregar al menos un producto válido (con cantidad y precio).",
	},
	payment_details: {
		validate: (v) => {
			if (!v) return true; // Opcional
			const payments = rules.isValidJSON(v);
			if (!Array.isArray(payments)) return false;
			return payments.every((p) =>
				rules.inList(
					p.method,
					PURCHASE_DATA.paymentMethods || [],
					"label",
				),
			);
		},
		message: "Los métodos de pago seleccionados no son válidos.",
	},
	notes: {
		validate: (v) => !v || (typeof v === "string" && v.length <= 1000), // En Blade pusiste maxlength="1000"
		message: "Las notas no pueden exceder los 1000 caracteres.",
	},
};

// Purchase detail validators (for each item in purchase_details)
const purchaseDetailValidators = {
	id: (v) => v === null || rules.isValidId(v),
	quantity: (v) => rules.isNum(v) && parseInt(v) > 0,
	unit_price: (v) => rules.isNum(v) && parseFloat(v) >= 0,
	sub_total: (v) => rules.isNum(v) && parseFloat(v) >= 0,
	purchasable_id: (v) => rules.isValidId(v),
	purchasable_type: (v) =>
		Object.values(PURCHASE_DATA.purchasableTypes || {}).includes(v),
};

// Payment detail validators (for each item in payment_details)
const paymentDetailValidators = {
    method: (v) => rules.inList(v, PURCHASE_DATA.paymentMethods || [], 'label'),
    change_amount: (v) => rules.isNum(v) && parseFloat(v) > 0,
    reference: (v) => !v || (typeof v === 'string' && v.length >= 4 && v.length <= 12)
};

// ==================== Validation Functions ====================

function getActiveFieldValidators() {
    const validators = {...baseFieldValidators};

    // Remove Notes validator if not provided
    if (!$('#notes').val().trim()) {
        delete validators.notes;
    }

    // Remove Payment Details validator if payment status is not 'paid'
    if ($('#payment_status').val() !== 'paid') {
        delete validators.payment_details;
    }

    return validators;
}

function validatePurchaseForm(values, fieldValidators) {
	// Get current form values and details
	const { purchase_details = [], payment_details = [], ...purchase } = values;
	let errors = []; // Array to store validation errors

	// Validate main purchase fields
	Object.entries(fieldValidators).forEach(([fieldId, config]) => {
		const value =
			purchase[fieldId] !== undefined
				? purchase[fieldId]
				: values[fieldId];

		// Skip purchase_details and payment_details here, they are validated separately
		if (fieldId !== "purchase_details" && fieldId !== "payment_details") {
			if (!config.validate(value)) {
				if (fieldId !== 'total') showFieldError(fieldId, config.message);
				errors.push([false, fieldId, config.message]);
			} else {
				if (fieldId !== 'total') clearFieldError(fieldId);
			}
		}
	});

    // Validate purchase_details array is not empty and has valid items
    if (!fieldValidators.purchase_details.validate(purchase_details)) {
        errors.push([false, 'form-error-alert', fieldValidators.purchase_details.message]);
    }

	// Validate purchase_details
	purchase_details.forEach((detail, index) => {
		Object.entries(purchaseDetailValidators).forEach(([key, validator]) => {
			if (!validator(detail[key])) {
				const fieldName = `Detalle ${index + 1}: ${key}`;
				errors.push([
					false,
					null,
					`Item ${index + 1}: Compruebe que el producto, cantidad y precio sean válidos.`,
				]);
			}
		});
	});

	// Validate payment_details if payment status is 'paid'
	if (
		purchase.payment_status === "paid" &&
		typeof payment_details !== "undefined"
	) {
		payment_details.forEach((payment, index) => {
			Object.entries(paymentDetailValidators).forEach(([key, validator]) => {
				if (!validator(payment[key])) {
					errors.push([
						false,
						null,
						`Pago ${index + 1}: Campo ${key} inválido.`,
					]);
				}
			});
		});
	}

	return errors.length > 0 ? errors[0] : [true, "", ""];
}

// ==================== Real-Time Validation Handler ====================

function bindRealTimeValidation() {
    // Real-time validation for main fields
    const mainFields = ['invoice_number', 'supplier_id', 'payment_status', 'date', 'notes'];

    mainFields.forEach(fieldId => {
        const $element = $(`#${fieldId}`);
        if (!$element.length) return;

        // Infer event type based on element type (selects and date inputs use 'change', others use 'input' and 'focusout')
        const eventType = $element.is('select, input[type="date"]') ? 'change' : 'input focusout';

        $element.on(eventType, function() {
            const config = baseFieldValidators[fieldId];
            const value = $(this).val();

            // Special exception for 'notes' field: if it's empty, we clear errors instead of showing them
            if (fieldId === 'notes' && (!value || !value.trim())) {
                clearFieldError(fieldId);
                return;
            }

            if (config) {
                if (!config.validate(value)) {
                    showFieldError(fieldId, config.message);
                } else {
                    clearFieldError(fieldId);
                }
            }
        });
    });

    // Delegated real-time validation for purchase details (quantity and unit price)
	// We use delegation (.on on the table ID) so it works with newly added rows
    $('#purchase-details-table').on('input focusout', 'input[name="quantity"], input[name="unit-price"]', function() {
        const $input = $(this);
        const name = $input.attr('name');
        // Map 'unit-price' to rule name 'unit_price' for validation
        const key = name === 'unit-price' ? 'unit_price' : name; 
        const validator = purchaseDetailValidators[key];

        if (validator) {
            if (!validator($input.val())) {
                $input.addClass('is-invalid');
                showFieldErrorInAlert(
					$(this),
					`Compruebe que la ${key === "unit_price" ? "cantidad y el precio unitario sean válidos" : "cantidad se válida"}  para cada producto agregado.`,
				);
            } else {
                $input.removeClass('is-invalid');
                clearFieldErrorInAlert();
            }
        }
    });

    $('#purchase-details-table').on('change', 'select[name="purchasable_id"]', function() {
        const $select = $(this);
        const validator = purchaseDetailValidators.purchasable_id;

        if (validator) {
            if (!validator($select.val())) {
                $select.addClass('is-invalid');
                showFieldErrorInAlert($(this), `Debe seleccionar un producto válido para cada detalle de compra.`);
            } else {
                $select.removeClass('is-invalid');
                clearFieldErrorInAlert();
            }
        }
    });
}

// ==================== Form Submission Handler ====================

function submitPurchaseForm() {
    const fieldValidators = getActiveFieldValidators();
    const values = getFormFields();

	// Exclude fields 'total', 'purchase_details' y 'payment_details' from error clearing since they are validated separately
	const filteredValidators = Object.fromEntries(
		Object.entries(fieldValidators).filter(
			([key]) => !['total', 'purchase_details', 'payment_details'].includes(key)
		)
	);

	clearAllFieldErrors(filteredValidators);
    const validationResult = validatePurchaseForm(values, fieldValidators);

    return [...validationResult, values];
}

$(document).on('submit', `#${FORM_ID}`, async function(e) {
    // Prevent default form submission
    e.preventDefault();
    setLoadingState(FORM_ID, true);

    // Validate and submit form
    const [isValid, fieldId, message, values] = submitPurchaseForm();

    // If valid, submit the form
    if (isValid) {
		// Hide any previous error alert
		$("#form-error-alert").addClass("d-none");
		
        const form = this;
        const url = form.action;
        const token = $(form).find('input[name="_token"]').val();
        const method = $(form).find('input[name="_method"]').val();
        const httpMethod = method ? method.toUpperCase() : 'POST';
        
        if (httpMethod === 'PUT') {
            const purchaseId = httpMethod === 'PUT' ? url.split('/').pop() : null; // Extract ID from URL for editing
            values.id = purchaseId; // Include ID in payload for updates
        }

		// Delete all null IDs from purchase_details 
		if (Array.isArray(values.purchase_details)) {
			values.purchase_details = values.purchase_details.map(detail => {
				if (detail.id === null) {
					const { id, ...rest } = detail;
					return rest;
				}
				return detail;
			});
		}

        try {
            const response = await fetch(url, {
                method: httpMethod,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify(values)
            });

            if (response.ok) {
				const data = await response.json();
				window.location.href = data.redirect || route('purchases.index');
			} else {
				const errorData = await response.json();
				console.error('Error en la respuesta del servidor:', errorData);

                const { field: firstField, message: firstMessage } = getLaravelFirstError(errorData);
				showFieldError(firstField, firstMessage);
				showFieldErrorInAlert(null, errorData.message || 'Error al enviar el formulario. Por favor, inténtelo de nuevo.');
			}
        } catch (error) {
			SwalToast.fire({
                icon: SwalNotificationTypes.ERROR,
                title: "Error al enviar el formulario"
            });
        } finally {
            setLoadingState(FORM_ID, false);
        }
	}
	else {
        // Show error message in alert and focus the first invalid field
        setLoadingState(FORM_ID, false);
        showFieldErrorInAlert(fieldId, message);
    }
});

// Initialize real-time validation when the document is ready
$(() => {
	bindRealTimeValidation();
	bindPurchaseFormEvents();
	bindOffcanvasEvents();
});