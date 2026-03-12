import { CreateNewDataTable } from "../../utils/datatables";
import { formatTime } from "../../utils/utils";

// ==================== Constants ====================

// Model Configuration
const MODEL_NAME = 'asistencia';
const MODEL_DATA = window.AttendanceAppData || {};
const DATE_FORMATTER = new Intl.DateTimeFormat('es-CR', {
	day: '2-digit',
	month: 'long',
	year: 'numeric',
});
const DEFAULT_SELECT_VALUE = 'all';
const ATTENDANCE_TABLE_ID = 'attendance-table';
const TAB_NAV_SELECTOR = '#main-tab-tab .nav-link[data-bs-target]';
const LOADING_TAB_TEMPLATE = '<div class="alert alert-info" role="alert"><i class="bi bi-info-circle me-2"></i><span>Cargando contenido...</span></div>';
const FAILED_TAB_TEMPLATE = '<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-circle me-2"></i><span>No se pudo cargar esta pestaña. Intenta nuevamente.</span></div>';

// Tab ID Constants
const TAB_IDS = {
	attendance: 'nav-attendance',
	history: 'nav-history',
	salary: 'nav-salary',
};

const FILTER_IDS = {
	employee: '#attendanceEmployeeFilter',
	date: '#attendanceDateFilter',
};

// Routes Configuration
const MODEL_ROUTES = {
	index: 	route('attendance.tabs', { tab: ':tab' }),
	create: route('attendance.create'),
	historyData: route('attendance.history.data'),
	delete: route('attendance.destroy', { attendance: ':id' }),
};

/**
 * Escapes potentially unsafe HTML characters to prevent XSS in rendered cells.
 *
 * @param {string} value
 * @returns {string}
 */
function escapeHtml(value = '') {
	return String(value)
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#39;');
}

/**
 * Builds initials from a full name (up to 2 characters).
 *
 * @param {string} name
 * @returns {string}
 */
function getInitials(name = '') {
	const nameParts = String(name)
		.trim()
		.split(/\s+/)
		.filter(Boolean);

	if (nameParts.length <= 0) {
		return '??';
	}

	return nameParts
		.slice(0, 2)
		.map((part) => part.charAt(0).toUpperCase())
		.join('');
}

/**
 * Returns normalized employee id/name from server payloads.
 *
 * @param {Object} source
 * @param {Object} sourceMap
 * @returns {{ id: string, name: string }}
 */
function getNormalizedEmployee(source = {}, sourceMap = {}) {
	const employeeId = String(source?.[sourceMap.id] ?? '').trim();
	const employeeName = String(source?.[sourceMap.name] ?? '').trim();

	return {
		id: employeeId,
		name: employeeName,
	};
}

/**
 * Returns all unique employees from either dedicated employee_filters or rows.
 *
 * @param {Array<Object>} rows
 * @param {Array<Object>} employeeFilters
 * @returns {Map<string, string>}
 */
function collectEmployees(rows = [], employeeFilters = []) {
	const employees = new Map();
	const hasDedicatedFilters = Array.isArray(employeeFilters) && employeeFilters.length > 0;

	const assignEmployee = (employee) => {
		if (!employee.id || !employee.name || employees.has(employee.id)) {
			return;
		}

		employees.set(employee.id, employee.name);
	};

	if (hasDedicatedFilters) {
		employeeFilters.forEach((employee) => {
			assignEmployee(getNormalizedEmployee(employee, { id: 'id', name: 'name' }));
		});
		return employees;
	}

	rows.forEach((row) => {
		assignEmployee({
			id: String(row?.employee_id ?? '').trim(),
			name: String(row?.employee?.name ?? '').trim(),
		});
	});

	return employees;
}

/**
 * Renders employee info in a compact avatar card style (without status dot).
 *
 * @param {string|Object} data
 * @param {string} type
 * @param {Object} row
 * @returns {string}
 */
function renderEmployeeInfo(data, type, row = {}) {
	if (type !== 'display') {
		if (typeof data === 'string') {
			return data;
		}

		const rawName = data?.name ?? row?.name ?? '';
		const rawEmail = data?.email ?? row?.email ?? '';
		return `${rawName} ${rawEmail}`.trim();
	}

	const rawName = data?.name ?? row?.name ?? (typeof data === 'string' ? data : 'Sin nombre');
	const rawEmail = data?.email ?? row?.email ?? '';
	const initials = data?.initials ?? getInitials(rawName);

	const name = escapeHtml(rawName || 'Sin nombre');
	const email = escapeHtml(rawEmail || 'Sin correo');
	const safeInitials = escapeHtml(initials || '??');

	return `
		<div class="d-flex align-items-center gap-2">
			<div class="rounded-circle d-inline-flex align-items-center justify-content-center fw-semibold bg-secondary-subtle" style="width: 2.5rem; height: 2.5rem; min-width: 2.5rem;">
				${safeInitials}
			</div>
			<div class="d-flex flex-column">
				<span class="fw-semibold lh-sm">${name}</span>
				<small class="text-muted lh-sm">${email}</small>
			</div>
		</div>
	`;
}

