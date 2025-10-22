import {
    clearAllFieldErrors,
    clearFieldError,
    showFieldError,
    validateAndDisplayField,
    validateAmount,
    validatePaymentType,
    validateVoucher,
    validateReference,
    validateChangeAmount
} from '../../utils/validation.js';
import { setLoadingState } from '../../utils/utils.js';

// Ensure jQuery is loaded
if (typeof $ === 'undefined') {
    throw new Error('This script requires jQuery');
}

// Constants and Variables
const isEdit = document.querySelector('form[id^="edit-"]') !== null;
const formId = isEdit ? 'edit-method-payment-form' : 'create-method-payment-form';

// Field validators
const fieldValidators = {
    amount: {
        validator: validateAmount,
        emptyMsg: 'El monto es obligatorio.',
        invalidMsg: 'Ingrese un monto válido (mayor o igual a 0).'
    },
    type_payment: {
        validator: validatePaymentType,
        emptyMsg: 'El tipo de pago es obligatorio.',
        invalidMsg: 'Seleccione un tipo de pago válido.'
    },
    voucher: {
        validator: validateVoucher,
        emptyMsg: 'El comprobante es obligatorio para SINPE.',
        invalidMsg: 'Ingrese un comprobante válido.'
    },
    reference: {
        validator: validateReference,
        emptyMsg: 'La referencia es obligatoria para tarjeta.',
        invalidMsg: 'Ingrese una referencia válida.'
    },
    changeAmount: {
        validator: validateChangeAmount,
        emptyMsg: 'El monto de cambio es obligatorio para efectivo.',
        invalidMsg: 'Ingrese un monto de cambio válido (mayor o igual a 0).'
    }
};

/**
 * Validates the payment method form fields.
 * @param values
 * @returns {boolean}
 */
function validateMethodPaymentForm(values) {
    // Remove conditional fields if not needed
    if (values.type_payment !== 'sinpe') {
        delete fieldValidators.voucher;
    }
    if (values.type_payment !== 'card') {
        delete fieldValidators.reference;
    }
    if (values.type_payment !== 'cash') {
        delete fieldValidators.changeAmount;
    }

    // Validate common fields
    return validateAndDisplayField(
        fieldValidators,
        values,
        showFieldError,
        clearFieldError
    );
}

/**
 * Form Submission Handler.
 *
 * Handles the payment method form submission.
 * @returns {boolean}
 */
function submitMethodPaymentForm() {
    clearAllFieldErrors(fieldValidators);

    // Get form values
    const values = {
        amount: $('#amount').val().trim(),
        type_payment: $('#type_payment').val(),
    };

    // Include conditional fields based on payment type
    if (values.type_payment === 'sinpe') {
        values.voucher = $('#voucher').val().trim();
    } else if (values.type_payment === 'card') {
        values.reference = $('#reference').val().trim();
    } else if (values.type_payment === 'cash') {
        values.changeAmount = $('#changeAmount').val().trim();
    }

    // Validate form
    // If there are validation errors, do not submit the form
    return validateMethodPaymentForm(values);
}

// Event Listeners
/**
 * Real-time validation for input fields.
 * Validates fields on input and shows/hides error messages accordingly.
 */
Object.keys(fieldValidators).forEach((fieldId) => {
    $(document).on('input change', `#${fieldId}`, function () {
        const value = $(this).val().trim();
        const { validator, emptyMsg, invalidMsg } = fieldValidators[fieldId];
        const typePayment = $('#type_payment').val();

        // For conditional fields, only validate if the type matches
        const isConditionalField = ['voucher', 'reference', 'changeAmount'].includes(fieldId);

        if (isConditionalField) {
            const shouldValidate = 
                (fieldId === 'voucher' && typePayment === 'sinpe') ||
                (fieldId === 'reference' && typePayment === 'card') ||
                (fieldId === 'changeAmount' && typePayment === 'cash');

            if (!shouldValidate) {
                // Clear errors for conditional fields if not applicable
                clearFieldError(fieldId);
                return;
            }
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
});

/**
 * Form submission event listener.
 * Validates the form and manages the loading state.
 */
$(document).on('submit', `#${formId}`, (e) => {
    // Prevent default form submission
    e.preventDefault();
    setLoadingState(formId, true);
    if (submitMethodPaymentForm()) e.currentTarget.submit();
    else setLoadingState(formId, false);
});