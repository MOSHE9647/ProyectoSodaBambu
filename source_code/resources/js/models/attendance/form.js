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

const HIDDEN_OVERRIDE_FIELDS = ['start_time', 'end_time'];

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
 * Builds the active validator map used by the attendance form.
 *
 * The `end_time` field is optional, so its validator is dynamically removed
 * whenever the input is empty. This avoids showing invalid-state errors for
 * a field that the user is not required to complete.
 *
 * @returns {Record<string, {validator: Function, emptyMsg?: string, invalidMsg: string}>}
 * A shallow-cloned validator map containing only currently enforceable rules.
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
 * Reads a form field value from the DOM and normalizes it.
 *
 * @param {string} selector - jQuery selector for the target input/select element.
 * @returns {string} The field value converted to string and trimmed.
 */
function getFieldValue(selector) {
	return String($(selector).val() ?? '').trim();
}

/**
 * Runs field-level validation and paints error UI for the attendance form.
 *
 * Delegates the heavy lifting to the shared `validateAndDisplayField` utility,
 * injecting local UI handlers for showing and clearing field-specific errors.
 *
 * @param {Record<string, string>} values - Current form values keyed by field id.
 * @param {Record<string, {validator: Function, emptyMsg?: string, invalidMsg: string}>} fieldValidators
 * Active validator configuration.
 * @returns {boolean} `true` when all active fields are valid; otherwise `false`.
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
 * Executes full-form validation for attendance submission.
 *
 * It first clears previous validation errors, then gathers normalized values,
 * and finally validates against the active rule set.
 *
 * @returns {boolean} `true` if submit can proceed; otherwise `false`.
 */
function submitAttendanceForm() {
	const fieldValidators = getActiveFieldValidators();
	clearAllFieldErrors(fieldValidators);

	const values = {
		[FIELD_KEYS.employeeId]: getFieldValue(FORM_SELECTORS.employeeId),
		[FIELD_KEYS.startTime]: getFieldValue(FORM_SELECTORS.startTime),
		[FIELD_KEYS.endTime]: getFieldValue(FORM_SELECTORS.endTime),
	};

	return validateAttendanceForm(values, fieldValidators);
}

/**
 * Validates a single field and updates its inline error state.
 *
 * @param {string} fieldId - DOM id of the field being validated.
 * @param {string} value - Current trimmed field value.
 * @param {Record<string, {validator: Function, emptyMsg?: string, invalidMsg: string}>} validators
 * Validator map to resolve the field rule.
 * @returns {void}
 */
function validateSingleField(fieldId, value, validators) {
	if (!Object.hasOwn(validators, fieldId)) {
		console.warn(`No validator defined for field: ${fieldId}`);
		return;
	}

	const { validator, emptyMsg, invalidMsg } = validators[fieldId];

	if (!value) {
		if (emptyMsg) {
			showFieldError(fieldId, emptyMsg);
		} else {
			clearFieldError(fieldId);
		}
		return;
	}

	if (!validator(value)) {
		showFieldError(fieldId, invalidMsg);
		return;
	}

	clearFieldError(fieldId);
}

// ==================== Event Listeners ====================

/**
 * Real-time validation for input and select fields.
 *
 * Uses delegated binding on `document` so it keeps working when the form
 * is injected/re-rendered by lazy-loaded tab content.
 *
 * @param {JQuery.TriggeredEvent} e - jQuery event object.
 * @returns {void}
 */
$(document).on('input change', `#${FORM_ID}`, function (e) {
	const $target = $(e.target);
	const fieldId = $target.attr('id');
	if (!fieldId) return;

	const validators = getActiveFieldValidators();
	const value = String($target.val() ?? '').trim();

	validateSingleField(fieldId, value, validators);
});

