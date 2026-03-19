import { deleteModel } from "../actions.js";
import { CreateNewDataTable } from "../../utils/datatables.js";
import {
	escapeHtml,
	formatTime,
	getInitials,
	formatDate,
	setLoadingState,
} from "../../utils/utils.js";
import { initAttendanceForm } from "./form.js";
import {
	clearAllFieldErrors,
	clearFieldError,
	showFieldError,
	validateAndDisplayField,
} from "../../utils/validation.js";

// ==================== Constants ====================

const MODEL_NAME = "reporte de asistencia";
const APP_DATA = window.AttendanceAppData ?? {};
const APP_EMPLOYEES = Array.isArray(APP_DATA.employees)
	? APP_DATA.employees
	: [];
const DEFAULT_FILTER_VALUE = "all";
const ATTENDANCE_TABLE_ID = "attendance-table";
const TAB_BUTTON_SELECTOR = "#main-tab-tab .nav-link[data-bs-target]";

const TAB_IDS = {
	attendance: "nav-attendance",
	history: "nav-history",
	salary: "nav-salary",
};
const FILTER_SELECTORS = {
	employee: "#attendanceEmployeeFilter",
	date: "#attendanceDateFilter",
};
const SALARY = {
	formId: "employee-salary-form",
	submitFormId: "salary-submit-form",
	tab: "salary",
	eventNamespace: ".salaryCalcForm",
};
const MODEL_ROUTES = {
	index: route("attendance.index"),
	historyData: route("attendance.history.data"),
	tabs: route("attendance.tabs", { tab: ":tab" }),
	destroy: route("attendance.destroy", { timesheet: ":id" }),
};

const loadedTabs = new Set();

/**
 * Binds delegated realtime validation handlers for dynamic/lazy forms.
 *
 * The handler is first unbound (by namespace) and then rebound to prevent
 * duplicate listeners when the same tab content is reinitialized.
 *
 * @param {string} formSelector - CSS selector for the target form.
 * @param {Record<string, {validator: Function, emptyMsg?: string, invalidMsg: string}>} validators
 * Field validator map keyed by field id.
 * @param {string} namespace - jQuery namespace suffix used to scope listeners.
 * @returns {void}
 */
function bindRealtimeValidation(formSelector, validators, namespace) {
	$(document)
		.off(`input${namespace} change${namespace}`, formSelector)
		.on(`input${namespace} change${namespace}`, formSelector, (event) => {
			const target = $(event.target);
			const fieldId = target.attr("id");
			if (!fieldId || !Object.hasOwn(validators, fieldId)) return;

			const value = String(target.val() ?? "").trim();
			const { validator, emptyMsg, invalidMsg } = validators[fieldId];

			if (!value) {
				if (emptyMsg) showFieldError(fieldId, emptyMsg);
				else clearFieldError(fieldId);
				return;
			}

			if (!validator(value)) {
				showFieldError(fieldId, invalidMsg);
				return;
			}

			clearFieldError(fieldId);
		});
}

/**
 * Removes blank or whitespace-only query params in place.
 *
 * @param {URLSearchParams} params - Mutable query parameter collection.
 * @returns {void}
 */
function pruneEmptyParams(params) {
	for (const [key, value] of [...params.entries()]) {
		if (!String(value).trim()) params.delete(key);
	}
}

/**
 * Wraps arbitrary salary-tab content in the standard card layout shell.
 *
 * @param {string} content - Inner HTML to place inside the salary card body.
 * @returns {string} Full HTML string with title/header and provided content.
 */
function renderSalaryCard(content) {
	return `
		<div class="card-container rounded-2 p-4">
			<h5 class="text-muted pb-3 border-bottom border-secondary">
				<i class="bi bi-currency-dollar me-3"></i>
				Calcular Salario por Colaborador
			</h5>
			${content}
		</div>
	`;
}

// ==================== Global Functions ====================

/**
 * Deletes an attendance entry.
 *
 * Exposed globally so DataTable action buttons can invoke it by function name.
 *
 * @param {Event} event - Trigger event from delete action.
 * @returns {void}
 */
window.deleteAttendance = (event) => deleteModel(event, MODEL_NAME);

// ==================== Helper Functions ====================

/**
 * Initializes the attendance history tab and wires DataTable + filter behavior.
 *
 * This function is responsible for creating the DataTable instance, rendering
 * row cells, rebuilding dynamic employee filter options, and synchronizing the
 * clear-filters button state.
 *
 * @returns {void}
 */
