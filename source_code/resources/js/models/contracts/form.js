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
		days_to_serve: $('#days_to_serve input[type="checkbox"]:checked').map((_, el) => el.value).get(),
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

const getFormElements = () => {
    return {
		client_id: $("#client_id"),
		business_name: $("#business_name"),
		start_date: $("#start_date"),
		end_date: $("#end_date"),
		days_to_serve: $('#days_to_serve input[type="checkbox"]'),
		portions_per_day: $("#portions_per_day"),
		total_value: $("#total_value"),
		contract_details_table: $("#contract-details-table"),
	};
};

// ==================== Validation Helpers ====================

// Converts a "YYYY-MM-DD" string into a local date at 00:00:00
const getLocalMidnight = (dateString) => {
    if (!dateString) return new Date("Invalid");
    // Separate the string to prevent JS from treating it as UTC
    const [year, month, day] = dateString.split('T')[0].split('-');
    return new Date(year, month - 1, day, 0, 0, 0, 0);
};

// Get today's date at local midnight (00:00:00)
const getTodayMidnight = () => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return today;
};

const rules = {
	isNum: (v) => v !== "" && !isNaN(v) && v !== null,
	isString: (v) => typeof v === "string" && v.trim().length > 0,
	isNotEmpty: (v) => v !== "" && v !== null,
	isInList: (v, list, key = "value") => list.some((item) => String(item[key]) === String(v)),
	isValidId: (v) => Number.isInteger(v) && v > 0,
	isValidDate: (v) => !isNaN(Date.parse(v)),
	isTodayOrFutureDate: (v) => getLocalMidnight(v) >= getTodayMidnight(),
	isFutureDate: (v) => getLocalMidnight(v) > getTodayMidnight(),
	isValidDay: (v) => WEEK_DAYS.some((day) => day.value === v),
	isValidMealTime: (v) => MEAL_TIMES.some((meal) => meal.value === v),
	hasAtLeastOneDay: (days) => Array.isArray(days) && days.length > 0,
};

const baseFieldValidators = {
    client_id: {
        validate: (v) => rules.isNum(v) && rules.isValidId(parseInt(v)),
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
        validate: (v, data) => rules.isValidDate(v) && rules.isFutureDate(v) && getLocalMidnight(v) > getLocalMidnight(data.start_date),
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
		validate: (v) =>
			(rules.isNum(v) && rules.isValidId(parseInt(v))) || v === null,
		message: "ID de detalle de contrato no válido.",
	},
	product_id: {
		validate: (v) =>
			rules.isNum(v) &&
			rules.isValidId(parseInt(v)) &&
			CONTRACTS_DATA.products.some(
				(product) => product.id === parseInt(v),
			),
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
			getLocalMidnight(v) <= getLocalMidnight(data.end_date),
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
                    updateSummary.progressBar(); // Update progress bar on days change
				});
			return;
		}

		// Bind validation for other fields
		$(`#${fieldId}`)
			.on("change input focusout", function () {
				validateContractField(fieldId, $(this).val());
                updateSummary.progressBar(); // Update progress bar on field change
			});
	});
}

// ===================== Summary Updaters =====================

