import { bindOffcanvasEvents } from "../../utils/offcanvas.js";
import { SwalToast } from "../../utils/sweetalert.js";
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
const PRODUCTS = CONTRACTS_DATA.products || {};
const CLIENTS = CONTRACTS_DATA.clients || {};

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
		btn_add_row: $("#btn-add-row"),
		btn_generate_menu: $("#btn-generate-menu"),
		contract_details_table: $("#contract-details-table"),
	};
};

const appendContractDetailRow = (product, mealTimeValue, serveDate) => {
	// Generate the options for the Product Select, marking the current product as selected
	const productsOptions = PRODUCTS
		.map(
			(p) =>
				`<option value="${p.id}" data-price="${p.price}" ${p.id === product.id ? "selected" : ""}>
					${p.name}
				</option>`,
		)
		.join("");

	// Generate the options for the Meal Time Select, marking the current meal time as selected
	const mealTimesOptions = MEAL_TIMES
		.map(
			(m) =>
				`<option value="${m.value}" ${m.value == mealTimeValue ? "selected" : ""}>
					${m.label}
				</option>`,
		)
		.join("");

	// Build the new row HTML, ensuring that the price input is populated with the product's price and is disabled/readonly
	const newRow = `
        <tr>
            <td>
				<div class="border-secondary w-auto text-start">
					<div class="input-group input-group-sm has-validation">	
						<select name="product_id" class="form-select " aria-describedby="product_id-error">
							<option value="-1">Seleccione un producto</option>
							${productsOptions}
						</select>	
						<div id="product_id-error" class="invalid-feedback ps-2" role="alert">
							<strong></strong>
						</div>
					</div>
				</div>
            </td>
            <td>
				<div class="border-secondary w-auto text-start">
					<div class="input-group input-group-sm has-validation">	
						<select name="meal_time" class="form-select " aria-describedby="meal_time-error">
							<option value="-1">Seleccione un tiempo de comida</option>
							${mealTimesOptions}
						</select>	
						<div id="meal_time-error" class="invalid-feedback ps-2" role="alert">
							<strong></strong>
						</div>
					</div>
				</div>
            </td>
            <td>
				<div class="border-secondary w-auto text-start">
					<div class="input-group input-group-sm has-validation">
						<input type="date" name="serve_date" class="form-control" aria-describedby="service_date-error"
							min="${new Date().toISOString().split("T")[0]}" 
							value="${serveDate}"
						>
						<div id="product_id-error" class="invalid-feedback ps-2" role="alert">
							<strong></strong>
						</div>
					</div>
				</div>
            </td>
            <td>
				<div class="border-secondary w-auto text-start">
					<div class="input-group input-group-sm has-validation">
						<span class="input-group-text" id="unit_price-icon-left">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 7.010000228881836 31.639999389648438 40.98999786376953" width="12" height="12" fill="currentColor">
								<path d="M31.64 33.91Q30.20 39.60 26.44 42.70Q22.58 45.92 16.82 45.87L16.31 45.87L15.70 48.00L12.18 48.00L12.89 45.51Q11.16 45.17 9.67 44.58L8.72 48.00L5.20 48.00L6.64 42.87Q0 37.99 0 27.10Q0 19.19 4.27 14.23Q8.67 9.11 16.21 8.86L16.75 7.01L20.26 7.01L19.68 9.06Q21.29 9.30 22.90 9.94L23.73 7.01L27.25 7.01L25.95 11.60Q29.59 14.31 31.03 19.29L26.37 20.39Q25.63 18.09 24.56 16.58L17.48 41.77Q25.07 41.16 26.90 32.71L31.64 33.91M21.75 14.04Q20.39 13.28 18.58 13.04L10.84 40.48Q12.28 41.28 13.99 41.60L21.75 14.04M15.06 13.01Q9.86 13.60 7.23 17.72Q4.88 21.36 4.88 27.08Q4.88 34.11 8.01 38.04L15.06 13.01Z"></path>
							</svg>
						</span>
						<input type="number" name="unit_price" class="form-control quantity-input" aria-describedby="unit_price-error" placeholder="Ej: 5000" step="0.01" min="0" 
							value="${product.price}"
							disabled readonly
						>
						<div id="unit_price-error" class="invalid-feedback ps-2" role="alert">
							<strong></strong>
						</div>
					</div>
				</div>
            </td>
            <td>
                <button type="button" class="action-btn btn btn-sm btn-outline-danger rounded-2 btn-delete-row" data-bs-title="Eliminar este detalle del contrato">
                    <i class="bi bi-trash3 pointer-events-none"></i>
                </button>
            </td>
        </tr>
    `;

	// Insert the new row into the table body
	$("#contract-details-table tbody").append(newRow);

	// Update Bootstrap tooltips for dynamically added elements
	if (typeof bootstrap !== "undefined") {
		const tooltipTriggerList = [].slice.call(
			document.querySelectorAll('[data-bs-toggle="tooltip"]'),
		);
		tooltipTriggerList.map(function (tooltipTriggerEl) {
			return new bootstrap.Tooltip(tooltipTriggerEl);
		});
	}

	// Refresh the summary section to reflect the new details
	updateSummary.details();
};