function initHistoryTab() {
	/**
	 * Rebuilds employee filter options from API payload or fallback sources.
	 *
	 * Priority order:
	 * 1. Explicit `filterData` from backend.
	 * 2. Current response rows.
	 * 3. Initial employees embedded in page boot data.
	 *
	 * @param {Array<object>} [rows=[]] - Table row data.
	 * @param {Array<object>} [filterData=[]] - Precomputed employee filter data.
	 * @returns {void}
	 */
	const updateSelectFilter = (rows = [], filterData = []) => {
		const select = document.querySelector(FILTER_SELECTORS.employee);
		if (!select) return;

		const employees = new Map();
		const source = filterData.length
			? filterData
			: rows.length
				? rows.map((r) => ({
						id: r.employee_id,
						name: r.employee?.name,
					}))
				: APP_EMPLOYEES;

		source.forEach((emp) => {
			if (emp?.id && emp?.name)
				employees.set(String(emp.id), String(emp.name));
		});

		const sorted = Array.from(employees.entries()).sort((a, b) =>
			a[1].localeCompare(b[1], "es", { sensitivity: "base" }),
		);
		const currentVal = employees.has(select.value)
			? select.value
			: DEFAULT_FILTER_VALUE;

		select.innerHTML =
			`<option value="${DEFAULT_FILTER_VALUE}">Todos</option>` +
			sorted
				.map(
					([id, name]) =>
						`<option value="${escapeHtml(id)}">${escapeHtml(name)}</option>`,
				)
				.join("");
		select.value = currentVal;
	};

	/**
	 * Toggles clear-filters button visibility based on current filter values.
	 *
	 * The clear button is shown if at least one filter is active:
	 * - employee filter differs from the default "all"
	 * - date filter has a non-empty value
	 *
	 * @returns {void}
	 */
	const syncClearFiltersButtonVisibility = () => {
		const employeeValue =
			document.querySelector(FILTER_SELECTORS.employee)?.value ??
			DEFAULT_FILTER_VALUE;
		const dateValue =
			document.querySelector(FILTER_SELECTORS.date)?.value?.trim() ?? "";
		const hasActiveFilters =
			employeeValue !== DEFAULT_FILTER_VALUE || dateValue.length > 0;

		document
			.getElementById("attendance-clear-filters")
			?.classList.toggle("d-none", !hasActiveFilters);
	};

	/**
	 * Initializes a DataTable for displaying employee attendance records with filtering capabilities.
	 * 
	 * @type {DataTable}
	 * @description Creates a comprehensive attendance tracking table with the following features:
	 * - Displays employee information (name, email, and avatar initials)
	 * - Shows work dates formatted according to locale
	 * - Indicates holiday status with visual badges
	 * - Tracks clock-in (start_time) and clock-out (end_time) times
	 * - Calculates and displays total hours worked with color-coded status badges:
	 *   - Red: Overtime (>8 hours)
	 *   - Green: Normal hours (≤8 hours)
	 *   - Gray: No hours recorded
	 * 
	 * @param {string} ATTENDANCE_TABLE_ID - The DOM element ID for the DataTable container
	 * @param {string} MODEL_ROUTES.historyData - API endpoint for fetching attendance data
	 * 
	 * @property {Object[]} columns - Column definitions including:
	 *   - employee: Employee info with avatar and contact details
	 *   - work_date: Date of attendance record
	 *   - is_holiday: Holiday status indicator
	 *   - start_time: Clock-in time (pending badge if not set)
	 *   - end_time: Clock-out time (pending badge if not set)
	 *   - total_hours: Total hours with status-based styling and icons
	 * 
	 * @property {Object} actions - Row action configurations:
	 *   - delete: Remove attendance records via deleteAttendance() function
	 * 
	 * @property {Object[]} filters - Available filter options:
	 *   - Employee selector: Filter by specific employee or all employees
	 *   - Date picker: Filter by specific work date
	 *   - Clear button: Reset all active filters
	 * 
	 * @property {Object} options - DataTable configuration:
	 *   - Disables search bar
	 *   - Positions custom buttons at top-left
	 *   - Dynamic AJAX filtering based on employee_id and work_date parameters
	 *   - Supports month-based queries
	 */
	const dataTable = CreateNewDataTable(
		ATTENDANCE_TABLE_ID,
		MODEL_ROUTES.historyData,
		[
			{
				data: "employee",
				name: "employee_id",
				searchable: false,
				render: (data, type, row) => {
					if (type !== "display")
						return `${row?.employee?.name || ""} ${row?.employee?.email || ""}`.trim();
					const name = escapeHtml(
						row?.employee?.name || "Sin nombre",
					);
					return `
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center fw-semibold bg-secondary-subtle" style="width: 2.5rem; height: 2.5rem; min-width: 2.5rem;">
                            ${escapeHtml(getInitials(name))}
                        </div>
                        <div class="d-flex flex-column">
                            <span class="fw-semibold lh-sm">${name}</span>
							<small class="text-muted lh-sm">${escapeHtml(row?.employee?.email || "Sin correo")}</small>
                        </div>
                    </div>`;
				},
			},
			{
				data: "work_date",
				name: "work_date",
				render: (data) => {
					return formatDate(data);
				},
			},
			{
				data: "is_holiday",
				name: "is_holiday",
				render: (val) =>
					val
						? '<span class="badge border rounded-pill text-warning-emphasis bg-warning-subtle px-2 py-2"><i class="bi bi-stars me-1"></i>Feriado</span>'
						: '<span class="text-muted px-4">&mdash;</span>',
			},
			{
				data: "start_time",
				name: "start_time",
				render: (val) =>
					val
						? `<span class="fw-semibold">${escapeHtml(formatTime(val))}</span>`
						: '<span class="badge border rounded-pill text-warning-emphasis bg-warning-subtle px-2 py-2"><i class="bi bi-hourglass-split me-1"></i>Pendiente</span>',
			},
			{
				data: "end_time",
				name: "end_time",
				render: (val) =>
					val
						? `<span class="fw-semibold">${escapeHtml(formatTime(val))}</span>`
						: '<span class="badge border rounded-pill text-warning-emphasis bg-warning-subtle px-2 py-2"><i class="bi bi-hourglass-split me-1"></i>Pendiente</span>',
			},
			{
				data: "total_hours",
				name: "total_hours",
				render: (val) => {
					if (!val)
						return '<span class="text-muted px-4 py-2">&mdash;</span>';
					const hrs = parseFloat(String(val).replace(",", "."));
					const compact = `${Math.round(isNaN(hrs) ? 0 : hrs)}h`;
					if (hrs > 8)
						return `<span class="badge border rounded-pill text-danger-emphasis bg-danger-subtle px-2 py-2"><i class="bi bi-lightning-charge-fill me-1"></i>${compact}</span>`;
					if (hrs === 0)
						return `<span class="badge border rounded-pill text-secondary-emphasis bg-secondary-subtle px-2 py-2"><i class="bi bi-x-lg me-1"></i>${compact}</span>`;
					return `<span class="badge border rounded-pill text-success-emphasis bg-success-subtle px-2 py-2"><i class="bi bi-check-lg me-1"></i>${compact}</span>`;
				},
			},
		],
		{
			delete: {
				route: MODEL_ROUTES.destroy,
				tooltip: `Eliminar ${MODEL_NAME}`,
				func: window.deleteAttendance,
				funcName: "deleteAttendance",
			},
		},
		[
			{
				type: "select",
				id: FILTER_SELECTORS.employee.replace("#", ""),
				label: "Colaborador",
				labelIcon: "bi-person-fill me-2",
				class: "attendance-employee-filter",
				wrapperClass: "w-auto",
				placeholderSelected: true,
				options: [
					{
						value: DEFAULT_FILTER_VALUE,
						text: "Todos",
						selected: true,
					},
				],
			},
			{
				type: "date",
				id: FILTER_SELECTORS.date.replace("#", ""),
				label: "Fecha",
				labelIcon: "bi-calendar-date me-2",
				class: "attendance-date-filter",
				wrapperClass: "w-auto",
				placeholder: "Seleccione una fecha",
			},
			{
				type: "button",
				id: "attendance-clear-filters",
				text: "Limpiar filtros",
				icon: "bi-eraser-fill",
				class: "btn-outline-primary mb-2 d-none",
				func: window.clearAttendanceFilters,
				funcName: "clearAttendanceFilters",
			},
		],
		{
			showSearchBar: false,
			customButtonsPosition: "top-start",
			ajax: {
				data: (req) => {
					const empId = document.querySelector(FILTER_SELECTORS.employee)?.value;
					const date = document.querySelector(FILTER_SELECTORS.date)?.value;
					if (empId && empId !== DEFAULT_FILTER_VALUE)
						req.employee_id = empId;
					if (date) {
						req.work_date = date;
						req.month = String(date).slice(0, 7);
					}
				},
			},
		},
	);

	/**
	 * Clears all active history filters and triggers DataTable reload.
	 *
	 * Exposed on `window` because the DataTable button configuration resolves
	 * function handlers globally by name.
	 *
	 * @returns {void}
	 */
	window.clearAttendanceFilters = () => {
		const empSelect = document.querySelector(FILTER_SELECTORS.employee);
		const dateInput = document.querySelector(FILTER_SELECTORS.date);
		if (empSelect) empSelect.value = DEFAULT_FILTER_VALUE;
		if (dateInput) dateInput.value = "";

		syncClearFiltersButtonVisibility();
		dataTable.ajax.reload();
	};

	$(FILTER_SELECTORS.employee)
		.add(FILTER_SELECTORS.date)
		.off(".attendanceHistory")
		.on("change.attendanceHistory", () => {
			syncClearFiltersButtonVisibility();
			dataTable.ajax.reload();
		});
	dataTable
		.off("xhr.attendanceHistory")
		.on("xhr.attendanceHistory", (_, __, res) => {
			updateSelectFilter(res?.data, res?.employee_filters);
			syncClearFiltersButtonVisibility();
		});

	syncClearFiltersButtonVisibility();
}

