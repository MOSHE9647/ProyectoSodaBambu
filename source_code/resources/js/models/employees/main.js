import { deleteModel } from "../actions.js";
import { CreateNewDataTable } from "../../utils/datatables";
import { escapeHtml, formatTime } from "../../utils/utils";

const MODEL_NAME = "asistencia";
const APP_DATA = window.AttendanceAppData ?? {};
const APP_EMPLOYEES = Array.isArray(APP_DATA.employees) ? APP_DATA.employees : [];
const DEFAULT_FILTER_VALUE = "all";
const ATTENDANCE_TABLE_ID = "attendance-table";
const TAB_BUTTON_SELECTOR = "#main-tab-tab .nav-link[data-bs-target]";
const DATE_FORMATTER = new Intl.DateTimeFormat("es-CR", {
	day: "2-digit",
	month: "long",
	year: "numeric",
});
const TAB_IDS = {
	attendance: "nav-attendance",
	history: "nav-history",
	salary: "nav-salary",
};
const FILTER_SELECTORS = {
	employee: "#attendanceEmployeeFilter",
	date: "#attendanceDateFilter",
};
const FORM_SELECTORS = {
	form: "#employee-attendance-form",
	employeeId: "#employee_id",
	attendanceDate: "#work_date_display",
	workDate: "#work_date",
	startTime: "#start_time",
	endTime: "#end_time",
	isHolidayTrue: "#is_holiday_true",
	attendanceCompleteAlert: "#attendance-complete-alert",
	attendanceStartAddedAlert: "#attendance-start-time-added-alert",
	startTimeCantModifyText: "#attendance-start-time-cant-be-modify",
	endTimeCantModifyText: "#attendance-end-time-cant-be-modify",
	totalHoursInfo: "#total-hours-info",
	totalHoursStartTime: "#start-time",
	totalHoursEndTime: "#end-time",
	totalHoursValue: "#total-hours",
};
const MODEL_ROUTES = {
	index: 		 route("attendance.index"),
	historyData: route("attendance.history.data"),
	tabs:		 route("attendance.tabs", { tab: ":tab" }),
	store: 		 route("attendance.store"),
	update: 	 route("attendance.update", { timesheet: ":id" }),
	destroy:	 route("attendance.destroy", { timesheet: ":id" }),
};
const TAB_LOADING_HTML =
	'<div class="alert alert-info" role="alert"><i class="bi bi-info-circle me-2"></i><span>Cargando contenido...</span></div>';
const TAB_ERROR_HTML =
	'<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-circle me-2"></i><span>No se pudo cargar esta pestaña. Intenta nuevamente.</span></div>';

const loadedTabs = new Set();

window.deleteAttendance = function (event) {
	return deleteModel(event, MODEL_NAME);
};

function parseTimeToMinutes(value) {
	if (!value) {
		return null;
	}

	const match = String(value).match(/(\d{2}):(\d{2})/);
	if (!match) {
		return null;
	}

	const hours = Number.parseInt(match[1], 10);
	const minutes = Number.parseInt(match[2], 10);

	if (Number.isNaN(hours) || Number.isNaN(minutes)) {
		return null;
	}

	return (hours * 60) + minutes;
}

function formatMinutesTo12h(totalMinutes) {
	if (totalMinutes === null || totalMinutes === undefined || Number.isNaN(totalMinutes)) {
		return "Hora invalida";
	}

	const normalized = Math.max(0, totalMinutes);
	const hours = Math.floor(normalized / 60);
	const minutes = normalized % 60;
	const hour12 = hours % 12 || 12;
	const period = hours >= 12 ? "PM" : "AM";

	return `${String(hour12).padStart(2, "0")}:${String(minutes).padStart(2, "0")} ${period}`;
}

function formatTimeFromInput(value) {
	const minutes = parseTimeToMinutes(value);
	return formatMinutesTo12h(minutes);
}

