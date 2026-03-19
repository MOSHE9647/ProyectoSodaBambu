// Description: JavaScript code for handling employee attendance form validation and submission.
import {
	clearAllFieldErrors,
	clearFieldError,
	showFieldError,
	validateAndDisplayField,
	validateRole,
	validateTime,
} from '../../utils/validation.js';
import {
	setLoadingState,
	calcWorkedHours,
	DOMHelper,
	escapeHtml,
	format12h,
} from '../../utils/utils.js';

// ==================== Environment Checks ====================

// Ensure jQuery is loaded
if (typeof $ === 'undefined') {
	throw new Error('This script requires jQuery');
}

// ==================== Constants ====================

const FORM_ID = 'employee-attendance-form';
const SUBMIT_FORM_ID = 'attendance-submit-form';
const APP_DATA = window.AttendanceAppData ?? {};
const APP_EMPLOYEES = Array.isArray(APP_DATA.employees) ? APP_DATA.employees : [];

const FORM_SELECTORS = {
	form: `#${FORM_ID}`,
	employeeId: '#attendance-employee_id',
	attendanceDate: '#attendance-work_date_display',
	workDate: '#attendance-work_date',
	startTime: '#attendance-start_time',
	endTime: '#attendance-end_time',
	isHolidayTrue: '#attendance-is_holiday_true',
	attendanceCompleteAlert: '#attendance-complete-alert',
	attendanceStartAddedAlert: '#attendance-start-time-added-alert',
	startTimeCantModifyText: '#attendance-start-time-cant-be-modify',
	endTimeCantModifyText: '#attendance-end-time-cant-be-modify',
	totalHoursInfo: '#attendance-total-hours-info',
	totalHoursStartTime: '#attendance-start-time-display',
	totalHoursEndTime: '#attendance-end-time-display',
	totalHoursValue: '#attendance-total-hours',
};

const MODEL_ROUTES = {
	store: route('attendance.store'),
	update: route('attendance.update', { timesheet: ':id' }),
};

const FIELD_KEYS = {
	employeeId: FORM_SELECTORS.employeeId.replace('#', ''),
	startTime: FORM_SELECTORS.startTime.replace('#', ''),
	endTime: FORM_SELECTORS.endTime.replace('#', ''),
};

const baseFieldValidators = {
	[FIELD_KEYS.employeeId]: {
		validator: validateRole,
		emptyMsg: 'Debe seleccionar un colaborador.',
		invalidMsg: 'Seleccione un colaborador válido.',
	},
	[FIELD_KEYS.startTime]: {
		validator: validateTime,
		emptyMsg: 'La hora de entrada es obligatoria.',
		invalidMsg: 'Ingrese una hora de entrada válida.',
	},
	[FIELD_KEYS.endTime]: {
		validator: (value) => {
			const startTime = $(FORM_SELECTORS.startTime).val();
			return calcWorkedHours(startTime, value) > 0;
		},
		emptyMsg: '',
		invalidMsg: 'La hora de salida debe ser posterior a la hora de entrada.',
	},
};

// ==================== Validation Functions ====================

/**
 * Creates a filtered copy of fieldValidators, excluding end_time if not filled (it is optional).
 * @returns {Object}
 */
function getActiveFieldValidators() {
	const validators = { ...baseFieldValidators };

	// end_time is optional – only validate if it has a value
	if (!$(FORM_SELECTORS.endTime).val()) {
		delete validators[FIELD_KEYS.endTime];
	}

	return validators;
}

/**
 * Validates the attendance form fields.
 * @param {Object} values
 * @param {Object} fieldValidators
 * @returns {boolean}
 */
function validateAttendanceForm(values, fieldValidators) {
	return validateAndDisplayField(
		fieldValidators,
		values,
		showFieldError,
		clearFieldError
	);
}

// ==================== UI Manipulation Functions ====================

/**
 * Form Submission Handler.
 *
 * Collects form values and runs validation.
 * @returns {boolean}
 */
function submitAttendanceForm() {
	const fieldValidators = getActiveFieldValidators();
	clearAllFieldErrors(fieldValidators);

	const values = {
		[FIELD_KEYS.employeeId]: $(FORM_SELECTORS.employeeId).val() ?? '',
		[FIELD_KEYS.startTime]: $(FORM_SELECTORS.startTime).val() ?? '',
		[FIELD_KEYS.endTime]: $(FORM_SELECTORS.endTime).val() ?? '',
	};

	return validateAttendanceForm(values, fieldValidators);
}

// ==================== Event Listeners ====================

/**
 * Real-time validation for input and select fields.
 * Uses event delegation so it works even after the form is lazy-loaded.
 */
$(document).on('input change', `#${FORM_ID}`, function (e) {
	const $target = $(e.target);
	const fieldId = $target.attr('id');
	const fieldName = FIELD_KEYS[fieldId] || fieldId;
	const validators = getActiveFieldValidators();

	// Skip if field is not in validators
	if (!validators.hasOwnProperty(fieldName)) {
		console.warn(`No validator defined for field: ${fieldName}`);
		return;
	}

	const value = $target.val().trim();
	const { validator, emptyMsg, invalidMsg } = validators[fieldName];

	if (!value) {
		if (emptyMsg) {
			showFieldError(fieldId, emptyMsg);
		} else {
			clearFieldError(fieldId);
		}
	} else if (!validator(value)) {
		showFieldError(fieldId, invalidMsg);
	} else {
		clearFieldError(fieldId);
	}
});