/**
 * Initializes salary tab controls, validation and async submit workflow.
 *
 * This includes:
 * - payroll period synchronization (display -> hidden field)
 * - payroll half visibility based on employee payment frequency
 * - realtime and submit-time validation
 * - async content refresh for salary results
 *
 * @returns {void}
 */
function initSalaryTab() {
	const container = document.querySelector(
		`#${TAB_IDS.salary} .js-tab-lazy-content`,
	);
	const form = container?.querySelector(`#${SALARY.formId}`);
	if (!container || !form) return;

	const employeeSelect = form.querySelector("#salary-employee_id");
	const periodInput = form.querySelector("#salary-payroll_period_display");
	const periodHidden = form.querySelector("#salary-payroll_period");
	const halfGroup = form.querySelector("[data-payroll-half-group]");
	const halfRadios = form.querySelectorAll('input[name="payroll_half"]');

	/**
	 * Synchronizes visible payroll period input with hidden submit field.
	 *
	 * @returns {void}
	 */
	const syncPayrollPeriod = () => {
		if (periodInput && periodHidden)
			periodHidden.value = String(periodInput.value || "").trim();
	};

	/**
	 * Shows/hides payroll-half controls depending on selected employee frequency.
	 *
	 * Rules:
	 * - Non-biweekly: hide group, disable radios, clear checked states.
	 * - Biweekly: show group and auto-pick default half if none is selected.
	 *
	 * Default half is chosen by current day of month.
	 * @returns {void}
	 */
	const syncPayrollHalfVisibility = () => {
		const selectedOption = employeeSelect?.selectedOptions?.[0];
		const isBiweekly = selectedOption?.dataset?.paymentFrequency === "biweekly";
		const defaultHalf = new Date().getDate() <= 15 ? "first_half" : "second_half";
		const checkedRadio = Array.from(halfRadios).find((r) => r.checked);

		halfGroup?.classList.toggle("d-none", !isBiweekly);
		halfRadios.forEach((radio) => {
			radio.disabled = !isBiweekly;
			if (!isBiweekly) radio.checked = false;
		});

		if (isBiweekly && !checkedRadio) {
			const defaultRadio = Array.from(halfRadios).find((r) => r.value === defaultHalf);
			if (defaultRadio) defaultRadio.checked = true;
		}
	};
	
	const FIELD_KEYS = {
		EMPLOYEE: "salary-employee_id",
		PERIOD: "salary-payroll_period_display",
	};

	const FIELD_VALIDATORS = {
		[FIELD_KEYS.EMPLOYEE]: {
			validator: (val) => val && val !== "-1",
			invalidMsg: "Selecciona un colaborador válido",
		},
		[FIELD_KEYS.PERIOD]: {
			validator: (val) => /^\d{4}-\d{2}$/.test(String(val).trim()),
			emptyMsg: "Selecciona un período de nómina",
			invalidMsg: "Formato YYYY-MM requerido",
		},
	};

	/**
	 * Executes salary form validation and paints field errors.
	 *
	 * @returns {boolean} `true` when form values satisfy all active rules.
	 */
	const validateSalaryCalcForm = () => {
		const values = {
			[FIELD_KEYS.EMPLOYEE]: form.querySelector(`#${FIELD_KEYS.EMPLOYEE}`)?.value,
			[FIELD_KEYS.PERIOD]: form.querySelector(`#${FIELD_KEYS.PERIOD}`)?.value,
		};

		clearAllFieldErrors(FIELD_VALIDATORS);

		return validateAndDisplayField(
			FIELD_VALIDATORS,
			values,
			showFieldError,
			clearFieldError,
		);
	};

	periodInput?.addEventListener("change", syncPayrollPeriod);
	employeeSelect?.addEventListener("change", syncPayrollHalfVisibility);
	bindRealtimeValidation(
		`#${SALARY.formId}`,
		FIELD_VALIDATORS,
		SALARY.eventNamespace,
	);

	syncPayrollPeriod();
	syncPayrollHalfVisibility();

	/**
	 * Handles salary form submit with async tab refresh.
	 *
	 * @param {SubmitEvent} event - Native submit event from salary form.
	 * @returns {Promise<void>}
	 */
	form.addEventListener("submit", async (event) => {
		event.preventDefault();
		syncPayrollPeriod();
		setLoadingState(SALARY.submitFormId, true);

		if (validateSalaryCalcForm()) {
			const params = new URLSearchParams(new FormData(form));
			pruneEmptyParams(params);

			const baseUrl = MODEL_ROUTES.tabs.replace(":tab", SALARY.tab);
			const queryString = params.toString();
			const url = queryString ? `${baseUrl}?${queryString}` : baseUrl;

			try {
				const response = await fetch(url, {
					headers: { "X-Requested-With": "XMLHttpRequest" },
				});
				if (!response.ok) throw new Error(`Error ${response.status}`);

				container.innerHTML = await response.text();
				initSalaryTab();
			} catch (error) {
				container.innerHTML = renderSalaryCard(
					'<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><span>No se pudo calcular el salario. Intentalo de nuevo.</span></div>',
				);
				console.error(error);
				setLoadingState(SALARY.submitFormId, false);
			}
		} else {
			setLoadingState(SALARY.submitFormId, false);
		}
	});
}