/**
 * Initializes all attendance form behaviors for the active tab content.
 *
 * Responsibilities:
 * 1. Cache required DOM references.
 * 2. Manage UI state for create/update attendance flows.
 * 3. Keep hidden override inputs in sync for read-only/disabled fields.
 * 4. Wire live updates for worked-hours summary.
 * 5. Validate and submit the form safely.
 *
 * Safe to call repeatedly after tab lazy-reloads because listeners are bound
 * to the currently resolved form elements.
 *
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
	 * Recomputes and renders the worked-hours summary block.
	 *
	 * It toggles visibility based on computed hours and formats both start/end
	 * times for human-readable display.
	 *
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
	 * Applies a compact input state update (value/required/readonly).
	 *
	 * @param {HTMLInputElement|null} input - Target input element.
	 * @param {{ value?: string, required?: boolean, readonly?: boolean }} options
	 * Partial state descriptor.
	 * @returns {void}
	 */
	const setInputState = (input, { value = '', required, readonly }) => {
		if (!input) return;
		input.value = value;
		if (typeof required === 'boolean') input.required = required;
		if (typeof readonly === 'boolean') DOMHelper.setReadonly(input, readonly);
	};

	/**
	 * Resets UI and field states before applying employee-specific timesheet data.
	 *
	 * This ensures a deterministic baseline whenever employee selection changes.
	 * @returns {void}
	 */
	const resetBaseState = () => {
		[
			els.attendanceCompleteAlert,
			els.attendanceStartAddedAlert,
			els.startTimeCantModifyText,
			els.endTimeCantModifyText,
			els.totalHoursInfo,
		].forEach((el) => DOMHelper.toggleVisibility(el, false));

		setInputState(els.startTime, { value: '', required: true, readonly: false });
		setInputState(els.endTime, { value: '', required: false, readonly: false });

		if (els.isHolidayTrue) els.isHolidayTrue.checked = false;
		if (els.workDate && els.attendanceDate)
			els.workDate.value = els.attendanceDate.value;
	};

	/**
	 * Removes update-specific hidden overrides from the form.
	 *
	 * Used when switching back to "create" mode to avoid stale `_method`
	 * and time override payloads.
	 * @returns {void}
	 */
	const clearHiddenOverrides = () => {
		DOMHelper.removeHiddenOverride(form, '_method');
		HIDDEN_OVERRIDE_FIELDS.forEach((field) => {
			DOMHelper.removeHiddenOverride(form, field);
		});
	};

	/**
	 * Applies UI state according to the selected employee and today's timesheet.
	 *
	 * Behavior summary:
	 * - No timesheet: prepare a clean create flow (`POST` store route).
	 * - Existing timesheet: switch to update flow (`PUT` update route).
	 * - Start time present and pending end: show pending alert.
	 * - Start+end present: lock both fields and display completion summary.
	 *
	 * @returns {void}
	 */
	const applyState = () => {
		// 1. Reset base state
		resetBaseState();

		// 2. Lookup employee data
		const employee = APP_EMPLOYEES.find(
			(e) => String(e.id) === String(els.employeeId?.value),
		);
		const ts = employee?.today_timesheet;

		// If there is no timesheet, prepare for create (Store)
		if (!ts) {
			form.action = MODEL_ROUTES.store;
			clearHiddenOverrides();
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
			setInputState(els.startTime, {
				value: ts.start_time,
				required: false,
				readonly: true,
			});
			DOMHelper.toggleVisibility(els.startTimeCantModifyText, true);
		}

		if (isPending) {
			DOMHelper.toggleVisibility(els.attendanceStartAddedAlert, true);
			if (els.attendanceStartAddedAlert) {
				els.attendanceStartAddedAlert.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-2"></i>Entrada registrada a las ${escapeHtml(format12h(ts.start_time))} -- pendiente hora de salida`;
			}
		} else if (hasStart && hasEnd) {
			setInputState(els.endTime, {
				value: ts.end_time,
				required: false,
				readonly: true,
			});
			DOMHelper.toggleVisibility(els.endTimeCantModifyText, true);
			DOMHelper.toggleVisibility(els.attendanceCompleteAlert, true);
			updateTotalHours();
		}
	};

	// Listeners for state management
	els.employeeId?.addEventListener('change', applyState);
	els.startTime?.addEventListener('change', updateTotalHours);
	els.endTime?.addEventListener('change', updateTotalHours);

	/**
	 * Handles form submission with validation and hidden field synchronization.
	 *
	 * @param {SubmitEvent} event - Native form submit event.
	 * @returns {void}
	 */
	const handleFormSubmit = (event) => {
		event.preventDefault();
		setLoadingState(SUBMIT_FORM_ID, true);

		if (els.workDate && els.attendanceDate)
			els.workDate.value = els.attendanceDate.value;

		// Sync hidden inputs when fields are disabled
		[
			{ input: els.startTime, field: 'start_time' },
			{ input: els.endTime, field: 'end_time' },
		].forEach(({ input, field }) => {
			if (input?.disabled) {
				DOMHelper.setHiddenOverride(form, field, input.value);
			} else {
				DOMHelper.removeHiddenOverride(form, field);
			}
		});

		if (submitAttendanceForm()) {
			event.currentTarget.submit();
		} else {
			setLoadingState(SUBMIT_FORM_ID, false);
		}
	};
	form.addEventListener('submit', handleFormSubmit);

	applyState(); // Initial state
}
