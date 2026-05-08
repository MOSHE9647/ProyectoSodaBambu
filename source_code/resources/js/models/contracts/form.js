import Swal from "sweetalert2";
import { bindOffcanvasEvents } from "../../utils/offcanvas.js";
import { SwalConfirmation, SwalModal, SwalToast } from "../../utils/sweetalert.js";
import { enableBootstrapTooltips, getLaravelFirstError, setLoadingState } from "../../utils/utils.js";
import { clearAllFieldErrors, clearFieldError, showFieldError } from "../../utils/validation.js";
import { openPaymentModal } from "../../pages/sales/payment.js";
import { PaymentStatus } from "../../pages/sales/api.js";
import { initializeCashRegister } from "../../pages/sales/cash-register.js";

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

const DAY_NAMES = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

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
                serve_date: $row.find('input[name="serve_date"]').val()
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
		btn_clear_details: $("#btn-clear-details"),
		calculate_contract_value_btn: $("#calculate-contract-value-btn"),
		contract_details_table: $("#contract-details-table"),
	};
};

const appendContractDetailRow = (product, mealTimeValue, serveDate, startDate = "") => {
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
						<input type="date" name="serve_date" class="form-control" aria-describedby="serve_date-error"
							min="${startDate}"
							value="${serveDate}"
						>
						<div id="serve_date-error" class="invalid-feedback ps-2" role="alert">
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

// Calculates the real total number of service days in the calendar
const calculateTotalServiceDays = (startDateStr, endDateStr, daysToServeArray) => {
    if (!startDateStr || !endDateStr || !daysToServeArray || daysToServeArray.length === 0) return 0;
    
    const startDate = getLocalMidnight(startDateStr);
    const endDate = getLocalMidnight(endDateStr);
    let totalDays = 0;
    
    let currentDate = new Date(startDate);
    while (currentDate <= endDate) {
        const dayOfWeek = DAY_NAMES[currentDate.getDay()];
        if (daysToServeArray.includes(dayOfWeek)) {
            totalDays++;
        }
        currentDate.setDate(currentDate.getDate() + 1);
    }
    return totalDays;
};

const askForMenuGenerationOptions = async (values) => {
	const { start_date, end_date, days_to_serve } = values;

    // Calculate the real total service days in the calendar
    const totalServiceDays = calculateTotalServiceDays(start_date, end_date, days_to_serve);
    
	// Get details that already exist in the table (to subtract them from the estimate)
    const existingDetails = getFormFields().contract_details.filter(d => d.product_id !== -1 && d.meal_time !== "-1" && d.serve_date);

	// Build service day badges dynamically
	const serviceDayBadges = WEEK_DAYS.map((d) => {
		const isActive = days_to_serve.includes(d.value);
		const colorClass = isActive ? "info" : "secondary";
		return `
			<span class="badge border rounded-2 fw-semibold text-${colorClass}-emphasis bg-${colorClass}-subtle px-2 py-2" style="font-size: .68rem">
				${d.label.slice(0, 3)}
			</span>
		`;
	}).join('');

	// Build meal-time checkboxes dynamically
    const mealCheckboxes = MEAL_TIMES.map((m) => {
		const colorClass = m.value === "breakfast" ? "warning" : m.value === "lunch" ? "success" : "secondary";
		const iconClass = m.value === "breakfast" ? "bi-sunrise" : m.value === "lunch" ? "bi-sun" : "bi-cup-straw";
		const description = m.value === "breakfast" ? "Mañana" : m.value === "lunch" ? "Mediodía" : "Tarde";
		return `
			<div class="col-6">
                <input type="checkbox" class="btn-check gen-meal-checkbox" id="gmt-${m.value}" value="${m.value}" autocomplete="off" checked>
                <label for="gmt-${m.value}" data-color="${colorClass}" class="gen-meal-card d-flex align-items-center gap-2 w-100 border border-2 border-${colorClass} bg-${colorClass}-subtle rounded-4 p-2" style="cursor: pointer; transition: .15s;">
                    <div class="d-flex align-items-center justify-content-center flex-shrink-0 rounded-3 bg-${colorClass}" style="width:34px; height:34px;">
                        <i class="bi ${iconClass} text-white fs-6"></i>
                    </div>
                    <div>
                        <div class="fw-semibold text-${colorClass} meal-title" style="font-size: .82rem;">${m.label}</div>
                        <div class="text-${colorClass}-emphasis meal-desc" style="font-size: .7rem;">${description}</div>
                    </div>
                    <i class="bi bi-check-circle-fill text-${colorClass} ms-auto me-1 gen-check" style="font-size: .9rem;"></i>
                </label>
            </div>
		`;
	}).join('');

    const result = await SwalConfirmation.fire({
		title: "Generador de Menú",
		html: `
			<div class="mb-4 text-start">
				<p class="fw-semibold text-uppercase text-muted mb-2" style="font-size: .7rem; letter-spacing: .08em;">
					Per&iacute;odo del Contrato
				</p>

				<div class="d-flex justify-content-between alignt-items-center gap-2 mb-3 flex-wrap">
					<div class="d-flex justify-content-between align-items-center gap-2">
						<div class="d-flex align-items-center bg-info-subtle text-info-emphasis border border-info rounded-3 gap-2 px-3 py-2" style="font-size:.83rem;">
							<i class="bi bi-calendar-event"></i>
							<span class="fw-semibold">${start_date}</span>
						</div>
						<i class="bi bi-arrow-right text-muted" style="font-size:.8rem;"></i>
						<div class="d-flex align-items-center bg-info-subtle text-info-emphasis border border-info rounded-3 gap-2 px-3 py-2" style="font-size:.83rem;">
							<i class="bi bi-calendar-check"></i>
							<span class="fw-semibold">${end_date}</span>
						</div>
					</div>
					<div class="d-flex align-items-center bg-success-subtle border border-success rounded-3 gap-2 ms-auto px-3 py-2" style="font-size:.83rem;">
						<i class="bi bi-grid-3x3-gap text-success"></i>
						<span class="fw-bold text-success">${totalServiceDays}</span>
						<span class="text-muted">días hábiles</span>
					</div>
				</div>

				<div class="d-flex align-items-start flex-wrap gap-2">${serviceDayBadges}</div>
			</div>

			<div class="mb-4 text-start">
				<p class="fw-semibold text-uppercase text-muted mb-2" style="font-size: .7rem; letter-spacing: .08em;">
					Tiempos de Comida a Incluir <span class="text-danger">*</span>
				</p>

				<div class="row g-2">${mealCheckboxes}</div>
			</div>

			<div class="mb-3 text-start">
				<p class="fw-semibold text-uppercase text-muted mb-2" style="font-size: .7rem; letter-spacing: .08em;">
					Si ya existen detalles en la tabla...
				</p>

				<div class="d-flex justify-content-between gap-2">
					<label for="conflictReplace" class="conflict-card d-flex flex-column flex-1 border border-2 border-success bg-success-subtle rounded-4 w-100 gap-1 p-3" id="conflictReplaceWrap" style="cursor: pointer; transition: .15s;">
						<input class="d-none" type="radio" name="conflictMode" id="conflictReplace" value="replace" checked>
						<div class="d-flex align-items-center justify-content-between">
							<div class="d-flex align-items-center justify-content-center flex-shrink-0 rounded-3 bg-success conflict-icon" style="width: 32px; height:32px;">
								<i class="bi bi-arrow-repeat text-white fs-6"></i>
							</div>
							<i class="bi bi-record-circle-fill fs-6 text-success" id="conflictReplaceIcon"></i>
						</div>
						<div class="fw-semibold text-success mt-1 fs-6 title-text">Reemplazar todo</div>
						<div class="text-success-emphasis lh-base desc-text" style="font-size:.72rem;">Borra los existentes y genera desde cero.</div>
					</label>

					<label for="conflictKeep" class="conflict-card d-flex flex-column flex-1 border border-2 border-secondary bg-secondary-subtle rounded-4 w-100 gap-1 p-3" id="conflictKeepWrap" style="cursor: pointer; transition: .15s;">
						<input class="d-none" type="radio" name="conflictMode" id="conflictKeep" value="keep">
						<div class="d-flex align-items-center justify-content-between">
							<div class="d-flex align-items-center justify-content-center flex-shrink-0 rounded-3 bg-secondary conflict-icon" style="width: 32px; height:32px;">
								<i class="bi bi-shield-check text-white fs-6"></i>
							</div>
							<i class="bi bi-circle text-secondary fs-6" id="conflictKeepIcon"></i>
						</div>
						<div class="fw-semibold text-secondary mt-1 fs-6 title-text">Conservar existentes</div>
						<div class="text-secondary-emphasis lh-base desc-text" style="font-size:.72rem;">Solo agrega los que falten por completar.</div>
					</label>
				</div>
			</div>

			<div class="row g-2">
				<div class="col-6 d-none" id="days-input-container">
					<div class="text-start border-secondary w-auto">
						<label for="days-to-generate" class="form-label fw-semibold text-uppercase text-muted mb-2" style="font-size: .7rem; letter-spacing: .08em;">
							Días a generar <span class="text-danger">*</span>
						</label>
						
						<div class="input-group has-validation">				
							<span class="input-group-text" id="days-to-generate-icon-left">
								<i class="bi bi-calendar"></i>
							</span>
						
							<input id="days-to-generate" name="days-to-generate" type="number" class="form-control" aria-describedby="days-to-generate-icon-left days-to-generate-error" value="${totalServiceDays}" min="1" max="${totalServiceDays}" step="1">

							<span class="input-group-text">
								<i class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-title="Cantidad de días a los que se les generará el menú dentro del periodo establecido."></i>
							</span>

							<!-- AÑADE ESTO PARA ARREGLAR EL ESTILO -->
							<div id="days-to-generate-error" class="invalid-feedback" role="alert">
								<strong></strong>
							</div>
						</div>
					</div>
				</div>
				<div class="col-12 d-flex align-items-end justify-content-end" id="estimate-container" style="transition: width 0.2s;">
					<div class="d-flex align-items-center justify-content-between border border-1 border-info bg-info-subtle rounded-3 p-3 w-100" style="height: 37.6px;">
						<div class="d-flex align-items-center gap-2" style="font-size: .82rem;">
							<i class="bi bi-calculator text-info fs-6"></i>
							<span class="text-info-emphasis">Filas estimadas a generar</span>
						</div>
						<span id="genEstimate" class="fs-6 fw-bold text-info-emphasis">0</span>
					</div>
				</div>
			</div>
        `,
		width: "560px",
		confirmButtonText: "Generar Menú",
		cancelButtonText: "Cancelar",
		didOpen: () => {
			// --- Real-Time Estimation Function ---
			const updateEstimate = () => {
				const checkedMeals = Array.from(document.querySelectorAll(".gen-meal-checkbox:checked")).map((cb) => cb.value);
				const isReplace = document.getElementById("conflictReplace").checked;
				const daysInput = parseInt(document.getElementById("days-to-generate").value) || 0;

				let daysToCalculate = isReplace ? totalServiceDays : daysInput;
				if (daysToCalculate > totalServiceDays) daysToCalculate = totalServiceDays;
				if (daysToCalculate < 0) daysToCalculate = 0;

				// Calculate 2 rows (dish + drink) per meal time, multiplied by days
				let estimatedRows = daysToCalculate * checkedMeals.length * 2;

				// If user chooses "Keep", subtract rows that already exist in the table from the estimate
				if (!isReplace) {
					const existingCount = existingDetails.filter((d) => checkedMeals.includes(d.meal_time)).length;
					estimatedRows = Math.max(0, estimatedRows - existingCount);
				}

				document.getElementById("genEstimate").textContent = estimatedRows;
			};

			// --- Visual Logic for "Replace" vs "Keep" ---
			window.updateConflictStyles = () => {
				const isReplace = document.getElementById("conflictReplace").checked;
				const $replaceWrap = $("#conflictReplaceWrap");
				const $keepWrap = $("#conflictKeepWrap");

				if (isReplace) {
					$replaceWrap.removeClass("border-secondary bg-secondary-subtle").addClass("border-success bg-success-subtle");
					$replaceWrap.find(".conflict-icon").removeClass("bg-secondary").addClass("bg-success");
					$replaceWrap.find(".title-text").removeClass("text-secondary").addClass("text-success");
					$replaceWrap.find(".desc-text").removeClass("text-secondary-emphasis").addClass("text-success-emphasis");
					$("#conflictReplaceIcon").removeClass("bi-circle text-secondary").addClass("bi-record-circle-fill text-success");

					$keepWrap.removeClass("border-success bg-success-subtle").addClass("border-secondary bg-secondary-subtle");
					$keepWrap.find(".conflict-icon").removeClass("bg-success").addClass("bg-secondary");
					$keepWrap.find(".title-text").removeClass("text-success").addClass("text-secondary");
					$keepWrap.find(".desc-text").removeClass("text-success-emphasis").addClass("text-secondary-emphasis");
					$("#conflictKeepIcon").removeClass("bi-record-circle-fill text-success").addClass("bi-circle text-secondary");

					$("#days-input-container").addClass("d-none");
					$("#estimate-container").removeClass("col-6").addClass("col-12");
				} else {
					$replaceWrap.removeClass("border-success bg-success-subtle").addClass("border-secondary bg-secondary-subtle");
					$replaceWrap.find(".conflict-icon").removeClass("bg-success").addClass("bg-secondary");
					$replaceWrap.find(".title-text").removeClass("text-success").addClass("text-secondary");
					$replaceWrap.find(".desc-text").removeClass("text-success-emphasis").addClass("text-secondary-emphasis");
					$("#conflictReplaceIcon").removeClass("bi-record-circle-fill text-success").addClass("bi-circle text-secondary");

					$keepWrap.removeClass("border-secondary bg-secondary-subtle").addClass("border-success bg-success-subtle");
					$keepWrap.find(".conflict-icon").removeClass("bg-secondary").addClass("bg-success");
					$keepWrap.find(".title-text").removeClass("text-secondary").addClass("text-success");
					$keepWrap.find(".desc-text").removeClass("text-secondary-emphasis").addClass("text-success-emphasis");
					$("#conflictKeepIcon").removeClass("bi-circle text-secondary").addClass("bi-record-circle-fill text-success");

					$("#days-input-container").removeClass("d-none");
					$("#estimate-container").removeClass("col-12").addClass("col-6");
				}
				updateEstimate(); // Update estimate when mode changes
			};

			// Conflict card event listeners
			document.querySelectorAll('input[name="conflictMode"]').forEach((radio) => {
				radio.addEventListener("change", window.updateConflictStyles);
			});
			window.updateConflictStyles();

			// Meal checkbox event listeners
			document.querySelectorAll(".gen-meal-checkbox").forEach((checkbox) => {
				checkbox.addEventListener("change", (e) => {
					const checkedBoxes = document.querySelectorAll(".gen-meal-checkbox:checked");

					if (checkedBoxes.length === 0) {
						e.target.checked = true; // Keep at least one option selected
						Swal.showValidationMessage("Debe mantener al menos un tiempo de comida seleccionado.");
						setTimeout(() => Swal.resetValidationMessage(), 3000);
						return;
					}

					const card = e.target.nextElementSibling;
					const colorClass = card.getAttribute("data-color");
					const checkIcon = card.querySelector(".gen-check");
					const title = card.querySelector(".meal-title");
					const desc = card.querySelector(".meal-desc");

					if (e.target.checked) {
						card.classList.add(`border-${colorClass}`, `bg-${colorClass}-subtle`);
						card.classList.remove("border-secondary", "bg-secondary-subtle");
						title.classList.add(`text-${colorClass}`, "fw-semibold");
						desc.classList.add(`text-${colorClass}-emphasis`);
						desc.classList.remove("text-muted");
						checkIcon.classList.replace("bi-circle", "bi-check-circle-fill");
						checkIcon.classList.replace("text-muted", `text-${colorClass}`);
					} else {
						card.classList.remove(`border-${colorClass}`, `bg-${colorClass}-subtle`);
						card.classList.add("border-secondary", "bg-secondary-subtle");
						title.classList.remove(`text-${colorClass}`);
						desc.classList.remove(`text-${colorClass}-emphasis`);
						desc.classList.add("text-muted");
						checkIcon.classList.replace("bi-check-circle-fill", "bi-circle");
						checkIcon.classList.replace(`text-${colorClass}`, "text-muted");
					}
					updateEstimate(); // Update estimate when meal selection changes
				});
			});

			// Days input event listener
			document.getElementById("days-to-generate").addEventListener("input", updateEstimate);

			// Enable Bootstrap tooltips for dynamically added elements
			if (typeof bootstrap !== "undefined") {
				const container = document.querySelector(".swal-container");
				enableBootstrapTooltips(container);
			}
		},
		preConfirm: () => {
			const selectedMeals = Array.from(document.querySelectorAll(".gen-meal-checkbox:checked")).map((cb) => cb.value);
			const conflictMode = document.querySelector('input[name="conflictMode"]:checked').value;
			let daysCount = totalServiceDays;

			// Validate days input when "Keep" mode is selected
			if (conflictMode === "keep") {
				const inputVal = document.getElementById("days-to-generate").value;
				if (!inputVal || parseInt(inputVal) < 1 || parseInt(inputVal) > totalServiceDays) {
					Swal.showValidationMessage(`Ingrese una cantidad válida de días (1 - ${totalServiceDays}).`);
					return false;
				}
				daysCount = parseInt(inputVal);
			}

			return { selectedMeals, conflictMode, daysCount };
		},
	});

    return result.isConfirmed ? result.value : null;
};

const generateRandomMenu = async () => {
	const formData = getFormFields();

	if (!formData.start_date || !formData.end_date || formData.days_to_serve.length === 0) {
		SwalToast.fire({ icon: "warning", title: "Por favor, defina la fecha de inicio, fin y los días de servicio." });
		return;
	}

	const dishes = PRODUCTS.filter((p) => p.type === "dish");
	const drinks = PRODUCTS.filter((p) => p.type === "drink");

	if (dishes.length === 0 && drinks.length === 0) {
		SwalToast.fire({ icon: "warning", title: "No hay productos disponibles para generar el menú." });
        return;
	}

	const startDate = getLocalMidnight(formData.start_date);
	const endDate = getLocalMidnight(formData.end_date);
	const daysToServe = formData.days_to_serve;

	// Open the modal
	const options = await askForMenuGenerationOptions({
		start_date: formData.start_date,
		end_date: formData.end_date,
		days_to_serve: formData.days_to_serve
	});
	if (!options) return; // User canceled
    
    const { selectedMeals, conflictMode, daysCount } = options;
	const detailsTable = getFormElements().contract_details_table || $("#contract-details-table");

    // Handle conflict mode
	if (conflictMode === "replace") {
		detailsTable.find("tbody tr:not(#empty-row)").remove();
	} else {
        // "Keep" mode: remove only partially filled rows (invalid or empty)
		detailsTable.find("tbody tr:not(#empty-row)").each(function () {
			const pId = $(this).find('select[name="product_id"]').val();
			const mT = $(this).find('select[name="meal_time"]').val();
			const sD = $(this).find('input[name="serve_date"]').val();
			if (pId === "-1" || mT === "-1" || !sD) {
				$(this).remove();
			}
		});
	}

	// Map existing rows (needed only in "Keep" mode)
	const dayCoverage = {};
	if (conflictMode === "keep") {
		detailsTable.find("tbody tr:not(#empty-row)").each(function () {
			const pId = parseInt($(this).find('select[name="product_id"]').val());
			const mT = $(this).find('select[name="meal_time"]').val();
			const sD = $(this).find('input[name="serve_date"]').val();

			const prod = PRODUCTS.find((p) => p.id === pId);
			if (prod && sD && mT !== "-1") {
				if (!dayCoverage[sD]) dayCoverage[sD] = {};
				if (!dayCoverage[sD][mT]) dayCoverage[sD][mT] = { dishes: 0, drinks: 0 };

				if (prod.type === "dish") dayCoverage[sD][mT].dishes++;
				if (prod.type === "drink") dayCoverage[sD][mT].drinks++;
			}
		});
	}

	// Start generation
	let currentDate = new Date(startDate);
	let usedThisWeek = new Set(); 
	let serviceDaysProcessed = 0;

	const drawRandomProduct = (catalog) => {
		if (catalog.length === 0) return null;
		let available = catalog.filter(p => !usedThisWeek.has(p.id));
		if (available.length === 0) available = catalog;
		const product = available[Math.floor(Math.random() * available.length)];
		usedThisWeek.add(product.id);
		return product;
	};

	while (currentDate <= endDate) {
		if (serviceDaysProcessed >= daysCount) break;
		if (currentDate.getDay() === 1) usedThisWeek.clear();

		const dayOfWeek = DAY_NAMES[currentDate.getDay()];

		if (daysToServe.includes(dayOfWeek)) {
			const year = currentDate.getFullYear();
			const month = String(currentDate.getMonth() + 1).padStart(2, "0");
			const day = String(currentDate.getDate()).padStart(2, "0");
			const formattedDate = `${year}-${month}-${day}`;

			// 5. Generate only for meal times selected in the modal
			selectedMeals.forEach((mealValue) => {
				const coverage = dayCoverage[formattedDate]?.[mealValue] || { dishes: 0, drinks: 0 };
				
				if (coverage.dishes < 1) {
					const dish = drawRandomProduct(dishes);
					if (dish) appendContractDetailRow(dish, mealValue, formattedDate, startDate);
				}

				if (coverage.drinks < 1) {
					const drink = drawRandomProduct(drinks);
					if (drink) appendContractDetailRow(drink, mealValue, formattedDate, startDate);
				}
			});

			serviceDaysProcessed++;
		}
		currentDate.setDate(currentDate.getDate() + 1); 
	}

    // Refresh UI
	$("#empty-row").addClass("d-none");
	updateSummary.progressBar();
	recalculateTotalValue();
	validateTableUniqueness();
    
    SwalToast.fire({ icon: "success", title: "Menú generado correctamente." });
};

const recalculateTotalValue = (initialRecalc = false) => {
	const tableEl = getFormElements().contract_details_table;
	const totalValueInput = getFormElements().total_value;

	const portionsPerDay = parseInt(getFormElements().portions_per_day.val()) || 0;
	const totalValue = parseInt(totalValueInput.val()) || 0;
	
	let contractValue = 0;

	$(tableEl).find("tbody tr:not(#empty-row)").each(function () {
		const productPrice = parseFloat($(this).find('select[name="product_id"] option:selected').data("price")) || 0;
		contractValue += (productPrice * portionsPerDay);
	});

	if (initialRecalc) $(totalValueInput).val(contractValue.toFixed(0));
	else $(totalValueInput).val(contractValue.toFixed(0)).trigger("input");
};

const clearDetailsTable = () => {
	const tableEl = getFormElements().contract_details_table;
	if ($(tableEl).find("tbody tr:not(#empty-row)").length === 0) {
		SwalToast.fire({
			icon: "info",
			title: "La tabla de detalles del contrato ya está vacía.",
		});
		return;
	}

	SwalConfirmation.fire({
		icon: "warning",
		title: "¿Está seguro de que desea eliminar todos los detalles del contrato?",
		text: "Esta acción no se puede deshacer.",
		confirmButtonText: "Sí, eliminar",
		cancelButtonText: "Cancelar",
	}).then((result) => {
		if (result.isConfirmed) {
			// Remove all rows except the empty state row and update the summary
			$(tableEl).find("tbody tr:not(#empty-row)").remove();
			updateSummary.details();
			updateSummary.progressBar();
		}
	});
};

const getMissingDatesCount = () => {
    const formData = getFormFields();
    const expectedDates = new Set();
    
    // Calculate expected service dates based on start_date, end_date and days_to_serve
    if (formData.start_date && formData.end_date && formData.days_to_serve.length > 0) {
        const startDate = getLocalMidnight(formData.start_date);
        const endDate = getLocalMidnight(formData.end_date);
        
        let currentDate = new Date(startDate);
        while (currentDate <= endDate) {
            const dayOfWeek = DAY_NAMES[currentDate.getDay()];
            if (formData.days_to_serve.includes(dayOfWeek)) {
                const year = currentDate.getFullYear();
                const month = String(currentDate.getMonth() + 1).padStart(2, "0");
                const day = String(currentDate.getDate()).padStart(2, "0");
                expectedDates.add(`${year}-${month}-${day}`);
            }
            currentDate.setDate(currentDate.getDate() + 1);
        }
    }

    // Calculate provided service dates from the contract details table
    const providedDates = new Set();
    $(getFormElements().contract_details_table).find('tbody tr:not(#empty-row)').each(function () {
        const serveDate = $(this).find('input[name="serve_date"]').val();
        if (serveDate) {
            providedDates.add(serveDate);
        }
    });

    // Return the count of expected dates that are missing from the provided dates
    return [...expectedDates].filter(expectedDate => !providedDates.has(expectedDate)).length;
};

const confirmIncompleteContract = async (missingCount) => {
    const confirmation = await SwalConfirmation.fire({
        icon: "question",
        title: "Contrato Incompleto",
        text: `Faltan ${missingCount} día(s) por asignar en este período. ¿Desea guardar el contrato de todos modos y completar el menú después?`,
        confirmButtonText: "Sí, guardar",
        cancelButtonText: "Revisar menú",
    }).then((result) => {
        return result.isConfirmed;
    });

	return confirmation;
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
	serve_date: {
		validate: (v, data) => {
			// If we're editing, the service date can be today or in the past, but if we're creating a new contract, it must be today or in the future.
			// In both cases, it must also be on or after the contract's start date and correspond to one of the selected days to serve.
            const isDateValid = IS_EDITING 
                ? rules.isValidDate(v) && getLocalMidnight(v) >= getLocalMidnight(data.start_date) 
                : rules.isValidDate(v) && rules.isTodayOrFutureDate(v) && getLocalMidnight(v) >= getLocalMidnight(data.start_date);
            if (!isDateValid) return false;

			// Check if the day of the week of the service date corresponds to one of the selected days to serve in the contract
            const dateObj = getLocalMidnight(v);
            const dayName = DAY_NAMES[dateObj.getDay()];

            return data.days_to_serve.includes(dayName);
        },
        message: "Fecha inválida o no corresponde a los días de servicio definidos."
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

function validatePaymentDetails(payment_details = []) {
	if (!Array.isArray(payment_details) && payment_details.length > 0) {
		// SwalToast.fire({ icon: "error", title: "Algunos de los detalles de pago no son válidos." });
		return { isValid: false, message: "Algunos de los detalles de pago no son válidos." };
	}
	
	// Validate payment details
	payment_details.forEach((detail, index) => {
		for (const [fieldId, validator] of Object.entries(
			paymentDetailValidators,
		)) {
			const value = detail[fieldId];
			if (!validator.validate(value)) {
				return { isValid: false, message: `Pago ${index + 1}: ${validator.message}` };
			}
		}
	});

	return { isValid: true, message: null };
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

function validateTableUniqueness() {
	const seen = new Set();
	let hasDuplicates = false;

	$("#contract-details-table tbody tr:not(#empty-row)").each(function () {
		const $row = $(this);
		const productId = $row.find('select[name="product_id"]').val();
		const mealTime = $row.find('select[name="meal_time"]').val();
		const serveDate = $row.find('input[name="serve_date"]').val();

		// Clear previous duplicate highlights
		$row.removeClass("table-danger border-danger");

		// Only validate if row isn't empty
		if (productId !== "-1" && mealTime !== "-1" && serveDate) {
			const key = `${productId}-${mealTime}-${serveDate}`;
			if (seen.has(key)) {
				$row.addClass("table-danger border-danger");
				hasDuplicates = true;
			} else {
				seen.add(key);
			}
		}
	});

	if (hasDuplicates) {
		SwalToast.fire({
			icon: "error",
			title: "Se detectaron filas duplicadas (Mismo Producto + Fecha + Tiempo).",
		});
	}

	return !hasDuplicates;
};

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

// Helper extracted outside event handlers to avoid recreating it on each keystroke
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
	details: (initialRecalc = false) => {
		const rows = $("#contract-details-table tbody tr:not(#empty-row)");
		const badge = $("#added-items-badge");
		const summaryContainer = $("#contract-summary-meals");

		// Update badge items count
		if (badge.length) {
			badge.text(rows.length);
		}

		// Show/hide the empty-state message based on whether there are rows
		if (rows.length === 0) {
			$("#empty-row").removeClass("d-none");
			summaryContainer.html('<span class="text-muted" style="font-size: .82rem;">Sin detalles aún.</span>');
			recalculateTotalValue(initialRecalc); // Ensure total value is recalculated when details change
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
		recalculateTotalValue(initialRecalc); // Recalculate total value whenever details change
	},
	progressBar: (initialLoad = false) => {
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
			if (fieldId === "total_value" && initialLoad) return;

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

// ==================== Submission Handler ====================

const handleFormSubmission = async (event, validationResult) => {
	const form = event.target;
	const url = form.action;
	const method = IS_EDITING ? "PUT" : "POST";
	const [message, values] = validationResult;

	if (method === "PUT") {
		const contractId = url.split("/").pop();
		values.id = parseInt(contractId); // Include contract ID in the payload for updates
	}

	values.contract_details = values.contract_details.map(detail => {
		// Convert empty strings to null for optional fields
		if (detail.id === null || detail.id === '-1') {
			const { id, ...rest } = detail; // Exclude id from the payload if it's null or -1
			return rest;
		}
		return detail;
	});

	values.payment_status = PaymentStatus.PAID; // Assume paid by default, will be updated if payment details are provided

	const submitToServer = async () => {
		setLoadingState(FORM_ID, true);

		try {
			const response = await fetch(url, {
				method: method,
				headers: {
					"Content-Type": "application/json",
					Accept: "application/json",
					"X-CSRF-TOKEN": typeof csrfToken !== "undefined" ? csrfToken : document.querySelector('meta[name="csrf-token"]')?.content || "",
				},
				body: JSON.stringify(values),
			});

			const result = await response.json();
			if (!response.ok) throw result;

			return { success: true, data: result.data, redirect: result.redirect, message: result.message };
		} catch (error) {
			console.error("Error al guardar:", error);
			const errorMsg = error.errors
				? getLaravelFirstError(error.errors)
				: error.message || "Error al procesar el contrato";
			SwalToast.fire({ icon: "error", title: errorMsg });
			return { success: false };
		} finally {
			setLoadingState(FORM_ID, false);
		}
	};

	const amountPaid = Number(CONTRACTS_DATA.amountPaid) || 0;
	const newTotal = Number(values.total_value) || 0;
	const pendingBalance = newTotal - amountPaid;

	if (pendingBalance > 0) {
		await openPaymentModal({
			total: pendingBalance,
			title: IS_EDITING ? "Cobrar Diferencia del Contrato" : "Procesar Pago del Contrato",
			loadingId: FORM_ID,
			onComplete: async (paymentDetails, totalTendered) => {
				const { isValid, message } = validatePaymentDetails(paymentDetails);
				if (!isValid) {
					Swal.showValidationMessage(message || "Algunos de los detalles de pago no son válidos.");
					setTimeout(() => Swal.resetValidationMessage(), 3000);
					return false;
				}
				values.payment_details = paymentDetails;
				const result = await submitToServer();

				if (result.success && result.redirect) {
					setTimeout(() => window.location.href = result.redirect, 1500);
				}
				return result;
			}
		});
	} else {
		values.payment_details = []; // Ensure payment details is an empty array if no payment is needed
		
		if (pendingBalance < 0) {
			const amountToReturn = Math.abs(pendingBalance).toLocaleString('es-CR', { 
				style: 'currency', 
				currency: 'CRC' 
			});
	
			const confirmation = await SwalConfirmation.fire({
				icon: "info",
				title: "Confirmar Devolución",
				html: `
					El cliente tiene un pago previo que excede el nuevo total del contrato. 
					Se deberá procesar una devolución por un monto de <strong>${amountToReturn}</strong>.
					<br><br>¿Deseas continuar con el registro?
				`,
				confirmButtonText: "Sí, confirmar y enviar",
				cancelButtonText: "No, revisar contrato",
			}).then((result) => result.isConfirmed);
	
			if (!confirmation) {
				setLoadingState(FORM_ID, false);
				return;
			}
		}

		const result = await submitToServer();

		if (result.success) {
			SwalToast.fire({ icon: "success", title: result.message || "Contrato guardado exitosamente." });
			if (result.redirect) {
				window.location.href = result.redirect;
			}
		}
	}
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
			recalculateTotalValue(); // Recalculate total value in real-time as portions change
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

	// Trigger initial update in case there are pre-selected days (e.g., when editing)
	updateSummary.daysToServe();

	// Deselect All Days Button
	$("#deselect-all-days")
		.off("click")
		.on("click", function () {
			// Deselect all checkboxes and trigger change to refresh validation and summary
			elements.days_to_serve.prop("checked", false).trigger("change");
		});

	// Meal Time Selection Event within Contract Details (delegated)
	elements.contract_details_table.on(
		"change",
		'select[name="meal_time"]',
		function () {
			updateSummary.details(); // Recalculate details summary after meal time change
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

	// Service Date Change Event within Contract Details (delegated) - validates the date and checks overall uniqueness on each change
	elements.contract_details_table.on('change', 'select, input', function () {
		const fieldName = $(this).attr('name');
		
		// If date is changed manually, validate it immediately to provide real-time feedback
		if (fieldName === 'serve_date') {
			const isValid = contractDetailValidators.serve_date.validate($(this).val(), getFormFields());
			if (!isValid) {
				$(this).addClass('is-invalid');
				$(this).siblings('.invalid-feedback').find('strong').text(contractDetailValidators.serve_date.message);
			} else {
				$(this).removeClass('is-invalid');
			}
		}
	
		// Check the overall uniqueness of the table with each change
		validateTableUniqueness();
	});

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
	elements.btn_generate_menu.on("click", async function () {
		await generateRandomMenu();
	});

	// Clear Details Event - removes all rows from the contract details table after confirmation and updates the summary accordingly
	elements.btn_clear_details.on("click", function () {
		clearDetailsTable();
	});

	// Recalculate Total Value Event - recalculates the total contract value based on the current details and portions per day, then updates the summary
	elements.calculate_contract_value_btn.on("click", function () {
		if ($(elements.contract_details_table).find("tbody tr:not(#empty-row)").length === 0) {
			SwalToast.fire({
				icon: "info",
				title: "Deben existir detalles de contrato para calcular el valor total.",
			});
			return;
		}
		recalculateTotalValue();
	});

	// Intercept form submission
    $(`#${FORM_ID}`).on("submit", async function (e) {
        e.preventDefault();

		// 1. Strong (blocking) validations
        const [isValid, fieldId, message, values] = validateContractForm();
        if (!isValid) {
            SwalToast.fire({
                icon: "error",
                title: message || "Por favor, corrija los errores en el formulario antes de enviar.",
            });
            return;
        }

        if (!validateTableUniqueness()) {
            return; 
        }

		// 2. Check for missing service days
        const missingDatesCount = getMissingDatesCount();
		let proceedWithSubmission = true;

		if (missingDatesCount > 0) {
			proceedWithSubmission = await confirmIncompleteContract(missingDatesCount);
		}

		if (!proceedWithSubmission) {
			SwalToast.fire({
				icon: "info",
				title: "Revise el menú para completar los días faltantes.",
			});
			return;
		}

		handleFormSubmission(e, [message, values]);
    });
};

// ====================== Initialization ======================

$(() => {
	initializeCashRegister(); // Ensure cash register is initialized before any interactions
    bindEventListeners(); // Bind all event listeners for form fields and buttons
    bindRealTimeValidation(); // Bind real-time validation for form fields
    bindOffcanvasEvents("create-offcanvas"); // Bind events related to the offcanvas component for creating contract details
	updateSummary.details(true); // Initial details summary update on page load
    updateSummary.progressBar(); // Initial progress bar update on page load
});