/**
 * Initializes the attendance form behavior, including state handling,
 * validation, and hidden override synchronization.
 * @returns {void}
 */
export function initAttendanceForm() {
	const form = document.querySelector(FORM_SELECTORS.form);
	if (!form) return;

	// Get all required DOM elements at once
	const els = Object.fromEntries(
		Object.entries(FORM_SELECTORS).map(([key, selector]) => [
			key,
			form.querySelector(selector),
		]),
	);

	/**
	 * Recalculates worked hours and updates the summary section.
	 * @returns {void}
	 */
	const updateTotalHours = () => {
		const hours = calcWorkedHours(els.startTime?.value, els.endTime?.value);
		DOMHelper.toggleVisibility(els.totalHoursInfo, hours > 0);

		if (hours > 0) {
			if (els.totalHoursStartTime)
				els.totalHoursStartTime.textContent = format12h(els.startTime.value);
			if (els.totalHoursEndTime)
				els.totalHoursEndTime.textContent = format12h(els.endTime.value);
			if (els.totalHoursValue)
				els.totalHoursValue.textContent = `${hours}h trabajadas`;
		}
	};

	/**
	 * Applies form state based on the selected employee and today's timesheet.
	 * @returns {void}
	 */
	const applyState = () => {
		// 1. Reset base state
		DOMHelper.toggleVisibility(els.attendanceCompleteAlert, false);
		DOMHelper.toggleVisibility(els.attendanceStartAddedAlert, false);
		DOMHelper.toggleVisibility(els.startTimeCantModifyText, false);
		DOMHelper.toggleVisibility(els.endTimeCantModifyText, false);
		DOMHelper.toggleVisibility(els.totalHoursInfo, false);
		DOMHelper.setReadonly(els.startTime, false);
		DOMHelper.setReadonly(els.endTime, false);
		if (els.startTime) {
			els.startTime.value = "";
			els.startTime.required = true; // Restore required on reset
		}
		if (els.endTime) {
			els.endTime.value = "";
			els.endTime.required = false; // end_time is optional
		}
		if (els.isHolidayTrue) els.isHolidayTrue.checked = false;
		if (els.workDate && els.attendanceDate)
			els.workDate.value = els.attendanceDate.value;

		// 2. Lookup employee data
		const employee = APP_EMPLOYEES.find(
			(e) => String(e.id) === String(els.employeeId?.value),
		);
		const ts = employee?.today_timesheet;

		// If there is no timesheet, prepare for create (Store)
		if (!ts) {
			form.action = MODEL_ROUTES.store;
			DOMHelper.removeHiddenOverride(form, '_method');
			DOMHelper.removeHiddenOverride(form, 'start_time');
			DOMHelper.removeHiddenOverride(form, 'end_time');
			return;
		}

		// 3. Apply existing data (Update)
		form.action = MODEL_ROUTES.update.replace(':id', ts.id);
		DOMHelper.setHiddenOverride(form, '_method', 'PUT');
		if (ts.is_holiday && els.isHolidayTrue)
			els.isHolidayTrue.checked = true;

		const hasStart = !!ts.start_time;
		const hasEnd = !!ts.end_time;
		const totalHrs = parseFloat(ts.total_hours || '0');
		const isPending =
			hasStart && (!hasEnd || isNaN(totalHrs) || totalHrs <= 0);

		if (hasStart) {
			els.startTime.value = ts.start_time;
			els.startTime.required = false; // Remove required when readonly to avoid native validation error
			DOMHelper.setReadonly(els.startTime, true);
			DOMHelper.toggleVisibility(els.startTimeCantModifyText, true);
		}

		if (isPending) {
			DOMHelper.toggleVisibility(els.attendanceStartAddedAlert, true);
			if (els.attendanceStartAddedAlert) {
				els.attendanceStartAddedAlert.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-2"></i>Entrada registrada a las ${escapeHtml(format12h(ts.start_time))} -- pendiente hora de salida`;
			}
		} else if (hasStart && hasEnd) {
			els.endTime.value = ts.end_time;
			els.endTime.required = false; // Remove required when readonly
			DOMHelper.setReadonly(els.endTime, true);
			DOMHelper.toggleVisibility(els.endTimeCantModifyText, true);
			DOMHelper.toggleVisibility(els.attendanceCompleteAlert, true);
			updateTotalHours();
		}
	};

	// Listeners for state management
	els.employeeId?.addEventListener('change', applyState);
	els.startTime?.addEventListener('change', updateTotalHours);
	els.endTime?.addEventListener('change', updateTotalHours);

	const handleFormSubmit = (event) => {
		event.preventDefault();
		setLoadingState(SUBMIT_FORM_ID, true);

		if (els.workDate && els.attendanceDate)
			els.workDate.value = els.attendanceDate.value;

		// Sync hidden inputs when fields are disabled
		if (els.startTime?.disabled)
			DOMHelper.setHiddenOverride(form, 'start_time', els.startTime.value);
		else DOMHelper.removeHiddenOverride(form, 'start_time');

		if (els.endTime?.disabled)
			DOMHelper.setHiddenOverride(form, 'end_time', els.endTime.value);
		else DOMHelper.removeHiddenOverride(form, 'end_time');

		if (submitAttendanceForm()) {
			event.currentTarget.submit();
		} else {
			setLoadingState(SUBMIT_FORM_ID, false);
		}
	};
	form.addEventListener('submit', handleFormSubmit);

	applyState(); // Initial state
}