const generateRandomMenu = () => {
	const formData = getFormFields();

	// Validates that start_date, end_date and days_to_serve are defined before attempting to generate the menu.
	if (
		!formData.start_date ||
		!formData.end_date ||
		formData.days_to_serve.length === 0
	) {
		SwalToast.fire({
			icon: "warning",
			title: "Por favor, defina la fecha de inicio, fin y los días de servicio antes de generar el menú.",
		});
		return;
	}

	const startDate = getLocalMidnight(formData.start_date);
	const endDate = getLocalMidnight(formData.end_date);
	const products = PRODUCTS;
	const mealTimes = MEAL_TIMES;

	if (products.length === 0) {
		SwalToast.fire({
			icon: "warning",
			title: "No hay productos disponibles para generar el menú.",
		});
		return;
	}

	let confirmation = confirm("¿Está seguro de que desea generar un menú aleatorio? Esto reemplazará cualquier detalle de contrato existente.");
	if (!confirmation) {
		return;
	}

	// Clear the table before generating to avoid massive duplicates
	$("#contract-details-table tbody tr:not(#empty-row)").remove();

	let currentDate = new Date(startDate);

	// Iterate through each day in the date range, checking if it matches the selected service days and generating a menu item for each meal time if it does.
	while (currentDate <= endDate) {
		// Get the day name (Monday, Tuesday, etc...)
		const dayNames = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
		const dayOfWeek = dayNames[currentDate.getDay()];

		// Verify if the current day of the week is included in the selected days to serve
		if (formData.days_to_serve.includes(dayOfWeek)) {
			// Filter all products by 'type'
			const dishes = PRODUCTS.filter((p) => p.type === "dish");
			const drinks = PRODUCTS.filter((p) => p.type === "drink");

			// Generate a detail for each meal time, selecting a random product for each
			mealTimes.forEach((meal) => {
				// Format date to local YYYY-MM-DD
				const year = currentDate.getFullYear();
				const month = String(currentDate.getMonth() + 1).padStart(
					2,
					"0",
				);
				const day = String(currentDate.getDate()).padStart(2, "0");
				const formattedDate = `${year}-${month}-${day}`;

				// Random dish
				if (dishes.length > 0) {
					const randomDish = dishes[Math.floor(Math.random() * dishes.length)];
					appendContractDetailRow(randomDish, meal.value, formattedDate);
				}

				// Random drink
				if (drinks.length > 0) {
					const randomDrink = drinks[Math.floor(Math.random() * drinks.length)];
					appendContractDetailRow(randomDrink, meal.value, formattedDate);
				}
			});
		}

		currentDate.setDate(currentDate.getDate() + 1); // Move to the next day
	}

	// Hide the "empty" state and update the summary section to reflect the generated menu
	$("#empty-row").addClass("d-none");
	updateSummary.progressBar();

	// Recalculate total value based on the generated menu
	recalculateTotalValue();
};

