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
const FORM_ID = IS_EDITING ? 'edit-product-form' : 'create-product-form';

function validateSelect(value) {
    return value !== '-1' && value !== '';
}

function validateBooleanSelect(value) {
    return value === '1' || value === '0';
}

function validateNonNegativeAmount(value) {
    const amount = parseFloat(value);
    return !isNaN(amount) && amount >= 0;
}

const fieldValidators = {
    barcode: {
        validator: validateName,
        emptyMsg: 'El codigo de barras es obligatorio.',
        invalidMsg: 'El codigo de barras no puede exceder 255 caracteres.'
    },
    name: {
        validator: validateName,
        emptyMsg: 'El nombre del producto es obligatorio.',
        invalidMsg: 'El nombre no puede exceder 255 caracteres.'
    },
    type: {
        validator: validateSelect,
        emptyMsg: 'El tipo de producto es obligatorio.',
        invalidMsg: 'Seleccione un tipo de producto valido.'
    },
    has_inventory: {
        validator: validateBooleanSelect,
        emptyMsg: 'Indique si maneja inventario.',
        invalidMsg: 'Seleccione una opcion valida para inventario.'
    },
    sale_price: {
        validator: validateNonNegativeAmount,
        emptyMsg: 'El precio de venta es obligatorio.',
        invalidMsg: 'Ingrese un precio de venta valido mayor o igual a 0.'
    },
    tax_percentage: {
        validator: validateNonNegativeAmount,
        emptyMsg: 'El impuesto es obligatorio.',
        invalidMsg: 'Ingrese un impuesto valido mayor o igual a 0.'
    },
    reference_cost: {
        validator: validateNonNegativeAmount,
        emptyMsg: 'El costo de referencia es obligatorio.',
        invalidMsg: 'Ingrese un costo de referencia valido mayor o igual a 0.'
    },
    margin_percentage: {
        validator: validateNonNegativeAmount,
        emptyMsg: 'El margen es obligatorio.',
        invalidMsg: 'Ingrese un margen valido mayor o igual a 0.'
    },
    category_id: {
        validator: validateSelect,
        emptyMsg: 'La categoria es obligatoria.',
        invalidMsg: 'Seleccione una categoria valida.'
    }
};

/**
 * Validates product form fields.
 * @param {Object} values
 * @returns {boolean}
 */
function validateProductForm(values) {
    return validateAndDisplayField(
        fieldValidators,
        values,
        showFieldError,
        clearFieldError
    );
}

/**
 * Handles the form submission process.
 * @returns {boolean}
 */
function submitProductForm() {
    clearAllFieldErrors(fieldValidators);

    const values = {
        barcode: $('#barcode').val().trim(),
        name: $('#name').val().trim(),
        type: $('#type').val().trim(),
        has_inventory: $('#has_inventory').val().trim(),
        sale_price: $('#sale_price').val().trim(),
        tax_percentage: $('#tax_percentage').val().trim(),
        reference_cost: $('#reference_cost').val().trim(),
        margin_percentage: $('#margin_percentage').val().trim(),
        category_id: $('#category_id').val().trim()
    };

    return validateProductForm(values);
}

/**
 * Real-time validation for product form fields.
 */
$(document).on('input change', `#${FORM_ID}`, function (e) {
    const $target = $(e.target);
    const fieldId = $target.attr('id');

    if (!Object.prototype.hasOwnProperty.call(fieldValidators, fieldId)) {
        return;
    }

    const value = ($target.val() ?? '').toString().trim();
    const { validator, emptyMsg, invalidMsg } = fieldValidators[fieldId];

    if (!value) {
        showFieldError(fieldId, emptyMsg);
    } else if (!validator(value)) {
        showFieldError(fieldId, invalidMsg);
    } else {
        clearFieldError(fieldId);
    }
});

/**
 * Handles product form submission.
 */
$(document).on('submit', `#${FORM_ID}`, (e) => {
    e.preventDefault();
    setLoadingState(FORM_ID, true);

    if (submitProductForm()) e.currentTarget.submit();
    else setLoadingState(FORM_ID, false);
});
