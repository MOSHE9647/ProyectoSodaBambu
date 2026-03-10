import {
	clearAllFieldErrors,
	clearFieldError,
	formatPhoneNumber,
	showFieldError,
	validateAndDisplayField,
	validateEmail,
	validateName,
	validatePhone
} from '../../utils/validation.js';
import { setLoadingState } from '../../utils/utils.js';

// Ensure jQuery is loaded
if (typeof $ === 'undefined') {
	throw new Error('This script requires jQuery');
}

// Constants and Variables
const IS_EDITING = document.querySelector('form[id^="edit-"]') !== null;
const FORM_ID = IS_EDITING ? 'edit-client-form' : 'create-client-form';

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
		emptyMsg: '',
		invalidMsg: 'Ingrese un teléfono válido en formato +506 XXXX XXXX.'
	}
};

// Validation Functions

/**
 * Validates the client form fields.
 * @param {Object} values
 * @returns {boolean} True if all fields are valid, false otherwise.
 */
function validateClientForm(values) {
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
 * @returns {boolean} True if the form is valid and can be submitted, false otherwise.
 */
function submitClientForm() {
	clearAllFieldErrors(fieldValidators);

	// Cache DOM elements
	const $firstName = $('#first_name');
	const $lastName = $('#last_name');
	const $email = $('#email');
	const $phone = $('#phone');

	// Get form values
	const values = {
		first_name: $firstName.val().trim(),
		last_name: $lastName.val().trim(),
		email: $email.val().trim(),
	};

	const phone = $phone.val().trim();

	if (phone) {
		values.phone = formatPhoneNumber(phone);
	}
	return validateClientForm(values);
}

// Event Listeners

/**
 * Real-time validation for input fields.
 * Validates fields on input and shows/hides error messages accordingly.
 */
$(document).on('input change', `#${FORM_ID} input`, function (e) {
	const $target = $(e.target);
	const fieldId = $target.attr('id');

	// Skip if field is not in validators
	if (!fieldValidators.hasOwnProperty(fieldId)) {
		return;
	}

	let value = $target.val().trim();
	const {validator, emptyMsg, invalidMsg} = fieldValidators[fieldId];
	
	// Format phone number in real-time
	if (fieldId === 'phone') {
		value = formatPhoneNumber(value);
		$target.val(value);
	}

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

/**
 * Form submission event listener.
 * Validates the form and manages the loading state.
 */
$(document).on('submit', `#${FORM_ID}`, (e) => {
	e.preventDefault();
	setLoadingState(FORM_ID, true);

	// Validate form and submit if valid
	if (submitClientForm()) e.currentTarget.submit();
	else setLoadingState(FORM_ID, false);
});