// Description: JavaScript for forgot password page
import { clearFieldError, showFieldError, validateEmail } from '../../utils/validation.js';
import { setLoadingState } from '../../utils/utils.js';

// Ensure jQuery is loaded
if (typeof $ === 'undefined') {
	throw new Error('This script requires jQuery');
}

// Constants and Variables
const forgotPasswordFormId = 'forgot-password';

/**
 * Validates the forgot password form fields.
 * @param email
 * @returns {boolean}
 */
function validateForgotPasswordForm(email) {
	let isValid = true;
	const trimmedEmail = (email || '').trim();

	if (!trimmedEmail) {
		showFieldError('email', 'El correo electrónico es obligatorio.');
		isValid = false;
	} else if (!validateEmail(trimmedEmail)) {
		showFieldError('email', 'Ingrese un correo electrónico válido.');
		isValid = false;
	}

	return isValid;
}

/**
 * Form Submission Handler
 *
 * Handles the submission of the forgot password form.
 * @returns {boolean}
 */
function submitForgotPasswordForm() {
	clearFieldError('email');

	// Get form values and validate them
	const email = $('#email').val().trim();
	let isValid = validateForgotPasswordForm(email);

	// If there are validation errors, do not submit the form
	if (!isValid) return false;

	// Show loading state
	setLoadingState(forgotPasswordFormId, true);
	return true;
}

/**
 * Real-time Email Validation.
 * Validates the email field as the user types.
 */
$(document).on('input', '#email', () => {
	const value = $('#email').val().trim();

	if (!value) {
		showFieldError('email', 'El correo electrónico es obligatorio.');
	} else if (!validateEmail(value)) {
		showFieldError('email', 'Ingrese un correo electrónico válido.');
	} else {
		clearFieldError('email');
	}
})

/**
 * Form Submission Event Listener
 * Validates the form and manages the loading state.
 */
$(document).on('submit', `#${forgotPasswordFormId}-form`, (e) => {
	e.preventDefault();
	if (submitForgotPasswordForm()) e.currentTarget.submit();
	else setLoadingState(forgotPasswordFormId, false);
});