// Helper extraído fuera de los eventos para no recrearlo en cada pulsación
const formatLaravelDate = (dateValue) => {
    if (!dateValue) return "";
    // Ensure that the date is treated as local by appending a time component if it's not already present
    const date = new Date(dateValue.includes('T') ? dateValue : `${dateValue}T00:00:00`);
    const months = ["ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
    const day = String(date.getDate()).padStart(2, "0");
    return `${day} ${months[date.getMonth()]} ${date.getFullYear()}`;
};

const updateSummary = {
	client: ($el) => {
		const selectedOption = $el.find("option:selected");
		const clientName =
			selectedOption.val() !== "-1"
				? selectedOption.text()
				: "No seleccionado";
		const $summary = $("#contract-summary-client");

		if ($summary.length) {
			$summary
				.text(clientName)
				.css(
					"color",
					clientName === "No seleccionado"
						? "inherit"
						: "var(--bambu-logo-bg)",
				);
		}
	},
	portions: ($el) => {
		const value = $el.val();
		const isValid = baseFieldValidators.portions_per_day.validate(value);
		$("#contract-summary-portions").text(isValid ? value : "—");
	},
	totalValue: ($el) => {
		const value = $el.val();
		const isValid = baseFieldValidators.total_value.validate(value);
		const formattedValue = isValid
			? String(value).replace(/\B(?=(\d{3})+(?!\d))/g, " ")
			: "0";
		$("#contract-summary-value").text(formattedValue);
	},
	period: () => {
		const start = $("#start_date").val();
		const end = $("#end_date").val();
		const isStartValid = baseFieldValidators.start_date.validate(start);
		const isEndValid = baseFieldValidators.end_date.validate(end, {
			start_date: start,
		});

		const $summary = $("#contract-summary-period");
		if ($summary.length) {
			$summary.text(
				isStartValid && isEndValid
					? `${formatLaravelDate(start)} - ${formatLaravelDate(end)}`
					: "No definido",
			);
		}
	},
	daysToServe: () => {
		const count = $('#days_to_serve input[type="checkbox"]:checked').length;
		const $summary = $("#contract-summary-days");

		if ($summary.length) {
			if (count === 0) {
				$summary.text("—");
			} else if (count === 1) {
				$summary.text(`${count} día`);
			} else {
				$summary.text(`${count} días`);
			}
		}
	},
	progressBar: () => {
		const values = getFormFields();

        // Defines which fields are mandatory for the 100% progress 
        // (excluding details because they are dynamic)
		const fieldsToTrack = [
			"client_id",
			"business_name",
			"start_date",
			"end_date",
			"days_to_serve",
			"portions_per_day",
			"total_value",
		];

		let validCount = 0;

		// Evaluates each field against its validator and counts how many are valid
		fieldsToTrack.forEach((fieldId) => {
			const validator = baseFieldValidators[fieldId];
			if (validator && validator.validate(values[fieldId], values)) {
				validCount++;
			}
		});

		// Calcs the percentage of completion based on valid fields
		const percentage = Math.round(
			(validCount / fieldsToTrack.length) * 100,
		);

		// Updates the progress bar's width and aria-valuenow
		const $bar = $("#contract-progress-bar");
		if ($bar.length) {
			$bar.css("width", `${percentage}%`).attr(
				"aria-valuenow",
				percentage,
			);

            const $progressIndicator = $("#contract-progress");
            if ($progressIndicator.length) {
                $progressIndicator.text(`${percentage}%`);
            }

			// Change the color of the progress bar based on completion percentage
			$bar.removeClass("bg-danger bg-warning");

			if (percentage < 40) {
				$bar.addClass("bg-danger"); // Red for less than 40%
			} else if (percentage < 100) {
				$bar.addClass("bg-warning"); // Yellow for 40% to 99%
			} else {
				$bar.css("background", "var(--bambu-logo-bg)"); // Green for 100%
			}
		}
	},
};

// ===================== Event Listeners ======================

const bindEventListeners = () => {
    const elements = getFormElements();

    // Client Selection Event
    elements.client_id
        .off("change")
        .on("change", function () { updateSummary.client($(this)); })
        .trigger("change");

    // Portions per day and Total value events
    elements.portions_per_day
        .off("input")
        .on("input", function () { updateSummary.portions($(this)); })
        .trigger("input");

    elements.total_value
        .off("input")
        .on("input", function () { updateSummary.totalValue($(this)); })
        .trigger("input");

    // Contract Period Events (Start and End Dates)
    const handleDatesChange = () => updateSummary.period();
    elements.start_date.off("change").on("change", handleDatesChange).trigger("change");
    elements.end_date.off("change").on("change", handleDatesChange).trigger("change");

    // Days to Serve Event
    elements.days_to_serve
        .off("change")
        .on("change", function () { updateSummary.daysToServe(); });
    
    // Triggers initial update in case there are pre-selected days (e.g., when editing)
    updateSummary.daysToServe();

    // Deselect All Days Button
    $("#deselect-all-days")
        .off("click")
        .on("click", function () {
            // Deselects all checkboxes and triggers change to update validation and summary
            elements.days_to_serve.prop("checked", false).trigger("change");
        });
};

$(() => {
    bindEventListeners();
    bindRealTimeValidation();
    bindOffcanvasEvents("create-offcanvas");
    updateSummary.progressBar(); // Initial progress bar update on page load
});