import {
    clearAllFieldErrors,
    clearFieldError,
    showFieldError,
    validateAndDisplayField,
    validateName
} from '../../utils/validation.js';
import { setLoadingState } from '../../utils/utils.js';

// Ensure jQuery is loaded
if (typeof $ === 'undefined') {
    throw new Error('This script requires jQuery');
}

// Constants and Variables
const IS_EDITING = document.querySelector('form[id^="edit-"]') !== null;
const FORM_ID = IS_EDITING ? 'edit-category-form' : 'create-category-form';

const fieldValidators = {
    name: {
        validator: validateName,
        emptyMsg: 'El nombre es obligatorio.',
        invalidMsg: 'El nombre no puede exceder 255 caracteres.'
    }
};

// Validation Functions

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

// UI Manipulation Functions

/**
 * Handles the form submission process.
 * @returns {boolean} True if form is valid and can be submitted, false otherwise.
 */
function submitCategoryForm() {
    clearAllFieldErrors(fieldValidators);

    // Cache DOM elements
    const $name = $('#name');

    const values = {
        name: $name.val().trim()
    };

    return validateCategoryForm(values);
}

// Event Listeners

/**
 * Real-time validation for category form fields.
 * Validates fields on input and change events, providing immediate feedback to the user.
 * @param {Event} e - The event object triggered by user interaction.
 */
$(document).on('input change', `#${FORM_ID}`, function (e) {
    const $target = $(e.target);
    const fieldId = $target.attr('id');

    // Skip if field is not in validators
    if (!fieldValidators.hasOwnProperty(fieldId)) {
        return;
    }

    let value = $target.val().trim();
    const {validator, emptyMsg, invalidMsg} = fieldValidators[fieldId];

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