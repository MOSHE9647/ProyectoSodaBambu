// Description: JavaScript for password reset page
import {
	clearFieldError, showFieldError,
	validateEmail, validatePassword,
	validatePasswordConfirmation,
	validateAndDisplayField
} from '../../utils/validation.js';
import {setLoadingState} from '../../utils/utils.js';

// Ensure jQuery is loaded
if (typeof $ === 'undefined') {
	throw new Error('This script requires jQuery');
}

// Constants and Variables
const resetFormId = 'reset-password';
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
	},
	password_confirmation: {
		validator: (confirmPassword) => validatePasswordConfirmation(
			getPasswordValue(),
			confirmPassword
		),
		emptyMsg: 'La confirmación de la contraseña es obligatoria.',
		invalidMsg: 'Las contraseñas no coinciden.'
	}
};

// Validation Functions
/**
 * Retrieves the trimmed value of the password field.
 * @returns {string}
 */
function getPasswordValue() {
	return $('#password').val().trim();
}

/**
 * Validates the reset password form fields.
 * @param email
 * @param password
 * @param confirmPassword
 * @returns {boolean}
 */
function validateResetPasswordForm(email, password, confirmPassword) {
	const values = {email, password, password_confirmation: confirmPassword};
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
 * Form Submission Handler
 *
 * Handles the submission of the reset password form.
 * @returns {boolean}
 */
function submitResetPasswordForm() {
	clearAllFieldErrors();

	// Get form values and validate them
	const email = $('#email').val().trim();
	const password = $('#password').val().trim();
	const confirmPassword = $(`#password_confirmation`).val().trim();
	let isValid = validateResetPasswordForm(
		email, password, confirmPassword
	);

	// If there are validation errors, do not submit the form
	if (!isValid) return false;

	// Show loading state
	setLoadingState(resetFormId, true);
	return true;
}

// Event Listeners
/**
 * Real-time validation for input fields.
 * Validates fields on input and shows/hides error messages accordingly.
 */
Object.keys(fieldValidators).forEach((fieldId) => {
	$(document).on('input', `#${fieldId}`, () => {
		const value = $(`#${fieldId}`).val().trim();
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
 * Form Submission Event Listener
 */
$(document).on('submit', `#${resetFormId}-form`, (e) => {
	e.preventDefault();
	if (submitResetPasswordForm()) e.currentTarget.submit();
	else setLoadingState(resetFormId, false);
});