/**
 * Formats date to short Spanish style used in the table mockup (e.g. 12 mar 2026).
 *
 * @param {string} value
 * @returns {string}
 */
function formatCompactDate(value) {
	const date = new Date(value);

	if (isNaN(date.getTime())) {
		return 'Fecha inválida';
	}

	return DATE_FORMATTER
		.format(date)
		.replaceAll('.', '');
}

/**
 * Renders holiday as a badge or a muted dash when not holiday.
 *
 * @param {boolean} value
 * @param {string} type
 * @returns {string|boolean}
 */
function renderHolidayBadge(value, type) {
	if (type !== 'display') {
		return value;
	}

	if (!value) {
		return '<span class="text-muted px-4">&mdash;</span>';
	}

	return '<span class="badge border rounded-pill text-warning-emphasis bg-warning-subtle px-2 py-2"><i class="bi bi-stars me-1"></i>Feriado</span>';
}

/**
 * Renders a time value or a pending badge when the value is missing.
 *
 * @param {string|null} value
 * @param {string} type
 * @returns {string}
 */
function renderTimeCell(value, type) {
	if (type !== 'display') {
		return value ?? '';
	}

	if (!value) {
		return '<span class="badge border rounded-pill text-warning-emphasis bg-warning-subtle px-2 py-2"><i class="bi bi-hourglass-split me-1"></i>Pendiente</span>';
	}

	return `<span class="fw-semibold">${escapeHtml(formatTime(value))}</span>`;
}

/**
 * Renders total hours with status-like badges, or dash when pending.
 *
 * @param {string|null} value
 * @param {string} type
 * @returns {string}
 */
function renderTotalHoursCell(value, type) {
	if (!value) {
		return '<span class="text-muted px-4 py-2">&mdash;</span>';
	}

	const numericHours = Number.parseFloat(String(value).replace(',', '.'));
	const compact = `${Math.round(isNaN(numericHours) ? 0 : numericHours)}h`;

	if (!isNaN(numericHours) && numericHours > 8) {
		return `<span class="badge border rounded-pill text-danger-emphasis bg-danger-subtle px-2 py-2"><i class="bi bi-lightning-charge-fill me-1"></i>${escapeHtml(compact)}</span>`;
	}

	return `<span class="badge border rounded-pill text-success-emphasis bg-success-subtle px-2 py-2"><i class="bi bi-check-lg me-1"></i>${escapeHtml(compact)}</span>`;
}

// ==================== Helper Functions ====================

/**
 * Toggles the label of the "is_holiday" checkbox button based on its checked state.
 *
 * @param {jQuery} $scope - The jQuery scope to search within (defaults to document)
 */
function toggleIsHolidayButtonLabel($scope = $(document)) {
	const isHolidayButton = $scope.find('#is_holiday');
	const isHolidayButtonLabel = $scope.find('label.btn[for="is_holiday"]');

	if (isHolidayButton.length <= 0 || isHolidayButtonLabel.length <= 0) {
		return;
	}

	const isHoliday = isHolidayButton.is(':checked');
	isHolidayButtonLabel.text(isHoliday ? 'Sí, es feriado' : 'No, no es feriado');
}

/**
 * Loads the content of a tab pane via AJAX if it hasn't been loaded yet.
 * Uses a Set to track already-loaded tabs and avoid duplicate requests.
 *
 * @param {string} tabPaneId - The ID of the tab pane element to load content into
 * @returns {Promise<void>} A promise that resolves when the content is loaded
 */
const loadedTabs = new Set();

