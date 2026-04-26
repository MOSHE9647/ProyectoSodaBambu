import { 
    clearAllFieldErrors,
    clearFieldError,
    showFieldError,
    validateAndDisplayField,
    validateEmail,
    validateName,
    validatePhone,
    formatPhoneNumber
} from '../../utils/validation.js';
import { setLoadingState } from '../../utils/utils.js';

// ==================== Environment Checks ====================

// Ensure jQuery is loaded
if (typeof $ === 'undefined') {
    throw new Error('This script requires jQuery');
}

// ==================== Constants ====================

const IS_EDITING = document.querySelector('form[id^="edit-"]') !== null;
const FORM_ID = IS_EDITING ? 'edit-supplier-form' : 'create-supplier-form';

const fieldValidators = {
    name: {
        validator: validateName,
        emptyMsg: 'El nombre es obligatorio.',
        invalidMsg: 'El nombre no puede exceder 255 caracteres.'
    },
    email: {
        validator: validateEmail,
        emptyMsg: 'El correo electrónico es obligatorio.',
        invalidMsg: 'Ingrese un correo electrónico válido.'
    },
    phone: {
        validator: validatePhone,
        emptyMsg: 'El número de teléfono es obligatorio.',
        invalidMsg: 'Ingrese un teléfono válido en formato +506 XXXX XXXX.'
    }
};

// ==================== Validation Functions ====================

/**
 * Validates the supplier form fields.
 * @param {Object} values 
 * @param {Object} fieldValidators 
 * @returns {boolean} True if all fields are valid, false otherwise.
 */
function validateSupplierForm(values, fieldValidators) {
    return validateAndDisplayField(
        fieldValidators,
        values,
        showFieldError,
        clearFieldError
    );
}

// ==================== UI Manipulation Functions ====================

/**
 * Handles the form submission process.
 * @returns {boolean} True if the form is valid and can be submitted, false otherwise.
 */
export function submitSupplierForm() {
    clearAllFieldErrors(fieldValidators);

    // Cache DOM elements
    const $name = $('#name');
    const $email = $('#email');
    const $phone = $('#phone');

    // Get form values
    const values = {
        name: $name.val().trim(),
        email: $email.val().trim(),
        phone: $phone.val().trim()
    };

    return validateSupplierForm(values, fieldValidators);
}

// ==================== Event Listeners ====================

export const realTimeValidationHandler = (e) => {
    const $target = $(e.target);
	const fieldId = $target.attr("id");

	// Skip if field is not in validators
	if (!fieldValidators.hasOwnProperty(fieldId)) {
		return;
	}

	let value = $target.val().trim();
	const { validator, emptyMsg, invalidMsg } = fieldValidators[fieldId];

	// Format phone number in real-time
	if (fieldId === "phone") {
		value = formatPhoneNumber(value);
		$target.val(value);
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
};

/**
 * Real-time validation and formatting for supplier form fields.
 * @param {Event} e The input or change event.
 */
$(document).on('input change', `#${FORM_ID}`, function(e) {
    realTimeValidationHandler(e);
});

/**
 * Handles the supplier form submission.
 * Validates the form and manages loading state.
 * @param {Event} e The submit event.
 */
$(document).on('submit', `#${FORM_ID}`, (e) => {
    // Prevent default form submission
    e.preventDefault();
    setLoadingState(FORM_ID, true);

    // Validate and submit form
    if (submitSupplierForm()) e.currentTarget.submit();
    else setLoadingState(FORM_ID, false);
});