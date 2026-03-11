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
const FORM_ID = IS_EDITING ? 'edit-supply-form' : 'create-supply-form';

const fieldValidators = {
    name: {
        validator: validateName,
        emptyMsg: 'El nombre del insumo es obligatorio.',
        invalidMsg: 'El nombre no puede exceder 255 caracteres.'
    },
    measure_unit: {
        validator: (val) => val.length > 0 && val.length <= 50,
        emptyMsg: 'La unidad de medida es obligatoria.',
        invalidMsg: 'La unidad de medida no puede exceder 50 caracteres.'
    }
};

/**
 * Validates the supply form fields.
 */
function validateSupplyForm(values) {
    return validateAndDisplayField(
        fieldValidators,
        values,
        showFieldError,
        clearFieldError
    );
}

/**
 * Form Submission Handler.
 */
function submitSupplyForm() {
    clearAllFieldErrors(fieldValidators);

    const $name = $('#name');
    const $measureUnit = $('#measure_unit');

    const values = {
        name: $name.val().trim(),
        measure_unit: $measureUnit.val().trim(),
    };

    return validateSupplyForm(values);
}

// Event Listeners
$(document).on('input change', `#${FORM_ID} input, #${FORM_ID} select`, function (e) {
    const $target = $(e.target);
    const fieldId = $target.attr('id');

    if (!fieldValidators.hasOwnProperty(fieldId)) return;

    let value = $target.val().trim();
    const {validator, emptyMsg, invalidMsg} = fieldValidators[fieldId];

    if (!value) {
        if (emptyMsg) showFieldError(fieldId, emptyMsg);
        else clearFieldError(fieldId);
    } else if (!validator(value)) {
        showFieldError(fieldId, invalidMsg);
    } else {
        clearFieldError(fieldId);
    }
});

$(document).on('submit', `#${FORM_ID}`, (e) => {
    e.preventDefault();
    setLoadingState(FORM_ID, true);

    if (submitSupplyForm()) e.currentTarget.submit();
    else setLoadingState(FORM_ID, false);
});