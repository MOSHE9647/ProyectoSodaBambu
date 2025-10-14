// Description: JavaScript code for handling login form validation and submission.
import {validateAndDisplayField, validateEmail, validatePassword} from '../utils/validation.js';
import {clearFieldError, showFieldError} from '../utils/errorHandling.js';
import {setLoadingState} from '../utils/utils.js';

// Ensure jQuery is loaded
if (typeof $ === 'undefined') {
	throw new Error('This script requires jQuery');
}

// Constants and Variables
const loginFormId = 'login';
const fieldValidators = {
	email: {
		validator: validateEmail,
		emptyMsg: 'El correo electrónico es obligatorio.',
		invalidMsg: 'Ingrese un correo electrónico válido.'
	},
	password: {
		validator: validatePassword,
		emptyMsg: 'La contraseña es obligatoria.',
		invalidMsg: 'La contraseña debe contener, al menos, 8 caracteres alfanuméricos.'
	}
};

// Validation Functions

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
 * Clears all field errors in the form.
 */
function clearAllFieldErrors() {
	Object.keys(fieldValidators).forEach(clearFieldError);
}

/**
 * Form Submission Handler.
 *
 * Handles the login form submission.
 * @returns {boolean}
 */
function submitLoginForm() {
	clearAllFieldErrors();

	// Get form values and validate them
	const email = $('#email').val().trim();
	const password = $('#password').val().trim();
	let isValid = validateLoginForm(email, password);

	// If there are validation errors, do not submit the form
	if (!isValid) return false;

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

		if (!value) {
			showFieldError(fieldId, emptyMsg);
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
$(document).on('submit', `#${loginFormId}-form`, (e) => {
	// Prevent default form submission
	e.preventDefault();
	if (submitLoginForm()) e.currentTarget.submit();
	else setLoadingState(loginFormId, false);
});
