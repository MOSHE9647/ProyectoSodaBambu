import { bindOffcanvasEvents } from "../../utils/offcanvas.js";
import { clearAllFieldErrors, clearFieldError, showFieldError } from "../../utils/validation.js";

// ==================== Environment Checks ====================

if (typeof $ === "undefined") {
	throw new Error("This script requires jQuery");
}

// ======================== Constants =========================

const CONTRACTS_DATA = window.CONTRACT_FORM_DATA || {};
const IS_EDITING = CONTRACTS_DATA.isEditing || false;
const FORM_ID = CONTRACTS_DATA.formId || 'create-contract-form';

const PAYMENT_STATUSES = CONTRACTS_DATA.paymentStatuses || {};
const PAYMENT_METHODS = CONTRACTS_DATA.paymentMethods || {};
const MEAL_TIMES = CONTRACTS_DATA.mealTimes || {};
const WEEK_DAYS = CONTRACTS_DATA.weekDays || {};

// ========================= Helpers ==========================

const getFormFields = () => {
	const CONTRACT = {
		client_id: parseInt($("#client_id").val()),
		business_name: $("#business_name").val(),
		start_date: $("#start_date").val(),
		end_date: $("#end_date").val(),
		days_to_serve: $('#days-to-serve input[type="checkbox"]:checked').map((_, el) => el.value).get(),
		portions_per_day: parseInt($("#portions_per_day").val()),
		total_value: parseInt($("#total_value").val()),
	};

    CONTRACT.contract_details = $('#contract-details-table')
        .find('tbody tr:not(#empty-row)')
        .map((_, el) => {
            const $row = $(el);
            return {
                id: $row.data('contract-detail-id') || null,
                product_id: parseInt($row.find('select[name="product_id"]').val()),
                meal_time: $row.find('select[name="meal_time"]').val(),
                service_date: $row.find('input[name="service_date"]').val()
            };
        })
        .get();

	return CONTRACT;
};

// ==================== Validation Helpers ====================

const rules = {
    isNum: (v) => v !== "" && !isNaN(v) && v !== null,
    isString: (v) => typeof v === "string" && v.trim().length > 0,
    isNotEmpty: (v) => v !== "" && v !== null,
    isInList: (v, list, key = 'value') => list.some(item => String(item[key]) === String(v)),
    isValidId: (v) => Number.isInteger(v) && v > 0,
    isValidDate: (v) => !isNaN(Date.parse(v)),
    isTodayOrFutureDate: (v) => new Date(v) >= new Date(),
    isFutureDate: (v) => new Date(v) > new Date(),
    isValidDay: (v) => WEEK_DAYS.some(day => day.value === v),
    isValidMealTime: (v) => MEAL_TIMES.some(meal => meal.value === v),
    hasAtLeastOneDay: (days) => Array.isArray(days) && days.length > 0,
};

const baseFieldValidators = {
    client_id: {
        validate: (v) => 
            rules.isNum(v) && 
            rules.isValidId(parseInt(v)) && 
            CONTRACTS_DATA.clients.some(client => client.id === parseInt(v)),
        message: "Debe seleccionar un cliente válido."
    },
    business_name: {
        validate: (v) => rules.isString(v) && v.length >= 3 && v.length <= 255,
        message: "El nombre de la empresa es obligatorio (3-255 caracteres)."
    },
    start_date: {
        validate: (v) => rules.isValidDate(v) && rules.isTodayOrFutureDate(v),
        message: "La fecha de inicio debe ser una fecha válida y no puede ser en el pasado."
    },
    end_date: {
        validate: (v, data) => rules.isValidDate(v) && rules.isFutureDate(v) && new Date(v) > new Date(data.start_date),
        message: "La fecha de fin debe ser una fecha válida, en el futuro y posterior a la fecha de inicio."
    },
    days_to_serve: {
        validate: (v) => rules.hasAtLeastOneDay(v) && v.every(day => rules.isValidDay(day)),
        message: "Debe seleccionar al menos un día de servicio válido."
    },
    portions_per_day: {
        validate: (v) => rules.isNum(v) && v > 0,
        message: "Las porciones por día deben ser un número positivo."
    },
    total_value: {
        validate: (v) => rules.isNum(v) && v > 0,
        message: "El valor total del contrato debe ser un número positivo."
    },
    contract_details: {
        validate: (details) => Array.isArray(details) && details.length > 0,
        message: "Algunos de los detalles del contrato no son válidos."
    },
    payment_details: {
        validate: (details) => Array.isArray(details) && details.length > 0,
        message: "Algunos de los detalles de pago no son válidos."
    }
};

