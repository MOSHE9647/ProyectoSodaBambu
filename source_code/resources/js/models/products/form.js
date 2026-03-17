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
const PRODUCT_TYPE_MERCHANDISE = 'merchandise';

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

function validateDecimalPercentage(value) {
    const amount = parseFloat(value);
    return !isNaN(amount) && amount >= 0 && amount <= 1;
}

function normalizePercentage(value) {
    const amount = parseFloat(value);

    if (isNaN(amount) || amount < 0) {
        return NaN;
    }

    return amount > 1 ? amount / 100 : amount;
}

function calculateSalePrice(referenceCost, taxPercentage, marginPercentage) {
    const priceWithTax = referenceCost + (referenceCost * taxPercentage);
    return priceWithTax + (priceWithTax * marginPercentage);
}

function isMerchandiseSelected() {
    return ($('#type').val() ?? '').toString().trim() === PRODUCT_TYPE_MERCHANDISE;
}

function syncSalePriceBehavior() {
    const isMerchandise = isMerchandiseSelected();
    const $salePrice = $('#sale_price');

    $salePrice.prop('readonly', isMerchandise);

    if (!isMerchandise) {
        return;
    }

    const referenceCost = parseFloat($('#reference_cost').val());
    const taxPercentage = normalizePercentage($('#tax_percentage').val());
    const marginPercentage = normalizePercentage($('#margin_percentage').val());

    if ([referenceCost, taxPercentage, marginPercentage].some((value) => isNaN(value))) {
        $salePrice.val('');
        return;
    }

    const salePrice = calculateSalePrice(referenceCost, taxPercentage, marginPercentage);
    $salePrice.val(salePrice.toFixed(2));
}

function validateSalePriceVsCost() {
    const $salePrice = $('#sale_price');
    const $referenceCost = $('#reference_cost');
    const salePrice = parseFloat($salePrice.val());
    const referenceCost = parseFloat($referenceCost.val());

    if (isNaN(salePrice) || isNaN(referenceCost)) {
        return true;
    }

    return salePrice > referenceCost;
}

function validateMarginWarning() {
    const marginPercentage = normalizePercentage($('#margin_percentage').val());

    if (isNaN(marginPercentage)) {
        return;
    }

    const $marginWarning = $('#margin-warning');

    if (marginPercentage < 0.10) {
        if (!$marginWarning.length) {
            $('#margin_percentage').after(
                '<small id="margin-warning" class="text-warning d-block mt-1">⚠ Margen bajo (< 10%). Considere aumentarlo.</small>'
            );
        }
    } else {
        $marginWarning.remove();
    }
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
        validator: (value) => {
            if (!validateNonNegativeAmount(value)) return false;
            if (isMerchandiseSelected()) return true;
            return validateSalePriceVsCost();
        },
        emptyMsg: 'El precio de venta es obligatorio.',
        invalidMsg: 'Ingrese un precio de venta mayor al costo de referencia.'
    },
    tax_percentage: {
        validator: validateDecimalPercentage,
        emptyMsg: 'El impuesto es obligatorio.',
        invalidMsg: 'Ingrese un impuesto valido entre 0 y 1. Ej: 0.13'
    },
    reference_cost: {
        validator: validateNonNegativeAmount,
        emptyMsg: 'El costo de referencia es obligatorio.',
        invalidMsg: 'Ingrese un costo de referencia valido mayor o igual a 0.'
    },
    margin_percentage: {
        validator: validateDecimalPercentage,
        emptyMsg: 'El margen es obligatorio.',
        invalidMsg: 'Ingrese un margen valido entre 0 y 1. Ej: 0.35'
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
    if (values.type === PRODUCT_TYPE_MERCHANDISE) {
        clearFieldError('sale_price');
    }

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
    syncSalePriceBehavior();

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
    syncSalePriceBehavior();
    validateMarginWarning();

    const $target = $(e.target);
    const fieldId = $target.attr('id');

    if (!Object.prototype.hasOwnProperty.call(fieldValidators, fieldId)) {
        return;
    }

    if (fieldId === 'sale_price' && isMerchandiseSelected()) {
        clearFieldError(fieldId);
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

$(document).ready(() => {
    syncSalePriceBehavior();
    validateMarginWarning();
});