async function loadTabContent(tabPaneId) {
	if (loadedTabs.has(tabPaneId)) {
		return;
	}

	const container = document.querySelector(`#${tabPaneId} .js-tab-lazy-content`);
	if (!container) {
		return;
	}

	const url = container.dataset.url;
	if (!url) {
		return;
	}

	container.innerHTML = LOADING_TAB_TEMPLATE;

	try {
		const response = await fetch(url, {
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
			},
		});

		if (!response.ok) {
			throw new Error(`Error ${response.status}`);
		}

		container.innerHTML = await response.text();
		loadedTabs.add(tabPaneId);
		toggleIsHolidayButtonLabel($(container));

		// If the loaded tab is the attendance history, initialize the DataTable
		if (tabPaneId === TAB_IDS.history) {
			initializeHistoryDataTable(tabPaneId);
		}
	} catch (error) {
		container.innerHTML = FAILED_TAB_TEMPLATE;
		console.error(error);
	}
}

// ==================== Data Initialization ====================

/**
 * Initializes the tab behavior for the attendance page.
 *
 * Registers a `shown.bs.tab` listener on every tab button inside `#main-tab-tab`
 * so that tab content is loaded lazily via {@link loadTabContent} the first time
 * each tab is opened.
 *
 * If the server supplied an `initialTab` value (e.g. after a redirect), this
 * function activates that tab programmatically:
 * - If it is already the active tab, content is loaded directly.
 * - Otherwise, Bootstrap's Tab API is used to show it, which fires
 *   `shown.bs.tab` and triggers the lazy-load automatically.
 *
 * @returns {void}
 */
function initializeTabBehavior() {
	const initialTab = MODEL_DATA.initialTab ?? TAB_IDS.attendance;

	// Register lazy-load on tab activation
	document
		.querySelectorAll(TAB_NAV_SELECTOR)
		.forEach((button) => {
			button.addEventListener("shown.bs.tab", (event) => {
				const target = event.target?.dataset?.bsTarget;
				if (!target) {
					return;
				}

				loadTabContent(target.replace("#", ""));
			});
		});

	// Activate the tab indicated by the server (e.g., after a redirect)
		const targetButton = document.querySelector(`${TAB_NAV_SELECTOR}[data-bs-target="#${initialTab}"]`);
	if (targetButton) {
		if (targetButton.classList.contains("active")) {
			// Already active by default: load directly
			loadTabContent(initialTab);
		} else {
			// Activate via Bootstrap; shown.bs.tab will trigger loadTabContent
			bootstrap.Tab.getOrCreateInstance(targetButton).show();
		}
	}
}

/**
 * Syncs collaborator filter options from current DataTable response rows.
 *
 * @param {Array<Object>} rows
 * @param {Array<Object>} employeeFilters
 * @returns {void}
 */
function syncEmployeeFilterOptions(rows = [], employeeFilters = []) {
	const select = document.querySelector(FILTER_IDS.employee);
	if (!select) {
		return;
	}

	const currentValue = String(select.value || DEFAULT_SELECT_VALUE);
	const employees = collectEmployees(rows, employeeFilters);

	const sortedEmployees = Array.from(employees.entries())
		.sort((a, b) => a[1].localeCompare(b[1], 'es', { sensitivity: 'base' }));

	const optionsHtml = [
		`<option value="${DEFAULT_SELECT_VALUE}">Todos</option>`,
		...sortedEmployees.map(([id, name]) => `<option value="${escapeHtml(id)}">${escapeHtml(name)}</option>`),
	].join('');

	select.innerHTML = optionsHtml;
	select.value = employees.has(currentValue) ? currentValue : DEFAULT_SELECT_VALUE;
}

/**
 * Adds filter listeners and updates collaborator select on each AJAX response.
 *
 * @param {*} dataTable
 * @returns {void}
 */
function setupHistoryFilters(dataTable) {
	if (!dataTable) {
		return;
	}

	const reloadData = () => dataTable.ajax.reload();
	const employeeFilter = $(FILTER_IDS.employee);
	const dateFilter = $(FILTER_IDS.date);

	employeeFilter.off('.attendanceFilters').on('change.attendanceFilters', reloadData);
	dateFilter.off('.attendanceFilters').on('change.attendanceFilters', reloadData);

	dataTable.off('xhr.attendanceFilters').on('xhr.attendanceFilters', (_event, _settings, json) => {
		const rows = Array.isArray(json?.data) ? json.data : [];
		const employeeFilters = Array.isArray(json?.employee_filters) ? json.employee_filters : [];
		syncEmployeeFilterOptions(rows, employeeFilters);
	});
}

/**
 * Defines columns for attendance history table.
 *
 * @returns {Array<Object>}
 */
