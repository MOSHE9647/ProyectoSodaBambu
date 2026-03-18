import { deleteModel } from "../actions.js";
import { CreateNewDataTable } from "../../utils/datatables";
import {
	escapeHtml,
	formatTime,
	getInitials,
	formatDate,
	setLoadingState,
} from "../../utils/utils";
import { initAttendanceForm } from "./form.js";

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
const MODEL_ROUTES = {
	index: route("attendance.index"),
	historyData: route("attendance.history.data"),
	tabs: route("attendance.tabs", { tab: ":tab" }),
	destroy: route("attendance.destroy", { timesheet: ":id" }),
};

const loadedTabs = new Set();

// ==================== Global Functions ====================

/**
 * Deletes an attendance entry.
 * @param {Event} event - Trigger event from delete action.
 * @returns {void}
 */
window.deleteAttendance = (event) => deleteModel(event, MODEL_NAME);

// ==================== Helper Functions ====================

/**
 * Initializes the attendance history tab and wires DataTable filters.
 * @returns {void}
 */
function initHistoryTab() {
	/**
	 * Rebuilds the employee select filter using incoming rows/filter payload.
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
	 * Synchronizes the visibility of the clear filters button based on active filter states.
	 * 
	 * Checks the current values of the employee and date filters. If either filter contains
	 * an active value (employee is not the default value or date field has content), the
	 * clear filters button is displayed. Otherwise, the button is hidden.
	 * 
	 * @function syncClearFiltersButtonVisibility
	 * @returns {void}
	 */
	const syncClearFiltersButtonVisibility = () => {
		const employeeValue = String(
			$(FILTER_SELECTORS.employee).val() ?? DEFAULT_FILTER_VALUE,
		);
		const dateValue = String($(FILTER_SELECTORS.date).val() ?? "").trim();
		const hasActiveFilters =
			employeeValue !== DEFAULT_FILTER_VALUE || dateValue.length > 0;

		$("#attendance-clear-filters").toggleClass("d-none", !hasActiveFilters);
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
					const empId = $(FILTER_SELECTORS.employee).val();
					const date = $(FILTER_SELECTORS.date).val();
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

	window.clearAttendanceFilters = () => {
		$(FILTER_SELECTORS.employee).val(DEFAULT_FILTER_VALUE);
		$(FILTER_SELECTORS.date).val("");
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
 * Initializes salary tab filters and dynamic submit behavior.
 * @returns {void}
 */
function initSalaryTab() {
	const container = document.querySelector(`#${TAB_IDS.salary} .js-tab-lazy-content`);
	const form = container?.querySelector("#employee-salary-form");
	if (!container || !form) return;

	const employeeSelect = form.querySelector("#employee_id");
	const periodInput = form.querySelector("#payroll_period_display");
	const periodHidden = form.querySelector("#payroll_period");
	const halfGroup = form.querySelector("[data-payroll-half-group]");
	const halfRadios = form.querySelectorAll('input[name="payroll_half"]');

	const syncPayrollPeriod = () => {
		if (periodInput && periodHidden)
			periodHidden.value = String(periodInput.value || "").trim();
	};

	const syncPayrollHalfVisibility = () => {
		const selectedOption = employeeSelect?.selectedOptions?.[0];
		const paymentFrequency = selectedOption?.dataset?.paymentFrequency;
		const isBiweekly = paymentFrequency === "biweekly";
		const today = new Date();
		const defaultHalf = today.getDate() <= 15 ? "first_half" : "second_half";
		const checkedRadio = Array.from(halfRadios).find((radio) => radio.checked);

		halfGroup?.classList.toggle("d-none", !isBiweekly);
		halfRadios.forEach((radio) => {
			radio.disabled = !isBiweekly;
			if (!isBiweekly) radio.checked = false;
		});

		if (isBiweekly && !checkedRadio) {
			const defaultRadio = Array.from(halfRadios).find(
				(radio) => radio.value === defaultHalf,
			);
			if (defaultRadio) defaultRadio.checked = true;
		}
	};

	periodInput?.addEventListener("change", syncPayrollPeriod);
	employeeSelect?.addEventListener("change", syncPayrollHalfVisibility);
	syncPayrollPeriod();
	syncPayrollHalfVisibility();

	form.addEventListener("submit", async (event) => {
		event.preventDefault();
		syncPayrollPeriod();
		setLoadingState("submit-salary-form", true);

		const formData = new FormData(form);
		const params = new URLSearchParams();
		for (const [key, value] of formData.entries()) {
			const normalizedValue = String(value ?? "").trim();
			if (normalizedValue.length) params.set(key, normalizedValue);
		}

		const baseUrl = MODEL_ROUTES.tabs.replace(":tab", "salary");
		const url = params.toString() ? `${baseUrl}?${params.toString()}` : baseUrl;

		try {
			const response = await fetch(url, {
				headers: { "X-Requested-With": "XMLHttpRequest" },
			});
			if (!response.ok) throw new Error(`Error ${response.status}`);

			container.innerHTML = await response.text();
			initSalaryTab();
		} catch (error) {
			const renderContent = (content) => {
				return `
					<div class="card-container rounded-2 p-4">
						<h5 class="text-muted pb-3 border-bottom border-secondary">
							<i class="bi bi-currency-dollar me-3"></i>
							Calcular Salario por Colaborador
						</h5>
						${content}
					</div>
				`;
			};

			container.innerHTML = renderContent(
				'<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><span>No se pudo calcular el salario. Intentalo de nuevo.</span></div>'
			);
			console.error(error);
			setLoadingState("submit-salary-form", false);
		}
	});
}

/**
 * Lazily loads tab content and initializes tab-specific behaviors once.
 * @param {string} tabId - Tab content container ID.
 * @returns {Promise<void>}
 */
async function loadTab(tabId) {
	if (loadedTabs.has(tabId)) return;

	const container = document.querySelector(`#${tabId} .js-tab-lazy-content`);
	const url = container?.dataset?.url;
	if (!container || !url) return;

	const isSalaryTab = tabId === TAB_IDS.salary;
	const renderTabContent = (content) => {
		if (!isSalaryTab) return content;
		return `
			<div class="card-container rounded-2 p-4">
				<h5 class="text-muted pb-3 border-bottom border-secondary">
					<i class="bi bi-currency-dollar me-3"></i>
					Calcular Salario por Colaborador
				</h5>
				${content}
			</div>
		`;
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