function getWorkedHours(startTime, endTime) {
	const startMinutes = parseTimeToMinutes(startTime);
	const endMinutes = parseTimeToMinutes(endTime);

	if (startMinutes === null || endMinutes === null || endMinutes <= startMinutes) {
		return 0;
	}

	return Math.floor((endMinutes - startMinutes) / 60);
}

function getEmployeeById(employeeId) {
	if (!employeeId) {
		return null;
	}

	const normalizedId = String(employeeId).trim();
	return APP_EMPLOYEES.find((employee) => String(employee?.id ?? "").trim() === normalizedId) ?? null;
}

function toggleElementVisibility(element, shouldShow) {
	if (!element) {
		return;
	}

	element.classList.toggle("d-none", !shouldShow);
}

function setInputReadonlyAndDisabled(input, shouldLock) {
	if (!input) {
		return;
	}

	input.readOnly = shouldLock;
	input.disabled = shouldLock;
}

function setHiddenOverride(form, fieldName, value) {
	if (!form) {
		return;
	}

	let hiddenInput = form.querySelector(`input[type="hidden"][name="${fieldName}"]`);
	if (!hiddenInput) {
		hiddenInput = document.createElement("input");
		hiddenInput.type = "hidden";
		hiddenInput.name = fieldName;
		form.appendChild(hiddenInput);
	}

	hiddenInput.value = value ?? "";
}

function removeHiddenOverride(form, fieldName) {
	if (!form) {
		return;
	}

	const hiddenInput = form.querySelector(`input[type="hidden"][name="${fieldName}"]`);
	if (hiddenInput && hiddenInput.id !== "work_date") {
		hiddenInput.remove();
	}
}

function getAttendanceDom() {
	const form = document.querySelector(FORM_SELECTORS.form);
	if (!form) {
		return null;
	}

	return {
		form,
		employeeInput: form.querySelector(FORM_SELECTORS.employeeId),
		attendanceDateInput: form.querySelector(FORM_SELECTORS.attendanceDate),
		workDateInput: form.querySelector(FORM_SELECTORS.workDate),
		startTimeInput: form.querySelector(FORM_SELECTORS.startTime),
		endTimeInput: form.querySelector(FORM_SELECTORS.endTime),
		isHolidayTrueInput: form.querySelector(FORM_SELECTORS.isHolidayTrue),
		attendanceCompleteAlert: form.querySelector(FORM_SELECTORS.attendanceCompleteAlert),
		attendanceStartAddedAlert: form.querySelector(FORM_SELECTORS.attendanceStartAddedAlert),
		startTimeCantModifyText: form.querySelector(FORM_SELECTORS.startTimeCantModifyText),
		endTimeCantModifyText: form.querySelector(FORM_SELECTORS.endTimeCantModifyText),
		totalHoursInfo: form.querySelector(FORM_SELECTORS.totalHoursInfo),
		totalHoursStartTime: form.querySelector(FORM_SELECTORS.totalHoursStartTime),
		totalHoursEndTime: form.querySelector(FORM_SELECTORS.totalHoursEndTime),
		totalHoursValue: form.querySelector(FORM_SELECTORS.totalHoursValue),
	};
}

function updateTotalHoursInfo(dom, startTime, endTime) {
	if (!dom) {
		return;
	}

	const workedHours = getWorkedHours(startTime, endTime);
	const hasCompleteShift = workedHours > 0;

	toggleElementVisibility(dom.totalHoursInfo, hasCompleteShift);

	if (!hasCompleteShift) {
		return;
	}

	if (dom.totalHoursStartTime) {
		dom.totalHoursStartTime.textContent = formatTimeFromInput(startTime);
	}

	if (dom.totalHoursEndTime) {
		dom.totalHoursEndTime.textContent = formatTimeFromInput(endTime);
	}

	if (dom.totalHoursValue) {
		dom.totalHoursValue.textContent = `${workedHours}h trabajadas`;
	}
}