function getHistoryColumns() {
	return [
		{
			data: "employee",
			name: "employee_id",
			searchable: false,
			render: (data, type, row) => renderEmployeeInfo(data ?? row?.employee, type, row),
			// Employee Information
		},
		{
			data: "work_date",
			name: "work_date",
			render: (data, type) => (type === 'display' ? formatCompactDate(data) : data),
			// Attendance registered day
		},
		{
			data: "is_holiday",
			name: "is_holiday",
			render: (data, type) => renderHolidayBadge(data, type),
			// Indicates if the attendance day is a holiday
		},
		{
			data: "start_time",
			name: "start_time",
			render: (data, type) => (type === 'display' ? `<span class="fw-semibold">${escapeHtml(formatTime(data))}</span>` : data),
			// Start time of attendance (in format HH:mm A)
		},
		{
			data: "end_time",
			name: "end_time",
			render: (data, type) => renderTimeCell(data, type),
			// End time of attendance (in format HH:mm A)
		},
		{
			data: "total_hours",
			name: "total_hours",
			render: (data, type) => renderTotalHoursCell(data, type),
			// Total hours registered for that attendance day
		},
	];
}

/**
 * Defines DataTable actions for attendance history.
 *
 * @returns {Object}
 */
function getHistoryActions() {
	return {
		delete: {
			route: MODEL_ROUTES.delete,
			tooltip: `Eliminar ${MODEL_NAME}`,
			func: () => {},
			funcName: '',
		},
	};
}


/**
 * Defines custom filter controls for attendance history DataTable.
 *
 * @returns {Array<Object>}
 */
function getHistoryCustomButtons() {
	return [
		{
			type: 'select',
			id: FILTER_IDS.employee.replace('#', ''),
			label: 'Colaborador',
			labelIcon: 'bi-person-fill me-2',
			class: 'attendance-employee-filter',
			wrapperClass: 'w-auto',
			placeholder: 'Todos',
			placeholderSelected: true,
			options: [
				{ value: DEFAULT_SELECT_VALUE, text: 'Todos', selected: true },
			],
		},
		{
			type: 'date',
			id: FILTER_IDS.date.replace('#', ''),
			label: 'Fecha',
			labelIcon: 'bi-calendar-date me-2',
			class: 'attendance-date-filter',
			wrapperClass: 'w-auto',
			placeholder: 'Selecciona una fecha',
		},
    ];
}

/**
 * Returns route used by DataTable according to active tab.
 *
 * @param {string} tabPaneId
 * @returns {string}
 */
function resolveAttendanceTableRoute(tabPaneId) {
	if (tabPaneId === TAB_IDS.history) {
		return MODEL_ROUTES.historyData;
	}

	const tabKey = Object.keys(TAB_IDS).find((key) => TAB_IDS[key] === tabPaneId);
	return MODEL_ROUTES.index.replace(':tab', tabKey);
}

/**
 * Appends current DataTable filter values to request payload.
 *
 * @param {Object} request
 */
function appendHistoryFiltersToRequest(request) {
	const employeeFilterValue = String($(FILTER_IDS.employee).val() ?? DEFAULT_SELECT_VALUE).trim();
	const dateFilterValue = String($(FILTER_IDS.date).val() ?? '').trim();

	if (employeeFilterValue && employeeFilterValue !== DEFAULT_SELECT_VALUE) {
		request.employee_id = employeeFilterValue;
	}

	if (dateFilterValue) {
		request.work_date = dateFilterValue;
		request.month = dateFilterValue.slice(0, 7);
	}
}

/**
 * Initializes attendance history DataTable for selected tab.
 *
 * @param {string} tabPaneId
 */
function initializeHistoryDataTable(tabPaneId) {
	const columns = getHistoryColumns();
	const actions = getHistoryActions();
	const customButtons = getHistoryCustomButtons();
	const tableRoute = resolveAttendanceTableRoute(tabPaneId);

	const dataTable = CreateNewDataTable(ATTENDANCE_TABLE_ID, tableRoute, columns, actions, customButtons, {
		showSearchBar: false,
		customButtonsPosition: 'top-start',
		ajax: {
			data: appendHistoryFiltersToRequest,
		}
	});

	setupHistoryFilters(dataTable);
}

// Ensure the DOM is fully loaded before initializing tab behavior
$(() => {
	// Initialize tab behavior and lazy loading
	initializeTabBehavior();

	// Handle is_holiday checkbox label toggle when content is injected dynamically
	$(document).on('change', '#is_holiday', function () {
		const scope = $(this).closest('.js-tab-lazy-content');
		toggleIsHolidayButtonLabel(scope.length > 0 ? scope : $(document));
	});
});