const contractDetailValidators = {
	id: {
		validate: (v) => (rules.isNum(v) && rules.isValidId(parseInt(v))) || v === null,
		message: "ID de detalle de contrato no válido.",
	},
	product_id: {
		validate: (v) =>
			rules.isNum(v) &&
			rules.isValidId(parseInt(v)) &&
			CONTRACTS_DATA.products.some((product) => product.id === parseInt(v)),
		message: "Seleccione un producto válido.",
	},
	meal_time: {
		validate: (v) => rules.isString(v) && rules.isValidMealTime(v),
		message: "Seleccione un tiempo de comida válido.",
	},
	service_date: {
		validate: (v, data) =>
			rules.isValidDate(v) &&
			rules.isTodayOrFutureDate(v) &&
			new Date(v) <= new Date(data.end_date),
		message:
			"Fecha de servicio no válida. Debe ser una fecha válida, no puede ser en el pasado y debe estar dentro del rango del contrato.",
	},
};

const paymentDetailValidators = {
    method: {
        validate: (v) => rules.isString(v) && rules.isInList(v, PAYMENT_METHODS),
        message: "Seleccione un método de pago válido."
    },
    change_amount: {
        validate: (v) => rules.isNum(v) && parseFloat(v) > 0,
        message: "El monto de cambio debe ser un número positivo."
    },
    reference: {
        validate: (v) => rules.isString(v) && v.trim().length >= 4 && v.trim().length <= 12,
        message: "El número de referencia debe tener entre 4 y 12 caracteres."
    }
};

// ==================== Validation Helpers ====================

function getActiveFieldValidators() {
    const contractValidators = { ...baseFieldValidators };
    const detailValidators = { ...contractDetailValidators };

    // Remove 'id' validator for contract details if we're creating a new contract (not editing)
    if (! IS_EDITING) {
        delete detailValidators.id;
    }

    return { contractValidators, detailValidators };
}

function validateContractField(fieldId, value) {
    const { contractValidators, detailValidators } = getActiveFieldValidators();
    const validator = contractValidators[fieldId] || detailValidators[fieldId];

    if (validator) {
        const isValid = validator.validate(value, getFormFields());
        if (!isValid) {
            showFieldError(fieldId, validator.message);
        } else {
            clearFieldError(fieldId);
        }
        return isValid;
    }

    return true; // No validator means the field is considered valid
}

function validateContractForm(payment_details = []) {
    // Get current form values and active validators
	const values = getFormFields();
    const fieldValidators = getActiveFieldValidators();

    // Destructure contract details and payment details from values
	const { contract_details = [], ...contract } = values;
	const { contractValidators, detailValidators } = fieldValidators;
	let errors = [];

	// Validate main contract fields
	for (const [fieldId, validator] of Object.entries(contractValidators)) {
		const value =
			contract[fieldId] !== undefined
				? contract[fieldId]
				: values[fieldId];
		if (!validator.validate(value, values)) {
			showFieldError(fieldId, validator.message);
			errors.push([false, fieldId, validator.message]);
		} else {
			clearFieldError(fieldId);
		}
	}

	// Validate contract details
	contract_details.forEach((detail, index) => {
		for (const [fieldId, validator] of Object.entries(detailValidators)) {
			const value = detail[fieldId];
			if (!validator.validate(value, values)) {
				errors.push([
					false,
					fieldId,
					`Fila ${index + 1}: ${validator.message}`,
				]);
			}
		}
	});

	// Validate payment details
	payment_details.forEach((detail, index) => {
		for (const [fieldId, validator] of Object.entries(
			paymentDetailValidators,
		)) {
			const value = detail[fieldId];
			if (!validator.validate(value, values)) {
				errors.push([
					false,
					fieldId,
					`Pago ${index + 1}: ${validator.message}`,
				]);
			}
		}
	});

    // Clear errors for fields that passed validation (except contract_details and payment_details which are validated as a whole)
    const filteredValidators = Object.fromEntries(
        Object.entries(fieldValidators).filter(
            ([fieldId]) => !["contract_details", "payment_details"].includes(fieldId)
        )
    );

    clearAllFieldErrors(filteredValidators);
    const validationResult = errors.length > 0 ? errors[0] : [true, null, null];

    // Return validation result along with current form values for potential use in form submission
	return [...validationResult, values];
}

// =============== Real-Time Validation Handler ===============

function bindRealTimeValidation() {
	// Get active validators to determine which fields to bind
	let mainFields = Object.keys(baseFieldValidators);

	// Remove contract_details and payment_details since they are validated separately
	mainFields = mainFields.filter(
		(f) => !["contract_details", "payment_details"].includes(f),
	);

	// Bind change event for main contract fields
	mainFields.forEach((fieldId) => {
		if (fieldId === "days_to_serve") {
			// Bind validation for days_to_serve checkboxes
			$(`#${fieldId} input[type="checkbox"]`)
				.on("change", function () {
					const selectedDays = $(`#${fieldId} input[type="checkbox"]:checked`)
						.map((_, el) => el.value)
						.get();
					validateContractField(fieldId, selectedDays);
				});
			return;
		}

		// Bind validation for other fields
		$(`#${fieldId}`)
			.on("change input focusout", function () {
				validateContractField(fieldId, $(this).val());
			});
	});
}

$(() => {
    bindRealTimeValidation();
    bindOffcanvasEvents("create-offcanvas");
});