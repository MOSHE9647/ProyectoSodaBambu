// Description: JavaScript code for handling login form validation and submission.

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
 * Validates if the provided email is in a correct format.
 * @param email
 * @returns {boolean}
 */
function validateEmail(email) {
	const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	return emailRegex.test(email);
}

/**
 * Validates if the provided password meets the criteria.
 * @param password
 * @returns {boolean}
 */
function validatePassword(password) {
	// Password must be at least 8 alphanumerical characters long
	const passwordRegex = /^[A-Za-z0-9]{8,}$/;
	return passwordRegex.test(password);
}

/**
 * Validates the login form fields.
 * @param email
 * @param password
 * @returns {boolean}
 */
function validateLoginForm(email, password) {
	let isValid = true;
	const values = {email, password};

	Object.keys(fieldValidators).forEach((fieldId) => {
		const {validator, emptyMsg, invalidMsg} = fieldValidators[fieldId];
		const value = (values[fieldId] || '').trim();

		if (!value) {
			showFieldError(fieldId, emptyMsg);
			isValid = false;
		} else if (!validator(value)) {
			showFieldError(fieldId, invalidMsg);
			isValid = false;
		}
	});

	return isValid;
}

// UI Manipulation Functions

/**
 * Retrieves the field and its associated error element based on the field ID.
 * @param fieldId
 * @returns {{field: *|jQuery|HTMLElement, errorElement: *|jQuery|[]}}
 */
function getFieldElements(fieldId) {
	const field = $(`#${fieldId}`);
	const errorElement = $(`#${fieldId}-error`).children('strong');
	return {field, errorElement};
}

/**
 * Displays an error message for a specific field.
 * @param fieldId
 * @param message
 */
function showFieldError(fieldId, message) {
	const {field, errorElement} = getFieldElements(fieldId);

	if (field.length && errorElement.length) {
		field.addClass('is-invalid');
		errorElement.text(message);
	} else {
		console.error(`Field or error element not found for ID: ${fieldId}`);
	}
}

/**
 * Clears the error message for a specific field.
 * @param fieldId
 */
function clearFieldError(fieldId) {
	const {field, errorElement} = getFieldElements(fieldId);

	if (field.length && errorElement.length) {
		field.removeClass('is-invalid');
		errorElement.text('');
	} else {
		console.error(`Field or error element not found for ID: ${fieldId}`);
	}
}

/**
 * Clears all field errors in the form.
 */
function clearAllFieldErrors() {
	Object.keys(fieldValidators).forEach(clearFieldError);
}

/**
 * Sets the loading state for the form's submit button.
 * @param formId
 * @param isLoading
 */
function setLoadingState(formId, isLoading) {
	// Get necessary elements
	const spinner = $(`#${formId}-spinner`);
	const submitButton = $(`#${formId}-button`);
	const submitButtonText = $(`#${formId}-button-text`);

	// Check if elements exist
	if (!submitButton.length || !spinner.length || !submitButtonText.length) {
		console.error(`Submit button or spinner not found for form ID: ${formId}`);
		return;
	}

	// Toggle loading state
	if (isLoading) {
		// Disable the button and hide the text
		submitButton.attr('disabled', 'disabled');
		submitButtonText.removeClass('d-flex').addClass('d-none');

		// Show the spinner
		spinner.removeClass('d-none');
		spinner.find('span').eq(1).removeClass('visually-hidden');
	} else {
		// Enable the button and show the text
		submitButton.removeAttr('disabled');
		submitButtonText.removeClass('d-none').addClass('d-flex');

		// Hide the spinner
		spinner.addClass('d-none');
		spinner.find('span').eq(1).addClass('visually-hidden');
	}
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
