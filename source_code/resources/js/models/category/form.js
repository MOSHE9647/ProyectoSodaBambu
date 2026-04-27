import {
    clearAllFieldErrors,
    clearFieldError,
    showFieldError,
    validateAndDisplayField,
    validateName
} from '../../utils/validation.js';
import { setLoadingState } from '../../utils/utils.js';

// ==================== Environment Checks ====================

// Ensure jQuery is loaded
if (typeof $ === 'undefined') {
    throw new Error('This script requires jQuery');
}

// ==================== Constants ====================

const IS_EDITING = document.querySelector('form[id^="edit-"]') !== null;
const FORM_ID = IS_EDITING ? 'edit-category-form' : 'create-category-form';

const fieldValidators = {
    name: {
        validator: validateName,
        emptyMsg: 'El nombre es obligatorio.',
        invalidMsg: 'El nombre no puede exceder 255 caracteres.'
    },
    description: {
        validator: (value) => {
            return value.length <= 255;
        },
        emptyMsg: '',
        invalidMsg: 'La descripción no puede exceder 255 caracteres.'
    }
};

// ==================== Validation Functions ====================

/**
 * Validates the category form fields.
 * @param {Object} values 
 * @returns {boolean} True if all fields are valid, false otherwise.
 */
function validateCategoryForm(values) {
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
 * @returns {boolean} True if form is valid and can be submitted, false otherwise.
 */
export function submitCategoryForm(customFieldId = null) {
    clearAllFieldErrors(fieldValidators);
    delete fieldValidators.description; // Description is optional, so we remove it from validation

    // Cache DOM elements
    const $name = $(`${customFieldId ? `#${customFieldId}` : "#name"}`);

    const values = {
        name: $name.val().trim(),
    };

    return validateCategoryForm(values);
}

// ==================== Event Listeners ====================

/**
 * Real-time validation for category form fields.
 * Validates fields on input and change events, providing immediate feedback to the user.
 * @param {Event} e - The event object triggered by user interaction.
 * @param {string} customFieldId - The ID of the field being validated.
 */
export const realTimeValidationHandler = (e, customFieldId = null) => {
    const $target = $(e.target);
	let fieldId = customFieldId || $target.attr("id");

	// Skip if field is not in validators
	if (!fieldValidators.hasOwnProperty(fieldId)) {
		return;
	}

	let value = $target.val().trim();
	const { validator, emptyMsg, invalidMsg } = fieldValidators[fieldId];

    fieldId = $target.attr("id");
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

$(document).on('input change', `#${FORM_ID}`, function (e) {
    realTimeValidationHandler(e);
});

/**
 * Handles the form submission event.
 * Validates the form and sets loading state accordingly.
 * @param {Event} e - The submit event triggered by the form submission.
 */
$(document).on('submit', `#${FORM_ID}`, (e) => {
    // Prevent default form submission
    e.preventDefault();
    setLoadingState(FORM_ID, true);

    // Validate form and submit if valid
    if (submitCategoryForm()) e.currentTarget.submit();
    else setLoadingState(FORM_ID, false);
});