function setStartTimePendingState(dom, timesheet) {
	if (!dom || !timesheet?.start_time) {
		return;
	}

	if (dom.startTimeInput) {
		dom.startTimeInput.value = timesheet.start_time;
		setInputReadonlyAndDisabled(dom.startTimeInput, true);
	}

	toggleElementVisibility(dom.startTimeCantModifyText, true);
	toggleElementVisibility(dom.attendanceStartAddedAlert, true);

	if (dom.attendanceStartAddedAlert) {
		dom.attendanceStartAddedAlert.innerHTML = `
			<i class="bi bi-exclamation-triangle-fill me-2"></i>
			Entrada registrada a las ${escapeHtml(formatTimeFromInput(timesheet.start_time))} -- pendiente hora de salida
		`;
	}

	if (dom.endTimeInput) {
		dom.endTimeInput.value = dom.endTimeInput.value || "";
	}
}

function setCompleteAttendanceState(dom, timesheet) {
	if (!dom || !timesheet?.start_time || !timesheet?.end_time) {
		return;
	}

	toggleElementVisibility(dom.attendanceCompleteAlert, true);

	if (dom.startTimeInput) {
		dom.startTimeInput.value = timesheet.start_time;
		setInputReadonlyAndDisabled(dom.startTimeInput, true);
	}

	if (dom.endTimeInput) {
		dom.endTimeInput.value = timesheet.end_time;
		setInputReadonlyAndDisabled(dom.endTimeInput, true);
	}

	toggleElementVisibility(dom.startTimeCantModifyText, true);
	toggleElementVisibility(dom.endTimeCantModifyText, true);
	updateTotalHoursInfo(dom, timesheet.start_time, timesheet.end_time);
}

function resetAttendanceFormState(dom) {
	if (!dom) {
		return;
	}

	toggleElementVisibility(dom.attendanceCompleteAlert, false);
	toggleElementVisibility(dom.attendanceStartAddedAlert, false);
	toggleElementVisibility(dom.startTimeCantModifyText, false);
	toggleElementVisibility(dom.endTimeCantModifyText, false);
	toggleElementVisibility(dom.totalHoursInfo, false);

	if (dom.startTimeInput) {
		setInputReadonlyAndDisabled(dom.startTimeInput, false);
		dom.startTimeInput.value = "";
	}

	if (dom.endTimeInput) {
		setInputReadonlyAndDisabled(dom.endTimeInput, false);
		dom.endTimeInput.value = "";
	}

	if (dom.isHolidayTrueInput) {
		dom.isHolidayTrueInput.checked = false;
	}
}

function fillStaticWorkDate(dom) {
	if (!dom?.attendanceDateInput || !dom?.workDateInput) {
		return;
	}

	dom.workDateInput.value = dom.attendanceDateInput.value;
}

function applyEmployeeAttendanceState(dom) {
	if (!dom || !dom.employeeInput) {
		return;
	}

	resetAttendanceFormState(dom);
	fillStaticWorkDate(dom);

	const selectedEmployee = getEmployeeById(dom.employeeInput.value);
	const todayTimesheet = selectedEmployee?.today_timesheet ?? null;

	if (!todayTimesheet) {
		removeHiddenOverride(dom.form, "_method");
		removeHiddenOverride(dom.form, "start_time");
		removeHiddenOverride(dom.form, "end_time");
		dom.form.action = MODEL_ROUTES.store;
		return;
	}

	if (todayTimesheet.is_holiday && dom.isHolidayTrueInput) {
		dom.isHolidayTrueInput.checked = true;
	}

	const hasStartTime = String(todayTimesheet.start_time ?? "").trim().length > 0;
	const hasEndTime = String(todayTimesheet.end_time ?? "").trim().length > 0;
	const totalHours = Number.parseFloat(String(todayTimesheet.total_hours ?? "0"));
	const isPendingEndTime = hasStartTime && (!hasEndTime || Number.isNaN(totalHours) || totalHours <= 0);

	if (hasStartTime && hasEndTime && !isPendingEndTime) {
		setCompleteAttendanceState(dom, todayTimesheet);
	} else if (isPendingEndTime) {
		setStartTimePendingState(dom, todayTimesheet);
	}

	dom.form.action = MODEL_ROUTES.update.replace(":id", String(todayTimesheet.id));
	setHiddenOverride(dom.form, "_method", "PUT");
}