/**
 * Lazily loads tab content and initializes tab-specific behaviors once.
 *
 * Loaded tab ids are memoized in `loadedTabs` to avoid redundant network calls
 * and duplicate initializations during tab switching.
 *
 * @param {string} tabId - Tab content container ID.
 * @returns {Promise<void>}
 */
async function loadTab(tabId) {
	if (loadedTabs.has(tabId)) return;

	const container = document.querySelector(`#${tabId} .js-tab-lazy-content`);
	const url = container?.dataset?.url;
	if (!container || !url) return;

	const isSalaryTab = tabId === TAB_IDS.salary;
	/**
	 * Applies salary card shell only for salary tab content.
	 *
	 * @param {string} content - Inner HTML to render inside tab container.
	 * @returns {string}
	 */
	const renderTabContent = (content) => {
		if (!isSalaryTab) return content;
		return renderSalaryCard(content);
	};

	container.innerHTML = renderTabContent(
		'<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i><span>Cargando contenido...</span></div>',
	);

	try {
		const response = await fetch(url, {
			headers: { "X-Requested-With": "XMLHttpRequest" },
		});
		if (!response.ok) throw new Error(`Error ${response.status}`);

		container.innerHTML = await response.text();
		loadedTabs.add(tabId);

		if (tabId === TAB_IDS.attendance) initAttendanceForm();
		if (tabId === TAB_IDS.history) initHistoryTab();
		if (tabId === TAB_IDS.salary) initSalaryTab();
	} catch (error) {
		container.innerHTML = renderTabContent(
			'<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><span>No se pudo cargar el contenido de esta pestaña. Inténtalo de nuevo.</span></div>',
		);
		console.error(error);
	}
}

// ==================== Bootstrap Initialization ====================

/**
 * Bootstraps attendance module behavior once DOM is ready.
 *
 * It wires Bootstrap tab switch listeners for lazy loading and ensures the
 * configured initial tab is loaded immediately (or activated then loaded).
 *
 * @returns {void}
 */
$(() => {
	const initialTabId = APP_DATA.initialTab ?? TAB_IDS.attendance;

	document.querySelectorAll(TAB_BUTTON_SELECTOR).forEach((button) => {
		button.addEventListener("shown.bs.tab", (e) => {
			const tabId = e.target?.dataset?.bsTarget?.replace("#", "");
			if (tabId) loadTab(tabId);
		});
	});

	const initialBtn = document.querySelector(
		`${TAB_BUTTON_SELECTOR}[data-bs-target="#${initialTabId}"]`,
	);
	if (initialBtn) {
		if (initialBtn.classList.contains("active")) loadTab(initialTabId);
		else bootstrap.Tab.getOrCreateInstance(initialBtn).show();
	}
});
