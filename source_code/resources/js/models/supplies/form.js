import {
    clearAllFieldErrors,
    clearFieldError,
    showFieldError,
    validateAndDisplayField,
    validateName
} from '../../utils/validation.js';
import { calculateAlertDate, setLoadingState } from '../../utils/utils.js';

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
        validator: (val) => val.length > 0 && val.length <= 255,
        emptyMsg: 'La unidad de medida es obligatoria.',
        invalidMsg: 'La unidad de medida no puede exceder 255 caracteres.'
    },
    quantity: {
        validator: (val) => val !== '' && !isNaN(val) && parseInt(val) >= 0,
        emptyMsg: 'La cantidad es obligatoria.',
        invalidMsg: 'La cantidad debe ser un número entero positivo.'
    },
    unit_price: {
        validator: (val) => val !== '' && !isNaN(val) && parseFloat(val) >= 0,
        emptyMsg: 'El precio unitario es obligatorio.',
        invalidMsg: 'El precio debe ser un número válido.'
    },
    expiration_alert_days: {
        validator: (val) => val === '' || (!isNaN(val) && parseInt(val) >= 0),
        emptyMsg: null, // Es opcional
        invalidMsg: 'Los días de alerta deben ser un número entero.'
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
export function submitSupplyForm() {
    clearAllFieldErrors(fieldValidators);

    const $name = $('#name');
    const $measureUnit = $('#measure_unit');

    const values = {
        name: $name.val().trim(),
        measure_unit: $measureUnit.val().trim(),
        quantity: $('#quantity').val().trim(),
        unit_price: $('#unit_price').val().trim(),
        expiration_date: $('#expiration_date').val().trim(),
        expiration_alert_days: $('#expiration_alert_days').val().trim(),
    };

    return validateSupplyForm(values);
}

export const realTimeValidationHandler = (e) => {
    const $target = $(e.target);
    const fieldId = $target.attr('id');

    // Special handling for expiration date and alert days to calculate and display alert date
    if (["expiration_date", "expiration_alert_days"].includes(fieldId)) {
		const alertDate = calculateAlertDate();
		$("#expiration-alert-date").text(alertDate || "");

		const alertContainer = $("#expiration-alert-date-container");
		alertContainer.toggleClass("d-none", !alertDate);
	}

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
};

// Event Listeners
$(document).on('input change', `#${FORM_ID}`, function (e) {
    realTimeValidationHandler(e);
});

$(document).on('submit', `#${FORM_ID}`, (e) => {
    e.preventDefault();
    setLoadingState(FORM_ID, true);

    if (submitSupplyForm()) e.currentTarget.submit();
    else setLoadingState(FORM_ID, false);
});