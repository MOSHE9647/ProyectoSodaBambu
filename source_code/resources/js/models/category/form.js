import {
    clearAllFieldErrors,
    clearFieldError,
    showFieldError,
    validateAndDisplayField,
    validateName
} from '../../utils/validation.js';
import {setLoadingState} from '../../utils/utils.js';

// Ensure jQuery is loaded
if (typeof $ === 'undefined') {
    throw new Error('This script requires jQuery');
}

// Constants and Variables
const isEdit = document.querySelector('form[id^="edit-"]') !== null;
const formId = isEdit ? 'edit-category-form' : 'create-category-form';
const fieldValidators = {
    name: {
        validator: validateName,
        emptyMsg: 'El nombre es obligatorio.',
        invalidMsg: 'El nombre no puede exceder 255 caracteres.'
    }
};

// Validation Functions
function validateCategoryForm(values) {
    return validateAndDisplayField(
        fieldValidators,
        values,
        showFieldError,
        clearFieldError
    );
}

function submitCategoryForm() {
    clearAllFieldErrors(fieldValidators);

    const values = {
        name: $('#name').val().trim()
    };

    return validateCategoryForm(values);
}

// Event Listeners
Object.keys(fieldValidators).forEach((fieldId) => {
    $(document).on('input change', `#${fieldId}`, function () {
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

$(document).on('submit', `#${formId}`, (e) => {
    e.preventDefault();
    setLoadingState(formId, true);
    if (submitCategoryForm()) e.currentTarget.submit();
    else setLoadingState(formId, false);
});