function syncStartTimeHiddenWhenDisabled(dom) {
	if (!dom?.form || !dom?.startTimeInput) {
		return;
	}

	if (dom.startTimeInput.disabled) {
		setHiddenOverride(dom.form, "start_time", dom.startTimeInput.value);
		return;
	}

	removeHiddenOverride(dom.form, "start_time");
}

function syncEndTimeHiddenWhenDisabled(dom) {
	if (!dom?.form || !dom?.endTimeInput) {
		return;
	}

	if (dom.endTimeInput.disabled) {
		setHiddenOverride(dom.form, "end_time", dom.endTimeInput.value);
		return;
	}

	removeHiddenOverride(dom.form, "end_time");
}

function validateAttendanceForm(dom) {
	if (!dom?.employeeInput || !dom?.startTimeInput) {
		return false;
	}

	const employeeId = String(dom.employeeInput.value ?? "").trim();
	const startTime = String(dom.startTimeInput.value ?? "").trim();
	const endTime = String(dom.endTimeInput?.value ?? "").trim();

	if (!employeeId || employeeId === "-1") {
		return false;
	}

	if (!startTime) {
		return false;
	}

	if (endTime && getWorkedHours(startTime, endTime) <= 0) {
		return false;
	}

	return true;
}

function bindAttendanceForm() {
	const dom = getAttendanceDom();
	if (!dom) {
		return;
	}

	applyEmployeeAttendanceState(dom);

	dom.employeeInput?.addEventListener("change", () => {
		applyEmployeeAttendanceState(dom);
	});

	dom.startTimeInput?.addEventListener("change", () => {
		updateTotalHoursInfo(dom, dom.startTimeInput?.value, dom.endTimeInput?.value);
	});

	dom.endTimeInput?.addEventListener("change", () => {
		updateTotalHoursInfo(dom, dom.startTimeInput?.value, dom.endTimeInput?.value);
	});

	dom.form.addEventListener("submit", (event) => {
		fillStaticWorkDate(dom);
		syncStartTimeHiddenWhenDisabled(dom);
		syncEndTimeHiddenWhenDisabled(dom);

		if (!validateAttendanceForm(dom)) {
			event.preventDefault();
		}
	});
}

function getEmployeeInitials(name = "") {
	const nameParts = String(name).trim().split(/\s+/).filter(Boolean);

	if (nameParts.length === 0) {
		return "??";
	}

	return nameParts
		.slice(0, 2)
		.map((part) => part.charAt(0).toUpperCase())
		.join("");
}

function formatCompactDate(value) {
	const date = new Date(value);

	if (isNaN(date.getTime())) {
		return "Fecha invalida";
	}

	return DATE_FORMATTER.format(date).replaceAll(".", "");
}

function renderEmployeeInfo(data, type, row = {}) {
	const employee = typeof data === "object" && data !== null ? data : row?.employee ?? {};
	const name = employee?.name ?? row?.name ?? (typeof data === "string" ? data : "Sin nombre");
	const email = employee?.email ?? row?.email ?? "";

	if (type !== "display") {
		return `${name} ${email}`.trim();
	}

	return `
		<div class="d-flex align-items-center gap-2">
			<div class="rounded-circle d-inline-flex align-items-center justify-content-center fw-semibold bg-secondary-subtle" style="width: 2.5rem; height: 2.5rem; min-width: 2.5rem;">
				${escapeHtml(getEmployeeInitials(name))}
			</div>
			<div class="d-flex flex-column">
				<span class="fw-semibold lh-sm">${escapeHtml(name || "Sin nombre")}</span>
				<small class="text-muted lh-sm">${escapeHtml(email || "Sin correo")}</small>
			</div>
		</div>
	`;
}

