import {
    clearAllFieldErrors,
    clearFieldError,
    showFieldError,
    validateAndDisplayField,
    validateRequired,
    validateDate
} from '../../utils/validation.js';
import { setLoadingState } from '../../utils/utils.js';

if (typeof $ === 'undefined') {
    throw new Error('This script requires jQuery');
}

const IS_EDITING = document.querySelector('form[id^="edit-"]') !== null;
const FORM_ID = IS_EDITING ? 'edit-purchase-form' : 'create-purchase-form';

const fieldValidators = {
    invoice_number: {
        validator: (value) => value.trim() !== '',
        emptyMsg: 'El número de factura es obligatorio.',
        invalidMsg: 'Ingrese un número de factura válido.'
    },
    date: {
        validator: validateDate,
        emptyMsg: 'La fecha es obligatoria.',
        invalidMsg: 'Ingrese una fecha válida.'
    },
    supplier_id: {
        validator: (value) => value !== '',
        emptyMsg: 'Debe seleccionar un proveedor.',
        invalidMsg: 'Seleccione un proveedor.'
    },
    payment_status: {
        validator: (value) => value !== '',
        emptyMsg: 'Debe seleccionar un estado de pago.',
        invalidMsg: 'Seleccione un estado de pago.'
    }
};

function validatePurchaseForm(values) {
    return validateAndDisplayField(
        fieldValidators,
        values,
        showFieldError,
        clearFieldError
    );
}

function submitPurchaseForm() {
    clearAllFieldErrors(fieldValidators);

    const $invoice = $('#invoice_number');
    const $date = $('#date');
    const $supplier = $('#supplier_id');
    const $payment = $('#payment_status');

    const values = {
        invoice_number: $invoice.val().trim(),
        date: $date.val(),
        supplier_id: $supplier.val(),
        payment_status: $payment.val()
    };

    return validatePurchaseForm(values);
}

// Real-time validation
$(document).on('input change', `#${FORM_ID}`, function (e) {
    const $target = $(e.target);
    const fieldId = $target.attr('id');

    if (!fieldValidators.hasOwnProperty(fieldId)) {
        return;
    }

    let value = fieldId === 'supplier_id' || fieldId === 'payment_status' ? $target.val() : $target.val().trim();
    const { validator, emptyMsg, invalidMsg } = fieldValidators[fieldId];

    if (!value) {
        showFieldError(fieldId, emptyMsg);
    } else if (!validator(value)) {
        showFieldError(fieldId, invalidMsg);
    } else {
        clearFieldError(fieldId);
    }
});

$(document).on('submit', `#${FORM_ID}`, (e) => {
    e.preventDefault();
    setLoadingState(FORM_ID, true);

    if (submitPurchaseForm()) {
        e.currentTarget.submit();
    } else {
        setLoadingState(FORM_ID, false);
    }
});