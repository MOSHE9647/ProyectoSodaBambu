// Description: JavaScript code for handling client creation form validation and submission.
import {
	clearAllFieldErrors,
	clearFieldError,
	showFieldError,
	validateAndDisplayField,
	validateEmail,
	validateName,
	validatePhone
} from '../../utils/validation.js';
import {setLoadingState} from '../../utils/utils.js';

// Ensure jQuery is loaded
if (typeof $ === 'undefined') {
	throw new Error('This script requires jQuery');
}

// Constants and Variables
const isEdit = document.querySelector('form[id^="edit-"]') !== null;
const formId = isEdit ? 'edit-client-form' : 'create-client-form';
const fieldValidators = {
	first_name: {
		validator: validateName,
		emptyMsg: 'El nombre es obligatorio.',
		invalidMsg: 'El nombre no puede exceder 255 caracteres.'
	},
	last_name: {
		validator: validateName,
		emptyMsg: 'Los apellidos son obligatorios.',
		invalidMsg: 'Los apellidos no pueden exceder 255 caracteres.'
	},
	email: {
		validator: validateEmail,
		emptyMsg: 'El correo electrónico es obligatorio.',
		invalidMsg: 'Ingrese un correo electrónico válido.'
	},
	phone: {
		validator: validatePhone,
		emptyMsg: '', // Phone is optional
		invalidMsg: 'Ingrese un teléfono válido en formato +506 XXXX XXXX.'
	}
};

// Validation Functions

/**
 * Validates the client form fields.
 * @param values
 * @returns {boolean}
 */
function validateClientForm(values) {
	// Validate all fields
	return validateAndDisplayField(
		fieldValidators,
		values,
		showFieldError,
		clearFieldError
	);
}

// UI Manipulation Functions

/**
 * Form Submission Handler.
 *
 * Handles the client form submission.
 * @returns {boolean}
 */
function submitClientForm() {
	clearAllFieldErrors(fieldValidators);

	// Get form values
	const values = {
		first_name: $('#first_name').val().trim(),
		last_name: $('#last_name').val().trim(),
		email: $('#email').val().trim(),
		phone: $('#phone').val().trim(),
	};

	// Validate form
	// If there are validation errors, do not submit the form
	return validateClientForm(values);
}

// Event Listeners
/**
 * Real-time validation for input fields.
 * Validates fields on input and shows/hides error messages accordingly.
 */
Object.keys(fieldValidators).forEach((fieldId) => {
	$(document).on('input change', `#${fieldId}`, function () {
		const value = $(this).val().trim();
		const {validator, emptyMsg, invalidMsg} = fieldValidators[fieldId];

		// Skip validation for optional phone field when empty
		if (fieldId === 'phone' && !value) {
			clearFieldError(fieldId);
			return;
		}

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
});

/**
 * Form submission event listener.
 * Validates the form and manages the loading state.
 */
$(document).on('submit', `#${formId}`, (e) => {
	// Prevent default form submission
	e.preventDefault();
	setLoadingState(formId, true);
	if (submitClientForm()) e.currentTarget.submit();
	else setLoadingState(formId, false);
});