function renderHolidayBadge(value, type) {
	if (!value) {
		return '<span class="text-muted px-4">&mdash;</span>';
	}

	return '<span class="badge border rounded-pill text-warning-emphasis bg-warning-subtle px-2 py-2"><i class="bi bi-stars me-1"></i>Feriado</span>';
}

function renderTimeCell(value, type) {
	if (!value) {
		return '<span class="badge border rounded-pill text-warning-emphasis bg-warning-subtle px-2 py-2"><i class="bi bi-hourglass-split me-1"></i>Pendiente</span>';
	}

	return `<span class="fw-semibold">${escapeHtml(formatTime(value))}</span>`;
}

function renderTotalHoursCell(value) {
	if (!value) {
		return '<span class="text-muted px-4 py-2">&mdash;</span>';
	}

	const numericHours = Number.parseFloat(String(value).replace(",", "."));
	const compactHours = `${Math.round(isNaN(numericHours) ? 0 : numericHours)}h`;

	if (!isNaN(numericHours) && numericHours > 8) {
		return `<span class="badge border rounded-pill text-danger-emphasis bg-danger-subtle px-2 py-2"><i class="bi bi-lightning-charge-fill me-1"></i>${escapeHtml(compactHours)}</span>`;
	}

	if (!isNaN(numericHours) && numericHours === 0) {
		return `<span class="badge border rounded-pill text-secondary-emphasis bg-secondary-subtle px-2 py-2"><i class="bi bi-x-lg me-1"></i>${escapeHtml(compactHours)}</span>`;
	}

	return `<span class="badge border rounded-pill text-success-emphasis bg-success-subtle px-2 py-2"><i class="bi bi-check-lg me-1"></i>${escapeHtml(compactHours)}</span>`;
}

function buildEmployeeMap(rows = [], employeeFilters = []) {
	const employees = new Map();
	const source = Array.isArray(employeeFilters) && employeeFilters.length > 0
		? employeeFilters.map(({ id, name }) => ({ id, name }))
		: rows.length > 0
			? rows.map((row) => ({ id: row?.employee_id, name: row?.employee?.name }))
			: APP_EMPLOYEES.map(({ id, name }) => ({ id, name }));

	for (const employee of source) {
		const id = String(employee?.id ?? "").trim();
		const name = String(employee?.name ?? "").trim();

		if (!id || !name || employees.has(id)) {
			continue;
		}

		employees.set(id, name);
	}

	return employees;
}

function updateEmployeeFilter(rows = [], employeeFilters = []) {
	const select = document.querySelector(FILTER_SELECTORS.employee);

	if (!select) {
		return;
	}

	const currentValue = String(select.value || DEFAULT_FILTER_VALUE);
	const employees = buildEmployeeMap(rows, employeeFilters);
	const sortedEmployees = Array.from(employees.entries()).sort((a, b) =>
		a[1].localeCompare(b[1], "es", { sensitivity: "base" }),
	);

	select.innerHTML = [
		`<option value="${DEFAULT_FILTER_VALUE}">Todos</option>`,
		...sortedEmployees.map(
			([id, name]) =>
				`<option value="${escapeHtml(id)}">${escapeHtml(name)}</option>`,
		),
	].join("");

	select.value = employees.has(currentValue) ? currentValue : DEFAULT_FILTER_VALUE;
}

function appendHistoryFilters(request) {
	const employeeId = String($(FILTER_SELECTORS.employee).val() ?? DEFAULT_FILTER_VALUE).trim();
	const workDate = String($(FILTER_SELECTORS.date).val() ?? "").trim();

	if (employeeId && employeeId !== DEFAULT_FILTER_VALUE) {
		request.employee_id = employeeId;
	}

	if (workDate) {
		request.work_date = workDate;
		request.month = workDate.slice(0, 7);
	}
}