const recalculateTotalValue = () => {
	const tableEl = getFormElements().contract_details_table;
	const toalValueInput = getFormElements().total_value;
	let totalValue = 0;

	$(tableEl).find("tbody tr:not(#empty-row)").each(function () {
		const productPrice = parseFloat($(this).find('select[name="product_id"] option:selected').data("price")) || 0;
		totalValue += productPrice;
	});

	$(toalValueInput).val(totalValue.toFixed(0)).trigger("input");
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
        validate: (v) => IS_EDITING ? rules.isValidDate(v) : (rules.isValidDate(v) && rules.isTodayOrFutureDate(v)),
        message: IS_EDITING
			? "La fecha de inicio debe ser una fecha válida."
			: "La fecha de inicio debe ser una fecha válida y no puede ser en el pasado."
    },
    end_date: {
        validate: (v, data) => IS_EDITING 
			? rules.isValidDate(v) && getLocalMidnight(v) > getLocalMidnight(data.start_date) 
			: rules.isValidDate(v) && rules.isFutureDate(v) && getLocalMidnight(v) > getLocalMidnight(data.start_date),
        message: IS_EDITING
			? "La fecha de fin debe ser una fecha válida y posterior a la fecha de inicio."
			: "La fecha de fin debe ser una fecha válida, posterior a la fecha de inicio y no puede ser en el pasado."
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
			PRODUCTS.some(
				(product) => product.id === parseInt(v),
			),
		message: "Seleccione un producto válido.",
	},
	meal_time: {
		validate: (v) => rules.isString(v) && rules.isValidMealTime(v),
		message: "Seleccione un tiempo de comida válido.",
	},
	service_date: {
		validate: (v, data) => IS_EDITING 
		? rules.isValidDate(v) && getLocalMidnight(v) > getLocalMidnight(data.start_date) 
		: rules.isValidDate(v) && rules.isTodayOrFutureDate(v) && getLocalMidnight(v) > getLocalMidnight(data.start_date),
		message: IS_EDITING
			? "La fecha de servicio debe ser una fecha válida y posterior a la fecha de inicio del contrato."
			: "La fecha de servicio debe ser una fecha válida, no puede ser en el pasado y debe ser posterior a la fecha de inicio del contrato."
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
    const months = ["ene.", "feb.", "mar.", "abr.", "may.", "jun.", "jul.", "ago.", "sep.", "oct.", "nov.", "dic."];
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
	details: () => {
		const rows = $("#contract-details-table tbody tr:not(#empty-row)");
		const badge = $("#added-items-badge");
		const summaryContainer = $("#contract-summary-meals");

		// Update badge items count
		if (badge.length) {
			badge.text(rows.length);
		}

		// Show/Hide "Sin detalles aún" message based on whether there are rows
		if (rows.length === 0) {
			$("#empty-row").removeClass("d-none");
			summaryContainer.html('<span class="text-muted" style="font-size: .82rem;">Sin detalles aún.</span>');
			return;
		} else {
			$("#empty-row").addClass("d-none");
		}

		// Group counts by meal_time value
		const counts = {};
		rows.each(function () {
			const mealTimeVal = $(this).find('select[name="meal_time"]').val();
			if (mealTimeVal && mealTimeVal !== "-1") {
				counts[mealTimeVal] = (counts[mealTimeVal] || 0) + 1;
			}
		});

		// Rebuild the "Loaded Details" HTML based on the counts, respecting the order of MEAL_TIMES
		let summaryHtml = "";

		// Iterates over MEAL_TIMES to respect the order and get the labels, while also assigning colors based on meal type
		MEAL_TIMES.forEach((meal) => {
			if (counts[meal.value]) {
				// Assign colors similarly to the backend (match Blade)
				let color = "secondary";
				const valLower = String(meal.value).toLowerCase();
				if (valLower.includes("breakfast")) color = "warning";
				else if (valLower.includes("lunch")) color = "success";

				summaryHtml += `
                    <div class="d-flex justify-content-between align-items-center" style="font-size: .82rem;">
                        <span class="badge border rounded-pill text-${color}-emphasis bg-${color}-subtle px-3 py-2" style="font-size: .72rem;">
                            ${meal.label}
                        </span>
                        <span class="text-muted">${counts[meal.value]} fila${counts[meal.value] > 1 ? "s" : ""}</span>
                    </div>
                `;
			}
		});

		// If there are rows but none have the meal time selected yet, show a specific message
		if (summaryHtml === "") {
			summaryHtml =
				'<span class="text-muted" style="font-size: .82rem;">Faltan tiempos de comida por definir.</span>';
		}

		summaryContainer.html(summaryHtml);
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
		.on("change", function () {
			updateSummary.client($(this));
		})
		.trigger("change");

	// Portions per day and Total value events
	elements.portions_per_day
		.off("input")
		.on("input", function () {
			updateSummary.portions($(this));
		})
		.trigger("input");

	elements.total_value
		.off("input")
		.on("input", function () {
			updateSummary.totalValue($(this));
		})
		.trigger("input");

	// Contract Period Events (Start and End Dates)
	const handleDatesChange = () => updateSummary.period();
	elements.start_date
		.off("change")
		.on("change", handleDatesChange)
		.trigger("change");
	elements.end_date
		.off("change")
		.on("change", handleDatesChange)
		.trigger("change");

	// Days to Serve Event
	elements.days_to_serve.off("change").on("change", function () {
		updateSummary.daysToServe();
	});

	// Triggers initial update in case there are pre-selected days (e.g., when editing)
	updateSummary.daysToServe();

	// Deselect All Days Button
	$("#deselect-all-days")
		.off("click")
		.on("click", function () {
			// Deselects all checkboxes and triggers change to update validation and summary
			elements.days_to_serve.prop("checked", false).trigger("change");
		});

	// Meal Time Selection Event within Contract Details (delegated)
	elements.contract_details_table.on(
		"change",
		'select[name="meal_time"]',
		function () {
			updateSummary.details();
		},
	);

	// Product Selection Event within Contract Details (delegated) - updates unit price based on selected product
	elements.contract_details_table.on(
		"change",
		'select[name="product_id"]',
		function () {
			const selectedOption = $(this).find("option:selected");
			const price = selectedOption.data("price") || 0;
			$(this).closest("tr").find('input[name="unit_price"]').val(price);
		},
	);

	// Delete Row Event within Contract Details (delegated) - removes the row and updates the summary accordingly
	elements.contract_details_table.on(
		"click",
		".btn-delete-row, .action-btn.btn-outline-danger",
		function () {
			$(this).closest("tr").remove();
			updateSummary.details(); // Recalc details summary after deletion
			updateSummary.progressBar(); // Recalc progress bar
		},
	);

	// Append Row Event - adds a new empty row to the contract details table and updates the summary accordingly
	elements.btn_add_row.on("click", function () {
		// Appends a new row with default values (id: -1 for new, price: 0) and triggers summary update
		appendContractDetailRow({ id: -1, price: 0 }, "-1", "");
	});

	// Generate Random Menu Event - generates a random menu based on the contract period and selected days, 
	// replacing existing details and updating the summary accordingly
	elements.btn_generate_menu.on("click", function () {
		generateRandomMenu();
	});
};

$(() => {
    bindEventListeners();
    bindRealTimeValidation();
    bindOffcanvasEvents("create-offcanvas");
	updateSummary.details(); // Initial details summary update on page load
    updateSummary.progressBar(); // Initial progress bar update on page load
});