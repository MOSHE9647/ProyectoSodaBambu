// Description: JavaScript code for handling login form validation and submission.
import {
	validateAndDisplayField,
	validateEmail,
	clearAllFieldErrors
} from '../utils/validation.js';
import {clearFieldError, showFieldError} from '../utils/validation.js';
import { togglePasswordVisibility } from '../utils/utils.js';
import {setLoadingState} from '../utils/utils.js';

// Ensure jQuery is loaded
if (typeof $ === 'undefined') {
	throw new Error('This script requires jQuery');
}

window.togglePasswordVisibility = togglePasswordVisibility;

// Constants and Variables
const loginFormId = 'login';
const fieldValidators = {
	email: {
		validator: validateEmail,
		emptyMsg: "El correo electrónico es obligatorio.",
		invalidMsg: "Ingrese un correo electrónico válido.",
	},
	password: {
		validator: () => true,
		emptyMsg: "La contraseña es obligatoria.",
		invalidMsg: "",
	},
};

// Validation Functions

/**
 * Updates the password toggle button class based on field error state.
 * @param {string} fieldId - The field ID
 * @param {boolean} hasError - Whether the field has an error
 */
function updatePasswordButtonClass(fieldId, hasError) {
	if (fieldId === 'password') {
		const $button = $(`#toggle-${fieldId}`);
		$button.toggleClass('btn-danger', hasError).toggleClass('btn-primary', !hasError);
	}
}

/**
 * Validates the login form fields.
 * @param email
 * @param password
 * @returns {boolean}
 */
function validateLoginForm(email, password) {
	const values = {email, password};
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
 * Handles the login form submission.
 * @returns {boolean}
 */
function submitLoginForm() {
	clearAllFieldErrors(fieldValidators);
	updatePasswordButtonClass('password', false);

	// Get form values and validate them
	const email = $('#email').val().trim();
	const password = $('#password').val().trim();
	let isValid = validateLoginForm(email, password);

	// If there are validation errors, do not submit the form
	if (!isValid) {
		// Check if password field has error and update button state
		updatePasswordButtonClass('password', $('#password').hasClass('is-invalid'));
		return false;
	}

	// Show loading state
	setLoadingState(loginFormId, true);
	return true;
}

// Event Listeners

/**
 * Real-time validation for input fields.
 * Validates fields on input and shows/hides error messages accordingly.
 */
Object.keys(fieldValidators).forEach((fieldId) => {
	$(document).on('input', `#${fieldId}`, function () {
		const value = $(this).val().trim();
		const {validator, emptyMsg, invalidMsg} = fieldValidators[fieldId];
		let hasError = false;

		if (!value) {
			showFieldError(fieldId, emptyMsg);
			hasError = true;
		} else if (!validator(value)) {
			showFieldError(fieldId, invalidMsg);
			hasError = true;
		} else {
			clearFieldError(fieldId);
			hasError = false;
		}

		// Update password button class based on error state
		updatePasswordButtonClass(fieldId, hasError);
	});
});

/**
 * Form submission event listener.
 * Validates the form and manages the loading state.
 */
$(document).on('submit', `#${loginFormId}-form`, (e) => {
	// Prevent default form submission
	e.preventDefault();
	if (submitLoginForm()) e.currentTarget.submit();
	else setLoadingState(loginFormId, false);
});