function bindHistoryFilters(dataTable) {
	if (!dataTable) {
		return;
	}

	const reloadTable = () => dataTable.ajax.reload();

	$(FILTER_SELECTORS.employee)
		.off(".attendanceHistory")
		.on("change.attendanceHistory", reloadTable);

	$(FILTER_SELECTORS.date)
		.off(".attendanceHistory")
		.on("change.attendanceHistory", reloadTable);

	dataTable
		.off("xhr.attendanceHistory")
		.on("xhr.attendanceHistory", (_event, _settings, response) => {
			updateEmployeeFilter(
				Array.isArray(response?.data) ? response.data : [],
				Array.isArray(response?.employee_filters) ? response.employee_filters : [],
			);
		});
}

function createHistoryTable() {
	return CreateNewDataTable(
		ATTENDANCE_TABLE_ID,
		MODEL_ROUTES.historyData,
		[
			{
				data: "employee",
				name: "employee_id",
				searchable: false,
				render: (data, type, row) => renderEmployeeInfo(data, type, row),
			},
			{
				data: "work_date",
				name: "work_date",
				render: (data, type) => (formatCompactDate(data)),
			},
			{
				data: "is_holiday",
				name: "is_holiday",
				render: (data, type) => renderHolidayBadge(data, type),
			},
			{
				data: "start_time",
				name: "start_time",
				render: (data, type) => renderTimeCell(data, type),
			},
			{
				data: "end_time",
				name: "end_time",
				render: (data, type) => renderTimeCell(data, type),
			},
			{
				data: "total_hours",
				name: "total_hours",
				render: (data) => renderTotalHoursCell(data),
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
				placeholder: "Todos",
				placeholderSelected: true,
				options: [
					{ value: DEFAULT_FILTER_VALUE, text: "Todos", selected: true },
				],
			},
			{
				type: "date",
				id: FILTER_SELECTORS.date.replace("#", ""),
				label: "Fecha",
				labelIcon: "bi-calendar-date me-2",
				class: "attendance-date-filter",
				wrapperClass: "w-auto",
				placeholder: "Selecciona una fecha",
			},
		],
		{
			showSearchBar: false,
			customButtonsPosition: "top-start",
			ajax: {
				data: appendHistoryFilters,
			},
		},
	);
}

async function loadTab(tabId) {
	if (loadedTabs.has(tabId)) {
		return;
	}

	const container = document.querySelector(`#${tabId} .js-tab-lazy-content`);
	const url = container?.dataset?.url;

	if (!container || !url) {
		return;
	}

	container.innerHTML = TAB_LOADING_HTML;

	try {
		const response = await fetch(url, {
			headers: { "X-Requested-With": "XMLHttpRequest" },
		});

		if (!response.ok) {
			throw new Error(`Error ${response.status}`);
		}

		container.innerHTML = await response.text();
		loadedTabs.add(tabId);

		if (tabId === TAB_IDS.attendance) {
			bindAttendanceForm();
		}

		if (tabId === TAB_IDS.history) {
			bindHistoryFilters(createHistoryTable());
		}
	} catch (error) {
		container.innerHTML = TAB_ERROR_HTML;
		console.error(error);
	}
}

function initializeTabs() {
	const initialTabId = APP_DATA.initialTab ?? TAB_IDS.attendance;

	document.querySelectorAll(TAB_BUTTON_SELECTOR).forEach((button) => {
		button.addEventListener("shown.bs.tab", (event) => {
			const tabId = event.target?.dataset?.bsTarget?.replace("#", "");

			if (tabId) {
				loadTab(tabId);
			}
		});
	});

	const initialTabButton = document.querySelector(
		`${TAB_BUTTON_SELECTOR}[data-bs-target="#${initialTabId}"]`,
	);

	if (!initialTabButton) {
		return;
	}

	if (initialTabButton.classList.contains("active")) {
		loadTab(initialTabId);
		return;
	}

	bootstrap.Tab.getOrCreateInstance(initialTabButton).show();
}

$(() => {
	initializeTabs();
});
